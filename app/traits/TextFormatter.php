<?php

namespace App\Traits;

trait TextFormatter
{
    public function formatText(string $text): string
    {
        return trim(mb_strtolower($text));
    }
}