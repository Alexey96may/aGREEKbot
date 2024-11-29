<?php
/**
 * The class for the user answers
 * 
 * Processing and displaying the user answer and optimize it for the simple work with it
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes;
require_once 'app/traits/TextFormatter.php';

class UserAnswer
{
    use \App\Traits\TextFormatter;

    /**
     * Original user`s answer
     * 
     * @var string
     */
    protected $userAnswer = '';

    /**
     * The number of points to add for the user`s true answer
     * 
     * @var int
     */
    protected $answerPrice = 1;

    /**
     * The type of the user`s answer: text, voice, video, sticker, etc.
     * 
     * @var int
     */
    protected $answerType = "undefined";
    
    public function __construct()
    {
        $this->userAnswer = '';
    }

    /**
     * Get the type of the user`s answer
     * 
     * @return string
     */
    public function getAnswerType(): string
    {
        return $this->answerType;
    }

    /**
     * Get the origin user answer
     * 
     * @return string
     */
    public function getUserAnswer(): string
    {
        return $this->userAnswer;
    }

    /**
     * Get the user answer formated for the comparasment with the another formated string
     * 
     * @return string
     */
    public function getFormatUserAnswer(): string
    {
        $formatUserAnswer = $this->formatText($this->userAnswer);
        return $formatUserAnswer;
    }

    /**
     * Get the answer price for the user`s true answer
     * 
     * @return int
     */
    public function getAnswerPrice(): int
    {
        return $this->answerPrice;
    }

    /**
     * Set the answer price for the user`s true answer
     * 
     * @param int $scoreToAdd
     * @return UserAnswer
     */
    public function setAnswerPrice(int $scoreToAdd): UserAnswer
    {
        $this->answerPrice = $scoreToAdd;
        return $this;
    }

    /**
     * Check whether the user answer (or answers) is correct according to the $trueAnswers parameter
     * 
     * @param string $trueAnswers
     * @return bool
     */
    public function isCorrectAnswer(string $trueAnswers): bool
    {
        $trueAnswerArr = explode(",", $trueAnswers);
        $userAnswerArr = explode(",", $this->getUserAnswer());
        $isTrueAnswer = false;

        for ($i=0; $i < count($trueAnswerArr); $i++) { 
            $trueAnswerArr[$i] = $this->formatText($trueAnswerArr[$i]);
        }
        for ($u=0; $u < count($userAnswerArr); $u++) { 
            $userAnswerArr[$u] = $this->formatText($userAnswerArr[$u]);
            if (in_array($userAnswerArr[$u], $trueAnswerArr)) {
                $isTrueAnswer = true;
            }
        }
        return $isTrueAnswer;
    }

    /**
     * Evaluate the user answer from 0 to 1
     * 
     * Only one user answer will be evaluated!
     * 
     * @param string $trueAnswers
     * @return float
     */
    private function answerRater(string $trueAnswers): float
    {
        $trueAnswerArr = explode(",", $trueAnswers);
        $userAnswer = $this->getFormatUserAnswer();
    
        $sameLetterCount = 0;
        $rating = null;
        $akinnestStrings = [$userAnswer, ""];
        $maxStringLength = null;
    
        for ($u=0; $u < count($trueAnswerArr); $u++) {
            $maxCurrentStringLength = max(strlen($trueAnswerArr[$u]), strlen($userAnswer));
            $trueAnswerArr[$u] = mb_strtolower(trim($trueAnswerArr[$u]));
            $currentStringsArr = [$userAnswer, $trueAnswerArr[$u]];
            $currentLetterCount = 0;
            $currentUserAnswer = $userAnswer;
    
            for ($i=0; $i < $maxCurrentStringLength; $i++) { 
                if ($trueAnswerArr[$u][$i] === $currentUserAnswer[$i]) {
                    $currentLetterCount++;
                } else {
                    if (strlen($trueAnswerArr[$u]) > strlen($userAnswer)) {
                        $currentUserAnswer = mb_substr($currentUserAnswer, 0, $i) . "*" . mb_substr($currentUserAnswer, $i);
                    } elseif(strlen($trueAnswerArr[$u]) < strlen($currentUserAnswer)) {
                        $trueAnswerArr[$u] = mb_substr($trueAnswerArr[$u], 0, $i) . "*" . mb_substr($trueAnswerArr[$u], $i);
                    }
                }
            }
            if ($sameLetterCount <= $currentLetterCount) {
                $sameLetterCount = $currentLetterCount;
                $akinnestStrings = $currentStringsArr;
            }
        }
    
        $maxStringLength = max(strlen($akinnestStrings[0]), strlen($akinnestStrings[1]));
        $rating = $sameLetterCount / $maxStringLength;
        $rating = floatval($rating);
        return $rating;
    }
    
    /**
     * Get a message how precise is user`s answer
     * 
     * @param string $userFirstName
     * @param string $trueAnswers
     * @return string
     */
    public function wrongAnswerMessage(string $userFirstName, string $trueAnswers): string
    {
        $answerRating = $this->answerRater($trueAnswers);
        $message = '';
    
        if ($answerRating > 0.75) {
            $message = "Очень близко, $userFirstName! Возможно, в вашем ответе опечатка.";
        } else if($answerRating > 0.5) {
            $message = "Неправильно, но близко! Попытайтесь снова, $userFirstName!";
        } else {
            $message = "Попытайтесь снова, $userFirstName!";
        }
    
        return $message;
    }

    /**
     * Get a restriction message about the verb translation only as infinitives
     * 
     * @param string $true_answers
     * @return string
     */
    public function verbRestrictionMessage(string $true_answers): string
    {
        $userAnswerEnding = ['ю', 'у', 'сь'];
        $russianVerbEnding = ['ть', 'ся', 'ти'];

            $userAnswerLastLetter = mb_substr($this->getFormatUserAnswer(), -1);
            $userAnswerLastLetters = mb_substr($this->getFormatUserAnswer(), -2);
            $trueAnswerLastLetters = mb_substr($this->formatText($true_answers), -2);
            
            if (in_array($trueAnswerLastLetters, $russianVerbEnding)) {
                if (in_array($userAnswerLastLetter, $userAnswerEnding) || in_array($userAnswerLastLetters, $userAnswerEnding)) {
                    return 'Пожалуйста, переводите глаголы инфинитивами...';
                }
            }
        return '';
    }
    
    /**
     * Get a restriction message if there may be only one user answer.
     * 
     * @return string
     */
    public function decideOnAnswerMessage(): string
    {
        if (mb_strpos($this->getFormatUserAnswer(), ',') !== false) {
            return 'Определитесь с ответом, пожалуйста.';
        }
        return '';
    }
}