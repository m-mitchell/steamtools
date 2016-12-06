<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = "key";

    public static function fetch(){
        $info = Setting::get();
        $fetched = array();
        foreach($info as $k=>$v){
            $fetched[$v['attributes']['key']] = $v['attributes']['value'];
        }
        return $fetched;
    }
}
