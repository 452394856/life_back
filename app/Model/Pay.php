<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/28
 * Time: 10:38
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Pay extends Model
{
    protected $table = 'pay';

    public function users()
    {
        return $this->belongsToMany('App\User', 'pay_user', 'pay_id', 'user_id');
    }
}