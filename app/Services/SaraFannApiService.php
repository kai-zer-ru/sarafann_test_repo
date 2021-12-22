<?php

namespace App\Services;

use App\Models\ApiAccessTokenType;
use App\Models\ApiErrorCode;
use App\Models\ApiMethod;
use App\Models\User;
use App\Services\Api\Funcs;
use App\Services\Api\Versions\Version_1_0;
use Illuminate\Support\Arr;
use Predis\Client;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @SaraFannApiService
 * Описание класса SaraFannAPI
 * Class SaraFannAPI.
 *
 * @property ApiMethod                                         $method
 * @property string                                            $version
 * @property ServerRequestInterface                            $request
 * @property array                                             $inputData
 * @property null|int                                          $CurrencyId
 * @property int                                               $error
 * @property int                                               $errorText
 * @property int                                               $errorLink
 * @property bool                                              $is_database_connected
 * @property array                                             $versions
 * @property Version_1_0                                       $versionClass
 * @property User                                              $CurrentUser
 * @property null|int                                          $CurrentUserID
 */
class SaraFannApiService
{
    public const SOCIAL_VK = 'vkontakte';
    public const SOCIAL_OK = 'odnoklassniki';
    public const SOCIAL_FB = 'facebook';
    public const SOCIAL_APPLE = 'apple';

    /**
     * Константы ошибок.
     */
    public const ERROR_NO_ERRORS = 0;
    public const ERROR_UNKNOWN_METHOD = 101;
    public const ERROR_AUTH_IS_REQUIRED = 103;
    public const ERROR_INCORRECT_AUTH_DATA = 401;
    public const ERROR_INCORRECT_INPUT_DATA = 102;
    public const ERROR_SERVER_ERROR = 500;
    public const ERROR_NOT_FOUND = 404;
    /**
     * приватные данные.
     */
    public array $Socials = [
        self::SOCIAL_VK => 'vk.com',
        self::SOCIAL_OK => 'ok.ru',
        self::SOCIAL_FB => 'facebook.com',
        self::SOCIAL_APPLE => 'appple.com',
    ];
    /**
     * @var Version_1_0 $versionClass
     */
    public $versionClass;
    public bool $isLocal = false;
    public bool $isFullResponse = true;

    /** @var null|int|string $last_id */
    public $last_id;

    public ?bool $has_unread = null;

    public ?int $all_count = null;
    /**
     * Публичные данные.
     */
    public ?ApiMethod $method = null;
    public string $version;
    public array $inputData = [];
    public int $error = self::ERROR_NO_ERRORS;
    public string $errorText = '';
    public string $errorLink = '';
    /**
     * Переменные текущего пользователя и/или устройства.
     */
    public ?int $CurrentUserID = 0;
    public ?User $CurrentUser = null;
    public ?ServerRequestInterface $request = null;
    /**
     * Переменная с содержанием версионности и описанием методов.
     */
    public array $versions = [
        '1.0',
        '1.1',
        '1.11',
        '1.12',
    ];
    public Client $redis;

    /**
     * Константы общего назначения.
     */
//    private $paramsMap;
    private $paramsMapReverse;

