<?php

namespace Components;

class GenericResponse
{
    public $isOk;
    public $message;
    public $content;

    function __construct($isOk, $message = '', $content = '')
    {
        $this->isOk = $isOk;
        $this->message = $message;
        $this->content = $content;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /* Used to return a new Generic respoonse for the client */
    public static function obtain($isOk, $message = '', $content = '')
    {
        $text = json_encode(new GenericResponse($isOk, $message, $content), JSON_PRETTY_PRINT);
        return $text;
    }
}
