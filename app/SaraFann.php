<?php

namespace App;

    use App\Data\Config;
    use App\Data\Database;
    use App\Data\Redis;
    use App\Data\Router;
    use App\Logger\AuthUserProcessor;
    use App\Logger\BackTraceProcessor;
    use App\Logger\LineFormatter;
    use App\Logger\PsrLogMessageProcessor;
    use App\Logger\WebProcessor;
    use App\Pipeline\Next;
    use App\Pipeline\Resolver;
    use App\Services\SaraFannApiService;
    use Dotenv\Dotenv;
    use Dotenv\Repository\Adapter\EnvConstAdapter;
    use Dotenv\Repository\Adapter\ServerConstAdapter;
    use Dotenv\Repository\RepositoryBuilder;
    use Illuminate\Support\Arr;
    use Laminas\Diactoros\Response\JsonResponse;
    use Laminas\Diactoros\Response\RedirectResponse;
    use Laminas\Diactoros\Response\TextResponse;
    use Laminas\Diactoros\ServerRequestFactory;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Monolog\Processor\GitProcessor;
    use Predis\Client;
    use Psr\Http\Message\ServerRequestInterface;
    use SplQueue;

    class SaraFann
    {
        private Resolver $resolver;
        private SplQueue $queue;
        private Client $redis;

        public function __construct()
        {
            global $logger, $config, $api, $redis;
            $this->queue = new SplQueue();
            $this->resolver = new Resolver();
            $this->loadEnv();
            $logger = new Logger('main');
            switch (env('APP_LOG_LEVEL')) {
                case 'debug':
                    $logLevel = Logger::DEBUG;

                    break;
                case 'error':
                    $logLevel = Logger::ERROR;

                    break;
                default:
                    $logLevel = Logger::INFO;

                    break;
            }
            $formatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);
            $formatter->includeStacktraces(true);
            $streamHandler = new StreamHandler(__DIR__.'/../logs/sarafann-'.date('Y-m-d').'.log', $logLevel);
            $streamHandler->setFormatter($formatter);
            $logger->pushHandler($streamHandler);
            $logger->pushProcessor(new PsrLogMessageProcessor('Y-m-d H:i:s', true));
            $logger->pushProcessor(new AuthUserProcessor());
            $logger->useMicrosecondTimestamps(false);
            new Database();
            $redis = Redis::getRedis();
            $config = Config::getConfig();
            $api = new SaraFannApiService($redis);
            $this->redis = $redis;
        }

        public function handle(ServerRequestInterface $request, $next)
        {
            $delegate = new Next($this->queue, $this->resolver->resolve($next));

            return $delegate->handle($request);
        }

        public function pipe($middleware): void
        {
            $this->queue->enqueue($this->resolver->resolve($middleware));
        }

        public function prepareRequest(): ServerRequestInterface
        {
            @header('Content-type:application/json; charset=UTF-8');
            $request = ServerRequestFactory::fromGlobals();
            $referer = null;
            $schema = null;
            if (array_key_exists('HTTP_REFERER', $request->getServerParams())) {
                $refererFull = Arr::get($request->getServerParams(), 'HTTP_REFERER');
                if ('ionic://' === $refererFull) {
                    $refererArr = [];
                    $referer = '';
                    $schema = 'ionic';
                } else {
                    $refererArr = parse_url($refererFull);
                    $referer = $refererArr['host'];
                    $schema = $refererArr['scheme'];
                }
            } elseif (array_key_exists('HTTP_ORIGIN', $request->getServerParams())) {
                $refererFull = Arr::get($request->getServerParams(), 'HTTP_ORIGIN');
                if ('ionic://' === $refererFull) {
                    $refererArr = [];
                    $referer = '';
                    $schema = 'ionic';
                } else {
                    $refererArr = parse_url($refererFull);
                    $referer = $refererArr['host'];
                    $schema = $refererArr['scheme'];
                }
            }
            if (isset($refererArr['port'])) {
                header("Access-Control-Allow-Origin: {$schema}://{$referer}:{$refererArr['port']}");
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD');
                header('Access-Control-Allow-Headers: content-type,X-XSRF-TOKEN,x-csrf-token,x-requested-with,authorization,Authorization');
                header('Access-Control-Allow-Credentials: true');
            } else {
                header("Access-Control-Allow-Origin: {$schema}://{$referer}");
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Headers:content-type,X-XSRF-TOKEN,x-csrf-token,x-requested-with,authorization,Authorization');
                if (false !== strpos($referer, 'webvisor.com') || false !== strpos($referer, 'sarafann.com') || false !== strpos($referer, 'sarafann.ru')) {
                    header("X-Frame-Options: ''");
                } else {
                    header("X-Frame-Options: 'SAMEORIGIN'");
                }
            }
            $rData = json_decode($request->getBody()->getContents(), true);
            saveLogError(json_last_error_msg());
            saveLogInfo('rData = '.$request->getBody()->getContents());
            $data = [];
            if (json_last_error() && ('' !== $request->getBody()->getContents() && strstr(urldecode($request->getBody()->getContents()), '&'))) {
                $reqData = urldecode($request->getBody()->getContents());
                $reqData = explode('&', $reqData);
                saveLogInfo('reqData = '.json_encode($reqData));
                foreach ($reqData as $req) {
                    $r = explode('=', $req, 2);
                    $data[$r[0]] = $r[1];
                }
            } else {
                if (is_iterable($rData)) {
                    foreach ($rData as $k => $v) {
                        $data[$k] = $v;
                    }
                }
            }
            $data = array_merge($data ?: [], $request->getQueryParams());
            $request = $request->withParsedBody($data);
            $middleWares = require_once dirname(__DIR__).'/middlewares/middlewares.php';
            foreach ($middleWares as $middleWare) {
                $this->pipe($middleWare);
            }

            return $request;
        }

        public function getResponse(ServerRequestInterface $request)
        {
            $router = new Router($this);
            $response = $router->handle($request);
            if ($response instanceof RedirectResponse) {
                return $response;
            }
            if (is_string($response) || is_float($response) || is_int($response)) {
                return new TextResponse((string) $response);
            }

            return new JsonResponse($response);
        }

        private function loadEnv(): void
        {
            $adapters = [
                new EnvConstAdapter(),
                new ServerConstAdapter(),
            ];

            $repository = RepositoryBuilder::create()
                ->withReaders($adapters)
                ->withWriters($adapters)
                ->immutable()
                ->make();

            Dotenv::create($repository, dirname(__DIR__), null)->load();
        }
    }
