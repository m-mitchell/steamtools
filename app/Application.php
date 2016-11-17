<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'steam_appid', 'title', 'description', 'image_path', 'review_score', 'voters', 'is_child'
    ];
}
