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
require_once 'app/traits/TextFormatter.php';

use Exception;
use App\Classes\Core\Logs\Log;

class Game
{
    use \App\Traits\TextFormatter;

    /**
     * The curent game mod (May be "toRus" or "toGreek")
     * 
     * @var string
     */
    private $gameMode = 'toRus';

    public function __construct(string $settingsPath)
    {
        
        $log = Log::setPathByClass(__CLASS__);
        $settings_array = $this->fileReader($settingsPath);
        
        $log->log(json_encode($settings_array['gameMod']) . json_encode($settings_array));
        if (!isset($settings_array['gameMod'])) {
            $settings_array['gameMod'] = $this->gameMode;
            file_put_contents($settingsPath, print_r(json_encode($settings_array), true), LOCK_EX);
        } else {
            $this->gameMode = $this->fileReader($settingsPath)['gameMod'];
        }
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
     * @param string $settingsPath
     * @return true
     */
    public function changeGameMode(string $settingsPath): bool
    {
        $chatSettingsArr = $this->fileReader($settingsPath);
        $actualGameMod =  $this->getGameMode();

        if ($actualGameMod == 'toRus') {
            $chatSettingsArr['gameMod'] = 'toGreek';
            $this->gameMode = 'toGreek';
        } else {
            $chatSettingsArr['gameMod'] = 'toRus';
            $this->gameMode = 'toRus';
        }
        
        file_put_contents($settingsPath, print_r(json_encode($chatSettingsArr), true), LOCK_EX);
        return true;
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