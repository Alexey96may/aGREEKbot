<?php
/**
 * The class for the user answer Sticker Form
 * 
 * Processing and displaying the user`s sticker message and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;

require_once 'app/classes/AnswerForms/FilesAnswer.php';

class StickerAnswer extends FilesAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = "sticker";

    /**
     * Type of the sticker, currently one of “regular”, “mask”, “custom_emoji”.
     * 
     * @var string
     */
    protected $stickerType;
            
    /**
     * True, if the sticker is animated
     * 
     * @var bool
     */
    protected $isAnimated;

    /**
     * True, if the sticker is a video sticker
     * 
     * @var bool
     */
    protected $isVideo;
    
    /**
     * Sticker width
     * 
     * @var int
     */
    protected $width;

    /**
     * Sticker height
     * 
     * @var int
     */
    protected $height;
        
    /**
     * Emoji associated with the sticker. Optional!
     * 
     * @var string
     */
    protected $emoji = ':)';
        
    /**
     * Name of the sticker set to which the sticker belongs. Optional!
     * 
     * @var string
     */
    protected $setName = '';
    
    public function __construct(array $sticker)
    {
        parent::__construct($sticker);

        $this->stickerType = $sticker["type"];
        $this->isAnimated = $sticker["is_animated"];
        $this->isVideo = $sticker["is_video"];
        $this->width = $sticker["width"];
        $this->height = $sticker["height"];

        if (isset($sticker["emoji"])) {
            $this->emoji = $sticker["emoji"];
        }
        if (isset($sticker["set_name"])) {
            $this->setName = $sticker["set_name"];
        }
    }

    /**
     * Get the type of the sticker, currently one of “regular”, “mask”, “custom_emoji”.
     * 
     * @return string
     */
    public function getStickerType(): string
    {
        return $this->stickerType;
    }
    
    /**
     * Get the emoji associated with the sticker
     * 
     * @return string
     */
    public function getEmoji(): string
    {
        return $this->emoji;
    }
    
    /**
     * Get the name of the sticker set to which the sticker belongs
     * 
     * @return string
     */
    public function getSetName(): string
    {
        return $this->setName;
    }
    
    /**
     * Get true, if the sticker is animated
     * 
     * @return bool
     */
    public function getIsAnimated(): bool
    {
        return $this->isAnimated;
    }

    /**
     * Get true, if the sticker is a video
     * 
     * @return bool
     */
    public function getIsVideo(): bool
    {
        return $this->isVideo;
    }

    /**
     * Get sticker width
     * 
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get sticker height
     * 
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }
}