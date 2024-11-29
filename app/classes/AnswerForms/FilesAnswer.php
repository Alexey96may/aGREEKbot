<?php
/**
 * The class for the user`s answer Files Form
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
use App\Classes\UserAnswer;

require_once 'app/classes/UserAnswer.php';

class FilesAnswer extends UserAnswer
{
    /**
     * Identifier for this file, which can be used to download or reuse the file.
     * 
     * @var string
     */
    protected $fileID;

    /**
     * Unique identifier for this file, which is supposed to be the same over time and for different bots. Can't be used to download or reuse the file.
     * 
     * @var string
     */
    protected $fileUniqueID;

    /**
     * The file size of the user`s sticker. Optional!
     * 
     * @var int
     */
    protected $fileSize = 0;
    
    public function __construct(array $sticker)
    {
        $this->fileID = $sticker["file_id"];
        $this->fileUniqueID = $sticker["file_unique_id"];

        if (isset($sticker["file_size"])) {
            $this->fileSize = $sticker["file_size"];
        }
    }

    /**
     * Get the identifier for this file, which can be used to download or reuse the file.
     * 
     * @return string
     */
    public function getFileID(): string
    {
        return $this->fileID;
    }

    /**
     * Get the Unique identifier for this file, which is supposed to be the same over time and for different bots. Can't be used to download or reuse the file.
     * 
     * @return string
     */
    public function getFileUniqueID(): string
    {
        return $this->fileUniqueID;
    }

    /**
     * Get the file size of the user`s sticker
     * 
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }
}