<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    private string $url = 'https://servicenow.iu.edu';
    private string $username;
    private string $password;


    public function __construct()
    {
        $this->url = env('KNOWLEDGE_BASE_URL') or $this->url;
        $this->username = env('KNOWLEDGE_BASE_USERNAME');
        $this->password = env('KNOWLEDGE_BASE_PASSWORD');

    }
    public function getArticle(Request $request, string $id)
    {
        $data = $this->apiFetch("$this->url/api/iuit/iu_kb/get_article/$id");
        return response()->json($data);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        $url = "$this->url/api/iuit/iu_kb/search_articles?search=$q";
        $data = $this->apiFetch($url);
        return response()->json($data);
    }

    private function apiFetch(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output);
    }
}
