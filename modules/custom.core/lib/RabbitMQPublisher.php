<?php

namespace Custom\Core;

use Bitrix\Main\Config\Option;
use \Custom\Core\SimpleLogger as SimpleLogger;
use Exception;
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
}
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    private static ?AMQPStreamConnection $connection = null;
    private static ?AMQPChannel $channel = null;
    private static string $queueName = 'order_mail_queue';

    /**
     * @return void
     * @throws Exception
     */
    public static function init(): void
    {
        if (self::$connection === null) {
            try {
                self::$connection = new AMQPStreamConnection(
                    Option::get('custom.core', 'RABBITMQ_SERVER'),
                    Option::get('custom.core', 'RABBITMQ_PORT'),
                    Option::get('custom.core', 'RABBITMQ_LOGIN'),
                    Option::get('custom.core', 'RABBITMQ_PASS'),
                    Option::get('custom.core', 'RABBITMQ_VHOST')
                );
                self::$channel = self::$connection->channel();
                self::$channel->queue_declare(self::$queueName, false, true, false, false);

                SimpleLogger::log('RabbitMQ connection established', 'I', 'rabbit', 'publisher');
            } catch (Exception $e) {
                SimpleLogger::log('RabbitMQ connection failed: ' . $e->getMessage(), 'E', 'rabbit', 'publisher');
                throw $e;
            }
        }
    }

    /**
     * Формирование тела письма из шаблона SALE_ORDER_PAID
     *
     * @param array $arFields
     * @return array|null
     */
    private static function buildMailFromTemplate(array $arFields): ?array
    {
        $template = \CEventMessage::GetList(
            'id',
            'asc',
            [
                'TYPE_ID' => 'SALE_ORDER_PAID',
                'ACTIVE' => 'Y'
            ]
        )->Fetch();

        if (!$template) {
            SimpleLogger::log('SALE_ORDER_PAID template not found', 'E', 'rabbit', 'publisher');
            return null;
        }

        $subject = $template['SUBJECT'];
        $body = $template['MESSAGE'];

        // подставляем поля
        foreach ($arFields as $key => $value) {
            $value = is_array($value) ? implode(', ', $value) : (string)$value;
            $body = str_replace('#' . $key . '#', $value, $body);
            $subject = str_replace('#' . $key . '#', $value, $subject);
        }

        return [
            'SUBJECT' => $subject,
            'BODY' => $body,
            'MAIL_TO' => $arFields['EMAIL'] ?? null
        ];
    }

    /**
     * Публикация сообщения
     *
     * @param array $arFields
     * @return void
     * @throws Exception
     */
    public static function publish(array $arFields): void
    {
        SimpleLogger::log("Received data for sending: Email:{$arFields['EMAIL']} Order: {$arFields['ORDER_REAL_ID']}", 'I', 'rabbit', 'publisher');

        self::init();

        $mail = self::buildMailFromTemplate($arFields);
        if (!$mail) {
            SimpleLogger::log("Failed to build email from template: Email:{$arFields['EMAIL']} Order: {$arFields['ORDER_REAL_ID']}", 'E', 'rabbit', 'publisher');
            return;
        }

        try {
            $msg = new AMQPMessage(json_encode($mail, JSON_UNESCAPED_UNICODE), ['delivery_mode' => 2]);
            self::$channel->basic_publish($msg, '', self::$queueName);

            SimpleLogger::log("Data successfully sent to RabbitMQ: Email:{$arFields['EMAIL']} Order: {$arFields['ORDER_REAL_ID']}", 'I', 'rabbit', 'publisher');
        } catch (Exception $e) {
            SimpleLogger::log("Failed to publish message. Email:{$arFields['EMAIL']} Order: {$arFields['ORDER_REAL_ID']}. Exception: " . $e->getMessage(), 'E', 'rabbit', 'publisher');
            throw $e;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function close(): void
    {
        try {
            self::$channel?->close();
            self::$connection?->close();
            SimpleLogger::log('RabbitMQ connection closed', 'I', 'rabbit', 'publisher');
        } catch (Exception $e) {
            SimpleLogger::log('Error while closing RabbitMQ connection: ' . $e->getMessage(), 'E', 'rabbit', 'publisher');
            throw $e;
        }
    }
}
