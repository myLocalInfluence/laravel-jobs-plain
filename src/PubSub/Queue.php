<?php

namespace Myli\PlainJobs\PubSub;

use Illuminate\Queue\Queue as IlluminateQueue;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Myli\PlainJobs\Jobs\DispatcherJob;
use Illuminate\Support\Facades\Config;

class Queue extends IlluminateQueue implements QueueContract
{
    /**
     * The PubSubClient instance.
     *
     * @var \Google\Cloud\PubSub\PubSubClient
     */
    protected $pubsub;

    /**
     * Default queue name.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new GCP PubSub instance.
     *
     * @param \Google\Cloud\PubSub\PubSubClient $pubsub
     * @param string                            $default
     */
    public function __construct(PubSubClient $pubsub, string $default = null)
    {
        $this->pubsub  = $pubsub;
        $this->default = $default;
    }

    /**
     * Get the size of the queue.
     * PubSubClient have no method to retrieve the size of the queue.
     * To be updated if the API allow to get that data.
     *
     * @param string $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed         $data
     * @param string        $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $queue, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string $queue
     * @param array  $options
     * @return array
     */
    public function pushRaw($payload, $queue = null, array $options = null)
    {
        $topic = $this->getTopic($queue);

        return $topic->publish([
            'data'       => $payload,
            'attributes' => $options,
        ]);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string|object                        $job
     * @param mixed                                $data
     * @param string                               $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushRaw(
            $this->createPayload($job, $queue, $data),
            $queue,
            ['available_at' => (string) $this->availableAt($delay)]
        );
    }

    /**
     * @param $queue
     * @return string
     */
    private function getClass($queue = null)
    {
        if (!$queue) {
            return Config::get('laravel-jobs-plain.pubsub-plain.default-handler');
        }
        $queue = explode('/', $queue);
        $queue = end($queue);

        return (array_key_exists($queue, Config::get('laravel-jobs-plain.pubsub-plain.handlers')))
            ? Config::get('laravel-jobs-plain.pubsub-plain.handlers')[$queue]
            : Config::get('laravel-jobs-plain.pubsub-plain.default-handler');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $topic = $this->getTopic($queue);

        $subscription = $this->subscribeToTopic($topic);

        $messages = $subscription->pull([
            'returnImmediately' => true,
            'maxMessages'       => 1,
        ]);
        if (!empty($messages) && count($messages) > 0) {
            $class = $this->getClass($queue);

            return new Job(
                $this->container,
                $this,
                $messages[0],
                $class,
                $this->connectionName,
                $queue
            );
        }
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array  $jobs
     * @param mixed  $data
     * @param string $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $payloads = [];
        foreach ((array) $jobs as $job) {
            $payloads[] = ['data' => $this->createPayload($job, $queue, $data)];
        }
        $topic = $this->getTopic($queue);
        $this->subscribeToTopic($topic);

        return $topic->publishBatch($payloads);
    }

    /**
     * Acknowledge a message.
     *
     * @param \Google\Cloud\PubSub\Message $message
     * @param string                       $queue
     */
    public function acknowledge(Message $message, $queue = null)
    {
        $topic = $this->getTopic($queue);
        ($topic->subscription($this->getSubscriberName($topic)))->acknowledge($message);
    }

    /**
     * Acknowledge a message and republish it onto the queue.
     *
     * @param \Google\Cloud\PubSub\Message $message
     * @param string                       $queue
     * @return mixed
     */
    public function acknowledgeAndPublish(Message $message, $queue = null, $options = null, $delay = 0)
    {
        $topic        = $this->getTopic($queue);
        $subscription = $topic->subscription($this->getSubscriberName($topic));
        $subscription->acknowledge($message);
        $options = array_merge([
            'available_at' => (string) $this->availableAt($delay),
        ], $options);

        return $topic->publish([
            'data'       => $message->data(),
            'attributes' => $options,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createPayload($job, $queue, $data = '')
    {
        if (!$job instanceof DispatcherJob) {
            return base64_encode(parent::createPayload($job, $data, $queue));
        }

        return base64_encode(json_encode($job->getPayload()));
    }

    /**
     * Get the current topic
     *
     * @param $queue
     * @return Topic
     */
    public function getTopic($queue)
    {
        $queue = $queue ?: $this->default;

        return $this->pubsub->topic($queue);
    }

    /**
     * Create a new subscription to a topic.
     *
     * @param \Google\Cloud\PubSub\Topic $topic
     * @return \Google\Cloud\PubSub\Subscription
     */
    public function subscribeToTopic(Topic $topic)
    {
        return $topic->subscription($this->getSubscriberName($topic));
    }

    /**
     * Get subscriber name, based on topic and app configuration
     * ex: for topic named "custom", and app name "myli" on "prod" environnement, returns "custom-prod-myli-subscriber"
     *
     * @param \Google\Cloud\PubSub\Topic $topic
     * @return string
     */
    public function getSubscriberName(Topic $topic)
    {
        $topicName = $topic->name();

        $buildName = [
            str_replace('topics/', "subscriptions/", $topicName),
            config('app.name'),
            'subscriber',
        ];

        return implode('-', $buildName);
    }

    /**
     * Get the PubSub instance.
     *
     * @return \Google\Cloud\PubSub\PubSubClient
     */
    public function getPubSub()
    {
        return $this->pubsub;
    }
}
