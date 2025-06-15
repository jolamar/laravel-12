<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RideSystemsController extends Controller
{

    private int $timeForCacheETAsInSeconds = 5;
    private int $timeForCacheCapacityInSeconds = 30;
    private int $timeForCacheBusesInSeconds = 5;
    private int $timeForCacheRoutesInSeconds = 3600;
    private int $timeForCacheStopsInSeconds = 3600;

    public function getBuses(Request $request, $campus)
    {
        $capacityJson = Cache::store(config('cache.default'))->remember('iui-capacity', $this->timeForCacheCapacityInSeconds, function () {
            $endpoint = env('IUI_JAGLINE_URL') . '/GetVehicleCapacities?ApiKey=' . env('IUI_JAGLINE_API_KEY');
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);
            return $response->json();
        });

        return Cache::store(config('cache.default'))->remember('iui-buses', $this->timeForCacheBusesInSeconds, function () use ($capacityJson) {
            $endpoint = env('IUI_JAGLINE_URL') . '/GetMapVehiclePoints?ApiKey=' . env('IUI_JAGLINE_API_KEY');
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);
            $buses = $response->json();

            $etas = Cache::store(config('cache.default'))->remember("iui-etas", $this->timeForCacheETAsInSeconds, function () {
                $endpoint = env('IUI_JAGLINE_URL') . '/GetStopArrivalTimes?ApiKey=' . env('IUI_JAGLINE_API_KEY');
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);
                return $response->json();
            });

            $etas = collect($etas)->map(function ($eta) {
                $eta["RouteDescription"] = RideSystemsCleaner::clean_route_description($eta["RouteDescription"]);
                return $eta;
            })->toArray();

            return array_map(function ($bus) use ($capacityJson, $etas) {
                $bus['Capacity'] = 0;
                $bus['Occupancy'] = 0;
                $bus['Percentage'] = 0;
                foreach ($capacityJson as $capacity) {
                    if ($capacity['VehicleID'] == $bus['VehicleID']) {
                        $bus['Capacity'] = $capacity['Capacity'];
                        $bus['Occupancy'] = $capacity['CurrentOccupation'];
                        $bus['Percentage'] = $capacity['Percentage'];
                        break;
                    }
                }

                $eta = array_filter($etas, function ($e) use ($bus) {
                    return $e['RouteId'] == $bus['RouteID'];
                });

                $eta = array_values($eta);

                $etaWithTimes = array_filter($eta, function ($e) {
                    return isset($e['Times']) && count($e['Times']) > 0;
                });

                foreach ($etaWithTimes as &$e) {
                    if (isset($e['Times'])) {
                        $e['Times'] = array_filter($e['Times'], function ($time) use ($bus) {
                            return $time['VehicleId'] == $bus['VehicleID'];
                        });
                        $e['Times'] = array_values($e['Times']);
                    }
                }
                $bus['ETA'] = $eta;

                return $bus;
            }, $buses);
        });
    }

    public function getStops(Request $request, $campus)
    {
        return Cache::store(config('cache.default'))->remember('iui-stops', $this->timeForCacheStopsInSeconds, function () {
            $endpoint = env('IUI_JAGLINE_URL') . '/GetStops?ApiKey=' . env('IUI_JAGLINE_API_KEY');
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);
            return $response->json();
        });
    }

    public function getRoutes(Request $request, $campus)
    {
        return Cache::store(config('cache.default'))->remember('iui-routes', $this->timeForCacheRoutesInSeconds, function () {
            $endpoint = env('IUI_JAGLINE_URL') . '/GetRoutesForMapWithScheduleWithEncodedLine?ApiKey=' . env('IUI_JAGLINE_API_KEY');
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);

            return $response->collect()->map(function ($route) {
                $route["Description"] = RideSystemsCleaner::clean_route_description($route["Description"]);
                return $route;
            })->toArray();
        });
    }

    public function getEtas(Request $request, $campus)
    {
        $route_id = $request->input("route");
        return Cache::store(config('cache.default'))->remember('iui-etas', $this->timeForCacheETAsInSeconds, function () use ($route_id) {
            $endpoint = env('IUI_JAGLINE_URL') . '/GetStopArrivalTimes?ApiKey=' . env('IUI_JAGLINE_API_KEY') . '&RouteIds=' . $route_id;
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->withOptions(["curl" => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1']])->get($endpoint);

            return $response->collect()->map(function ($eta) {
                $eta["RouteDescription"] = RideSystemsCleaner::clean_route_description($eta["RouteDescription"]);
                return $eta;
            })->toArray();
        });
    }
}

class RideSystemsCleaner
{
    public static function clean_route_description($routeDescription): string
    {
        return trim(str_replace(
            "Estimated Times", //
            "", //
            $routeDescription
        ));
    }

}
