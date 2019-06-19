<?php

/**
 * List of plain connections and their handlers
 */

return [
    'sqs-plain'    => [
        'handlers'        => [
            'test-queue-name-sqs' => App\Jobs\HandlerJobSqs::class,
        ],
        'default-handler' => App\Jobs\HandlerJobSqs::class,
    ],
    'pubsub-plain' => [
        'handlers'        => [
            'test-queue-name-pubsub' => App\Jobs\HandlerJobPubsub::class,
        ],
        'default-handler' => App\Jobs\HandlerJobPubsub::class,
    ],
];
