<?php

namespace App\Classes;
require_once 'app/traits/TextFormatter.php';

class UserAnswer
{
    use \App\Traits\TextFormatter;

    private $originAnswer;
    private $formatedAnswer;

    public function __construct(string $messageText)
    {
        $this->originAnswer = $messageText;
        $this->formatedAnswer = $this->formatText($messageText);
    }

    public function getOrigAnswer(): string
    {
        return $this->originAnswer;
    }

    public function getFormatAnswer(): string
    {
        return $this->formatedAnswer;
    }
}