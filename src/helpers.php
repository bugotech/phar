<?php

if (! function_exists('work_path')) {
    /**
     * Get the path to the work with source or phar.
     *
     * @param  string  $path
     * @return string
     */
    function work_path($path = '')
    {
        return getcwd() . ($path ? '/' . $path : $path);
    }
}

if (! function_exists('bin_path')) {
    /**
     * Get the path to the work execute or phar.
     *
     * @param  string  $path
     * @return string
     */
    function bin_path($path = '')
    {
        global $_SERVER;

        $info = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $dir = $info['dirname'];

        return $dir . ($path ? '/' . $path : $path);
    }
}