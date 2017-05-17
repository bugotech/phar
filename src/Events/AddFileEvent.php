<?php namespace Bugotech\Phar\Events;


class AddFileEvent
{
    public $filename = '';

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return str_replace(base_path(), '', $this->filename);
    }
}