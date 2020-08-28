<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ForgotController extends Controller
{
    /**
     * 忘记密码
     */
    public function forgot(){
        $redirect_uri=request()->redirect_uri;
        return view("user.forgot",["redirect_uri"=>$redirect_uri]);
    }
    /**
     * 修改密码
     */
    public function forgotDo(){
        //表单验证
        request()->validate([
            'user_pwd' => 'bail|required|regex:/^[0-9A-Za-z]{8,16}$/',
            'password' => 'bail|required|same:user_pwd',
            'user_phone' => 'bail|required|regex:/^1[3-578]\d{9}$/',
        ],[
            "user_pwd.required"=>"密码不可为空",
            "user_pwd.regex"=>"密码必须由8-16位数字或这字母组成",
            "password.required"=>"确认密码不可为空",
            "password.same"=>"确认密码必须和密码一致",
            "user_phone.required"=>"手机号不可为空",
            "user_phone.regex"=>"手机号格式不正确",
        ]);
        $data=request()->except("_token","redirect_uri");
        $redirect_uri=request()->post("redirect_uri");
        //获取redis中该uuid的验证码
        $uuid=$_COOKIE["uuid"];
        $codeRedis=Redis::get($uuid);
        //不存在失效
        if(empty($codeRedis)){
            return redirect($_SERVER["HTTP_REFERER"])->with("msg","验证码错误或已失效");
        }
        //判断验证码是否正确
        if($codeRedis!=$data["code"]){
            return redirect($_SERVER["HTTP_REFERER"])->with("msg","验证码错误或已失效");
        }
        //密码加密
        $pwd=password_hash($data["user_pwd"],PASSWORD_DEFAULT);
        $res=User::where("user_phone",$data["user_phone"])->update(["user_pwd"=>$pwd]);
        if($res){
            return redirect("/login?redirect_uri=".$redirect_uri);
        }else{
            return redirect($_SERVER["HTTP_REFERER"])->with("msg","修改失败");
        }
    }
}
