<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        // ✅ MySQL (Azure-friendly, SSL via .env)
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'), // Azure: user@server
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? (function () {
                $opts = [];

                // Keep boolean false if set; don't let array_filter drop it.
                $opts[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = filter_var(
                    env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', false),
                    FILTER_VALIDATE_BOOLEAN
                );

                // Support either a single CA file or a CA directory (CAPATH)
                $ca = env('MYSQL_ATTR_SSL_CA');
                $capath = env('MYSQL_ATTR_SSL_CAPATH');

                if ($ca) {
                    $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\\/]|^\//', $ca) === 1;
                    $resolved = $isAbsolute ? $ca : base_path($ca);
                    $opts[\PDO::MYSQL_ATTR_SSL_CA] = str_replace('\\', '/', $resolved);
                } elseif ($capath) {
                    $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\\/]|^\//', $capath) === 1;
                    $resolved = $isAbsolute ? $capath : base_path($capath);
                    $opts[\PDO::MYSQL_ATTR_SSL_CAPATH] = str_replace('\\', '/', $resolved);
                }

                return array_filter($opts, function ($v) {
                    return !is_null($v) && $v !== '';
                });
            })() : [],
        ],

        // (Optional) MariaDB (mirrors MySQL settings)
        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? (function () {
                $opts = [];

                $opts[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = filter_var(
                    env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', false),
                    FILTER_VALIDATE_BOOLEAN
                );

                $ca = env('MYSQL_ATTR_SSL_CA');
                $capath = env('MYSQL_ATTR_SSL_CAPATH');

                if ($ca) {
                    $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\\/]|^\//', $ca) === 1;
                    $resolved = $isAbsolute ? $ca : base_path($ca);
                    $opts[\PDO::MYSQL_ATTR_SSL_CA] = str_replace('\\', '/', $resolved);
                } elseif ($capath) {
                    $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\\/]|^\//', $capath) === 1;
                    $resolved = $isAbsolute ? $capath : base_path($capath);
                    $opts[\PDO::MYSQL_ATTR_SSL_CAPATH] = str_replace('\\', '/', $resolved);
                }

                return array_filter($opts, function ($v) {
                    return !is_null($v) && $v !== '';
                });
            })() : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */
    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
