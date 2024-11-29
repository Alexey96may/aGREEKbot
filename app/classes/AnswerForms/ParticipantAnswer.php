<?php
/**
 * The class for the user Left Chat Participant Form
 * 
 * Processing and displaying the message of the left chat participant and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;
use App\Classes\UserAnswer;

require_once 'app/classes/UserAnswer.php';

class ParticipantAnswer extends UserAnswer
{
    /**
     * The participant ID.
     * 
     * @var int
     */
    protected $id;

    /**
     * The participant`s first name.
     * 
     * @var string
     */
    protected $firstName;

    /**
     * True, if the participant is a bot.
     * 
     * @var bool
     */
    protected $isBot;

    /**
     * The participant last name.
     * 
     * @var string
     */
    protected $lastName = '';

    /**
     * The participant`s name.
     * 
     * @var string
     */
    protected $userName = '';

    /**
     * The participant language is set as default on his device or set for Telegram app.
     * 
     * @var string
     */
    protected $languageCode = '';
    
    public function __construct(array $left_chat_participant)
    {
        $this->id = $left_chat_participant["id"];
        $this->firstName = $left_chat_participant["first_name"];
        $this->isBot = $left_chat_participant["is_bot"];

        if (isset($left_chat_participant["last_name"])) {
            $this->lastName = $left_chat_participant["last_name"];
        }
        if (isset($left_chat_participant["username"])) {
            $this->userName = $left_chat_participant["username"];
        }
        if (isset($left_chat_participant["language_code"])) {
            $this->languageCode = $left_chat_participant["language_code"];
        }
    }

    /**
     * Get the duration of the user`s voice.
     * 
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }
    
    /**
     * Get the duration of the user`s voice.
     * 
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    
    /**
     * Get true if the participant is a bot.
     * 
     * @return bool
     */
    public function getIsBot(): bool
    {
        return $this->isBot;
    }
    
    /**
     * Get the participant last name or ''.
     * 
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }
    
    /**
     * Get the participant user name or ''.
     * 
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }
    
    /**
     * Get the participant language code or ''.
     * 
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
}