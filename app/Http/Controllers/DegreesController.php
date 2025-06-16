<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DegreesController extends Controller
{
    private array $program_type_conversions = [
        "" => "0",
        "associates" => "1",
        "bachelors" => "2",
        "grad-cert" => "3",
        "undergrad-cert" => "4",
        "artist-performer" => "5",
        "doctoral-professional" => "6",
        "masters" => "7",
        "specialist" => "8",
        "accelerated" => "9",
    ];

    private array $campusConversion = [
        "" => "",
        "IUBLA" => "bloomington",
        "IUINA" => "indianapolis",
        "IUEAA" => "east",
        "IUKOA" => "kokomo",
        "IUNWA" => "northwest",
        "IUSBA" => "south-bend",
        "IUSEA" => "southeast",
        "IUCOA" => "columbus",
        "IUFTW" => "fort-wayne",
    ];

    private array $program_type_names = [
        "associates" => "Associate degrees",
        "bachelors" => "Bachelor's degrees",
        "grad-cert" => "Certificates: Graduate",
        "undergrad-cert" => "Certificates: Undergraduate",
        "artist-performer" => "Diplomas: Artist and Performer",
        "doctoral-professional" => "Doctoral and professional degrees",
        "masters" => "Master's degrees",
        "specialist" => "Post-master's/Specialist degrees",
        "accelerated" => "Accelerated Master's degrees"
    ];

    private array $program_type_degree_name_match = [
        "Associate" => "associates",
        "Bachelor" => "bachelors",
        "Graduate Certificate" => "grad-cert",
        "Artist Diploma" => "artist-performer",
        "Doctor" => "doctoral-professional",
        "Master" => "masters",
        "Specialist" => "specialist",
    ];

    private function typeOfDegree($degree): ?string
    {
        foreach($this->program_type_degree_name_match as $key => $value) {
            if (str_contains($degree, $key)) {
                return $value;
            }
        }
        return null;
    }
    private function getCampusString($campus): string
    {
        $campuses = explode(",", $campus);
        $campusString = "";
        foreach($campuses as $campus) {
            if (!isset($this->campusConversion[$campus])) {
                continue;
            }
            $campusString .= $this->campusConversion[$campus] . "|";
        }
        return substr($campusString, 0, -1);
    }
    public function getDegrees(Request $request): \Illuminate\Http\JsonResponse
    {
        $page = $request->input('page');
        $search = $request->input('q');
        $perPage = $request->input('per_page');
        $isOnline = $request->input('online') == "true";
        $campus = $request->input('campus');

        $inverseCampusConversion = [
            "IU Bloomington" => "IUBLA",
            "IUPUI" => "IUINA",
            "IU Indianapolis" => "IUINA",
            "IU East" => "IUEAA",
            "IU Kokomo" => "IUKOA",
            "IU Northwest" => "IUNWA",
            "IU South Bend" => "IUSBA",
            "IU Southeast" => "IUSEA",
            "IUPUC" => "IUCOA",
            "IU Columbus" => "IUCOA",
            "IU Fort Wayne" => "IUFTW",
            "" => "",
        ];

        $program_type = $request->input('type');

        $programs = "";
        $program_types = [];
        if ($request->has("type") && !empty($program_type)) {
            $programs = explode(",", $program_type);
            foreach ($programs as $program) {
                if (isset($this->program_type_conversions[$program])) {
                    $program_types[] = $this->program_type_conversions[$program];
                }
            }
            $programs = implode("|", $program_types);
        }

        if (Cache::has('degrees-page-' . $page . $search . $perPage . $isOnline . $campus . $program_type)) {
            return response()->json(Cache::get('degrees-page-' . $page . $search . $perPage. $isOnline . $campus . $program_type));
        }
        $degreesEndpoint = env('DEGREES_ENDPOINT');
        $campusString = $request->has("campus") ? $this->getCampusString($request["campus"]) : "";
        $response = Http::get($degreesEndpoint."&page=".$page."&q=".$search."&perPage=".$perPage."&campus=".$campusString.($request->has("online") ? "&online=" . ("&online=".($isOnline ? "Y|Y8|Y5" : "N")) : "").($request->has("type") ? "&program_type=" . $programs : ""));
        $json = $response->json();
        if (!isset($json["data"]) || $json["status"] == 404) {
            return response()->json(
                [
                    "status" => 200,
                    "message" => "ok",
                    "pagination" =>
                        [
                            "page" => 0,
                            "perPage" => 0,
                            "pages" => 0,
                            "total" => 0,
                            "prev" => null,
                            "next" => null,
                        ],
                    "data" => [],
                ]
            );
        }
        $degreesArray = $json["data"];
        $degrees = [];
        $key = 2000;
        foreach($degreesArray as $degree) {
            $degrees[] = [
                "id" => Str::slug($degree["name"] . " " . $degree["campus"] . " " . $degree["degree"]),
                "key" => $key++,
                "name" => $degree["name"],
                "degree" => null,
                "degreeName" => $degree["degree"],
                "type" => $this->typeOfDegree($degree["degree"]),
                "typeName" => strstr($degree["degree"], " in", true) ?? $degree["degree"],
                "campus" => $inverseCampusConversion[$degree["campus"]] ?? $degree["campus"],
                "campusUrl" => $degree["campusUrl"],
                "school" => $degree["schools"][0]["name"],
                "schoolUrl" => $degree["schools"][0]["url"],
                "online" => null,
                "description" => $degree["description"],
                "canon_url" => $degree["url"],
                "links" => $degree["links"],
                "tags" => null
            ];
        }

        $json["data"] = $degrees;
        $json["pagination"]["perPage"] = intval($json["pagination"]["perPage"]);
        unset($json["pagination"]["thisPage"]);
        $json = Cache::remember('degrees-page-' . $page . $search . $perPage . $isOnline . $campus . $program_type, 60 * 60 * 24 * 30, function () use ($json) {
            return $json;
        });
        return response()->json($json);
    }
}
