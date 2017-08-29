<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/28
 * Time: 10:42
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Pay;
use Validator;
use App\Service\Result;
use const App\Service\Validate;

class IndexController extends Controller
{
    public function __construct()
    {
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
        $pays = Pay::select('pay.id', 'pay.user_id', 'pay.time', 'pay.money', 'users.name')
            ->where('time', 'like', $time)->join('users', 'pay.user_id', '=', 'users.id')
            ->orderBy('time', 'desc')->get();
        foreach ($pays as $pay) {
            $pay->users;
        }
        foreach ($pays as &$pay) {
            $pay['time'] = substr($pay['time'], 5);
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
}