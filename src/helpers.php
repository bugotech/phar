<?php

if (! function_exists('work_path'))
{
    /**
     * Get the path to the work with source or phar.
     *
     * @param  string  $path
     * @return string
     */
    function work_path($path = '') {
        return getcwd() . ($path ? '/' . $path : $path);
    }
}