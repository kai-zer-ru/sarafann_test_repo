<?php

    use App\Pipeline\DefaultResponse;
    use App\SaraFann;
    use Laminas\Diactoros\Response\JsonResponse;
    use Laminas\Diactoros\Response\RedirectResponse;
    use Laminas\Diactoros\Response\TextResponse;

    try {
        if (PHP_MAJOR_VERSION < 7 || (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION < 4)) {
            header('Status: 500 Internal Server Error');
            echo json_encode(DefaultResponse::root500(500, 'Please, upgrade PHP'));

            return;
        }
        global $currentUserID, $authToken, $redis, $api, $config, $currencyId;
        define('START_TIME', microtime(true));
        if (!defined('TWO_WEEKS')) {
            define('TWO_WEEKS', 1209600);
        }
        if (!defined('ONE_WEEK')) {
            define('ONE_WEEK', 604800);
        }
        require_once dirname(__DIR__).'/vendor/autoload.php';
        $application = new SaraFann();
        $request = $application->prepareRequest();
        $response = $application->getResponse($request);
        $t = (microtime(true) - START_TIME) * 1000;
        header('Server-Timing: '.$t);
        if ($response instanceof RedirectResponse) {
            header('Location: '.$response->getHeader('location')[0]);
        } elseif ($response instanceof TextResponse) {
            echo $response->getBody();
        } else {
            echo $response->getBody();
        }
    } catch (Throwable $e) {
        saveLogError($e);
        $response = new JsonResponse(DefaultResponse::root500(), 500);
        echo $response->getBody();
    }
