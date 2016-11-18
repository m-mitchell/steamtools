<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    public static function fetch(){
        $info = Setting::get();
        $fetched = array();
        foreach($info as $k=>$v){
            $fetched[$v['key']] = $v['value'];
        }
        //var_dump($fetched);
        return $fetched;
    }
}
