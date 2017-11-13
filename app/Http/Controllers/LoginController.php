<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/21
 * Time: 15:54
 */

namespace App\Http\Controllers;

use const App\Service\Validate;
use const App\Service\ValidateStatus;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;
use App\Service\Result;

class LoginController extends Controller
{
    private $jwt;

    function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:30',
            'password' => 'required|max:30'
        ], [
            'username.required' => '用户名不能为空',
            'username.max' => '用户名长度不能超过30个字符',
            'password.required' => '密码不能为空',
            'password.max' => '密码长度不能超过30个字符'
        ]);
        if ($validator->fails()) {
            return Result::error(Validate, $validator->messages());
        }

        $user = User::where('username', $request->get('username'))->first();

        if(!$user){
            return Result::error(-2, ['error' => '帐号错误']);
        }

        if (password_verify($request->get('password'), $user->password)) {
            $token = $this->jwt->fromUser($user);
            $data = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name
                ],
                'token' => $token,
                'time' => time() + 7200,
            ];
            return Result::success($data);
        } else {
            return Result::error(-2, ['error' => '密码错误']);
        }
    }
}