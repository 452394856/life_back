<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/28
 * Time: 10:42
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Pay;
use App\User;
use Mockery\Exception;
use Validator;
use App\Service\Result;
use const App\Service\Validate;
use App\Model\PayUser;
use Tymon\JWTAuth\JWTAuth;
use DB;

class IndexController extends Controller
{
    private $auth;

    public function __construct(JWTAuth $auth)
    {
        $this->auth = $auth;
    }

    public function getTab(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|date_format:Y-m'
        ], [
            'time.required' => '日期不能为空',
            'time.date_format' => '日期格式不正确'
        ]);
        if ($validator->fails()) {
            return Result::error(Validate, $validator->messages());
        }

        $time = '%' . $request->get('time') . '%';
        $pays = Pay::select('pay.id', 'pay.user_id', 'pay.time', 'pay.money', 'users.name', 'pay.comment')
            ->where('time', 'like', $time)->join('users', 'pay.user_id', '=', 'users.id')
            ->orderBy('time', 'desc')->get();
        foreach ($pays as $pay) {
            $pay->users;
        }
        foreach ($pays as &$pay) {
            $pay['time'] = substr($pay['time'], 5);
            $pay['comment'] = is_null($pay['comment']) ? '无' : $pay['comment'];
            foreach ($pay['users'] as $key => $val) {
                unset($pay['users'][$key]);
                $pay['users'][$key] = [
                    'pay_user_id' => $val['id'],
                    'pay_user_name' => $val['name']
                ];
            }
        }

        return Result::success($pays);
    }

    public function getUserList(Request $request)
    {
        $userList = User::select('id', 'name')->orderBy('id', 'asc')->get();
        return Result::success($userList);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|date_format:Y-m-d',
            'money' => 'required|numeric',
            'check' => 'required|array'
        ], [
            'time.required' => '日期不能为空',
            'time.date_format' => '日期格式不正确',
            'money.required' => '金额不能为空',
            'money.numeric' => '金额必须是数值',
            'check.required' => '消费人不能为空',
            'check.array' => '消费人必须为数组'
        ]);
        if ($validator->fails()) {
            return Result::error(Validate, $validator->messages());
        }
        $user = $this->auth->parseToken()->authenticate();

        if (!in_array($user->id, $request->get('check'))) {
            return Result::error(444, '本人必须是消费人之一');
        }

        foreach ($request->get('check') as $val) {
            if (!User::where('id', '=', $val)->first()) {
                return Result::error(444, '消费人ID错误');
            }
        }

        try {
            DB::transaction(function () use ($user, $request) {
                $count = count($request->get('check'));
                $comment = $request->has('comment') ? $request->get('comment') : '';
                $id = DB::table('pay')->insertGetId(['user_id' => $user->id, 'time' => $request->get('time'),
                    'money' => round($request->get('money'), 2), 'pay_user_count' => $count, 'comment' => $comment]);
                foreach ($request->get('check') as $val) {
                    DB::table('pay_user')->insert(['pay_id' => $id, 'user_id' => $val]);
                }
            });
            return Result::success(['message' => '新增成功']);
        } catch (Exception $e) {
            return Result::error(444, '新增失败');
        }
    }

    public function del(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ], [
            'id.required' => 'id不能为空',
            'id.integer' => 'id必须为数字'
        ]);
        if ($validator->fails()) {
            return Result::error(Validate, $validator->messages());
        }

        $pay = Pay::where('id', '=', $request->get('id'))->first();
        if (!$pay) {
            return Result::error(444, '错误的ID');
        }

        $user = $this->auth->parseToken()->authenticate();
        if ($pay->user_id != $user->id) {
            return Result::error(444, '该记录不是您添加的，您没有删除的权限');
        }

        try {
            DB::transaction(function () use ($request) {
                DB::table('pay_user')->where('pay_id', '=', $request->get('id'))->delete();
                DB::table('pay')->where('id', '=', $request->get('id'))->delete();
            });
            return Result::success(['message' => '删除成功']);
        } catch (Exception $e) {
            return Result::error(444, '删除失败');
        }
    }

    public function total(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|date_format:Y-m'
        ], [
            'time.required' => '日期不能为空',
            'time.date_format' => '日期格式不正确',
        ]);
        if ($validator->fails()) {
            return Result::error(Validate, $validator->messages());
        }

        $user = $this->auth->parseToken()->authenticate();

        $payInfo = DB::table('pay')->select('pay.user_id', 'pay.money', 'pay.pay_user_count')->join('pay_user', 'pay.id', '=', 'pay_user.pay_id')
            ->where([['pay_user.user_id', '=', $user->id], ['pay.time', 'like', '%' . $request->get('time') . '%']])
            ->get();

        $consume = 0;
        $pay = 0;

        foreach ($payInfo as $val) {
            $consume += $val->money / $val->pay_user_count;
            if ($val->user_id == $user->id) {
                $pay += $val->money;
            }
        }
        $consume = round($consume, 2);
        $pay = round($pay, 2);
        $reduce = round($pay - $consume, 2);
        $data = compact('consume', 'pay', 'reduce');
        return Result::success($data);

    }
}