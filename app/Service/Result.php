<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/22
 * Time: 10:11
 */

namespace App\Service;

const Validate = -1;
const ValidateStatus = 200;
class Result
{
    public static function success($data)
    {
        $result = [
            'code' => 0,
            'data' => $data
        ];
        return response($result, 200);
    }

    public static function error($code, $data)
    {
        $result = [
            'code' => $code,
            'data' => $data
        ];
        return response($result, ValidateStatus);
    }
}