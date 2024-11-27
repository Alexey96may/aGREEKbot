<?php

namespace App\Classes;

class Room
{
    private $id;
    private const USERS_NUMBER_IN_RATING = 10;

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

    /**
     * Get users`s Top of the Room
     * 
     * @param string $scoresPath
     * @return string
     */
    public function getRoomRating(string $scoresPath): string
    {
        $chatScoresArr = $this->fileReader($scoresPath);

        $usersNumber = self::USERS_NUMBER_IN_RATING;
        if (count($chatScoresArr) < self::USERS_NUMBER_IN_RATING) {
            $usersNumber = count($chatScoresArr);
        }

        $result = "<b>Топ " . $usersNumber . "</b> игроков нашего чата: \n\n";
        $countNum = 1;
    
        if (count($chatScoresArr) !== 0) {
    
            arsort($chatScoresArr, SORT_NUMERIC);
            foreach ($chatScoresArr as $key => $value) {
                if ($countNum < self::USERS_NUMBER_IN_RATING) {
                    $symbolToCut = strpos($key, "__");
                    $originUserName = substr($key, 0, $symbolToCut);
                    $result .= "<b>" . $countNum . "</b>: " . $originUserName . " - " . $value . "\n";
                    $countNum++;
                } else {
                    break;
                }
            }
        } else {
            $result = "Рейтинг этого чата пока ещё пуст.";
        }
    
        return $result;
    }

    /**
     * Get the file content in the data array
     * 
     * @param string $filename
     * @return array with file content
     * @throws Exception when the file doesn`t contain a json array
     */
    public function fileReader(string $filename): array
    {
        $fileArray = json_decode(file_get_contents($filename), true);
        if (is_null($fileArray)) {
            throw new \Exception("It`s not the apropriated file structure in your file! Please, the file must contain a JSON array.");
        }
        return $fileArray;
    }
}