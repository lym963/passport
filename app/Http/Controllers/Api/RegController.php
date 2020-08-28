<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;

class RegController extends Controller
{
    /**
     * 注册
     */
    public function reg(){
        $data=file_get_contents("php://input");
        $data=json_decode($data,true);
        //添加用户数据
        $data["time_create"]=time();
        //密码加密
        $data["user_pwd"]=password_hash($data["user_pwd"],PASSWORD_DEFAULT);
        $res=User::create($data);
        if($res){
           return $this->Json(1,"ok");
        }
        return $this->Json(0,"注册失败");
    }
}
