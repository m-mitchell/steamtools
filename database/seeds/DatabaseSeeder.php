<?php

use Illuminate\Database\Seeder;
use App\Setting;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SettingsSeeder::class);
    }
}

class SettingsSeeder extends Seeder {

    public function run()
    {
        DB::table('settings')->delete();

        Setting::create([
            'key' => 'scrape_stale_days',
            'value' => '30'
        ]);

        Setting::create([
            'key' => 'last_scraped_date',
            'value' => '1989-09-29'
        ]);

        Setting::create([
            'key' => 'steam_api_key',
            'value' => 'SAMPLEKEY'
        ]);

        Setting::create([
            'key' => 'game_limit',
            'value' => '3'
        ]);

        Setting::create([
            'key' => 'admin_email',
            'value' => 'mail@megmitchell.ca'
        ]);
    }

}