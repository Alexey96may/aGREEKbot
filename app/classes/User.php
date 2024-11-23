<?php

namespace App\Classes;

class User
{
    private $id;
    private $firstName;
    private $lastName;

    public function __construct($fromUserArr)
    {
        $this->id = $fromUserArr['id'];
        $this->firstName = $fromUserArr['first_name'];
        $this->lastName = $fromUserArr['last_name'];
    }

    public function getID()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getFullName()
    {
        return $this->firstName . $this->lastName;
    }
}
