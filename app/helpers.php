<?php
/**
 * Created by PhpStorm.
 * User: kx
 * Date: 2017/8/21
 * Time: 15:57
 */
if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}