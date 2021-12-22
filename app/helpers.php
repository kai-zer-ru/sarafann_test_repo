<?php

    use App\Data\Config;
    use App\Models\User;
    use App\Services\Api\Funcs;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Carbon;
    use Laminas\Diactoros\ServerRequestFactory;
    use Monolog\Logger;
    use Psr\Http\Message\ServerRequestInterface;

    function config($name = null, $default = null)
    {
        $config = Config::getConfig();
        if (null === $name) {
            return $config;
        }

        if (strstr($name, '.')) {
            $keys = explode('.', $name);
        } else {
            $keys = [$name];
        }
        $result = $config;
        foreach ($keys as $key) {
            if (is_array($result) && array_key_exists($key, $result)) {
                $result = $result[$key];
            } else {
                $result = $default;
            }
        }

        return $result;
    }

    function setResponseCookie($name, $value, $hasExpire = true)
    {
        $responseDomain = config('session.domain');
        $referer = Arr::get($_SERVER, 'HTTP_REFERER', null);
        if (!$referer) {
            $referer = Arr::get($_SERVER, 'HTTP_ORIGIN', null);
        }
        if (null !== $referer && strstr($referer, '.corp')) {
            $responseDomain = '.sarafann.corp';
        }
        if ($hasExpire) {
            $expire = config('session.expire_on_close') ? 0 : Carbon::now()->addRealMinutes(config('session.lifetime'))->getTimestamp();
        } else {
            $expire = 0;
        }
        setcookie($name, $value, $expire, config('session.path'), $responseDomain, config('session.secure'), config('session.http_only'));
    }

    function authid()
    {
        global $currentUserID;

        return (int) $currentUserID;
    }

    function loginUser(User $user)
    {
        global $currentUserID;
        $currentUserID = $user->id;
    }

    function loginUserId($userId)
    {
        global $currentUserID;
        $currentUserID = $userId;
    }

    function authUser()
    {
        $user = null;
        if (authid()) {
            $user = User::find(authid());
        }

        return $user;
    }

    function iAmAdmin()
    {
        if (!authid()) {
            return false;
        }
        $admins = json_decode(env('ADMIN_IDS', '[]'), true);
        if (in_array(authid(), $admins, true)) {
            return true;
        }

        return false;
    }

    function getAdminRole()
    {
        if (!iAmAdmin()) {
            return 'user';
        }
        $adminRoles = config('admin.roles', []);
        foreach ($adminRoles as $role => $ids) {
            if (in_array(authid(), $ids)) {
                return $role;
            }
        }

        return 'user';
    }

    function addLogRecord($data, $level = 'debug', $context = [])
    {
        /**
         * @var Logger $logger
         */
        global $logger;
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        if (!$data) {
            $data = 'No data';
        }
        if (null === $logger) {
            file_put_contents(__DIR__.'/../logs/sarafann-'.date('Y-m-d').'.log', $data."\n", FILE_APPEND);
        } else {
            $logger->{$level}($data, $context);
        }
    }

    function saveLogDebug($data, $context = [])
    {
        addLogRecord($data, 'debug', $context);
    }

    function saveLogError($data, $context = [])
    {
        Funcs::saveErrorLog($data, $context);
    }

    function saveLogInfo($data, $context = [])
    {
        addLogRecord($data, 'info', $context);
    }

    function public_path($path = '')
    {
        return dirname(__DIR__).'/public/'.$path;
    }

    function getClientIp($request = null)
    {
        /**
         * @var ServerRequestInterface $request
         */
        if (null === $request) {
            $request = ServerRequestFactory::fromGlobals();
        }
        if ($request->hasHeader('X-Real-IP')) {
            return $request->getHeader('X-Real-IP');
        }

        return Arr::get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');
    }

    function prepareDate($bdate)
    {
        if ('0000-00-00' === $bdate) {
            return $bdate;
        }

        return date('Y-m-d', strtotime($bdate));
    }

    function isModerator()
    {
        if (!authid()) {
            return false;
        }
        if (iAmAdmin()) {
            return true;
        }
        $moderators = json_decode(env('MODERATORS', '[]'), true);

        return in_array(authid(), $moderators);
    }
