<?php

namespace App\Classes;

class User
{
    private $id;
    private $firstName;
    private $lastName = '';

    public function __construct($fromUserArr)
    {
        $strID = strval($fromUserArr['id']);
        $this->id = $strID;
        $this->firstName = $fromUserArr['first_name'];

        if (!empty($fromUserArr['last_name'])) {
            $this->lastName = $fromUserArr['last_name'];
        }
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
        if ($this->lastName === '') {
            return $this->firstName;
        }
        return $this->firstName . ' ' . $this->lastName;
    }

    private function getUserScoreName(): string
    {
        return $this->getFullName() . "__" . $this->getID();
    }

    public function getUserScore(string $scoresFilePath): int
    {
        $chatScoresArr = $this->fileReader($scoresFilePath);
    
        if (array_key_exists($this->getUserScoreName(), $chatScoresArr)) {
            return (int) $chatScoresArr[$this->getUserScoreName()];
        } else {
            $this->setUserScore($this->getUserScoreName(), 0);
            return 0;
        }
    }

    public function setUserScore(string $scoresFilePath, int $scoreToAdd): int
    {
        $chatScoresArr = $this->fileReader($scoresFilePath);
    
        if (array_key_exists($this->getUserScoreName(), $chatScoresArr)) {
            $chatScoresArr[$this->getUserScoreName()] = (int) $chatScoresArr[$this->getUserScoreName()] + $scoreToAdd;
        } else {
            $chatScoresArr[$this->getUserScoreName()] = 0 + $scoreToAdd;
        }

        file_put_contents($scoresFilePath, print_r(json_encode($chatScoresArr), true), LOCK_EX);
        return (int) $chatScoresArr[$this->getUserScoreName()];
    }

    public function getUserRatingMessage(string $scoresFilePath): string
    {
        $chatScoresArr = $this->fileReader($scoresFilePath);
        arsort($chatScoresArr, SORT_NUMERIC);

        if (array_key_exists($this->getUserScoreName(), $chatScoresArr)) {
            $userScoreNumber = (int) array_search($this->getUserScoreName(), array_keys($chatScoresArr)) + 1;
            return "Вы на <b>$userScoreNumber месте</b> в нашем чате! Ваш счёт: <b>" . $this->getUserScore($scoresFilePath) . '</b>.';
        } else {
            return "Вас ещё нет в статистике этого чата! \nОтветьте правильно хотя бы на один вопрос.";
        }
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
