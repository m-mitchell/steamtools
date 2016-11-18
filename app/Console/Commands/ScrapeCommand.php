<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\Scraper;
use App\Application;
use App\Setting;
use \DateTime;
use \DateInterval;

class ScrapeCommand extends Command
{
    use Scraper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes new & stale Steam games for the database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $settings = Setting::fetch();

        // Get the app ids for recently-released games
        $stop_date = $settings['last_scraped_date']; 
        $new_app_ids = $this->scrape_appids($stop_date);
        foreach($new_app_ids as $id){
            $this->scrape_game_data($id);
        }

        // Get the app ids for stale games
        $stale_date = new DateTime();
        $stale_date->sub(new DateInterval(sprintf("P%sD", $settings['scrape_stale_days'])));
        $stale_apps = Application::where("updated_at", "<", $stale_date);
        foreach($stale_apps as $app){
            $this->scrape_game_data($app->id);
        }

        $timestamp = Setting::firstOrNew(["key"=>'last_scraped_date']);
        $timestamp->value        = new DateTime();
        $timestamp->save();
    }
}
