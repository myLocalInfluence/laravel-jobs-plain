<?php

namespace Myli\PlainJobs\PubSub;

use Illuminate\Queue\Jobs\Job as IlluminateJob;
use Google\Cloud\PubSub\Message;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

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
        $fullData = [
            'data' => json_decode(base64_decode($this->job->data()), true),
            'attributes' => $this->job->attributes()
        ];
        $newArray = [
            'job'  => $this->classHandler . '@handle',
            'data' => $fullData,
        ];

        return json_encode($newArray);
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
     * @param  int $delay
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
