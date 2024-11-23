<?php

namespace App\Classes;

class Room
{
    private $id;

    public function __construct($chatArray)
    {
        $this->id = $chatArray['id'];
    }
}