<?php

namespace App\Traits;

use App\Application;
use GuzzleHttp\Client;
use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Cookie;
use \DateTime;
use \DateInterval;
use \Exception;
use Log;

trait Scraper {
    private $baseUrl = "http://store.steampowered.com";

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
        $client->setMaxRedirects(5);

        try {
            $adult_cookie = new Cookie('mature_content', 1, time() + 3600 * 24 * 7, '/', null, false, false);
            $client->getCookieJar()->set($adult_cookie);
            $crawler = $client->request("GET", $url, ["http_errors" => false]);
            if($client->getInternalResponse()->getStatus()!=200){
                 print $crawler->getInternalResponse()->getStatus()."\n"; //mtodo handle this
            } 

            //Bypass age verification form if necessary
            $agecheck_forms = $crawler->filter("#agecheck_form");
            if(!$agecheck_forms){
                $crawler->filter("#agegate_box");
            }
            if(count($agecheck_forms) > 0){
                $form = $agecheck_forms->first()->form();
                $form->setValues(array(
                    'ageDay'    => 1,
                    'ageMonth'  => "January",
                    'ageYear'   => 1900,
                ));
                $crawler = $client->submit($form);
            }

            // Some games don't have store pages bc they're not actually standalone
            // (E.g., Beta versions of other games)
            $homepage = $crawler->filter(".home_page_content");
            $dlc = $crawler->filter(".game_area_dlc_bubble");
            $video = $crawler->filter("#video_player_ctn");
            if(count($homepage) > 0 || count($dlc) > 0 || count($video) > 0){
                // This is a child game
                $game = Application::firstOrNew(["steam_appid"=>$appid]);
                $game->is_child = true;
                $game->save();
                return $game;
            }

            // Grab fields from page
            $title = $crawler->filter('.apphub_AppName')->first()->text();
            $img  = $crawler->filter('.game_header_image_full')->first()->attr('src');

            $desc_div = $crawler->filter('.game_description_snippet');
            $desc = "";
            if(count($desc_div) > 0){
                $desc = $desc_div->first()->text();
            }

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
            $cc = $client->getClient();
            $cc->get($img, ["save_to"=>public_path().$target_filename, "http_errors" => false]);
        } catch (Exception $e){
            Log::error('Caught exception on id '. $appid .': ' . $e->getMessage(). "\n");
            $game = Application::firstOrNew(["steam_appid"=>$appid]);
            $game->is_child = true;
            $game->needs_retry = true;
            $game->save();
            return null;
        } 

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
        $game->title        = $this->strip_emoji($title);
        $game->image_path   = $target_filename;
        $game->description  = $this->strip_emoji($desc);
        $game->review_score = $review_score;
        $game->metascore    = $metascore;
        $game->voters       = $voters;
        $game->save();
        return $game;
    }

    //http://store.steampowered.com/search/?sort_by=Released_DESC
    private function scrape_appids($stop_date){
        $app_ids = [];


        print sprintf("Scraping app IDs back to %s\n", $stop_date->format('Y-m-d'));

        $i = 0;
        while(true){
            $result = $this->scrape_app_ids_from_page($i);
            $app_ids = array_merge($app_ids, $result['ids']);
            if(count($result['ids'])==0){
                print(sprintf("No new IDs on page %s.\n", $i));
                break;
            }

            // Decide whether to check the next page
            $i++;

            // Check page date
            $last_date = $result['crawler']->filter(".search_released");
            if($last_date->count() == 0){
                continue;
            }
            $last_date = $last_date->last()->html();
            $date = DateTime::createFromFormat('d F, Y', $last_date);
            if(!$date){
                // We don't know the day, so assume it was the last day of the month
                $date = DateTime::createFromFormat('F Y', $last_date); 
                if(!$date){
                    // Some don't have release dates at all.
                    continue;
                }
                $date = $date->setDate($date->format('Y'), $date->format('m')+1, 1);
                $date->sub(new DateInterval("P1D"));
            }

            print sprintf("Scraped page %s (up to %s) - %s games in list\n", $i, $date->format('Y-m-d'), count($app_ids));
            if($date < $stop_date ){
                break;
            }
        }

        print sprintf("Done scraping app IDs.\n");
        return array_reverse($app_ids);
    }

    private function strip_emoji($text){
        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }

    private function scrape_app_ids_from_page($page){
        // Get recent games page
        $url = sprintf("%s/search/?sort_by=Released_DESC&page=%s", $this->baseUrl, $page);
        $client = new GoutteClient();
        $crawler = $client->request("GET", $url, ["http_errors" => false]);
        if($client->getInternalResponse()->getStatus()!=200){
             print $crawler->getInternalResponse()->getStatus()."\n"; //mtodo handle this
        } 

        // Grab app ids from page
        $page_ids = $crawler->filter('#search_result_container > div > a')->each(function(Crawler $node, $page){
            $link = $node->attr('href');

            preg_match("([0-9]+)", $link, $matches);
            $appid = (int)$matches[0];
            
            return $appid;
        });

        return array(
            "ids"=>$page_ids,
            "crawler"=>$crawler
        );
    }
}