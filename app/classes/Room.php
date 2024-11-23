<?php

namespace App\Classes;

class Room
{
    private $id;

    public function __construct($chatArray)
    {
        $strID = strval($chatArray['id']);
        $this->id = $strID;
    }

    public function getID(): string
    {
        return $this->id;
    }    
    
    public function isChatRoom(): bool
    {
        $firstIDLetter = mb_substr($this->id, 0, 1);

        if ($firstIDLetter === '-') {
            return true;
        }
        return false;
    }

    public function roomIDPath(): string
    {
        $filePath = '';
        if ($this->isChatRoom()) {
            $sanitizedChatId = substr($this->getID(), 1);
            $filePath = "chat" . $sanitizedChatId;
        } else {
            $filePath = "user" . $this->getID();
        }

        return $filePath;
    }
}