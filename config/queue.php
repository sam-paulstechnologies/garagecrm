<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'        => 'database',
            // null => use the default DB connection
            'connection'    => env('DB_QUEUE_CONNECTION', null),
            // we are using queue_jobs to avoid conflict with your business "jobs" table
            'table'         => env('DB_QUEUE_TABLE', 'queue_jobs'),
            'queue'         => env('DB_QUEUE', 'default'),
            'retry_after'   => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit'  => false,
        ],

        'beanstalkd' => [
            'driver'       => 'beanstalkd',
            'host'         => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue'        => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after'  => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for'    => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver'       => 'sqs',
            'key'          => env('AWS_ACCESS_KEY_ID'),
            'secret'       => env('AWS_SECRET_ACCESS_KEY'),
            'prefix'       => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue'        => env('SQS_QUEUE', 'default'),
            'suffix'       => env('SQS_SUFFIX'),
            'region'       => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'    => null,
            'after_commit' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Batches (Horizon/Batchable Jobs)
    |--------------------------------------------------------------------------
    */
    'batching' => [
        // keep batches on the same DB as the queue by default
        'database' => env('DB_QUEUE_CONNECTION', env('DB_CONNECTION')),
        'table'    => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    */
    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        // store failed jobs on the same DB as the queue
        'database' => env('DB_QUEUE_CONNECTION', env('DB_CONNECTION')),
        // your DB already has this table
        'table'    => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],

];
