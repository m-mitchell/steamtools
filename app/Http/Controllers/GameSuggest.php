<?php

namespace App\Http\Controllers;

use DB;
use App\User;
use App\Application;
use App\Tag;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Goutte\Client as GoutteClient;

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
        $suggestions_new = $this->get_new_games($library_games, 6);
        $new_games = [];
        foreach($suggestions_new as $suggestion){
            $new_games[] = $this->get_app_from_id($suggestion['appid']);
        }

        return view('game_suggest', ['new_games' => $new_games]);
    }

    private function get_app_from_id($appid){
        $app = Application::where('steam_appid', $appid)->get();
        if(count($app)==0){
            $app = $this->scrape_game_data($appid);
        } else {
            $app = $app[0];
        }
        
        return $app;
    }

    private function scrape_game_data($appid){

        //549470
        // Get page for this game
        $url = sprintf("http://store.steampowered.com/app/%s", $appid);
        $client = new GoutteClient();
        $crawler = $client->request("GET", $url, ["http_errors" => false]);

        // Some games don't have store pages bc they're not actually standalone
        // (E.g., Beta versions of other games)
        $homepage = $crawler->filter(".home_page_content");
        if(count($homepage) > 0){
            // This is a child game
            $data = [
                "steam_appid"  => $appid,
                "is_child"   => true
            ];
            $game = Application::firstOrNew($data);
            $game->save();
            return $game;
        }

        //Bypass age verification form if necessary
        $agecheck_forms = $crawler->filter("#agecheck_form");
        if(count($agecheck_forms) > 0){
            $form = $agecheck_forms->first()->form();
            $form->setValues(array(
                'ageDay'    => 1,
                'ageMonth'  => "January",
                'ageYear'   => 1900,
            ));
            $crawler = $client->submit($form);
        }

        // Grab fields from page
        $title = $crawler->filter('.apphub_AppName')->first()->text();
        $img  = $crawler->filter('.game_header_image_full')->first()->attr('src');
        $desc = $crawler->filter('.game_description_snippet')->first()->text();

        // Get review information
        $review_div = $crawler->filter('.responsive_reviewdesc');
        $review_score = 0;
        $voters = 0;
        if(count($review_div)>0){
            $str = $review_div->last()->text();
            preg_match_all("([0-9,]+)", $str, $matches);
            $review_score = (int)$matches[0][0];
            $voters = (int)str_replace(",", "", $matches[0][1]);
        }

        // Store in db
        $data = [
            "steam_appid"  => $appid,
            "title"        => $title,
            "image_path"   => $img,
            "description"  => $desc,
            "review_score" => $review_score,
            "voters"       => $voters
        ];


        $game = Application::firstOrNew($data);
        $game->save();
        return $game;
    }

    private function get_new_games($library_games, $count){
        $all_games = $library_games['response']['games'];
        $new_games = array_filter($all_games, function($x){ return $x['playtime_forever']==0; });
        shuffle($new_games);
        //mtodo get top-rated
        return array_slice($new_games, 0, $count);
    }
}