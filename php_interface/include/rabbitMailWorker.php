<?php
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../..');

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Bitrix\Main\Config\Option;
use Custom\Core\SimpleLogger;

// --- Константа по умолчанию для количества попыток отправки ---
const DEFAULT_MAX_MAIL_RETRY_ATTEMPTS = 5;

// --- Настройки SMTP ---
$smtpHost  = Option::get('custom.core', 'SMTP_SERVER');
$smtpPort  = (int) Option::get('custom.core', 'SMTP_PORT');
$smtpUser  = Option::get('custom.core', 'SMTP_LOGIN');
$smtpPass  = Option::get('custom.core', 'SMTP_PASS');
$fromEmail = Option::get('custom.core', 'SMTP_EMAIL');
$fromName  = Option::get('custom.core', 'SMTP_NAME');

// --- Настройки RabbitMQ ---
$rabbitHost   = Option::get('custom.core', 'RABBITMQ_SERVER');
$rabbitPort   = (int) Option::get('custom.core', 'RABBITMQ_PORT');
$rabbitUser   = Option::get('custom.core', 'RABBITMQ_LOGIN');
$rabbitPass   = Option::get('custom.core', 'RABBITMQ_PASS');
$rabbitVhost  = Option::get('custom.core', 'RABBITMQ_VHOST');
$rabbitQoS    = (int) Option::get('custom.core', 'RABBITMQ_QOS');
$queueName    = 'order_mail_queue';

// --- Количество попыток отправки письма ---
$maxRetryAttempts = (int) Option::get('custom.core', 'MAX_MAIL_RETRY_ATTEMPTS');
if ($maxRetryAttempts <= 0) {
    $maxRetryAttempts = DEFAULT_MAX_MAIL_RETRY_ATTEMPTS;
}

SimpleLogger::log('Worker starting...', 'I', 'rabbit', 'worker');

// --- Соединение с RabbitMQ ---
try {
    $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass, $rabbitVhost);
    $channel = $connection->channel();
    $channel->queue_declare($queueName, false, true, false, false);
} catch (\Exception $e) {
    SimpleLogger::log('RabbitMQ connection failed: ' . $e->getMessage(), 'E', 'rabbit', 'worker');
    die('RabbitMQ connection failed: ' . $e->getMessage() . '\n');
}

// --- Инициализация PHPMailer ---
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = ($smtpPort === 465) ? 'ssl' : 'tls';
    $mail->Port       = $smtpPort;
    $mail->setFrom($fromEmail, $fromName);
    $mail->SMTPKeepAlive = true;
    $mail->smtpConnect();

    SimpleLogger::log('SMTP connection established', 'I', 'rabbit', 'worker');

} catch (Exception $e) {
    SimpleLogger::log('SMTP connection failed: '.$e->getMessage(), 'E', 'rabbit', 'worker');

    die('SMTP init failed: '.$e->getMessage().'\n');
}

// --- Хранилище количества попыток и отложенные письма ---
$retryCounter = [];
$pendingMessages = [];

