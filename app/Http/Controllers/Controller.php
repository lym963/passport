<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    //返回数据方法
    public function Json($code,$msg,$data=""){
        $json=[
            "code"=>$code,
            "msg"=>$msg,
            "data"=>$data
        ];
        return json_encode($json);
    }
}
