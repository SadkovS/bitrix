<?php

namespace Custom\Core;

class TelegramBot {

    public const HOST = 'https://api.telegram.org/bot';
    private const TOKEN = '*************************';
    private const CHAT_ID = -4729906112;

    public static function sendMessage( string $text): bool
    {
        $date = (new \DateTime())->format('d.m.Y H:i:s');
        $response = Helper::curlRequest(
            self::HOST . self::TOKEN . '/sendMessage', [], 'GET',
            [
                'chat_id' => self::CHAT_ID,
                'text'    => $date . ': ' . $text
            ]
        );
        return $response['ok'] ?? false;
    }

}
