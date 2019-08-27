# Laravel Jobs Plain

`composer require myli/laravel-jobs-plain`

Remove the laravel dependency on jobs allowing you to process queue jobs with other non-laravel services that push plain json jobs into the queue.

# Config

Add the following to your `config/queue.php` :

```
        'sqs-plain' => [
            'driver' => 'sqs-plain',
            'key'    => env('AWS_QUEUE_KEY'),
            'secret' => env('AWS_QUEUE_SECRET'),
            'prefix' => env('AWS_PREFIX'),
            'queue'  => 'test',
            'region' => 'eu-west-1',
        ],

        'pubsub-plain' => [
            'driver'          => 'pubsub-plain',
            'queue'           => 'test',
            'project_id'      => env('PUBSUB_PROJECT_ID'),
            'retries'         => 3,
            'request_timeout' => 60,
            'keyFilePath'     => base_path() . '/' . env('PUBSUB_QUEUE_KEY'),
        ],
```
