<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EtaSpotController extends Controller
{

    private int $timeForCacheETAsInSeconds = 1;
    private int $timeForCacheAnnouncementsInSeconds = 60;
    private int $timeForCacheBusesInSeconds = 2;
    private int $timeForCacheRoutesInSeconds = 60;
    private int $timeForCacheStopsInSeconds = 3600;

    public function getAnnouncements(Request $request, $campus)
    {
        $etaSpotEndpoint = "";
        if (strtolower($campus) == "iub") {
            $etaSpotEndpoint = env("IUB_ETASPOT_URL") . "/service.php?service=get_service_announcements";
        } else if (strtolower($campus) == "bloomington-transit") {
            $etaSpotEndpoint = env("ETASPOT_URL") . "/service.php?service=get_service_announcements";
        }

        $json = Cache::store(config("cache.default"))->remember($etaSpotEndpoint, $this->timeForCacheAnnouncementsInSeconds, function () use ($etaSpotEndpoint) {
            $response = Http::withoutVerifying()->withHeaders([
                "Accept" => "application/json",
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });

        if (!empty($json["get_service_announcements"]) && isset($json["get_service_announcements"][0]["announcements"])) {
            return collect($json["get_service_announcements"][0]["announcements"])->map(function ($announcement) {
                return [
                    "text" => $announcement["text"],
                    "start" => $announcement["start"],
                    "end" => $announcement["end"],
                    "affectedRoutes" => $announcement["affectedRoutes"] ?? [],
                    "affectedStops" => $announcement["affectedStops"] ?? [],
                    "cause" => $announcement["cause"],
                    "effect" => $announcement["effect"],
                    "source" => $announcement["source"],
                ];
            })->values()->toArray();
        }
        return [];
    }

    public function getBuses(Request $request, $campus)
    {
        $etaSpotEndpoint = "";
        $idMultiplier = 1;
        if (strtolower($campus) == "iub") {
            $etaSpotEndpoint = env("IUB_ETASPOT_URL") . "/service.php?service=get_vehicles&includeETAData=1&inService=1";
        } else if (strtolower($campus) == "bloomington-transit") {
            $etaSpotEndpoint = env("ETASPOT_URL") . "/service.php?service=get_vehicles&includeETAData=1&inService=1";
            $idMultiplier = 1000;
        }

        if ($etaSpotEndpoint == "") {
            return [];
        }

        $json = Cache::store(config("cache.default"))->remember($etaSpotEndpoint, $this->timeForCacheBusesInSeconds, function () use ($etaSpotEndpoint, $idMultiplier) {
            $response = Http::withoutVerifying()->withHeaders([
                "Accept" => "application/json",
            ])->get($etaSpotEndpoint);

            return collect($response->json());
        });

        // Let's get stop names to display in the UI
        $stops = $this->getStops($request, $campus);

        if (!empty($json["get_vehicles"])) {
            return collect($json["get_vehicles"])
                ->map(function ($bus) use ($idMultiplier, $stops) {
                    $minutesToNextStops = $bus["minutesToNextStops"] ?? [];

                    if (!empty($minutesToNextStops) && $bus["lastStopID"] == 0 && $minutesToNextStops[0]["minutes"] < 15) {
                        $bus["lastStopID"] = $minutesToNextStops[0]["stopID"];
                    }

                    return [
                        "id" => intval($bus["equipmentID"]) * $idMultiplier,
                        "name" => $bus["equipmentID"],
                        "lat" => $bus["lat"],
                        "lon" => $bus["lng"],
                        "route" => intval($bus["routeID"]) * $idMultiplier,
                        "lastUpdate" => intval(substr($bus["receiveTime"], 0, 10)),
                        "heading" => intval($bus["h"]),
                        "lastStop" => intval($bus["lastStopID"]) * $idMultiplier,
                        "passenger_count" => $bus["load"],
                        "capacity" => $bus["capacity"],
                        "onSchedule" => $bus["onSchedule"],
                        "minutesToNextStop" => collect($minutesToNextStops)->map(function ($minute) use ($idMultiplier, $stops) {
                            $stopId = $minute["stopID"] * $idMultiplier;
                            $stopName = $stopId;
                            $stop = collect($stops)->firstWhere("id", $stopId);
                            if ($stop) {
                                $stopName = $stop["name"];
                            }
                            return [
                                "stopID" => $stopId,
                                "stopName" => $stopName,
                                "patternStopID" => $minute["patternStopID"] * $idMultiplier,
                                "routeID" => $minute["routeID"] * $idMultiplier,
                                "minutes" => $minute["minutes"],
                                "status" => $minute["status"] == "On Time" ? "On Time" : "Delayed",
                                "statusColor" => $minute["statuscolor"],
                                "arrivalTime" => $minute["time"],
                                "scheduledArrivalTime" => $minute["schedule"],
                            ];
                        })
                    ];
                })
                ->values()
                ->toArray();
        }
        return [];
    }

    public function getStops(Request $request, $campus)
    {
        $etaSpotEndpoint = "";
        $idMultiplier = 1;
        if (strtolower($campus) == "iub") {
            $etaSpotEndpoint = env("IUB_ETASPOT_URL") . "/service.php?service=get_stops";
        } else if (strtolower($campus) == "bloomington-transit") {
            $etaSpotEndpoint = env("ETASPOT_URL") . "/service.php?service=get_stops";
            $idMultiplier = 1000;
        }

        $json = Cache::store(config("cache.default"))->remember($etaSpotEndpoint, $this->timeForCacheStopsInSeconds, function () use ($etaSpotEndpoint, $idMultiplier) {
            $response = Http::withoutVerifying()->withHeaders([
                "Accept" => "application/json",
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });

        if (!empty($json["get_stops"])) {
            $stops = collect($json["get_stops"]);
            return $stops->map(function ($stop, $key) use ($idMultiplier) {
                return [
                    "id" => $stop["id"] * $idMultiplier,
                    "name" => $stop["name"],
                    "description" => $stop["shortName"],
                    "lat" => $stop["lat"],
                    "lon" => $stop["lng"],
                ];
            })->toArray();
        }

        return [];
    }

    public function getRoutes(Request $request, $campus)
    {
        $etaSpotEndpoint = "";
        $etaSpotEndpointBase = "";
        $idMultiplier = 1;
        if (strtolower($campus) == "iub") {
            $etaSpotEndpointBase = env("IUB_ETASPOT_URL");
            $etaSpotEndpoint = "$etaSpotEndpointBase/service.php?service=get_routes";
        } else if (strtolower($campus) == "bloomington-transit") {
            $etaSpotEndpointBase = env("ETASPOT_URL");
            $etaSpotEndpoint = "$etaSpotEndpointBase/service.php?service=get_routes";
            $idMultiplier = 1000;
        }

        return Cache::store(config("cache.default"))->remember($etaSpotEndpoint, $this->timeForCacheRoutesInSeconds, function () use ($etaSpotEndpointBase, $idMultiplier) {
            return $this->getActiveRoutePatternsForEndpoint($etaSpotEndpointBase, $idMultiplier);
        });
    }

    public function getEtas(Request $request, $campus)
    {
        $etaSpotEndpoint = "";
        $idMultiplier = 1;
        if (strtolower($campus) == "iub") {
            $etaSpotEndpoint = env("IUB_ETASPOT_URL") . "/service.php?service=get_stop_etas";
        } else if (strtolower($campus) == "bloomington-transit") {
            $etaSpotEndpoint = env("ETASPOT_URL") . "/service.php?service=get_stop_etas";
            $idMultiplier = 1000;
        }
        // ETA Spot
        $json = Cache::store(config("cache.default"))->remember($etaSpotEndpoint, $this->timeForCacheETAsInSeconds, function () use ($etaSpotEndpoint, $idMultiplier) {
            $response = Http::withoutVerifying()->withHeaders([
                "Accept" => "application/json",
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });
        $etas = [];

        if (!empty($json["get_stop_etas"])) {
            $stopETAs = collect($json["get_stop_etas"]);
            $stopETAs->map(function ($stopETA) use (&$etas, $idMultiplier, $campus) {
                foreach ($stopETA["enRoute"] ?? [] as $eta) {
                    if ($eta["equipmentID"] != "-") {
                        $etas[$eta["stopID"]][] = [
                            "stop_id" => intval($eta["stopID"]) * $idMultiplier,
                            "avg" => intval($eta["minutes"]),
                            "bus_id" => intval($eta["equipmentID"]) * $idMultiplier,
                            "bus_name" => $eta["equipmentID"],
                            "route_id" => $eta["routeID"] * $idMultiplier,
                            "type" => "live",
                            "campus" => $campus,
                        ];
                    }
                }
            });
        }
        return ["etas" => $etas];
    }

    // grabbed from https://github.com/emcconville/google-map-polyline-encoding-tool/blob/master/src/Polyline.php
    private function decodePolyline($string)
    {
        $points = array();
        $index = $i = 0;
        $previous = array(0, 0);
        while ($i < strlen($string)) {
            $shift = $result = 0x00;
            do {
                $bit = ord(substr($string, $i++)) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $number = $previous[$index % 2] + $diff;
            $previous[$index % 2] = $number;
            $index++;
            $points[] = $number * 1 / pow(10, 5);
        }
        return $points;
    }

    private function getActivePatternsJson($endpoint)
    {
        return Cache::store(config('cache.default'))->remember("$endpoint.active-patterns", $this->timeForCacheRoutesInSeconds, function () use ($endpoint) {
            $etaSpotEndpoint = $endpoint . '/service.php?service=get_active_patterns';
            $response = Http::withoutVerifying()->withHeaders([
                'Accept' => 'application/json',
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });
    }

    private function getPatternsJson($endpoint)
    {
        return Cache::store(config('cache.default'))->remember("$endpoint.patterns", $this->timeForCacheRoutesInSeconds, function () use ($endpoint) {
            $etaSpotEndpoint = $endpoint . '/service.php?service=get_patterns';
            $response = Http::withoutVerifying()->withHeaders([
                'Accept' => 'application/json',
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });
    }

    private function getRoutesJson($endpoint)
    {
        return Cache::store(config('cache.default'))->remember("$endpoint.routes", $this->timeForCacheRoutesInSeconds, function () use ($endpoint) {
            $etaSpotEndpoint = $endpoint . '/service.php?service=get_routes';
            $response = Http::withoutVerifying()->withHeaders([
                'Accept' => 'application/json',
            ])->get($etaSpotEndpoint);
            return collect($response->json());
        });
    }

    private function getActiveRoutePatternsForEndpoint($endpoint, $multiplier = 1)
    {
        $activePatternsResponse = $this->getActivePatternsJson($endpoint);
        if (isset($activePatternsResponse["get_active_patterns"]["result"])) {
            $activePatterns = $activePatternsResponse["get_active_patterns"]["result"];
            $activePatternIds = [];
            $activeRouteIds = [];
            foreach ($activePatterns as $route_id => $patternIdArray) {
                $activeRouteIds[] = $route_id;
                $activePatternIds = array_merge($activePatternIds, $patternIdArray);
            }

            $patternsResponse = $this->getPatternsJson($endpoint);
            if (isset($patternsResponse["get_patterns"])) {
                $patterns = $patternsResponse["get_patterns"];
                $patterns = collect($patterns)->filter(function ($pattern) use ($activePatternIds) {
                    return in_array($pattern["id"], $activePatternIds);
                })->values();


                $routesResponse = $this->getRoutesJson($endpoint);
                if (isset($routesResponse["get_routes"])) {
                    $routes = $routesResponse["get_routes"];

                    $routes = collect($routes)->filter(function ($route) use ($activeRouteIds) {
                        return in_array($route["id"], $activeRouteIds);
                    })->values();

                    $routes = $routes->map(function ($route) use ($activePatterns) {
                        $route["patternIDs"] = $activePatterns[$route["id"]] ?? [];
                        return $route;
                    });

                    return $routes->map(function ($route) use ($patterns, $multiplier) {
                        $routePatterns = $patterns->filter(function ($pattern) use ($route) {
                            return in_array($pattern["id"], $route["patternIDs"]);
                        })->values();

                        return [
                            "id" => $route["id"] * $multiplier,
                            "name" => $route["name"],
                            "abbr" => $route["abbr"],
                            "color" => $route["color"],
                            "stops" => $routePatterns->map(function ($pattern) {
                                return $pattern["stopIDs"];
                            })->flatten()->unique()->values()->map(function ($stop) use ($multiplier) {
                                return $stop * $multiplier;
                            })->toArray(),
                            "path" => $routePatterns->map(function ($pattern) {
                                return $this->pairLatLong($this->decodePolyline($pattern["encLine"]));
                            })->values()->toArray(),
                        ];
                    })->values()->toArray();
                }
            }
        }

        return [];
    }

    private function pairLatLong($array)
    {
        $pairs = [];
        for ($i = 0; $i < count($array); $i += 2) {
            $pairs[] = [$array[$i], $array[$i + 1]];
        }
        return $pairs;
    }
}
