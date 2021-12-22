<?php

namespace App\Logger;

    use Monolog\Processor\ProcessorInterface;

    class BackTraceProcessor implements ProcessorInterface
    {
        public function __invoke(array $record): array
        {
            $record['extra']['stack'] = [];
            $record['extra']['stack'] = $this->appendExtraFields($record['extra']['stack']);

            return $record;
        }

        private function appendExtraFields(array $extra): array
        {
            $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);

            $extra = null === $extra ? [] : $extra;
            foreach ($backTrace as $item) {
                if (
                    (array_key_exists('class', $item) && 'App\\Logger\\BackTraceProcessor' === $item['class']) ||
                    (array_key_exists('file', $item) && 'Logger.php' === basename($item['file'])) ||
                    (array_key_exists('file', $item) && 'BackTraceProcessor.php' === basename($item['file'])) ||
                    (array_key_exists('file', $item) && 'helpers.php' === basename($item['file']) && 'addLogRecord' === $item['function']) ||
                    (array_key_exists('file', $item) && 'helpers.php' === basename($item['file']) && 'debug' === $item['function'])
                ) {
                    continue;
                }
                if (array_key_exists('file', $item)) {
                    $file = basename($item['file']).':'.$item['line'].':'.$item['function'];
                } else {
                    $file = $item['class'].':'.$item['function'];
                }

                array_unshift($extra, $file);
            }

            return $extra;
        }
    }
