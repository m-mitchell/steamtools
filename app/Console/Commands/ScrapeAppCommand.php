<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\Scraper;
use App\Application;
use App\Setting;
use \DateTime;
use \DateInterval;

class ScrapeAppCommand extends Command
{
    use Scraper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:one {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes a specific Steam games for the database.';

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
        $id = $this->argument('id');
        $this->scrape_game_data($id);
    }
}
