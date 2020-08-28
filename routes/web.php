<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
//token验证
Route::get("/auth/token","User\LoginController@authToken");

//登录
Route::get("/login","User\LoginController@login");
Route::post("/loginDo","User\LoginController@loginDo");
Route::get("/quit","User\LoginController@quit");//退出登陆
Route::get("/login/github","User\LoginController@loginGithub");
Route::get("/oauth/github","User\LoginController@github");//github回调
//注册
Route::get("/register","User\RegController@register");
Route::post("/reg","User\RegController@reg");
Route::post("/reg/gain","User\RegController@gain");   //获取验证码
Route::post("/reg/code","User\RegController@code");   //验证验证码
Route::post("/reg/name","User\RegController@name");   //验证用户名
//忘记密码
Route::get("/forgot","User\ForgotController@forgot");
Route::post("/forgotDo","User\ForgotController@forgotDo");
