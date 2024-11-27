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
    
    /**
     * The file number (its name prefix) with the Questions
     * 
     * @var array
     */
    private const QUESTION_MODULES = array(1, 2, 3, 4);

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
     * Get the Game Module from the Setting file.
     * 
     * @param string $settings_path
     * @return int
     */
    public function getGameModule(string $settings_path): int
    {
        $settingsArr = $this->fileReader($settings_path);
        if (array_key_exists('fileGame', $settingsArr)) {
            return (int) $settingsArr['fileGame'];
        }
        return -1;
    }

    /**
     * Change the Game Module randomly and set it in the Setting file.
     * 
     * @param string $settings_path
     * @return int
     */
    public function changeGameModule(string $settings_path): int
    {
        $chatSettingArr = readTTFile($settings_path);
        $chatSettingArr['fileGame'] = array_rand(self::QUESTION_MODULES) + 1;
        
        file_put_contents($settings_path, print_r(json_encode($chatSettingArr), true), LOCK_EX);
        return (int) $chatSettingArr['fileGame'];
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