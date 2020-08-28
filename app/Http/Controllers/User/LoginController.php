<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Model\GithubUser;
use App\Model\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    /**
     * 登陆
     */
    public function login(){
        $redirect_uri=request()->redirect_uri;
        Cookie::queue("redirect_uri",$redirect_uri,120);
        //判断用户是否登陆
        if(empty(Cookie::get("userinfo"))){
            return view("user/login",["redirect_uri"=>$redirect_uri]);
        }
        return redirect($redirect_uri);
    }
    /**
     * 退出登陆
     */
    public function quit(){
        $redirect_uri=request()->redirect_uri;
        $token=Cookie::get("token");
        Cookie::queue('token', null , -1); // 销毁
        Redis::del("h:token_".$token);
        return redirect($redirect_uri);
    }
    /**
     * 执行登陆
     */
    public function loginDo(){
        //表单验证
        request()->validate([
            'user' => 'bail|required',
            'user_pwd' => 'bail|required',
        ],[
            "user.required"=>"用户名手机号或邮箱不可为空",
            "user_pwd.required"=>"密码不可为空",
        ]);
        //接值
        $user=request()->post("user");
        $pwd=request()->post("user_pwd");
        $redirect_uri=request()->redirect_uri;
        //$redirect_uri=request()->post("redirect_uri");
        //判断用户名 or 手机号 or 邮箱 是否存在
        $res=User::where("user_name",$user)->orWhere("user_phone",$user)->orWhere("user_email",$user)->first();
        if($res){
            //判断密码是否正确
            if(password_verify($pwd,$res->user_pwd)){
                //判断账号状态，是否锁定
                if($res["error_num"]>=3 && time()-$res["error_time"]<600){
                    return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号已锁定，请在十分钟后重试");
                }
                //登陆成功清空错误次数和时间
                User::where("user_id",$res["user_id"])->update(["error_num"=>0,"error_time"=>null]);
                //登陆成功
                //调用获取token方法;
                $token=$this->token();
                Redis::hmset("h:token_".$token,$res->toArray());
                Redis::expire("h:token_".$token,7200);
                Cookie::queue("token",$token,120,"/","shop1.com");
                return redirect($redirect_uri);
            }
            $user_id=$res["user_id"];       //当前用户id
            $error_num=$res["error_num"];   //错误次数
            $error_time=$res["error_time"];     //最后错误时间
            //判断错误次数
            if($error_num>=3){
                //判断最后登陆错误时间和当前时间是否超过十分钟
                if(time()-$error_time > 600){
                    //超过十分钟修改错误次数为1 错误时间为当前时间戳
                    User::where("user_id",$user_id)->update(["error_num"=>1,"error_time"=>time()]);
                    //登陆失败
                    return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号或密码错误");
                }else{
                    //未超过十分钟提示账号已锁定
                    return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号已锁定，请在十分钟后重试");
                }
            }else{
                //错误次数加1
                $error_num=$error_num+1;
                //修改错误次数以及错误时间
                User::where("user_id",$user_id)->update(["error_num"=>$error_num,"error_time"=>time()]);
                //判断如果错误次数加1等于3锁定账号
                if($error_num<3){
                    //登陆失败
                    return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号或密码错误");
                }
                return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号已锁定，请在十分钟后重试");
            }
        }
        //登陆失败
        return redirect($_SERVER["HTTP_REFERER"])->with("msg","账号或密码错误");
    }
    /**
     * github登陆
     */
    public function loginGithub(){
        $url="https://github.com/login/oauth/authorize?client_id=".env('OAUTH_GITHUB_ID')."&redirect_uri=".env("APP_ADMINURL")."/oauth/github";
        return redirect($url);
    }
    /**
     * github回调
     */
    public function github(){
        //接受github返回的code
        if(empty($_GET["code"])){
            $redirect_uri=Cookie::get("redirect_uri");
            //登陆失败
            return redirect("/login?redirect_uri=".$redirect_uri)->with("msg","登陆失败");
        }
        $code=$_GET["code"];
        //换取access_token
        $token=$this->getToken($code);
        //获取github用户信息
        $UserInfo=$this->githubUserInfo($token);
        //判断该github是否存在
        $res=GithubUser::where("guid",$UserInfo["id"])->first();
        if(!$res){
            //将用户信息填入数据库
            //判断github用户名是否为空
            if(empty($UserInfo["name"])){
                //生成随机用户名
                $UserInfo["name"]=substr(md5(rand(10000,99999).time()),5,15);
            }
            $data=[
                "guid"=>$UserInfo["id"],     //github返回id
                "avatar_url"=>$UserInfo["avatar_url"],
                "github_url"=>$UserInfo["html_url"],
                "github_username"=>$UserInfo["name"],
                "github_email"=>$UserInfo["email"],
                "create_time"=>time()
            ];
            $github=GithubUser::create($data);

            //将用户名和github表id存入主用户表
            $user=User::create(["user_name"=>$UserInfo["name"],"g_id"=>$github["g_id"],"time_create"=>time()])->toArray();

        }else{
            $user=User::where("g_id",$res["g_id"])->first()->toArray();
        }
        //调用获取token方法;
        $token=$this->token();
        //将用户信息存入redis
        Redis::hmset("h:token_".$token,$user);
        Redis::expire("h:token_".$token,7200);
        //将token存入cookie
        Cookie::queue("token",$token,120,"/","shop1.com");
        return redirect(Cookie::get("redirect_uri"));
    }
    /**
     * 根据code 换取 token
     */
    protected function getToken($code){
        $url = 'https://github.com/login/oauth/access_token';

        //post 接口  Guzzle or  curl
        $client = new Client();
        $response = $client->request('POST',$url,[
            'form_params'   => [
                'client_id'         => env('OAUTH_GITHUB_ID'),
                'client_secret'     => env('OAUTH_GITHUB_SEC'),
                'code'              => $code
            ]
        ]);
        //将查询到的字符串解析到变量中
        parse_str($response->getBody(),$str);
        return $str['access_token'];
    }
    /**
     * 获取github个人信息
     */
    public function githubUserInfo($token){
        $url = 'https://api.github.com/user';
        //请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'headers'   => [
                'Authorization' => "token $token"
            ]
        ]);
        return json_decode($response->getBody(),true);
    }
    /**
     * 生成token
     */
    public function token(){
        $uuid=Cookie::get("uuid");
        $token=substr(sha1(time().$uuid.mt_rand(11111,999999)),5,25);
        return $token;
    }
    /**
     * 验证token
     */
    public function authToken(){
        //接值
        $token=request()->token;
        //从reids获取
        $redisToken=Redis::hgetall("h:token_".$token);
        //判断是否存在
        if($redisToken){
            return 1;
        }else{
            return 0;
        }
    }
}
