<?php

namespace App\Classes;

class Answer
{
    private $text;

    public function __construct($messageText)
    {
        $this->text = $messageText;
    }
}