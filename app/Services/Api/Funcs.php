<?php

namespace App\Services\Api;

use Error;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Yandex\Translate\Exception as ExceptionYandex;
use Yandex\Translate\Translator;

class Funcs
{
    public static function checkValidRawData($rawData): bool
    {
        $isValidJson = json_decode($rawData, true);

        return null !== $isValidJson;
    }

    /**
     * Функция записи в лог.
     *
     * @param mixed $data
     * @param mixed $context
     */
    public static function saveErrorLog($data, $context = []): void
    {
        if (is_string($data)) {
            addLogRecord($data, 'error', $context);
        } else {
            addLogRecord($data->getTrace(), 'error', $context);
            addLogRecord($data->getMessage(), 'error', $context);
            addLogRecord($data->getTraceAsString(), 'error', $context);
            addLogRecord($data, 'error', $context);
        }
    }

    public static function saveDataBaseLog($func = null): void
    {
        $logArray = self::getDataBaseLog();
        if ($func) {
            $data = "SQL FROM {$func}: ".json_encode($logArray);
            saveLogDebug($data);
        } else {
            saveLogDebug($logArray);
        }
    }

    public static function urlExists($url): bool
    {
        $itc_curl_init = curl_init($url);
        curl_setopt($itc_curl_init, CURLOPT_NOBODY, true);
        curl_exec($itc_curl_init);
        $itc_http_code = curl_getinfo($itc_curl_init, CURLINFO_HTTP_CODE);

        if ('200' === $itc_http_code) {
            if ('Возникла ошибка, обратитесь к администратору support@myburse.pro' === self::getCurlData($url)) {
                return false;
            }
            $exist = true;
        } else {
            $exist = false;
        }
        curl_close($itc_curl_init);

        return $exist;
    }

    /**
     * Функция для получения данных по URL адресу.
     *
     * @param bool  $ResponseHttpCode
     * @param mixed $headers
     *
     * @return int|string
     */
    public static function getCurlData(string $url, $ResponseHttpCode = false, $headers = [])
    {
        $myCurl = curl_init();
        curl_setopt_array($myCurl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => 1,
        ]);
        $response = curl_exec($myCurl);
        $httpCode = curl_getinfo($myCurl, CURLINFO_HTTP_CODE);
        curl_close($myCurl);
        if ($ResponseHttpCode) {
            return $httpCode;
        }

