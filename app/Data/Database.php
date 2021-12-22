<?php

namespace App\Data;

    use App\Models\User;
    use Illuminate\Database\Capsule\Manager as Capsule;
    use Illuminate\Database\Eloquent\Relations\Relation;

    class Database
    {
        public function __construct()
        {
            $database = new Capsule();
            $database->addConnection([
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ], 'default');

//            $database->setEventDispatcher(new Dispatcher(new Container));
            $database->bootEloquent();
            $database->setAsGlobal();
            Relation::morphMap([
                'user' => User::class,
            ]);
        }
    }
