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

require_once 'app/classes/AnswerForms/ParticipantAnswer.php';

class LeftParticipantAnswer extends ParticipantAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = "left_chat_participant";
}