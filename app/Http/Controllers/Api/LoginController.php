<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    /**
     * 登陆
     */
    public function login(){
        //接值转数组
        $data=file_get_contents("php://input");
        $data=json_decode($data,true);
        //判断是否为空
        if($data["user"] && $data["pwd"] && $data["code"]){
            $this->Json(0,"不可为空");
        }
        //判断用户名是否存在
        $res=User::where("user_name",$data["user"])->orWhere("user_phone",$data["user"])->orWhere("user_email",$data["user"])->first()->toArray();
        if($res){
            //判断密码
            if(password_verify($data["pwd"],$res["user_pwd"])){
                //登陆成功返回token
                $token=$this->token();
                //将用户信息存入redis
                Redis::hmset("h:apinode_token_".$token,$res);
                Redis::expire("h:apinode_token_".$token,7200);
                return $this->Json(1,"ok",$token);
            }
        }
        return $this->Json(0,"用户名或密码错误");
    }
    /**
     * 生成token
     */
    public function token(){
        $token=substr(sha1(time().mt_rand(11111,999999)),5,25);
        return $token;
    }
}
