<?php

namespace Custom\Core\Services;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;
use RuntimeException;

/**
 * Сервис проверки расхождений между платежами CloudPayments и заказами Bitrix
 */
class OrdersPaymentCheckService
{
    private const CLOUDPAYMENTS_API_ENDPOINT = 'https://api.cloudpayments.ru/payments/list';
    protected array $lastDiscrepancies = [];
    protected array $lastPayments = [];
    protected array $lastOrders = [];

    /**
     * Запуск проверки за указанную дату
     *
     * @param string|null $date
     * @return void
     * @throws ArgumentException
     * @throws LoaderException
     */
    public function run(string $date = null): void
    {
        if (!$date) {
            $dateObj = new \DateTime('yesterday');
            $date = $dateObj->format('Y-m-d');
        }

        $dateFrom = $date;
        $dateTo   = $date;

        Loader::includeModule('main');

        $this->lastPayments = $this->fetchCloudPayments($dateFrom, $dateTo);
        $this->lastOrders   = $this->fetchBitrixOrders($dateFrom, $dateTo);

        $this->lastDiscrepancies = $this->compare($this->lastPayments, $this->lastOrders);

        if ($this->lastDiscrepancies) {
            $this->notify($this->lastDiscrepancies);
        }
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    protected function fetchCloudPayments($dateFrom, $dateTo): array
    {
        $publicId  = Option::get('custom.core','CLOUDPAYMENTS_PUBLIC_ID','');
        $apiSecret = Option::get('custom.core','CLOUDPAYMENTS_API_SECRET','');

        if (!$publicId || !$apiSecret) {
            throw new RuntimeException('CloudPayments credentials not set.');
        }

        $dateFromObj = \DateTime::createFromFormat('Y-m-d', $dateFrom) ?: \DateTime::createFromFormat('d.m.Y', $dateFrom);
        $dateToObj   = \DateTime::createFromFormat('Y-m-d', $dateTo)   ?: \DateTime::createFromFormat('d.m.Y', $dateTo);

        $payload = [
            'Date'   => $dateFromObj->format('Y-m-d'),
            'DateTo' => $dateToObj->format('Y-m-d'),
        ];

        $response = $this->httpJson(
            'POST',
            self::CLOUDPAYMENTS_API_ENDPOINT,
            ['Content-Type: application/json'],
            $payload,
            $publicId.':'.$apiSecret
        );

        $items = [];
        if (!empty($response['Model']) && is_array($response['Model'])) {
            foreach ($response['Model'] as $payment) {
                $orderNumber = (string)($payment['InvoiceId'] ?? $payment['Metadata']['order_id'] ?? $payment['AccountId'] ?? '');
                $items[] = [
                    'orderNumber' => $orderNumber,
                    'amount'      => (float)($payment['Amount'] ?? 0),
                    'currency'    => (string)($payment['Currency'] ?? ''),
                    'status'      => $this->normalizeGatewayStatus($payment['Status'] ?? ($payment['Reason'] ?? '')),
                    'raw'         => $payment,
                ];
            }
        }
        return $items;
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     */
    protected function fetchBitrixOrders($dateFrom, $dateTo): array
    {
        if (!Loader::includeModule('sale')) {
            throw new RuntimeException('Cannot include sale module.');
        }

        $result = [];
        $from = DateTime::createFromTimestamp(strtotime($dateFrom.' 00:00:00'));
        $to   = DateTime::createFromTimestamp(strtotime($dateTo.' 23:59:59'));

        $orderQuery = Order::getList([
            'filter' => ['>=DATE_INSERT' => $from, '<=DATE_INSERT' => $to],
            'select' => ['ID','ACCOUNT_NUMBER','PRICE','CURRENCY','PAYED','STATUS_ID']
        ]);

        while ($row = $orderQuery->fetch()) {
            $acc = (string)$row['ACCOUNT_NUMBER'];
            $result[$acc] = [
                'ID'             => (int)$row['ID'],
                'ACCOUNT_NUMBER' => $acc,
                'PRICE'          => (float)$row['PRICE'],
                'CURRENCY'       => (string)$row['CURRENCY'],
                'PAID'           => (string)$row['PAYED'],
                'STATUS_ID'      => (string)$row['STATUS_ID'],
            ];
        }

        return $result;
    }

    /**
     * @param array $txList
     * @param array $orders
     * @return array
     */
    protected function compare(array $txList, array $orders): array
    {
        $discrepancies = [];

        // Транзакции без заказов
        foreach ($txList as $t) {
            $orderNumber = $t['orderNumber'];
            $order = $orders[$orderNumber] ?? null;

            if (!$order) {
                $discrepancies[] = ['type'=>'MISSING_IN_BITRIX','orderNumber'=>$orderNumber,'tx'=>$t];
                continue;
            }

            $bitrixPaid = ($order['PAID'] === 'Y');

            if ($t['status']['paid'] && !$bitrixPaid || !$t['status']['paid'] && $bitrixPaid) {
                $discrepancies[] = ['type'=>'STATUS_MISMATCH','orderNumber'=>$orderNumber,'bitrix'=>$order,'tx'=>$t];
            }

            if (abs((float)$order['PRICE'] - (float)$t['amount']) > 0.01) {
                $discrepancies[] = ['type'=>'AMOUNT_MISMATCH','orderNumber'=>$orderNumber,'bitrix'=>$order,'tx'=>$t];
            }
        }

        // Заказы без транзакций
        foreach ($orders as $orderNumber => $order) {
            $found = false;
            foreach ($txList as $t) {
                if ($t['orderNumber'] === $orderNumber) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $discrepancies[] = [
                    'type' => 'MISSING_IN_PAYMENTS',
                    'orderNumber' => $orderNumber,
                    'bitrix' => $order,
                    'tx' => null
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * @param array $discrepancies
     * @return void
     */
    protected function notify(array $discrepancies): void
    {
        $message = "Обнаружены расхождения между заказами и платежами:\n\n";

        foreach ($discrepancies as $d) {
            $typeMessage = match($d['type']) {
                'MISSING_IN_BITRIX' => 'Нет заказа в Битрикс',
                'MISSING_IN_PAYMENTS' => 'Нет платежа в CloudPayments',
                'STATUS_MISMATCH' => 'Несоответствие статуса оплаты',
                'AMOUNT_MISMATCH' => 'Несоответствие суммы',
                default => $d['type']
            };

            $message .= "- [{$typeMessage}] Заказ={$d['orderNumber']}\n";

            if (!empty($d['bitrix'])) {
                $b = $d['bitrix'];
                $statusText = $this->bitrixStatusText($b['STATUS_ID']);
                $message .= "  Bitrix: ID={$b['ID']} Сумма={$b['PRICE']} {$b['CURRENCY']} Оплачен=" . ($b['PAID'] === 'Y' ? 'Да' : 'Нет') . " Статус={$b['STATUS_ID']} ({$statusText})\n";
            }

            if (!empty($d['tx'])) {
                $t = $d['tx'];
                $message .= "  CloudPayments: Сумма={$t['amount']} {$t['currency']} Статус оригинал={$t['status']['raw']}\n";
            }

            $message .= "\n";
        }

        // Преобразуем переводы строк в HTML
        $htmlMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        // Email
        $emails = explode(',', Option::get('custom.core', 'CLOUDPAYMENTS_REPORT_EMAILS', ''));
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@voroh.ru";

        foreach ($emails as $email) {
            $email = trim($email);
            if ($email) {
                @mail($email, "Расхождения оплат", $htmlMessage, $headers);
            }
        }
    }

    /**
     * @param $method
     * @param $url
     * @param $headers
     * @param $body
     * @param $basicAuth
     * @return array
     */
    protected function httpJson($method, $url, $headers = [], $body = null, $basicAuth = null): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($basicAuth) curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $code < 200 || $code >= 300) throw new RuntimeException($err ?: "HTTP status {$code}: {$resp}");
        $json = json_decode($resp, true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException("Invalid JSON: {$resp}");

        return $json;
    }

    /**
     * @param $raw
     * @return array
     */
    protected function normalizeGatewayStatus($raw): array
    {
        $paid = $refunded = $failed = false;
        $s = mb_strtolower((string)$raw);
        if (str_contains($s, 'completed') || strpos($s, 'authorized') !== false) $paid = true;
        if (str_contains($s, 'refunded') || strpos($s, 'reversed') !== false || strpos($s, 'cancel') !== false) $refunded = true;
        if (strpos($s, 'declin') !== false || strpos($s, 'fail') !== false) $failed = true;

        return ['paid'=>$paid,'refunded'=>$refunded,'failed'=>$failed,'raw'=>$raw];
    }

    /**
     * @return void
     */
    public function printReport(): void
    {
        echo "<pre>";
        echo "=== Платежи CloudPayments: " . count($this->lastPayments) . " ===\n";
        $counter = 0;
        foreach ($this->lastPayments as $p) {
            $counter++;
            $paid = $p['status']['paid'] ? 'Да' : 'Нет';
            $ref  = $p['status']['refunded'] ? 'Да' : 'Нет';
            $fail = $p['status']['failed'] ? 'Да' : 'Нет';
            $ord  = $p['orderNumber'] ?: '(нет заказа)';
            printf("[%03d] Заказ=%s Сумма=%.2f %s Оплачен=%s Возврат=%s Ошибка=%s\n", $counter, $ord, $p['amount'], $p['currency'], $paid, $ref, $fail);
        }

        echo "\n=== Заказы Bitrix: " . count($this->lastOrders) . " ===\n";
        $counter = 0;
        foreach ($this->lastOrders as $o) {
            $counter++;
            $statusText = $this->bitrixStatusText($o['STATUS_ID']);
            printf("[%03d] Заказ=%s ID=%d Сумма=%.2f %s Оплачен=%s Статус=%s (%s)\n", $counter, $o['ACCOUNT_NUMBER'], $o['ID'], $o['PRICE'], $o['CURRENCY'], $o['PAID'] === 'Y' ? 'Да' : 'Нет', $o['STATUS_ID'], $statusText);
        }

        echo "\n=== Расхождения: " . count($this->lastDiscrepancies) . " ===\n";
        foreach ($this->lastDiscrepancies as $d) {
            $typeMessage = match($d['type']) {
                'MISSING_IN_BITRIX' => 'Нет заказа в Битрикс',
                'MISSING_IN_PAYMENTS' => 'Нет платежа в CloudPayments',
                'STATUS_MISMATCH' => 'Несоответствие статуса оплаты',
                'AMOUNT_MISMATCH' => 'Несоответствие суммы',
                default => $d['type']
            };
            echo "- [{$typeMessage}] Заказ={$d['orderNumber']}\n";
            if (!empty($d['bitrix'])) {
                $b = $d['bitrix'];
                $statusText = $this->bitrixStatusText($b['STATUS_ID']);
                echo "  Bitrix: ID={$b['ID']} Сумма={$b['PRICE']} {$b['CURRENCY']} Оплачен=" . ($b['PAID'] === 'Y' ? 'Да' : 'Нет') . " Статус={$b['STATUS_ID']} ({$statusText})\n";
            }
            if (!empty($d['tx'])) {
                $t = $d['tx'];
                echo "  CloudPayments: Сумма={$t['amount']} {$t['currency']} Статус оригинал={$t['status']['raw']}\n";
            }
        }
        echo "</pre>";
    }

    /**
     * @param string $status
     * @return string
     */
    protected function bitrixStatusText(string $status): string
    {
        return match($status) {
            'N'  => 'Принят',
            'P'  => 'Оплачен',
            'F'  => 'Выполнен',
            'CD' => 'Отменён',
            'PR' => 'Выполнен неполный возврат',
            'RF' => 'Выполнен полный возврат',
            'RR' => 'Заявка на возврат',
            default => 'Неизвестный статус',
        };
    }
}
