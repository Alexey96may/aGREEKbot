<?php
/**
 * The class for the Quiz Game
 * 
 * Processing the Quiz Game
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes;
require_once 'app/Traits/TextFormatter.php';

use Exception;

class Game
{
    use \App\Traits\TextFormatter;

    /**
     * The file number (its name prefix) with the Questions
     * 
     * @var array
     */
    private const QUESTION_MODULES = array(1, 2, 3, 4);

    /**
     * The curent game mod (May be "toRus" or "toGreek")
     * 
     * @var string
     */
    private $gameMode = 'toRus';

    /**
     * The path to the setting file
     * 
     * @var string
     */
    private $settingsPath;

    /**
     * The path to the copy of the translTrain file
     * 
     * @var string
     */
    private $translCopyFilePath;

    /**
     * The path to the copy of the translTrain file
     * 
     * @var string
     */
    private $trueResponse;

        /**
     * The path to the copy of the translTrain file
     * 
     * @var string
     */
    private $trueQuestion;

    public function __construct(string $settingsPath, string $translCopyFilePath)
    {
        $this->settingsPath = $settingsPath;
        $this->translCopyFilePath = $translCopyFilePath;

        if (!file_exists($this->settingsPath)) {
            file_put_contents($this->settingsPath, '[]', LOCK_EX);
            $this->startGame();
        }
        if (!file_exists($this->translCopyFilePath)) {
            file_put_contents($this->translCopyFilePath, '[]', LOCK_EX);
            $this->startGame();
        }

        $settings_array = $this->fileReader($this->settingsPath);
        if (!isset($settings_array['gameMod'])) {
            $settings_array['gameMod'] = $this->gameMode;
            file_put_contents($this->settingsPath, print_r(json_encode($settings_array), true), LOCK_EX);
        } else {
            $this->gameMode = $this->fileReader($this->settingsPath)['gameMod'];
        }

        $this->setTrueResponse();
        $this->setTrueQuestion();
    }

    /**
     * Get the current game mod
     * 
     * @return string
     */
    public function getGameMode(): string
    {
        return $this->gameMode;
    }

    /**
     * Change the current game mod and save it in the setting file
     * 
     * Change the game mod from "toGreek" to "toRus" and vice versa.
     * 
     * @return true
     */
    public function changeGameMode(): bool
    {
        $chatSettingsArr = $this->fileReader($this->settingsPath);
        $actualGameMod =  $this->getGameMode();

        if ($actualGameMod == 'toRus') {
            $chatSettingsArr['gameMod'] = 'toGreek';
            $this->gameMode = 'toGreek';
        } else {
            $chatSettingsArr['gameMod'] = 'toRus';
            $this->gameMode = 'toRus';
        }

        $this->setTrueResponse();
        $this->setTrueQuestion();
        
        file_put_contents($this->settingsPath, print_r(json_encode($chatSettingsArr), true), LOCK_EX);
        return true;
    }

    /**
     * Get the Game Module from the Setting file.
     * 
     * @return int
     */
    public function getGameModule(): int
    {
        $settingsArr = $this->fileReader($this->settingsPath);
        if (array_key_exists('fileGame', $settingsArr)) {
            return (int) $settingsArr['fileGame'];
        }
        return -1;
    }

    /**
     * Change the Game Module randomly and set it in the Setting file.
     * 
     * @return int
     */
    public function changeGameModule(): int
    {
        $chatSettingArr = $this->fileReader($this->settingsPath);
        $chatSettingArr['fileGame'] = array_rand(self::QUESTION_MODULES) + 1;
        
        file_put_contents($this->settingsPath, print_r(json_encode($chatSettingArr), true), LOCK_EX);

        $this->setTrueResponse();
        $this->setTrueQuestion();
        return (int) $chatSettingArr['fileGame'];
    }

    /**
     * Get the actual true response.
     * 
     * @return string
     */
    public function getTrueResponse(): string
    {
        return $this->formatText($this->trueResponse);
    }

    /**
     * Set the actual true response.
     * 
     * @return bool
     */
    private function setTrueResponse(): bool
    {
        $translArr = $this->respArrNow();
        if ($this->getGameMode() == 'toRus') {
            $this->trueResponse = $translArr[0]["translation"];
        } else {
            $this->trueResponse = $translArr[0]["word"];
        }
        return true;
    }

    /**
     * Get the actual true question.
     * 
     * @return string
     */
    public function getTrueQuestion(): string
    {
        return $this->formatText($this->trueQuestion);
    }

    /**
     * Set the actual true question.
     * 
     * @return bool
     */
    private function setTrueQuestion(): bool
    {
        $translArr = $this->respArrNow();
        if ($this->getGameMode() == 'toRus') {
            $this->trueQuestion = $translArr[0]["word"];
        } else {
            $this->trueQuestion = $translArr[0]["translation"];
        }
        return true;
    }

    /**
     * Change Response and Question after user`s winning
     * 
     * @return bool
     */
    public function userWin(): bool
    {
        $this->unsetOptionsDone();

        if (count($this->respArrNow()) < 4) {
            $actualGamePath = TEMPL_PREFIX . "/translTraining" . $this->changeGameModule() . ".txt";
            $translArray = $this->arrRandomizer($this->fileReader($actualGamePath));
            $this->fileWriter($this->translCopyFilePath, json_encode($translArray));
        }

        $newQuestionArr = $this->respArrNow();
        array_shift($newQuestionArr);
        $this->fileWriter($this->translCopyFilePath, json_encode($newQuestionArr));

        $this->setTrueQuestion();

        return true;
    }

    //для рандомного перемешивания массива
    public function startGame(): Game
    {
        $actualGamePath = TEMPL_PREFIX . "/translTraining" . $this->changeGameModule() . ".txt";
        $translArray = $this->arrRandomizer($this->fileReader($actualGamePath));
        $this->fileWriter($this->translCopyFilePath, json_encode($translArray));
        
        $this->unsetOptionsDone();

        $this->setTrueResponse();
        $this->setTrueQuestion();

        return $this;
    }

    public function arrRandomizer(array $array): array
    {
        shuffle($array);
        return $array;
    }

    public function areOptionsDone(): bool
    {
        $settings_array = $this->fileReader($this->settingsPath);
        if (isset($settings_array['areOptionsDone'])) {
            if ($settings_array['areOptionsDone'] === 'nope') {
                return false;
            }
            return true;
        }
        return false;
    }

    public function getTrueOption()
    {
        if ($this->areOptionsDone()) {
            $settings_array = $this->fileReader($this->settingsPath);
            return $this->formatText($settings_array['areOptionsDone']);
        }
        return false;
    }
    
    public function setOptionsDone(int $true_option)
    {
        $settings_array = $this->fileReader($this->settingsPath);
        $settings_array['areOptionsDone'] = $true_option;
        $this->fileWriter($this->settingsPath, json_encode($settings_array));
    }
    
    public function unsetOptionsDone()
    {
        $settings_array = $this->fileReader($this->settingsPath);
        $settings_array['areOptionsDone'] = 'nope';
        $this->fileWriter($this->settingsPath, json_encode($settings_array));
    }

    public function getDictionaryMessage(): string
    {
        $defHintText = "К сожалению, для данного слова пока нет определения.";
        if (array_key_exists("hint", $this->respArrNow()[0])) {
                $defHint = $this->respArrNow()[0]["hint"];
            if ($defHint != "") {
                $defHintText = "Определение слова <b>«" . $this->getTrueQuestion() . "»</b> из словаря: \n\n" . $this->respArrNow()[0]["hint"];
            }
        }
        
        return $defHintText;
    }

    public function getActualGameModulePath()
    {
        if ($this->getGameModule() === -1) {
            $actualPath = TEMPL_PREFIX . "/translTraining" . $this->changeGameModule() . ".txt";
        } else {
            $actualPath = TEMPL_PREFIX . "/translTraining" . $this->getGameModule() . ".txt";
        }
        return $actualPath;
    }

    public function selectFourOptions(): array
    {
        $trueAnswer = $this->getTrueResponse();
        $trueAnswer = explode(',', $trueAnswer)[0];
        $trueAnswerLength = mb_strlen($trueAnswer);
        $actualResponses = $this->arrRandomizer($this->respArrNow());
        array_shift($actualResponses);
        shuffle($actualResponses);

        $responseKey = '';
        if ($this->getGameMode() == 'toRus') {
            $responseKey = 'translation';
        } else {
            $responseKey = 'word';
        }

        $optionsArray = [$trueAnswer];
        $usedWords = [$trueAnswer];

        while ($trueAnswerLength > 0) {
            foreach ($actualResponses as $value) {
                $sanitizeValue = $this->formatText($value[$responseKey]);
                $sanitizeValue = explode(',', $sanitizeValue)[0];
                $cutTrueResponse = mb_substr($trueAnswer, -$trueAnswerLength);

                if (preg_match("/$cutTrueResponse(.*?)/ui", $sanitizeValue) && !in_array($sanitizeValue, $usedWords)) {
                    array_push($optionsArray, $sanitizeValue);
                    array_push($usedWords, $sanitizeValue);
                    if (count($optionsArray) === 4) {
                        break 2;
                    }
                }
            }
            $trueAnswerLength = $trueAnswerLength - 1;
        }

        if (count($optionsArray) !== 4) {
            while (count($optionsArray) < 4) {
            	if (count($actualResponses) === 0) {
            		
            		array_push($optionsArray, '-');
            		continue;
            	}

                $sanitizeValue = $this->formatText($actualResponses[0][$responseKey]);
                $sanitizeValue = explode(',', $sanitizeValue)[0];

            	if (!in_array($sanitizeValue, $usedWords)) {
            		array_push($optionsArray, $sanitizeValue);
            	}
            	array_shift($actualResponses);
            }
        }

		shuffle($optionsArray);
        $this->setOptionsDone(array_search($trueAnswer, $optionsArray)+1);

        return $optionsArray;
    }

    public function respArrNow(): array
    {
        if ($this->fileReader($this->translCopyFilePath) == "[]") {
            $actualFileQw = TEMPL_PREFIX . "/translTraining" . $this->changeGameModule() . ".txt";
            $translArr = $this->arrRandomizer($this->fileReader($actualFileQw));
            $this->fileWriter($this->translCopyFilePath, json_encode($translArr));
            return $translArr;
        } else {
            return $this->fileReader($this->translCopyFilePath);
        }
    }

    public function fileWriter($file_path, $string)
    {
        file_put_contents($file_path, print_r($string, true), LOCK_EX);
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
            throw new Exception("It`s not the apropriated file structure in your file! Please, the file must contain a JSON array.");
        }
        return $fileArray;
    }
}