    /**
     * SaraFannApiService constructor.
     */
    public function __construct(Client $redis)
    {
        global $currentUserID;
        $this->CurrentUserID = $currentUserID;
        $this->redis = $redis;
        ini_set('max_execution_time', 90000);
        $this->paramsMapReverse = json_decode(file_get_contents(public_path('map_reverse.json')), true);
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function work($functionName)
    {
        /**
         * @var Version_1_0 $versionClass
         */
        $this->isFullResponse = true;
        $versionClass = $this->versionClass;
        $response = $versionClass->{$functionName}();
        if (in_array($functionName, [], true)) {
            $this->isFullResponse = false;

            return $this->prepareLightResponse($response);
        }

        return $response;
    }

    public function prepareLightResponse($response)
    {
        if (0 !== $this->error) {
            $response = ApiErrorCode::whereCode($this->error)->first()->getArray();
            $response['error_text'] = $this->errorText;
            $response = array_merge($response, ['token_type' => '', 'access_token' => '', 'expire' => 0]);
        }

        return $response;
    }

    public function setError($errorData = [])
    {
        $this->error = Arr::get($errorData, 'error', 0);
        $this->errorText = Arr::get($errorData, 'error_text', '');
    }

    // Выше - обработчики событий API
    //=================================

    /**
     * Функция инициалтзации SaraFannAPI.
     *
     * @return array
     */
    public function init(ServerRequestInterface $request)
    {
        $this->request = $request;
        $jsonSchema = public_path("/json/schema/methods/{$this->method->group}/{$this->method->name}.request.json");
        if (!is_file($jsonSchema)) {
            saveLogDebug(public_path("/json/schema/methods/{$this->method->group}/{$this->method->name}.request.json"));

            return [false, 404, 'Неизвестный метод'];
        }
        $jsonSchemaData = json_decode(file_get_contents($jsonSchema), true);
        if (
            array_key_exists('method', $jsonSchemaData) &&
            'post' === strtolower($jsonSchemaData['method']) &&
            'POST' !== $this->request->getMethod()
        ) {
            return [false, 419, 'Метод должен вызываться через POST'];
        }
        $GLOBALS['is_local'] = false;
        $this->isLocal = false;
        $this->last_id = null;
        if ('GET' === $this->request->getMethod()) {
            $GET = $this->request->getQueryParams();
            foreach ($GET as $field => $value) {
                if ('limit' === $field) {
                    if ((int) $value > 20) {
                        $value = 20;
                    }
                }
                $this->inputData[$field] = $value;
            }
        } else {
            $POST = $this->request->getParsedBody();
            foreach ($POST as $field => $value) {
                if ('limit' === $field) {
                    if ((int) $value > 20) {
                        $value = 20;
                    }
                }
                $this->inputData[$field] = $value;
            }
            $this->inputData['files'] = $this->request->getUploadedFiles();
        }
        $this->version = config('constants.api_version');

        $required = Arr::get($jsonSchemaData, 'required', []);
        foreach ($required as $value) {
            if (!array_key_exists($value, $this->inputData)) {
                $referer = Arr::get($this->request->getServerParams(), 'HTTP_REFERER', 'no referer');
                saveLogDebug('400 response');
                saveLogDebug($this->inputData);
                saveLogDebug($value);

                return [false, 400, 'Не все обязательные параметры переданы в запрос'];
            }
        }
        $authRequired = true;
        $accessTokenApp = ApiAccessTokenType::whereType('app')->first();
        if ($this->method->accessTokens->contains($accessTokenApp)) {
            $authRequired = false;
        }

        /**
         * @var User $user
         */
        global $currentUserID;
        saveLogInfo('authid = '.$currentUserID);
        $this->CurrentUserID = $currentUserID;
        $this->CurrentUser = User::find($currentUserID);

        $user = null;

        if ($this->CurrentUser && User::STATUS_ACTIVE !== $this->CurrentUser->status) {
            if (User::STATUS_DELETED === $this->CurrentUser->status) {
                if ('POST' === $this->request->getMethod() && 'restore' !== $this->method->name && 'logout' !== $this->method->name) {
                    return [false, 401, ''];
                }
            } elseif (User::STATUS_PARSING === $this->CurrentUser->status) {
                if ('POST' === $this->request->getMethod() && !(('activateAccount' === $this->method->name && 'smm' === $this->method->group) || ('confirmSms' === $this->method->name && 'users' === $this->method->group) || ('sendSms' === $this->method->name && 'users' === $this->method->group) || 'logout' === $this->method->name)) {
                    return [false, 401, 'Пользователь не активен'];
                }
            } else {
                return [false, 401, ''];
            }
        }
        if ($authRequired) {
            if (!$this->CurrentUser) {
                $this->setError(ApiErrorCode::whereCode(103)->first()->getArray());

                return [false, 103, 'Нужно использовать токен пользователя'];
            }
            $this->CurrentUserID = $this->CurrentUser->id;
        } else {
            if ($this->CurrentUser) {
                $this->CurrentUserID = $this->CurrentUser->id;
            }
        }
        $this->initVersionClass();

        $this->setUser();

        return $this->incrementActionCounter();
    }

    /**
     * Функция получения числовых данных из входящей строки запроса.
     *
     * @param null|int $default_value
     *
     * @return int
     */
    public function getInputDataInt(string $key, $default_value = 0)
    {
        if (isset($this->inputData[$key])) {
            if (null === $this->inputData[$key]) {
                return $default_value;
            }

            $val = $this->inputData[$key];
            if (is_array($val)) {
                return $default_value;
            }
            $val = (int) $val;
            if ((string) $val !== (string) $this->inputData[$key]) {
                return $default_value;
            }

            return $val;
        }
        $key = Arr::get($this->paramsMapReverse, $key, null);
        if (!$key) {
            return $default_value;
        }
        if (isset($this->inputData[$key])) {
            $val = (int) $this->inputData[$key];
            if ((string) $val !== (string) $this->inputData[$key]) {
                return $default_value;
            }

            return $val;
        }

        return $default_value;
    }

    public function initVersionClass()
    {
        $versionFacade = str_replace('.', '_', $this->version);
        $versionfacadeClass = "App\\Services\\Api\\Versions\\Version_{$versionFacade}";
        $versionfacadeClass = new $versionfacadeClass();
        $this->versionClass = $versionfacadeClass->init($this);
    }

    /**
     * Функция получения массива данных из входящей строки запроса.
     *
     * @param $key
     * @param array $default_value
     *
     * @return array
     */
    public function getInputArray($key, $default_value = [])
    {
        if (isset($this->inputData[$key])) {
            $data = $this->inputData[$key];
            if (is_array($data) || is_object($data)) {
                return $data;
            }

            return json_decode($data, true);
        }
        $key = Arr::get($this->paramsMapReverse, $key, null);
        if (!$key) {
            return $default_value;
        }
        if (isset($this->inputData[$key])) {
            $data = $this->inputData[$key];
            if (is_array($data) || is_object($data)) {
                return $data;
            }

            return json_decode($data, true);
        }

        return $default_value;
    }

    /**
     * Функция получения числовых данных из входящей строки запроса.
     *
     * @param $key
     * @param bool $default_value
     *
     * @return bool
     */
    public function getInputDataBool($key, $default_value = false)
    {
        if (isset($this->inputData[$key])) {
            return Funcs::strToBool($this->inputData[$key]);
        }
        $key = Arr::get($this->paramsMapReverse, $key, null);
        if (!$key) {
            return $default_value;
        }
        if (isset($this->inputData[$key])) {
            return Funcs::strToBool($this->inputData[$key]);
        }

        return $default_value;
    }

    /**
     * Функция получения дробных данных из входящей строки запроса.
     *
     * @param $key
     * @param null|float $default_value
     *
     * @return float
     */
    public function getInputDataFloat($key, $default_value = 0.0)
    {
        if (isset($this->inputData[$key])) {
            if (in_array($key, ['latitude', 'longitude'], true)) {
                if ($this->inputData[$key]) {
                    return number_format($this->inputData[$key], 6, '.', '');
                }

                return $default_value;
            }

            return number_format($this->inputData[$key], 2, '.', '');
        }
        $key = Arr::get($this->paramsMapReverse, $key, null);
        if (!$key) {
            return $default_value;
        }
        if (isset($this->inputData[$key])) {
            if (in_array($key, ['latitude', 'longitude'], true)) {
                return number_format($this->inputData[$key], 6, '.', '');
            }

            return number_format($this->inputData[$key], 2, '.', '');
        }

        return $default_value;
    }

    /**
     * Функция завершения работы API.
     *
     * @param float|string $st
     *
     * @return array
     */
    public function close(array $response, $st = START_TIME)
    {
        $rt = (int) ((microtime(true) - $st) * 1000);
//        if ($this->method) {
//            if ($rt > 1000 && 'production' === env('APP_ENV')) {
//                Funcs::sendToTlg(env('APP_ENV').' > '.$rt.' ms response "'.$this->method->group.'.'.$this->method->name.'"'."\nUserID:".$this->CurrentUserID."\n".json_encode($this->inputData), -382255972);
//            }
//        }
        $serverId = env('SERVER_ID', 0);
        if (true === $this->isFullResponse) {
            $allResponse = [
                'response' => [
                    'count' => count($response),
                    'items' => $response,
                ],
            ];
            if (null !== $this->last_id) {
                $allResponse['response']['last_id'] = $this->last_id;
            }
            saveLogInfo('Count = '.$this->all_count);
            if (null !== $this->all_count) {
                $allResponse['response']['all_count'] = $this->all_count;
            }
            $allResponse['run_time'] = $rt;
            $allResponse['server_time'] = time();
            $allResponse['error'] = $this->error;
            $allResponse['error_text'] = $this->errorText;
            $allResponse['server_id'] = $serverId;
            $key = session_id();
            $key = "session_cache_{$key}";
            $this->redis->del([$key]);
//            if (in_array($this->method->function_name, $this->stopFuncs)) {
            //                Redis::connection()->del('stopFuncs.'.$this->method->function_name.'.'.authid());
            //            }
            $this->method = null;
            $this->request = null;
            $this->inputData = [];
            $this->error = 0;
            $this->CurrentUserID = 0;
            $this->CurrentUser = null;
            $this->last_id = 0;

            return $allResponse;
        }
        $key = session_id();
        $key = "session_cache_{$key}";
        $this->redis->del([$key]);

        return $response;
    }

    /**
     * Функция получения строковых данных из входящей строки запроса.
     *
     * @param $key
     * @param string $defaultValue
     *
     * @return string
     */
    public function getInputDataString($key, $defaultValue = '')
    {
        if (isset($this->inputData[$key])) {
            $data = $this->inputData[$key];
        } else {
            $key = Arr::get($this->paramsMapReverse, $key, null);
            if (!$key) {
                return $defaultValue;
            }
            if (isset($this->inputData[$key])) {
                $data = $this->inputData[$key];
            } else {
                $data = $defaultValue;
            }
        }
        if (is_int($data)) {
            return (string) $data;
        }
        if (is_float($data)) {
            return (string) $data;
        }
        if (!is_string($data)) {
            return $defaultValue;
        }

        return TranslationService::clearTagsFull(TranslationService::clearSymbols($data));
    }

    public function getInputDataStringFull($key, $defaultValue = '')
    {
        if (isset($this->inputData[$key])) {
            $data = $this->inputData[$key];
        } else {
            $key = Arr::get($this->paramsMapReverse, $key, null);
            if (!$key) {
                return $defaultValue;
            }
            if (isset($this->inputData[$key])) {
                $data = $this->inputData[$key];
            } else {
                $data = $defaultValue;
            }
        }
        if (is_int($data)) {
            return (string) $data;
        }
        if (is_float($data)) {
            return (string) $data;
        }
        if (!is_string($data)) {
            return $defaultValue;
        }

        return $data;
    }

    public function isImage($filename)
    {
        $is = @getimagesize($filename);
        if (!$is) {
            return false;
        }
        if (!in_array($is[2], [1, 2, 3], true)) {
            return false;
        }

        return true;
    }

    private function getIP()
    {
        if ($this->request->hasHeader('X-Real-IP')) {
            return $this->request->getHeader('X-Real-IP');
        }

        return $this->request->getServerParams()['REMOTE_ADDR'];
    }

    private function setUser()
    {
        if ($this->CurrentUser) {
            $this->CurrentUser->save(false);
        }
    }

    private function incrementActionCounter()
    {
        if (env('APP_DEBUG')) {
            return [true, 200, ''];
        }
        $actionsLimitPerMinute = env('ACTIONS_LIMIT_PER_MINUTE', 100);
        $this->redis->hIncrBy('ActionCountersByMethod.'.date('Y-m-d'), "{$this->method->group}.{$this->method->name}", 1);
        if ($this->CurrentUserID) {
            $count = $this->redis->hIncrBy('ActionCounts.'.date('Y-m-d H:i'), $this->CurrentUserID, 1);
            if ($count > $actionsLimitPerMinute) {
                saveLogDebug('429 response!');
                saveLogDebug([$this->CurrentUserID, $count, $actionsLimitPerMinute, 'ActionCounts.'.date('Y-m-d H:i')]);

                return [false, 429, 'Too Many Requests'];
            }

            return [true, 200, ''];
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $count = $this->redis->hIncrBy('ActionCountsByIP.'.date('Y-m-d H:i'), $ip, 1);
        if ($count > $actionsLimitPerMinute) {
            saveLogDebug('429 response!');
            saveLogDebug([$ip, $count, $actionsLimitPerMinute, 'ActionCounts.'.date('Y-m-d H:i')]);

            return [false, 429, 'Too Many Requests'];
        }

        return [true, 200, ''];
    }
}
