<?php

namespace Myli\PlainJobs\PubSub;

use Google\Cloud\PubSub\Message;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as IlluminateJob;
use Ramsey\Uuid\Uuid;

class Job extends IlluminateJob implements JobContract
{
    /**
     * The PubSub queue.
     *
     * @var Queue
     */
    protected $pubsub;

    /**
     * The job instance.
     *
     * @var Message
     */
    protected $job;

    /**
     * @var string
     */
    protected $classHandler;

    /**
     * Job constructor.
     *
     * @param Container $container
     * @param Queue     $pubsub
     * @param Message   $job
     * @param string    $classHandler
     * @param           $connectionName
     * @param           $queue
     */
    public function __construct(
        Container $container,
        Queue $pubsub,
        Message $job,
        string $classHandler,
        $connectionName,
        $queue
    ) {
        $this->pubsub         = $pubsub;
        $this->job            = $job;
        $this->queue          = $queue;
        $this->container      = $container;
        $this->connectionName = $connectionName;
        $this->classHandler   = $classHandler;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->id();
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        $data = $this->job->data();
        if (!empty($data) && $this->isBase64Encoded($data)) {
            $data = base64_decode($data);
        }
        if (!empty($data) && $this->isJson($data)) {
            $data = json_decode($data, true);
        }
        $fullData = [
            'data'       => $data ?? [],
            'attributes' => $this->job->attributes() ?? []
        ];
        $newArray = [
            'job'  => $this->classHandler . '@handle',
            'data' => $fullData,
            'uuid' => Uuid::uuid4()
        ];

        return json_encode($newArray);
    }

    public function isJson(string $string) : bool
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function isBase64Encoded(string $data) : bool
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->pubsub->acknowledge($this->job, $this->queue);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return ((int) $this->job->attribute('attempts') ?? 0) + 1;
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     *
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $attempts = $this->attempts();
        $this->pubsub->acknowledgeAndPublish(
            $this->job,
            $this->queue,
            ['attempts' => (string) $attempts],
            $delay
        );
    }
}
