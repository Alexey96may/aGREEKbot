<?php
/**
 * The class for the throwing exceptions in case of empty file
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\Core\Errors;

use Exception;

class EmptyFileExeption extends Exception
{
    /**
     * The message with the problem
     * 
     * @var string
     */
    protected $message;

    public function __construct(string $message = "")
    {
        $this->message = $message;
    }
}