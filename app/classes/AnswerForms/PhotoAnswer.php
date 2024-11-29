<?php
/**
 * The class for the user answer Photo Form
 * 
 * Processing and displaying the user`s photo message and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;

require_once 'app/classes/AnswerForms/FilesAnswer.php';

class PhotoAnswer extends FilesAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = "photo";

    /**
     * Photo width
     * 
     * @var int
     */
    protected $width;

    /**
     * Photo height
     * 
     * @var int
     */
    protected $height;

    public function __construct(array $photo)
    {
        parent::__construct($photo);

        $this->width = $photo["width"];
        $this->height = $photo["height"];
    }
    
    /**
     * Get photo width
     * 
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get photo height
     * 
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }
}