        return $response;
    }

    public static function getDataBaseLog(): array
    {
        $logs = Manager::connection('default')->getQueryLog();
        $logArray = [];
        foreach ($logs as $log) {
            $query = $log['query'];
            $bindings = $log['bindings'];
            $time = $log['time'];
            foreach ($bindings as $binding) {
                if (is_string($binding)) {
                    $query = Str::replaceFirst('?', "'{$binding}'", $query);
                } else {
                    $query = Str::replaceFirst('?', $binding, $query);
                }
            }
            $logArray[] = [
                'query' => $query,
                'time' => $time,
            ];
        }

        return $logArray;
    }

    /**
     * @param mixed $post_data
     * @param array $headers
     * @param bool  $useProxy
     */
    public static function getCurlPostFullData(string $url, $post_data, $headers = [], $useProxy = false): array
    {
        $myCurl = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_data ? http_build_query($post_data) : http_build_query([]),
        ];
        curl_setopt_array($myCurl, $opts);
        curl_setopt($myCurl, CURLOPT_HTTPHEADER, $headers);


        try {
            $response = curl_exec($myCurl);
        } catch (Error $e) {
            saveLogError($e);

            return [500, json_encode($e->getTrace())];
        }
        $httpCode = curl_getinfo($myCurl, CURLINFO_HTTP_CODE);
        curl_close($myCurl);

        return [$httpCode, $response];
    }

    /**
     * @param array $headers
     * @param bool  $useProxy
     */
    public static function getCurlFullData(string $url, $headers = [], $useProxy = false): array
    {
        $myCurl = curl_init();
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
        ];
        curl_setopt_array($myCurl, $opts);
        curl_setopt($myCurl, CURLOPT_HTTPHEADER, $headers);

        try {
            $response = curl_exec($myCurl);
        } catch (Error $e) {
            saveLogError($e);

            return [500, json_encode($e->getTrace())];
        }
        $httpCode = curl_getinfo($myCurl, CURLINFO_HTTP_CODE);
        curl_close($myCurl);

        return [$httpCode, $response];
    }

    /**
     * @param null  $post_data
     * @param bool  $isPost
     * @param mixed $headers
     * @param mixed $isJsonRequest
     * @param bool  $useProxy
     * @param mixed $isPostArray
     */
    public static function getCurlJson(string $url, $post_data = null, $isPost = false, $headers = null, $isJsonRequest = false, $useProxy = false, $isPostArray = false): array
    {
        saveLogDebug($url);
        $myCurl = curl_init();
        curl_setopt_array($myCurl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
        ]);
        if ($headers && is_array($headers)) {
            curl_setopt($myCurl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($myCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 YaBrowser/19.9.0.1768 Yowser/2.5 Safari/537.36');
        if ($isPost) {
            curl_setopt($myCurl, CURLOPT_POST, 1);
            if ($isJsonRequest) {
                saveLogDebug(json_encode($post_data));
                curl_setopt($myCurl, CURLOPT_POSTFIELDS, json_encode($post_data));
                curl_setopt($myCurl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
            } else {
                if ($isPostArray) {
                    saveLogDebug($post_data);
                    curl_setopt($myCurl, CURLOPT_POSTFIELDS, $post_data ?: []);
                } else {
                    saveLogDebug(http_build_query($post_data));
                    curl_setopt($myCurl, CURLOPT_POSTFIELDS, $post_data ? http_build_query($post_data) : http_build_query([]));
                }
            }
        }
        $response = curl_exec($myCurl);
        curl_close($myCurl);
        $responseJson = json_decode($response, true);
        if (null === $responseJson) {
            saveLogInfo('Error in getCurlJson');
            saveLogInfo($url);
            saveLogInfo($post_data);
            saveLogInfo($response);
            saveLogInfo(json_last_error_msg());
            $responseJson = [];
        }

        return $responseJson;
    }

    public static function strToBool($str): bool
    {
        if (is_bool($str)) {
            return $str;
        }
        if (!$str) {
            return false;
        }
        if ('true' === $str) {
            return true;
        }
        if ('false' === $str) {
            return false;
        }

        return (bool) $str;
    }

    public static function boolToStr($bool): string
    {
        if (is_string($bool)) {
            return $bool;
        }
        if (!$bool) {
            return 'false';
        }

        return 'true';
    }

    /**
     * @param $phone
     *
     * @return bool|string
     */
    public static function checkPhoneNumber($phone)
    {
        if ('8' === mb_substr($phone, 0, 1)) {
            $phone = '7'.mb_substr($phone, 1);
        }
        if (!$phone || '0' === (string) $phone) {
            return false;
        }
        if ('+' !== $phone[0]) {
            $phone = '+'.$phone;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $swissNumberProto = $phoneUtil->parse($phone);
            $newPhone = '+'.$swissNumberProto->getCountryCode().$swissNumberProto->getNationalNumber();
            if (strlen($newPhone) < 10) {
                return false;
            }

            return $newPhone;
        } catch (NumberParseException $e) {
            self::saveErrorLog($e);

            return false;
        }
    }

    public static function translate($text)
    {
        try {
            $translator = new Translator('trnsl.1.1.20191211T095052Z.d540135152d22658.98fcd93386491412bb7721177f7d2d5aa8ec01c5');
            $t = $translator->translate($text, 'ru-en');
            $result = $t->getResult();
            if (is_array($result)) {
                return $result[0];
            }

            return $result;
        } catch (ExceptionYandex $e) {
            self::saveErrorLog($e);

            return $text;
        }
    }
}
