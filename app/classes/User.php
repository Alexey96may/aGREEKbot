<?php

namespace App\Classes;

class User
{
    private $id;
    private $firstName;
    private $lastName;

    public function __construct($fromUserArr)
    {
        $strID = strval($fromUserArr['id']);
        $this->id = $strID;
        $this->firstName = $fromUserArr['first_name'];
        $this->lastName = $fromUserArr['last_name'];
    }

    public function getID(): string
    {
        $strID = strval($this->id);
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . $this->lastName;
    }
}
