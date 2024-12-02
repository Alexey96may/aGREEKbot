<?php
/**
 * The class for the user answer Text Form
 * 
 * Processing and displaying the user`s text message and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;
use App\Classes\UserAnswer;

require_once 'app/Classes/UserAnswer.php';

class UndefinedAnswer extends UserAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = 'undefined';
}