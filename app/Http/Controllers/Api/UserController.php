<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(3);
//        return $this->success($users);
        return UserResource::collection($users);
    }

    public function store(UserRequest $request)
    {
        User::create($request->all());
        return $this->setStatusCode(201)->success('用户注册成功');
    }

    public function show(User $user)
    {
        return $this->success(new UserResource($user));
    }

    public function login(Request $request)
    {

        // 获取当前守卫的名称
        $parent_guard = Auth::getDefaultDriver();

        // claims 方法：在载荷中加上guard
        $token = Auth::claims(['guard' => $parent_guard])->attempt(['name' => $request->name, 'password' => $request->password]);

        if($token){
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
        $user = Auth::guard('api')->user();
        return $this->success(new UserResource($user));
    }

}