// --- Callback для обработки сообщений ---
$callback = function (AMQPMessage $msg) use ($mail, &$retryCounter, &$pendingMessages, $maxRetryAttempts) {
    $data = json_decode($msg->body, true);
    $emailTo = $data['MAIL_TO'] ?? null;

    if (!$emailTo) {
        SimpleLogger::log('Invalid message skipped: '.$msg->body, 'E', 'rabbit', 'worker');
        $msg->ack();
        return;
    }

    try {
        $mail->clearAddresses();
        $mail->clearAttachments();

        $mail->addAddress($emailTo);
        $mail->addBcc('tickets@voroh.ru', 'tickets@voroh.ru');
        $mail->Subject = $data['SUBJECT'] ?? 'Без темы';
        $mail->isHTML(true);
        $mail->Body    = $data['BODY'] ?? '';
        $mail->AltBody = strip_tags($data['BODY'] ?? '');

        $mail->send();

        // --- worker__info ---
        $orderId   = $data['ORDER_ID'] ?? '-';
        $smtpReply = $mail->getSMTPInstance()->getLastReply();
        $smtpCode  = $smtpReply ? substr(trim($smtpReply), 0, 3) : '-';
        $smtpMsgId = $mail->getLastMessageID() ?: '-';

        SimpleLogger::log(
            "worker__info | Order: {$orderId}, To: {$emailTo}, SMTP code: {$smtpCode}, Session ID: {$smtpMsgId}",
            'I', 'rabbit', 'worker'
        );

        unset($retryCounter[$emailTo]);
        $msg->ack();

    } catch (Exception $e) {
        // --- worker__error ---
        $orderId   = $data['ORDER_ID'] ?? '-';
        $smtpReply = $mail->getSMTPInstance()->getLastReply();
        $smtpCode  = $smtpReply ? substr(trim($smtpReply), 0, 3) : '-';
        $smtpMsgId = $mail->getLastMessageID() ?: '-';

        SimpleLogger::log(
            "worker__error | Order: {$orderId}, To: {$emailTo}, Error code: {$smtpCode}, Session ID: {$smtpMsgId}, Message: {$e->getMessage()}",
            'E', 'rabbit', 'worker'
        );

        // если проблема именно в SMTP соединении — сохраняем письмо для пересылки
        if (stripos($e->getMessage(), 'SMTP connect') !== false ||
            stripos($e->getMessage(), 'SMTP Error') !== false) {

            SimpleLogger::log('SMTP connection issue. Saving message for resend later: ' . $emailTo, 'E', 'rabbit', 'worker');
            $pendingMessages[] = $data;
            $msg->ack();
            return;
        }

        // обычная логика ретраев
        $retryCounter[$emailTo] = ($retryCounter[$emailTo] ?? 0) + 1;
        SimpleLogger::log('Mailer Error for ' . $emailTo . ' (attempt ' . $retryCounter[$emailTo] . '): ' . $e->getMessage(), 'E', 'rabbit', 'worker');

        if ($retryCounter[$emailTo] >= $maxRetryAttempts) {
            SimpleLogger::log('Max attempts reached for ' . $emailTo . ' Message dropped.', 'E', 'rabbit', 'worker');
            $msg->ack();
        } else {
            $msg->nack(true);
        }
    }
};

// --- QoS ---
$channel->basic_qos(null, $rabbitQoS, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

SimpleLogger::log('Worker started, waiting for messages...', 'I', 'rabbit', 'worker');

// --- Основной цикл ---
while ($channel->is_consuming()) {
    try {
        $channel->consume();
    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
        // проверяем SMTP коннект
        try {
            if (!$mail->getSMTPInstance()->noop()) {
                SimpleLogger::log('SMTP connection lost. Reconnecting...', 'E', 'rabbit', 'worker');
                $mail->smtpClose();
                $mail->smtpConnect();
                SimpleLogger::log('SMTP reconnected', 'I', 'rabbit', 'worker');

                // пересылаем отложенные письма
                foreach ($pendingMessages as $k => $data) {
                    try {
                        $mail->clearAddresses();
                        $mail->clearAttachments();

                        $mail->addAddress($data['MAIL_TO']);
                        $mail->addBcc('tickets@voroh.ru', 'tickets@voroh.ru');
                        $mail->Subject = $data['SUBJECT'] ?? 'Без темы';
                        $mail->isHTML(true);
                        $mail->Body    = $data['BODY'] ?? '';
                        $mail->AltBody = strip_tags($data['BODY'] ?? '');

                        $mail->send();

                        $orderId   = $data['ORDER_ID'] ?? '-';
                        $smtpReply = $mail->getSMTPInstance()->getLastReply();
                        $smtpCode  = $smtpReply ? substr(trim($smtpReply), 0, 3) : '-';
                        $smtpMsgId = $mail->getLastMessageID() ?: '-';

                        SimpleLogger::log(
                            "worker__info | Pending resend | Order: {$orderId}, To: {$data['MAIL_TO']}, SMTP code: {$smtpCode}, Session ID: {$smtpMsgId}",
                            'I', 'rabbit', 'worker'
                        );

                        unset($pendingMessages[$k]);
                    } catch (Exception $ex) {
                        $orderId   = $data['ORDER_ID'] ?? '-';
                        $smtpReply = $mail->getSMTPInstance()->getLastReply();
                        $smtpCode  = $smtpReply ? substr(trim($smtpReply), 0, 3) : '-';
                        $smtpMsgId = $mail->getLastMessageID() ?: '-';

                        SimpleLogger::log(
                            "worker__error | Pending resend | Order: {$orderId}, To: {$data['MAIL_TO']}, Error code: {$smtpCode}, Session ID: {$smtpMsgId}, Message: {$ex->getMessage()}",
                            'E', 'rabbit', 'worker'
                        );
                    }
                }
            }
        } catch (Exception $smtpEx) {
            SimpleLogger::log('SMTP reconnect failed: ' . $smtpEx->getMessage(), 'E', 'rabbit', 'worker');
        }

        usleep(100000);
        continue;
    } catch (\Exception $e) {
        SimpleLogger::log('Worker connection error: ' . $e->getMessage(), 'E', 'rabbit', 'worker');
        sleep(2);
    }
}
