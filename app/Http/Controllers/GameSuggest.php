<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

class GameSuggest extends Controller
{
    /**
     * Suggest games for a given Steam id
     *
     * @param  int  $id
     * @return Response
     */
    private $baseUrl = "http://api.steampowered.com";
    private $key = "086294E8629337CB3BA643368067E95C";

    public function __invoke($id){
        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children ', -1);

        //Try 76561197999112518 for a steam ID
        $client = new Client();

        // Get library for this person
        $url = "%s/IPlayerService/GetOwnedGames/v0001/?key=%s&steamid=%s&format=json";
        $url = sprintf($url, $this->baseUrl, $this->key, $id);
        $result = $client->request("GET", $url, ["http_errors" => false]);
        if($result->getStatusCode()!=200){
            //mtodo better error handling here
            echo $result->getStatusCode();
            return;
        }
        $library_games = json_decode($result->getBody(), true);

        // Get recent games for this person
        $url = "%s/IPlayerService/GetRecentlyPlayedGames/v0001/?key=%s&steamid=%s&format=json";
        $url = sprintf($url, $this->baseUrl, $this->key, $id);
        $result = $client->request("GET", $url, ["http_errors" => false]);
        if($result->getStatusCode()!=200){
            //mtodo better error handling here
            echo $result->getStatusCode();
            return;
        }
        $recent_games = json_decode($result->getBody(), true);


        // Suggest top-rated unplayed games
        $suggestions_new = $this->get_new_games($library_games, 3);
        $new_games = [];
        foreach($suggestions_new as $suggestion){
            $new_games[] = $this->get_app_from_id($suggestion['appid']);
        }

        return view('game_suggest', ['new_games' => $new_games]);
    }

    private function get_app_from_id($appid){
        //mtodo
        return [
            'id' => $appid
        ];
    }

    private function get_new_games($library_games, $count){
        $all_games = $library_games['response']['games'];
        $new_games = array_filter($all_games, function($x){ return $x['playtime_forever']==0; });
        shuffle($new_games);
        //mtodo get top-rated
        return array_slice($new_games, 0, $count);
    }
}