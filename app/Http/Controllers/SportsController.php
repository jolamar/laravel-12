<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SportsController extends Controller
{
    private array $campusEndpoints = [
        "IUB" => "https://iuhoosiers.com/api",
        "IUPUI" => "https://iuindyjags.com/api",
        "IUI" => "https://iuindyjags.com/api",
        "IUSB" => "https://iusbtitans.com/api",
        "IUS" => "https://iusathletics.com/api",
        "IUK" => "https://iukcougars.com/api",
        "IUN" => "https://iunredhawks.com/api",
        "IUFW" => "https://gomastodons.com/api",
        "IUC" => "https://iuccrimsonpride.com/api",
        "IUPUC" => "https://iuccrimsonpride.com/api", // alias for IUC for backwards compatibility
    ];

    private array $sports = [
        "Baseball" => [
            "id" => 1,
            "title" => "Baseball",
            "shortname" => "baseball",
            "sport_short_display" => "",
            "abbrev" => "BB",
            "global_sport_id" => 1,
        ],
        "Softball" => [
            "id" => 12,
            "title" => "Softball",
            "shortname" => "softball",
            "sport_short_display" => "",
            "abbrev" => "SB",
            "global_sport_id" => 2,
        ],
        "Men's Soccer" => [
            "id" => 8,
            "title" => "Men's Soccer",
            "shortname" => "msoc",
            "sport_short_display" => "",
            "abbrev" => "MSOC",
            "global_sport_id" => 7,
        ],
        "Men's Cross Country" => [
            "id" => 33,
            "title" => "Men's Cross Country",
            "shortname" => "mxc",
            "sport_short_display" => "",
            "abbrev" => "MXC",
            "global_sport_id" => 29,
        ],
        "Women's Cross Country" => [
            "id" => 34,
            "title" => "Women's Cross Country",
            "shortname" => "wxc",
            "sport_short_display" => "",
            "abbrev" => "WXC",
            "global_sport_id" => 30,
        ],
        "Women's Soccer" => [
            "id" => 17,
            "title" => "Women's Soccer",
            "shortname" => "wsoc",
            "sport_short_display" => "",
            "abbrev" => "WSOC",
            "global_sport_id" => 8,
        ],
        "Women's Volleyball" => [
            "id" => 21,
            "title" => "Women's Volleyball",
            "shortname" => "wvball",
            "sport_short_display" => "",
            "abbrev" => "WVB",
            "global_sport_id" => 22,
        ],
        "Women's Basketball" => [
            "id" => 23,
            "title" => "Women's Basketball",
            "shortname" => "wbball",
            "sport_short_display" => "",
            "abbrev" => "WBB",
            "global_sport_id" => 23,
        ],
        "Men's Basketball" => [
            "id" => 25,
            "title" => "Men's Basketball",
            "shortname" => "mbball",
            "sport_short_display" => "",
            "abbrev" => "MBB",
            "global_sport_id" => 25,
        ],
        "Women's Tennis" => [
            "id" => 27,
            "title" => "Women's Tennis",
            "shortname" => "wten",
            "sport_short_display" => "",
            "abbrev" => "WTN",
            "global_sport_id" => 27,
        ],
        "Men's Tennis" => [
            "id" => 29,
            "title" => "Men's Tennis",
            "shortname" => "mten",
            "sport_short_display" => "",
            "abbrev" => "MTN",
            "global_sport_id" => 29,
        ],
    ];

    private array $iueSports = [
        "Men's Soccer" => [
            "id" => 8,
            "title" => "Men's Soccer",
            "shortname" => "msoc",
            "sport_short_display" => "",
            "abbrev" => "MSOC",
            "global_sport_id" => 7,
        ],
        "Men's Cross Country" => [
            "id" => 88,
            "title" => "Men's Cross Country",
            "shortname" => "mxc",
            "sport_short_display" => "",
            "abbrev" => "MXC",
            "global_sport_id" => 88,
        ],
        "Women's Cross Country" => [
            "id" => 34,
            "title" => "Women's Cross Country",
            "shortname" => "wxc",
            "sport_short_display" => "",
            "abbrev" => "WXC",
            "global_sport_id" => 30,
        ],
        "Women's Soccer" => [
            "id" => 17,
            "title" => "Women's Soccer",
            "shortname" => "wsoc",
            "sport_short_display" => "",
            "abbrev" => "WSOC",
            "global_sport_id" => 8,
        ],
        "Women's Volleyball" => [
            "id" => 21,
            "title" => "Women's Volleyball",
            "shortname" => "wvball",
            "sport_short_display" => "",
            "abbrev" => "WVB",
            "global_sport_id" => 22,
        ],
        "Women's Basketball" => [
            "id" => 23,
            "title" => "Women's Basketball",
            "shortname" => "wbball",
            "sport_short_display" => "",
            "abbrev" => "WBB",
            "global_sport_id" => 23,
        ],
        "Men's Basketball" => [
            "id" => 25,
            "title" => "Men's Basketball",
            "shortname" => "mbball",
            "sport_short_display" => "",
            "abbrev" => "MBB",
            "global_sport_id" => 25,
        ],
        "Women's Tennis" => [
            "id" => 27,
            "title" => "Women's Tennis",
            "shortname" => "wten",
            "sport_short_display" => "",
            "abbrev" => "WTN",
            "global_sport_id" => 27,
        ],
        "Men's Tennis" => [
            "id" => 29,
            "title" => "Men's Tennis",
            "shortname" => "mten",
            "sport_short_display" => "",
            "abbrev" => "MTN",
            "global_sport_id" => 29,
        ],
        "Men's Golf" => [
            "id" => 31,
            "title" => "Men's Golf",
            "shortname" => "mgolf",
            "sport_short_display" => "",
            "abbrev" => "MGOLF",
            "global_sport_id" => 31,
        ],
        "Women's Golf" => [
            "id" => 33,
            "title" => "Women's Golf",
            "shortname" => "wgolf",
            "sport_short_display" => "",
            "abbrev" => "WGOLF",
            "global_sport_id" => 33,
        ],
        "Men's Indoor Track and Field" => [
            "id" => 35,
            "title" => "Men's Indoor Track & Field",
            "shortname" => "mitrack",
            "sport_short_display" => "",
            "abbrev" => "MITRACK",
            "global_sport_id" => 35,
        ],
        "Women's Indoor Track and Field" => [
            "id" => 37,
            "title" => "Women's Indoor Track & Field",
            "shortname" => "witrack",
            "sport_short_display" => "",
            "abbrev" => "WITRACK",
            "global_sport_id" => 37,
        ],
        "Men's Outdoor Track and Field" => [
            "id" => 39,
            "title" => "Men's Outdoor Track & Field",
            "shortname" => "motrack",
            "sport_short_display" => "",
            "abbrev" => "MOTRACK",
            "global_sport_id" => 39,
        ],
        "Women's Outdoor Track and Field" => [
            "id" => 41,
            "title" => "Women's Outdoor Track & Field",
            "shortname" => "wotrack",
            "sport_short_display" => "",
            "abbrev" => "WOTRACK",
            "global_sport_id" => 41,
        ],
        "ESPORTS" => [
            "id" => 43,
            "title" => "ESPORTS",
            "shortname" => "esports",
            "sport_short_display" => "",
            "abbrev" => "ESPORTS",
            "global_sport_id" => 43,
        ],
    ];

    public function getSchedule(Request $request, $campus)
    {
        if (!$campus) {
            return response()->json([
                "error" => "Campus is required"
            ], 400);
        }

        $uppercaseCampus = strtoupper($campus);

        if (in_array($uppercaseCampus, ["IUE"])) {
            return cache()->remember("sports_schedule_$uppercaseCampus", 60*60, function () use ($request, $campus) {
                return $this->getPrestoSchedule($request, $campus);
            });
        }

        if (isset($this->campusEndpoints[$uppercaseCampus])) {
            return cache()->remember("sports_schedule_{$uppercaseCampus}_{$request->get("starting")}_{$request->get("ending")}_{$request->get("path")}}", 30, function () use ($request, $campus) {
                return $this->getSidearmSchedule($request, $campus);
            });
        }

        return response()->json([
            "error" => "Campus not found"
        ], 404);
    }

    public function getRoster(Request $request, $campus) {
        if (!$request->get("path")) {
            return response()->json([
                "error" => "Path is required"
            ], 400);
        }

        $path = $request->get("path");

        $uppercaseCampus = strtoupper($campus);
        if (isset($this->campusEndpoints[$uppercaseCampus])) {
            $endpoint = $this->campusEndpoints[$uppercaseCampus] . "/roster_xml/sports/$uppercaseCampus?format=json&path=$path";
            return cache()->remember("sports_roster_{$uppercaseCampus}_$path", 60 * 60 * 24 * 7, function () use ($endpoint) {
                return Http::get($endpoint)->json();
            });
        }

        return response()->json([
            "roster" => [],
        ]);
    }

    public function getNews(Request $request, $campus) {
        $uppercaseCampus = strtoupper($campus);
        $path = $request->get("path");
        if (isset($this->campusEndpoints[$uppercaseCampus])) {
            $endpoint = $this->campusEndpoints[$uppercaseCampus] . "/stories_xml/sports/$uppercaseCampus?format=json&path=$path";
            return cache()->remember("sports_news_{$uppercaseCampus}_$path", 60 * 60 * 6, function () use ($endpoint) {
                return Http::get($endpoint)->json();
            });
        }

        return response()->json([
            "stories" => [],
        ]);
    }
    public function getAssets(Request $request, $campus) {
        $uppercaseCampus = strtoupper($campus);
        if (isset($this->campusEndpoints[$uppercaseCampus])) {
            $endpoint = $this->campusEndpoints[$uppercaseCampus] . "/assets/sports/$uppercaseCampus?operation=sports&require=realsport";
            return cache()->remember("sports_assets_$uppercaseCampus", 60 * 60 * 24 * 7, function () use ($endpoint, $uppercaseCampus) {
                $json = Http::get($endpoint)->json();
                $sportsWithScheduleOrRoster = [];
                $excludeSportsList = [
                    "IUK" => ["Basketball"]
                ];
                if (isset($json["sports"])) {
                    foreach ($json["sports"] as &$sport) {
                        if (!isset($sport["name"]) || in_array($sport["name"], $excludeSportsList[$uppercaseCampus] ?? [])) {
                            continue;
                        }
                        if (isset($sport["global_conference_division"]) && $sport["global_conference_division"] == "") {
                            $sport["global_conference_division"] = null;
                        }
                        $sport["has_roster"] = false;
                        $sport["has_schedule"] = false;
                        if (isset($sport["shortname"])) {
                            $roster = cache()->remember("sports_roster_{$uppercaseCampus}_{$sport["shortname"]}", 60 * 60 * 24, function () use ($uppercaseCampus, $sport) {
                                $rosterEndpoint = $this->campusEndpoints[$uppercaseCampus] . "/roster_xml/sports/$uppercaseCampus?format=json&path=" . $sport["shortname"];
                                return Http::get($rosterEndpoint)->json();
                            });
                            usleep(500);
                            if (isset($roster["roster"]) && count($roster["roster"]) > 0) {
                                $sport["has_roster"] = true;
                            }
                            $schedule = cache()->remember("sports_schedule_{$uppercaseCampus}_{$sport["shortname"]}", 60 * 60 * 24, function () use ($uppercaseCampus, $sport) {
                                $scheduleEndpoint = $this->campusEndpoints[$uppercaseCampus] . "/schedule_xml_2?format=json&path=" . $sport["shortname"];
                                return Http::get($scheduleEndpoint)->json();
                            });
                            usleep(500);
                            if (isset($schedule["schedule"]) && count($schedule["schedule"]) > 0) {
                                $sport["has_schedule"] = true;
                            }
                        }
                        if ($sport["has_roster"] || $sport["has_schedule"]) {
                            $sportsWithScheduleOrRoster[] = $sport;
                        }
                    }
                }
                $json["sports"] = $sportsWithScheduleOrRoster;
                return $json;
            });
        }

        if ($uppercaseCampus == "IUE") {
            $sports = [];
            foreach ($this->iueSports as $s) {
                $sports[] = [
                    "id" => $s["id"],
                    "name" => $s["title"],
                    "shortname" => $s["shortname"],
                    "sport_short_display" => $s["sport_short_display"],
                    "abbrev" => $s["abbrev"],
                    "global_sport_id" => $s["global_sport_id"],
                    "global_conference_division" => null,
                ];
            }
            return response()->json([
                "sports" => $sports,
            ]);
        }

        return response()->json([
            "sports" => [],
        ]);
    }

    private function correctSwimmingDivingTimes($schedule) {
        foreach($schedule as &$game) {
            $game["time"] = str_replace("Diving", "D", $game["time"]);
            $game["time"] = str_replace("Swimming", "S", $game["time"]);
        }

        return $schedule;
    }

    private function getSidearmSchedule(Request $request, $campus) {
        $starting = $request->get("starting");
        $ending = $request->get("ending");

        if (!empty($starting)) {
            $starting = Carbon::parse($starting)->format('Y-m-d');
        }

        if (!empty($ending)) {
            $ending = Carbon::parse($ending)->format('Y-m-d');
        }

        $path = $request->get("path");
        $endpoint = $this->campusEndpoints[strtoupper($campus)] . "/schedule_xml_2?format=json&starting=$starting&ending=$ending&path=$path";
        $response = cache()->remember("sports_schedule_{$campus}_{$starting}_{$ending}_$path", 60 * 60, function () use ($endpoint) {
            return Http::get($endpoint)->json();
        });
        if (isset($response["record"]) && empty($response["record"])) {
            $response["record"] = (object) $response["record"];
        }
        $schedule = [];
        foreach($response["schedule"] as $game) {
            $game["campus"] = $campus;
            $schedule[] = $this->getScoresForGame($request, $game, $campus);
        }
        $response["schedule"] = $schedule;
        $response["schedule"] = $this->correctSwimmingDivingTimes($response["schedule"]);
        return $response;
    }

    private function getPrestoSchedule(Request $request, $campus): \Illuminate\Http\JsonResponse
    {
        $campuses = [
            "IUE" => "https://www.iueredwolves.com",
        ];

        $endpoint = $campuses[strtoupper($campus)] . "/composite?print=rss";

        $response = Http::get($endpoint);
        $body = str_replace("ps:", "", str_replace("dc:", "", $response->body()));
        try {
            $xml = simplexml_load_string($body);
        } catch (\Exception $e) {
            return response()->json([
                "schedule" => [],
                "record" => (object) [],
            ]);
        }
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $schedule = $array["channel"]["item"];
        $starting = $request->get("starting");
        if ($starting) {
            $starting = Carbon::parse($starting);
            $schedule = array_filter($schedule, function($game) use ($starting) {
                $gameDate = Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis');
                return $gameDate->isAfter($starting) || $gameDate->isSameDay($starting);
            });
        }


        $games = [];
        foreach($schedule as $game) {
            $gameDate = Carbon::parse($game["pubDate"]);
            $gameDate->setTimezone('America/Indiana/Indianapolis');
            $gameDate = $gameDate->format('Y-m-d\TH:i:s\Z');

            $gameTitle = $game["title"];
            $gameTitle = explode(" - ", $gameTitle);
            $gameTitle = $gameTitle[0];

            $scores = [];
            if ($game["score"] != null) {
                $scores = str_replace("L, ", "", str_replace( "W, ", "", $game["score"]));
                $scores = explode("-", $scores);
            }


            $category = $game["category"];
            if(!is_string($category)) $category = "";
            if (array_key_exists($category, $this->iueSports)) {
                $sportName = $this->iueSports[$category];
            } else if (array_key_exists($category, $this->sports)) {
                $sportName = $this->sports[$category];
            } else {
                $sportName = (object) [];
            }


            $game = [
                "id" => Str::after($game["guid"], "#"),
                "schedule_id" => "" . hexdec(Str::after($game["guid"], "#")),
                "date" => Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis')->format('m/d/Y'),
                "formatted_date" => Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis')->format('m.d.Y'),
                "datetime_utc" => $gameDate,
                "end_datetime_utc" => null,
                "end_datetime" => null,
                "date_info" => [
                    "tbd" => false,
                    "all_day" => false,
                    "start_datetime" => $gameDate,
                    "start_date" => Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis')->format('Y-m-d'),
                ],
                "time" => Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis')->format('g:i A'),
                "title" => $gameTitle,
                "type" => "R",
                "status" => (Carbon::parse($game["pubDate"])->setTimezone('America/Indiana/Indianapolis')->isPast()) ? "O" : "A",
                "noplay_text" => "",
                "doubleheader" => "False",
                "game_promotion_name" => "",
                "game_promotion_link" => "",
                "sport" => $sportName,
                "location" => [
                    "location" => $game["opponent"] ?? "",
                    "HAN" => (Str::startsWith($game["opponent"] ?? "", "at ")) ? "A" : "H",
                    "neutral_hometeam" => null,
                    "facility" => "",
                ],
                "tv" => "",
                "tv_image" => null,
                "radio" => "",
                "custom_display_field_1" => null,
                "custom_display_field_2" => null,
                "custom_display_field_3" => null,
                "ticketmaster_event_id" => "",
                "links" => [
                    "livestats" => $game["link"],
                    "livestats_text" => "Live Stats",
                    "video" => null,
                    "video_text" => null,
                    "audio" => null,
                    "audio_text" => null,
                    "program" => null,
                    "program_text" => null,
                    "notes" => null,
                    "tickets" => null,
                    "history" => null,
                    "boxscore" => [
                        "bid" => null,
                        "url" => null,
                        "text" => null,
                    ],
                    "postgame" => [
                        "id" => null,
                        "url" => null,
                        "story_image_url" => null,
                        "text" => null,
                        "fulltext" => null,
                        "redirect_absolute_url" => null,
                    ],
                    "pregame" => [
                        "id" => null,
                        "url" => null,
                        "story_image_url" => null,
                        "text" => null,
                        "fulltext" => null,
                        "redirect_absolute_url" => null,
                    ],
                    "gamefiles" => []
                ],
                "opponent" => [
                    "opponent_global_id" => null,
                    "name" => $game["opponent"] ?? "",
                    "logo" => null,
                    "logo_image" => null,
                    "location" => "",
                    "mascot" => "",
                    "opponent_website" => "",
                    "conference_game" => "False",
                    "opponent_prefix" => "",
                    "tournament" => "",
                    "tourament_color" => "",
                ],
                "sponsor" => null,
                "event_image" => null,
                "results" => $game["score"] == null ? [] : [
                    [
                        "game" => "1",
                        "status" => $game["score"][0] ?? "",
                        "team_score" =>  $scores[0] ?? "",
                        "opponent_score" => $scores[1] ?? "",
                        "prescore_info" => "",
                        "postscore_info" => "",
                        "inprogress_info" => "",
                    ]
                ],
                "allaccess_videos" => [],
                "galleries" => [],
                "score" => (object) [],
                "campus" => $campus,
                "is_live" => false,
            ];

            $games[] = $game;
        }

        return response()->json([
            "schedule" => $games,
            "record" => (object) [],
        ]);
    }

    public function getScore(Request $request, string $campus, int $game_id) {

        $starting = $request->get("starting");
        $ending = $request->get("ending");

        if (!empty($starting)) {
            $starting = Carbon::parse($starting)->format('Y-m-d');
        }

        if (!empty($ending)) {
            $ending = Carbon::parse($ending)->format('Y-m-d');
        }
        $path = $request->get("path");
        $endpoint = $this->campusEndpoints[strtoupper($campus)] . "/schedule_xml_2?format=json&starting=$starting&ending=$ending&path=$path&game_id=$game_id";
        $response = cache()->remember("sports_score_{$campus}_$game_id", 60 * 60, function () use ($endpoint) {
            return Http::get($endpoint)->json();
        });
        if (empty($response["schedule"]) || Carbon::make($response["schedule"][0]["date"]) < Carbon::now()->subDays(7)) {
            foreach($this->campusEndpoints as $c => $endpoint) {
                try {
                    $response = Http::get($endpoint . "/schedule_xml_2?format=json&starting=$starting&ending=$ending&path=$path&game_id=$game_id")->json();

                    if (!empty($response["schedule"]) && Carbon::make($response["schedule"][0]["date"]) > Carbon::now()->subDays(7)) {
                        $campus = strtolower($c);
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            if (empty($response["schedule"])) {
                return response()->json([
                    "error" => "Game not found"
                ], 404);
            }
        }
        $response["schedule"][0]["campus"] = $campus;
        return $this->getScoresForGame($request, $response["schedule"][0], $campus);
    }

    private function getScoresForGame(Request $request, array $game, string $campus): array
    {
        $mock = $request->boolean("mock") ?? false;
        $game["score"] = (object) [];
        $game["campus"] = $campus;
        $game["is_live"] = false;
        $listOfGamesToday = cache()->store(config('cache.default'))->remember($campus."-todays-games", 60 * 60, function() use ($campus) {
            return $this->getTodaysGames($campus);
        });
        if (in_array($game["sport"]["abbrev"], $listOfGamesToday)) {
            $date = $request->input("date") ?? date('Y/m/d');
            $endpoint = $this->getScoresEndpoint($campus, $game["sport"]["abbrev"], $date);
            if (!empty($endpoint)) {
                $campus = strtolower($campus) == "iub" ? "Indiana" : $campus;
                $teamName = $request->input("team") ?? $campus;
                $allGamesJson = Http::get($endpoint);

                if ($allGamesJson == null) {
                    return $game;
                }

                $httpStatus = $allGamesJson->status();
                if($httpStatus == 404 || $httpStatus == 500) {
                    return $game;
                }
                $sportScores = collect($allGamesJson->collect()["games"])->where(function ($ncaaGame) use ($teamName, $date) {
                    return (
                            strtolower($ncaaGame["game"]["away"]["names"]["short"]) == strtolower($teamName)
                            || strtolower($ncaaGame["game"]["home"]["names"]["short"]) == strtolower($teamName)
                        ) && (
                            !isset($ncaaGame["game"]["startTimeEpoch"])
                            || Carbon::createFromTimestamp($ncaaGame["game"]["startTimeEpoch"])->isSameDay(Carbon::make($date))
                        );
                });

                $opponentNameFixes = [
                    "Ind." => "Indiana",
                    " (CA)" => "",
                    " (NY)" => "",
                    "Caro." => "Carolina",
                    "Ky." => "Kentucky",
                    " University" => "",
                    ", Bloomington" => "",
                    "St. " => "Saint ",
                    " St." => " State",
                ];

                $scores = [];
                $isLive = false;
                if($sportScores->isNotEmpty()) {
                    foreach ($sportScores as $score) {
                        $ncaaGame = $score["game"];
                        $awayTeam = $ncaaGame["away"];
                        $homeTeam = $ncaaGame["home"];

                        $scoreStartDate = Carbon::createFromTimestamp($ncaaGame["startTimeEpoch"]);
                        $gameStartDate = Carbon::make($game["datetime_utc"]);
                        $isWithinTwoHours = $gameStartDate->diffInMinutes($scoreStartDate) < 120;
                        if (!$isWithinTwoHours) {
                            continue;
                        }

                        $isLive = $ncaaGame["gameState"] == "live";

                        $scores = [
                            "sport" => $game["sport"]["abbrev"],
                            "away" => [
                                "score" => $mock ? rand(0, 100) . "" : $awayTeam["score"],
                                "description" => $awayTeam["description"],
                                "winner" => $awayTeam["winner"],
                                "name" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $awayTeam["names"]["short"]),
                                "full" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $awayTeam["names"]["full"]),
                                "seo" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $awayTeam["names"]["seo"]),
                                "conference" => $awayTeam["conferences"][0]["conferenceName"] ?? "",
                            ],
                            "home" => [
                                "score" =>  $mock ? rand(0, 100) . "" : $homeTeam["score"],
                                "description" => $homeTeam["description"],
                                "winner" => $homeTeam["winner"],
                                "name" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $homeTeam["names"]["short"]),
                                "full" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $homeTeam["names"]["full"]),
                                "seo" => str_replace(array_keys($opponentNameFixes), $opponentNameFixes, $homeTeam["names"]["seo"]),
                                "conference" => $homeTeam["conferences"][0]["conferenceName"] ?? "",
                            ],
                            "start_time" => $ncaaGame["startTime"],
                            "start_time_epoch" => $ncaaGame["startTimeEpoch"],
                            "start_date" => $ncaaGame["startDate"],
                            "current_period" => $ncaaGame["currentPeriod"],
                            "starts_in_minutes" => Carbon::createFromTimestamp($ncaaGame["startTimeEpoch"]) > Carbon::now() ? Carbon::createFromTimestamp($ncaaGame["startTimeEpoch"])->diffInMinutes() : 0,
                            "is_home" => strtolower($homeTeam["names"]["short"]) == strtolower($teamName),
                            "clock" => $ncaaGame["contestClock"] == ":00" ? "Halftime" : $ncaaGame["contestClock"],
                        ];
                    }
                }
                $game["score"] = (object) $scores;
                $game["campus"] = $campus;
                $game["is_live"] = ($mock && !empty($scores)) ? true : $isLive;
            }
        }

        return $game;
    }

    private function getScoresEndpoint(string $campus, string $abbrev, string $date): string
    {
        $year = date('Y');
        $footballStartDate = Carbon::make("2024/08/31");
        $weekDifference = $footballStartDate->diffInWeeks($date);
        $week = str_pad(1 + $weekDifference, 2, '0', STR_PAD_LEFT);
        if (in_array(strtolower($campus), ["iub", "iupui", "iui"])) {
            return match ($abbrev) {
                "MBB" => "https://data.ncaa.com/casablanca/scoreboard/basketball-men/d1/$date/scoreboard.json",
                "WBB" => "https://data.ncaa.com/casablanca/scoreboard/basketball-women/d1/$date/scoreboard.json",
                "FB" => "https://data.ncaa.com/casablanca/scoreboard/football/fbs/$year/$week/scoreboard.json",
                "MSOC" => "https://data.ncaa.com/casablanca/scoreboard/soccer-men/d1/$date/scoreboard.json",
                "WSOC" => "https://data.ncaa.com/casablanca/scoreboard/soccer-women/d1/$date/scoreboard.json",
                "WVB" => "https://data.ncaa.com/casablanca/scoreboard/volleyball-women/d1/$date/scoreboard.json",
                default => "",
            };
        }

        return "";
    }

    private function getTodaysGames($campus) {
        $date = date('Y/m/d');
        $prefix = "https://iuhoosiers.com";

        if($campus == "iupui" || $campus == "iui") {
            $prefix = "https://iuindyjags.com";
        }
        $iuHoosiersEndpoint = "$prefix/api/schedule_xml_2?format=json&starting=$date&ending=$date";

        $iuHoosiersJson = Http::get($iuHoosiersEndpoint);

        if ($iuHoosiersJson == null) {
            return null;
        }

        $httpStatus = $iuHoosiersJson->status();

        if($httpStatus == 404 || $httpStatus == 500) {
            return null;
        }

        return collect($iuHoosiersJson->collect()["schedule"])->map(function($game) {
            return $game["sport"]["abbrev"];
        })->toArray();
    }
}
