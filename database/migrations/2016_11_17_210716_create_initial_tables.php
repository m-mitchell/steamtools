<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('steam_appid')->unsigned();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('review_score')->unsigned()->default(0);
            $table->integer('voters')->unsigned()->default(0);
            $table->boolean('is_child')->default(false);
            $table->timestamps();
        });
            
       Schema::create('tag', function($table) {
            $table->increments('id');
            $table->string('name')->unique();
       });

        Schema::create('application_tag', function (Blueprint $table) {
            $table->integer('application_id')->unsigned();
            $table->integer('tag_id')->unsigned();
        });
            
       Schema::table('application_tag', function($table) {
           $table->foreign('application_id')->references('id')->on('application');
           $table->foreign('tag_id')->references('id')->on('tag');
       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_tag');
        Schema::dropIfExists('application');
        Schema::dropIfExists('tag');
    }
}
