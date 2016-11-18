<?php

namespace App\Traits;

use App\Application;
use GuzzleHttp\Client;
use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Cookie;
use \DateTime;
use \DateInterval;

trait Scraper {
    private $baseUrl = "http://store.steampowered.com";
    private $key = "SAMPLE";

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

    private function scrape_game_data($appid){
        // Get page for this game
        $url = sprintf("%s/app/%s", $this->baseUrl, $appid);
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
            $game = Application::firstOrNew(["steam_appid"=>$appid]);
            $game->is_child = true;
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

        //print $title." ".$metascore."<br>";


        $game = Application::firstOrNew(["steam_appid"=>$appid]);
        $game->title        = $title;
        $game->image_path   = $target_filename;
        $game->description  = $desc;
        $game->review_score = $review_score;
        $game->metascore    = $metascore;
        $game->voters       = $voters;
        $game->save();
        return $game;
    }

    //http://store.steampowered.com/search/?sort_by=Released_DESC
    private function scrape_appids($stop_date){
        $app_ids = [];

        $i = 0;
        while(true){
            // Get recent games page
            $url = sprintf("%s/search/?sort_by=Released_DESC&page=%s", $this->baseUrl, $i);
            $client = new GoutteClient();
            $crawler = $client->request("GET", $url, ["http_errors" => false]);

            // Grab app ids from page
            $page_ids = $crawler->filter('#search_result_container > div > a')->each(function(Crawler $node, $i){
                $link = $node->attr('href');

                preg_match("([0-9]+)", $link, $matches);
                $appid = (int)$matches[0];
                
                return $appid;
            });
            $app_ids = array_merge($app_ids, $page_ids);

            // Check page date
            $last_date = $crawler->filter(".search_released")->last()->html();
            $date = DateTime::createFromFormat('d F, Y', $last_date);
            if(!$date){
                # We don't know the day, so assume it was the last day of the month
                $date = DateTime::createFromFormat('F Y', $last_date); 
                $date = $date->setDate($date->format('Y'), $date->format('m')+1, 1);
                $date->sub(new DateInterval("P1D"));
            }

            if($date <= $stop_date){
                break;
            }
            $i++;
        }

        return array_reverse($app_ids);
    }
}