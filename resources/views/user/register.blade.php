@extends("layouts.layout")
@section("title","注册")
@section("content")
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- register -->
    <div class="pages section">
        <div class="container">
            <div class="pages-head">
                <h3>REGISTER</h3>
            </div>
            <div class="register">
                <div class="row">
                    <form class="col s12" action="{{url('/reg')}}" method="post">
                        @csrf
                        <input type="hidden" value="{{$redirect_uri}}" name="redirect_uri">
                        <div class="input-field">
                            <input type="text" id="name" name="user_name" class="validate" placeholder="NAME" required>
                            <span style="color:red">{{$errors->first('user_name')}}</span>
                        </div>
                        <div class="input-field">
                            <input type="email" id="email" name="user_email" placeholder="EMAIL" class="validate" required>
                            <span style="color:red">{{$errors->first('user_email')}}</span>
                        </div>
                        <div class="input-field">
                            <input type="password" id="pwd" name="user_pwd" placeholder="PASSWORD" class="validate" required>
                            <span style="color:red">{{$errors->first('user_pwd')}}</span>
                        </div>
                        <div class="input-field">
                            <input type="password" id="password" name="password" placeholder="CONFIRM PASSWORD" class="validate" required>
                            <span style="color:red">{{$errors->first('password')}}</span>
                        </div>
                        <div class="input-field">
                            <input type="text" id="phone" name="user_phone" placeholder="CELL-PHONE NUMBER" class="validate" required>
                            <button class="btn button-default gain" id="gain">GET CODE</button>
                            <span id="phoneSpan"></span>
                            <span  style="color:red">{{$errors->first('user_phone')}}</span>
                        </div>
                        <div class="input-field">
                            <input type="text" id="code" name="code" placeholder="AUTH CODE" class="validate" required>
                            <span style="color:red">
                                {{session("msg")}}
                            </span>
                        </div>
                        <center>
                            <input class="btn button-default" type="submit" id="sub" value="REGISTER">
                        </center>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end register -->


    <!-- loader -->
    <div id="fakeLoader"></div>
    <!-- end loader -->
    <script>
        var flag1=false
        var flag2=false
        var flag3=false
        var flag4=false
        var flag5=false
        var flag6=false
        //发送验证码
        $("#gain").click(function () {
            var phone=$("#phone").val();
            $.ajax({
                type:"post",
                url:"/reg/gain",
                data:{phone:phone},
//                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res){
                    if(res.error==0){
                        $("#phoneSpan").html("<font color=red>"+res.msg+"</font>")
                        return false
                    }
                    $("#phoneSpan").html("<font color=green>"+res.msg+"</font>")
                }
            })
            //将按钮改为秒数
            $("#gain").text("60s");
            $("button").attr("disabled","true");
            //设置每秒提示
            times=setInterval(gotime,1000)
        })
        //每秒提示方法
        function gotime(){
            //获取按钮文本
            var s=$("#gain").text();
            //获取整型
            s=parseInt(s);
            if(s>0){
                s=s-1;
                //设置按钮文本
                $("#gain").text(s+"s");
                //将按钮改为不可点击
                $("button").attr("disabled","true");
            }else{
                //清楚每秒显示
                clearInterval(times);
                //设置按钮文本
                $("#gain").text("获取");
                //将按钮改为可点击
                $("button").removeAttr("disabled");
            }
        }
        //验证验证码
        $("#code").blur(function () {
            //验证为通过阻止表单提交
            flag1=false
            var code=$(this).val()
            //判断不可为空
            if(!code){
                $(this).next().html("验证码不可为空")
                return false
            }
            //验证验证码是否有效
            $.ajax({
                type:"post",
                url:"/reg/code",
                data:{code:code},
//                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res){
                    if(res.error==0){
                        $("#code").next().html(res.msg)
                        return false
                    }
                    $("#code").next().html("")
                }
            });
            //验证为通过阻止表单提交
            flag1=true
        })
        //验证用户名
        $("#name").blur(function () {
            //验证为通过阻止表单提交
            flag2=false
            var name=$(this).val()
            //判断不可为空
            if(!name){
                $(this).next().html("用户名不可为空")
                return false
            }
            //验证正则
            var reg=/^[a-zA-Z0-9_-]{4,16}$/
            if(!reg.test(name)){
                $(this).next().html("用户名必须由4到16位（字母，数字，下划线，减号）")
                return false
            }
            //验证是否已存在
            $.ajax({
                type:"post",
                url:"/reg/name",
                data:{name:name},
//                dataType:"json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res){
                    if(res.error == 0){
                        $("#name").next().html(res.msg)
                        return false
                    }
                    $("#name").next().html("")
                }
            });
            //验证为通过阻止表单提交
            flag2=true
        })
        //验证邮箱
        $("#email").blur(function () {
            //验证为通过阻止表单提交
            flag3=false
            var email=$(this).val()
            //判断不可为空
            if(!email){
                $(this).next().html("邮箱不可为空")
                return false
            }
            //验证正则
            var reg=/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/
            if(!reg.test(email)){
                $(this).next().html("邮箱格式不正确")
                return false
            }
            $(this).next().html("");
            //验证为通过阻止表单提交
            flag3=true
        })
        //验证密码
        $("#pwd").blur(function () {
            //验证为通过阻止表单提交
            flag4=false
            var pwd=$(this).val()
            //判断不可为空
            if(!pwd){
                $(this).next().html("密码不可为空")
                return false
            }
            //验证正则
            var reg= /^[0-9A-Za-z]{8,16}$/
            if(!reg.test(pwd)){
                $(this).next().html("密码必须由8-16位数字或这字母组成")
                return false
            }
            $(this).next().html("");
            //验证为通过阻止表单提交
            flag4=true
        })
        //验证确认密码
        $("#password").blur(function () {
            //验证为通过阻止表单提交
            flag5=false
            var pwd=$("#pwd").val()
            var password=$(this).val()
            //判断确认密码是否和密码一致
            if(pwd!=password || password==""){
                $(this).next().html("确认密码和密码不一致")
                return false
            }
            $(this).next().html("");
            //验证为通过阻止表单提交
            flag5=true
        })
        //验证手机号
        $("#phone").blur(function () {
            //验证为通过阻止表单提交
            flag6=false
            var phone=$(this).val()
            //判断不可为空
            if(!phone){
                $("#phoneSpan").html("<font color=red>手机号不可为空</font>")
                return false
            }
            //验证正则
            var reg= /^1[3-578]\d{9}$/
            if(!reg.test(phone)){
                $("#phoneSpan").html("<font color=red>手机号格式不正确</font>")
                return false
            }
            $("#phoneSpan").html("");
            //验证为通过阻止表单提交
            flag6=true
        })
        //验证表单是否可以提交
        $("form").submit(function (event) {
            // 终止默认事件的传递
            if(flag1==false || flag2==false || flag3==false || flag4==false || flag5==false || flag6==false){
                event.preventDefault()
            }

        })
    </script>
@endsection