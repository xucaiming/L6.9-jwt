<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\AdminResource;
use App\Jobs\Api\SaveLastTokenJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Http\Requests\Api\UserRequest;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AdminController extends Controller
{
    public function index()
    {
        $users = Admin::paginate(3);
//        return $this->success($users);
        return AdminResource::collection($users);
    }

    public function store(UserRequest $request)
    {
        Admin::create($request->all());
        return $this->setStatusCode(201)->success('用户注册成功');
    }

    public function show(Admin $user)
    {
        return $this->success(new AdminResource($user));
    }

    // 实现单点登录
    public function login(Request $request)
    {
        // 获取当前守卫的名称
        $parent_guard = Auth::getDefaultDriver();
        // claims 方法：在载荷中加上guard  便于在RefreshTokenMiddle中间件中判断
        $token = Auth::claims(['guard' => $parent_guard])->attempt(['name' => $request->name, 'password' => $request->password]);

        if($token){

            //如果登录 先检查原先是否有存token，有的话先失效，然后再存入最新的token
            $user = Auth::user();

            if($user->last_token){
                try{
                    Auth::setToken($user->last_token)->invalidate();
                } catch (TokenExpiredException $e){
                    //因为让一个过期的token再失效，会抛出异常，所以我们捕捉异常，不需要做任何处理
                }
            }

            // 保存新产生的token
//            $user->last_token = $token;
//            $user->save();
            // 替换成队列执行
            SaveLastTokenJob::dispatch($user, $token);

            return $this->setStatusCode(201)->success(['token' => 'bearer ' . $token]);
        }
        return $this->failed('账号或密码错误',400);
    }

    public function logout()
    {

//        Auth::guard('api')->logout();
        Auth::logout();
        return $this->success('退出成功...');
    }

    public function info()
    {
        $user = Auth::user();
        return $this->success(new AdminResource($user));
    }
}
