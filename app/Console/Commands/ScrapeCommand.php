<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\Scraper;
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

        //mtodo pull this from db & store it
        $stop_date = new DateTime();
        $stop_date->sub(new DateInterval("P1D"));

        // Get the app ids for recently-released games
        $app_ids = $this->scrape_appids($stop_date);

        // Get the app ids for stale games
        //mtodo

        foreach($app_ids as $id){
            $this->scrape_game_data($id);
        }

    }
}
