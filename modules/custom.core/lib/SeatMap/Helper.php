<?php

namespace Custom\Core\SeatMap;

class Helper
{
    /**
     * Отправляет HTTP запрос
     *
     * @param string $url Endpoint
     * @param array $headers Заголовки
     * @param string $method Тип запроса (GET|POST|PUT|DELETE)
     * @param array $params Параметры запроса
     * @return array
     */
    public static function curlRequest(string $url = '', array $headers = [], string $method = 'GET', array $params = []): array
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 100);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $json = false;
            foreach ($headers as $header) {
                if (str_contains('Content-Type: application/json', $header)) {
                    $json = true;
                }
            }

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($json) {
                    curl_setopt(
                        $ch, CURLOPT_POSTFIELDS, json_encode(
                            $params, JSON_UNESCAPED_UNICODE
                        )
                    );
                } else if ($params) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
                }
            }

            if (in_array($method, ['PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($json) {
                    curl_setopt(
                        $ch, CURLOPT_POSTFIELDS, json_encode(
                            $params, JSON_UNESCAPED_UNICODE
                        )
                    );
                } else if ($params) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
                }
            }

            if ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            if ($method === 'GET') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                if(count($params) > 0){
                    $queryStr = http_build_query($params);
                    $url      .= '?' . $queryStr;
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            if ($response === false) throw new \Exception(curl_error($ch));
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200)
                return ['status' => 'success', 'code' => $httpCode, 'response' => $response];
            if ($httpCode === 404)
                return ['status' => 'success', 'code' => $httpCode, 'response' => $response];
            else
                throw new \Exception($response);
        } catch (\Exception $e) {
            return ['status' => 'error', 'code' => $httpCode, 'message' => $e->getMessage()];
        }
    }

    /**
     * Генерирует GUID.
     *
     * @return string
     */
    public static function generateGUID(): string
    {
        // Check if the `com_create_guid` function exists (available on Windows).
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        }

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 4.
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set variant to RFC 4122.

        // Convert to hexadecimal format and return.
        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }
}
