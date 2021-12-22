<?php

namespace App\Controllers;

use App\Models\ApiErrorCode;
use App\Models\ApiMethod;
use App\Models\ApiMethodGroup;
use App\Models\ApiVersion;
use App\Services\Api\Funcs;
use App\Services\SaraFannApiService;
use Psr\Http\Message\ServerRequestInterface;

class ApiMainController extends Main
{
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);
    }

    public function getMethods()
    {
        /**
         * @var ApiMethodGroup $group
         * @var ApiMethod      $groupMethod
         */
        $version = config('constants.api_version');
        $versionModel = ApiVersion::whereVersionId($version)
            ->first();
        if (!$versionModel) {
            $versionModel = ApiVersion::whereVersionId(config('constants.api_version'))
                ->first();
        }
        $groups = ApiMethodGroup::all();
        $methods = [];
        foreach ($groups as $group) {
            $groupMethods = ApiMethod::with('accessTokens')
                ->where('is_hidden', 0)
                ->where('first_version', '<=', $versionModel->id)
                ->where('last_version', '>=', $versionModel->id)
                ->where('group', $group->name)
                ->where(function ($query) {
                    $query->where('status', '=', ApiMethod::STATUS_ENABLED);
                    if ('production' !== env('APP_ENV')) {
                        $query
                            ->orWhere('status', '=', ApiMethod::STATUS_TEST);
                    }
                });
            $groupMethods = $groupMethods
                ->get();
            if (0 === count($groupMethods)) {
                continue;
            }
            $groupMehodsArrayData = [];
            foreach ($groupMethods as $groupMethod) {
                $groupMethodArray = $groupMethod->toArray();
                $groupMethodArray['access_tokens'] = $groupMethod->accessTokens()->get();
                $groupMehodsArrayData[$groupMethod->name] = $groupMethodArray;
            }
            ksort($groupMehodsArrayData);
            $methods[$group->name] = $groupMehodsArrayData;
        }
        ksort($methods);

        return $methods;
    }

    public function getMethodData()
    {
        /**
         * @var ApiMethod $method
         * @var SaraFannApiService $api
         */
        global $api;
        $apiMethod = $this->request->getAttribute('apiMethod');
        [$group, $name] = explode('.', $apiMethod, 2);
        saveLogDebug('Start API from https://api.sarafann.com');
        saveLogDebug($group.'.'.$name);
        $method = ApiMethod::whereGroup($group)
            ->whereName($name)
            ->first();
        if (!$method) {
            if (iAmAdmin()) {
                saveLogDebug("No method {$group}.{$name}");
                Funcs::saveDataBaseLog();
            }
            $api->setError(ApiErrorCode::whereCode(101)->first()->getArray());

            return $api->close([]);
        }
        $api->setMethod($method);
        [$init, $status, $message] = $api->init($this->request);
        if (!$init) {
            saveLogDebug([$init, $status, $message]);
            $error = ApiErrorCode::whereCode($status)
                ->first()->getArray();
            $error['text'] = $message;

            return $error;
        }
        saveLogDebug('function = '.$method->function_name);
        $functionName = $method->function_name;
        $response = $api->work($functionName);
        $response = $api->close($response);
        if (array_key_exists('error', $response) && 0 !== (int) $response['error']) {
            if (103 === (int) $response['error']) {
                return $response;
            }
            if (404 === (int) $response['error']) {
                return $response;
            }
            if (400 === (int) $response['error']) {
                return $response;
            }
            if (401 === (int) $response['error']) {
                return $response;
            }
        }

        return $response;
    }

    public function errors()
    {
        $errorId = $this->request->getAttribute('errorId');
        $error = ApiErrorCode::whereCode($errorId)->first();
        if (!$error) {
            return [];
        }

        return $error;
    }

    public function errorsAll()
    {
        return ApiErrorCode::all();
    }

    public function indexView()
    {
        return [];
//        return view('api/main');
    }

    public function prepareErrorResponse($errorCode, $errorText = null)
    {
        $allResponse = [
            'response' => [
                'count' => 0,
                'items' => [],
            ],
        ];
        $error = ApiErrorCode::whereCode($errorCode)->first()->getArray();
        if ($errorText) {
            $error['error_text'] = $errorText;
        }
        $allResponse['run_time'] = (int) ((microtime(true) - START_TIME) * 1000);
        $allResponse['server_time'] = time();
        $allResponse['error'] = $error['error'];
        $allResponse['error_text'] = $error['error_text'];
        $allResponse['error_url'] = $error['error_url'];

        return $allResponse;
    }
}
