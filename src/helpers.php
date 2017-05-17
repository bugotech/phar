<?php

if ( ! function_exists('work_path'))
{
    /**
     * Get the path to the work with source or phar.
     *
     * @param  string  $path
     * @return string
     */
    function work_path($path = '')
    {
        return getcwd() . ($path ? '/'.$path : $path);
    }
}

if (! function_exists('in_phar')) {
    /**
     * Return is running into phar.
     *
     * @return bool
     */
    function in_phar()
    {
        return ('phar:' !== strtolower(substr(__FILE__, 0, 5)));
    }
}