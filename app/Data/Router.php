<?php

namespace App\Data;

    use App\Pipeline\DefaultResponse;
    use App\SaraFann;
    use FastRoute\Dispatcher;
    use Psr\Http\Message\ServerRequestInterface;

    class Router
    {
        private SaraFann $application;

        public function __construct(SaraFann $application)
        {
            $this->application = $application;
        }

        public function handle(ServerRequestInterface $request)
        {
            /**
             * @var Dispatcher $dispatcher
             */
            $dispatcher = require_once dirname(__DIR__, 1).'/../routes/routes_api_domain.php';
            $httpMethod = $request->getMethod();
            $uri = $request->getUri()->getPath();
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }
            $uri = rawurldecode($uri);
            $uri = '/'.trim($uri, '/');
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            $response = DefaultResponse::root();
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $response = DefaultResponse::root();

                    break;
                case Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    foreach (is_array($handler) ? $handler : [$handler] as $item) {
                        $this->application->pipe($item);
                    }

                    $params = $routeInfo[2];
                    foreach ($params as $param => $value) {
                        $request = $request->withAttribute($param, $value);
                    }
                    $response = $this->application->handle($request, 'MainController@root');

                    break;
            }

            return $response;
        }
    }
