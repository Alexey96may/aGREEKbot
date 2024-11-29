<?php
/**
 * The class for the user answer Voice Form
 * 
 * Processing and displaying the user`s voice message and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;

require_once 'app/classes/AnswerForms/FilesAnswer.php';

class VoiceAnswer extends FilesAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = "voice";

    /**
     * The duration of the user`s voice.
     * 
     * @var int
     */
    protected $duration;
    
    /**
     * The mime type of the user`s voice: It`s probably "audio/ogg" for a voice. Optional!
     * 
     * @var string
     */
    protected $mimeType = '';
    
    public function __construct(array $audio)
    {
        parent::__construct($audio);

        $this->duration = $audio["duration"];

        if (isset($audio["mime_type"])) {
            $this->mimeType = $audio["mime_type"];
        }
    }

    /**
     * Get the duration of the user`s voice.
     * 
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Get the mime type of the user`s voice.
     * 
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}