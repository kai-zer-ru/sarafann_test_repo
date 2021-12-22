<?php

namespace App\Logger;

    use Monolog\Processor\ProcessorInterface;

    class AuthUserProcessor implements ProcessorInterface
    {
        public function __invoke(array $record): array
        {
            global $currentUserID;
            $record['extra']['auth_user_id'] = $currentUserID;

            return $record;
        }
    }
