<?php
/**
 * The class for sending the response to the Telegram API
 * 
 * Send the response to the Telegram API
 *
 * @category aGreek Telegram Bot
 * @version 1.1
 * @author Alex Shulga
 * @author Alex Shulga <shulga_alexey@vk.com>
 * @copyright Copyright (c) 2024, Alex Shulga
 */

namespace App\Classes;

class MessageSender
{
    /**
     * The bot token for the access to send messages to the Telegram API.
     * 
     * @var string
     */
    private static $botToken;

    /**
     * The URL of the Telegram API.
     * 
     * @var string
     */
    private const BOT_URL = 'https://api.telegram.org/bot';

    /**
     * The method to send messages to the Telegram API.
     * 
     * @var string
     */
    private $method = 'sendMessage';

    /**
     * The query to be sent to the Telegram API.
     * 
     * @var array
     */
    private $queryArray = ['chat_id' => '', 'text' => 'Тестовое сообщение', 'parse_mode' => "HTML"];
    
    public function __construct(int $room_id, string $response_text)
    {
        if (empty(self::$botToken)) {
            throw new \Exception("You must set the bot token for the message sending!");
        }

        $this->queryArray['chat_id'] = $room_id;
        $this->queryArray['text'] = $response_text;
    }

    /**
     * Get the query array
     * 
     * @return array
     */
    public function getQueryArray(): array
    {
        return $this->queryArray;
    }

    /**
     * Set the query array
     * 
     * @param array $queryArray
     * @return MessageSender
     */
    public function setQueryArray(array $queryArray): MessageSender
    {
        $this->$queryArray = $queryArray;
        return $this;
    }
    
    /**
     * Get the query method
     * 
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set the query method
     * 
     * @param string $method
     * @return MessageSender
     */
    public function setMethod(string $method): MessageSender
    {
        $this->$method = $method;
        return $this;
    }

    /**
     * Get the bot token
     * 
     * @param string $botToken
     */
    public static function getBotToken(): string
    {
        return self::$botToken;
    }

    /**
     * Set the bot token
     * 
     * @param string $botToken
     */
    public static function setBotToken(string $botToken)
    {
        self::$botToken = $botToken;
    }

    /**
     * Send the message to the Telegram API with curl
     * 
     */
    public function sendMessage()
    {
        $ch = curl_init(self::BOT_URL . self::getBotToken() . "/" . $this->getMethod() . "?" . http_build_query($this->getQueryArray()));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resp = curl_exec($ch);
        curl_close($ch);

        return $resp;
    }
}