<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use GuzzleHttp\Client;

use App\Application;
use App\Setting;
use App\Traits\Scraper;

class GameSuggest extends Controller
{
    /**
     * Suggest games for a given Steam id
     *
     * @param  int  $id
     * @return Response
     */
    use Scraper;

    private $baseApiUrl = "http://api.steampowered.com";

    public function __invoke(Request $request){
        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children ', -1);

        $settings = Setting::fetch();

        $new_games = [];
        $fave_games = [];
        $id = $request->input('id');
        $return_values = [
            "settings"   => $settings,
            "id"         => $id,
            "error"      => null,
            "fave_games" => [],
            "new_games"  => []
        ];

        if($id!=null){

            $client = new Client();
            // Get library for this person
            $url = "%s/IPlayerService/GetOwnedGames/v0001/?key=%s&steamid=%s&format=json";
            $url = sprintf($url, $this->baseApiUrl, $settings['steam_api_key'], $id);
            $result = $client->request("GET", $url, ["http_errors" => false]);
            if($result->getStatusCode()!=200){
                $return_values["error"] = $result->getStatusCode();
                return view('game_suggest', $return_values);
            } 
            $library_games = json_decode($result->getBody(), true);

            // Get recent games for this person
            $url = "%s/IPlayerService/GetRecentlyPlayedGames/v0001/?key=%s&steamid=%s&format=json";
            $url = sprintf($url, $this->baseApiUrl, $settings['steam_api_key'], $id);
            $result = $client->request("GET", $url, ["http_errors" => false]);
            if($result->getStatusCode()!=200){
                $return_values["error"] = $result->getStatusCode();
                return view('game_suggest', $return_values);
            }
            $recent_games = json_decode($result->getBody(), true);

            // Suggest top-rated unplayed games
            $return_values["new_games"] = $this->get_new_games($library_games, $settings['game_limit']);

            // Suggest favorite games
            $return_values["fave_games"] = $this->get_fave_games($library_games, $recent_games, $settings['game_limit']);
        }

        return view('game_suggest', $return_values);
    }

    private function get_app_from_id($appid){
        $app = Application::where('steam_appid', $appid)->get();
        if(count($app)==0){
            $app = $this->scrape_game_data($appid);
        } else {
            $app = $app[0];
        }
        if(!$app->is_child){
            return $app;
        }
        return null;
    }

    private function get_new_games($library_games, $count){
        $all_games = $library_games['response']['games'];
        $suggestions = array_filter($all_games, function($x){ return $x['playtime_forever']==0; });

        $new_games = [];
        foreach($suggestions as $suggestion){
            $new_game = $this->get_app_from_id($suggestion['appid']);
            if($new_game!=null){
                $new_games[] = $new_game;
            }
        }

        usort($new_games, function($a,$b){ 
            return $b->metascore > $a->metascore; 
        });

        return array_slice($new_games, 0, $count);
    }

    private function get_fave_games($library_games, $recent_games, $count){
        $suggestions = $library_games['response']['games'];
        usort($suggestions, function($a,$b){ 
            return $b['playtime_forever'] > $a['playtime_forever']; 
        });

        $recent_app_ids = array_map(function($x){ 
            return $x['appid']; 
        }, $recent_games['response']['games']);

        $fave_games = [];
        foreach($suggestions as $suggestion){
            if(in_array($suggestion['appid'], $recent_app_ids)){
                continue;
            }
            $new_game = $this->get_app_from_id($suggestion['appid']);
            if($new_game!=null){
                $fave_games[] = $new_game;
            }
            if(count($fave_games)==$count){
                break;
            }
        }
        return $fave_games;
    }
}