<?php
/**
 * The class for the user New Chat Participant Form
 * 
 * Processing and displaying the message of the new chat participant and optimize it for the simple work with it.
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes\AnswerForms;

require_once 'app/Classes/AnswerForms/ParticipantAnswer.php';

class NewParticipantAnswer extends ParticipantAnswer
{
    /**
     * The type of the user`s answer.
     * 
     * @var string
     */
    protected $answerType = "new_chat_participant";
}