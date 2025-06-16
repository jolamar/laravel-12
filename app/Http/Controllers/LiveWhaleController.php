<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LiveWhaleController extends Controller
{
    private int $timeForCacheInSeconds;

    public function __construct()
    {
        $this->timeForCacheInSeconds = 3600;
    }

    public function getNews(Request $request) {
        $campus = strtolower($request->input('c') ?? "IU");

        $key = "news-${campus}";
        $liveWhaleNewsApiUrl = env('LIVEWHALE_NEWS_API_URL');

        $campusMappings = [
            "iub" => "IU Bloomington",
            "iupui" => "IU Indianapolis",
            "iui" => "IU Indianapolis",
            "iuk" => "IU Kokomo",
            "iusb" => "IU South Bend",
            "iue" => "IU East",
            "ius" => "IU Southeast",
            "iun" => "IU Northwest",
            "iupuc" => "IU Columbus",
            "iuc" => "IU Columbus",
            "iufw" => "IU Fort Wayne",
        ];

        if (isset($campusMappings[$campus])) {
            $campusName = $campusMappings[$campus];
            $json = Cache::store('file')->remember($key, $this->timeForCacheInSeconds, function() use ($liveWhaleNewsApiUrl, $campusName) {
                return Http::get("${liveWhaleNewsApiUrl}/news/filter/custom_data_iu_campus|matches|${campusName}/response_fields/summary,news_categories,custom_fields,image/paginate/300/sort_order/reverse-date")->json();
            });
        } else {
            // trending news
            $json = Cache::store('file')->remember($key, $this->timeForCacheInSeconds, function() use ($liveWhaleNewsApiUrl) {
                return Http::get("${liveWhaleNewsApiUrl}/news/response_fields/summary,news_categories,custom_fields,image/paginate/300/sort_order/reverse-date")->json();
            });
        }

        if (isset($json['data'])) {
            $news = collect($json['data']);

            return $news->map(function ($news) {
                return [
                    'path' => $news['url'],
                    'title' => $news['title'],
                    'topic' => $news['news_categories'] ?? '',
                    'url' => $news['url'],
                    'image' => str_replace("width/200/height/200/", "", $news['thumbnail']),
                    'image_small' => str_replace("width/200/height/200/", "width/400/height/400/", $news['thumbnail']),
                    'alt' => $news['thumbnail_caption'],
                    'summary' => $news['summary'],
                ];
            })->take(5)->toArray();
        }

        return [];
    }

    private function getEventsBy(Request $request, String $key, String $path) {
        $page = $request->input('page') ?? 1;
        $key = "${key}-${page}";
        $liveWhaleEventsApiUrl = env('LIVEWHALE_EVENTS_API_URL');
        return Cache::store('file')->remember($key, $this->timeForCacheInSeconds, function() use ($liveWhaleEventsApiUrl, $path, $page) {
            return Http::get("${liveWhaleEventsApiUrl}/${path}?page=${page}")->json();
        });
    }

    public function getEvents(Request $request, $size=300) {
        return $this->getEventsBy($request, "events-${size}", "events/paginate/${size}");
    }

    public function getEventsByGroup(Request $request, $group, $size=300) {
        return $this->getEventsBy($request, "group-${group}-${size}", "events/group/${group}/paginate/${size}");
    }

    public function getEventsByTag(Request $request, $tag, $size=300) {
        return $this->getEventsBy($request, "tag-${tag}-${size}", "events/tag_mode/all/tag/${tag}/paginate/${size}");
    }

    public function getEventsByCategory(Request $request, $category, $size=300) {
        return $this->getEventsBy($request, "category-${category}-${size}", "events/category/${category}/paginate/${size}");
    }
}
