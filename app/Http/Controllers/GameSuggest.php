<?php

namespace App\Http\Controllers;

use DB;
use App\User;
use App\Application;
use App\Tag;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Goutte\Client as GoutteClient;
use Symfony\Component\BrowserKit\Cookie;

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
    private $gameLimit = 3;

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
        //$new_games = $this->get_new_games($library_games, $this->gameLimit);
        $new_games = $this->get_fave_games($library_games, $recent_games, $this->gameLimit);

        // Suggest favorite games
        $fave_games = $this->get_fave_games($library_games, $recent_games, $this->gameLimit);

        return view('game_suggest', [
            'new_games'  => $new_games,
            'fave_games' => $fave_games
        ]);
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

    private function scrape_game_data($appid){
        // Get page for this game
        $url = sprintf("http://store.steampowered.com/app/%s", $appid);
        $client = new GoutteClient();
        $adult_cookie = new Cookie('mature_content', 1, time() + 3600 * 24 * 7, '/', null, false, false);
        $client->getCookieJar()->set($adult_cookie);
        $crawler = $client->request("GET", $url, ["http_errors" => false]);

        // Some games don't have store pages bc they're not actually standalone
        // (E.g., Beta versions of other games)
        $homepage = $crawler->filter(".home_page_content");
        $dlc = $crawler->filter(".game_area_dlc_bubble");
        if(count($homepage) > 0 || count($dlc) > 0){
            // This is a child game
            $data = [
                "steam_appid"  => $appid,
                "is_child"   => true
            ];
            $game = Application::firstOrCreate($data);
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
        $metascore = 0;
        if(count($review_div)>0){
            $str = $review_div->last()->text();
            preg_match_all("([0-9,]+)", $str, $matches);
            $review_score = (int)$matches[0][0];
            $voters = (int)str_replace(",", "", $matches[0][1]);

            $positive = ($review_score/100) * $voters;
            $metascore = $this->get_metascore($positive, $voters, 0.95);
        }

        // Save image
        $target_filename = sprintf("/uploads/game_images/%s.jpg", $appid);
        $client->getClient()->get($img, ["save_to"=>public_path().$target_filename]);


        // Store in db
        $data = [
            "steam_appid"  => $appid,
            "title"        => $title,
            "image_path"   => $target_filename,
            "description"  => $desc,
            "review_score" => $review_score,
            "metascore"    => $metascore,
            "voters"       => $voters
        ];

        print $title." ".$metascore."<br>";


        $game = Application::firstOrCreate($data);
        $game->save();
        return $game;
    }

    private function get_metascore($p, $n){
        //http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
        if($n==0){
            return 0;
        }
        $z = 1.96; //0.95 confidence
        $phat = $p/$n;
        $score = ($phat + $z*$z/(2*$n) - $z * sqrt(($phat*(1-$phat)+$z*$z/(4*$n))/$n))/(1+$z*$z/$n);

        return $score;
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
            return $b->metascore - $a->metascore; 
        });

        return $new_games;
    }

    private function get_fave_games($library_games, $recent_games, $count){
        $suggestions = $library_games['response']['games'];
        usort($suggestions, function($a,$b){ 
            return $b['playtime_forever'] - $a['playtime_forever']; 
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