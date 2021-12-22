<?php

namespace App\Pipeline;

        use Psr\Http\Message\ServerRequestInterface;
        use SplQueue;

        class Next
        {
            private $next;
            private SplQueue $queue;

            public function __construct(SplQueue $queue, callable $next)
            {
                $this->queue = $queue;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request)
            {
                if ($this->queue->isEmpty()) {
                    return ($this->next)($request);
                }
                $middleware = $this->queue->dequeue();

                return $middleware($request, function (ServerRequestInterface $request) {
                    return $this->handle($request);
                });
            }
        }
