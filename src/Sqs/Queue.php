<?php

namespace Myli\PlainJobs\Sqs;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Config;
use Myli\PlainJobs\Jobs\DispatcherJob;

/**
 * Class Queue
 *
 * @package App\Services
 */
class Queue extends SqsQueue
{
    /**
     * Create a payload string from the given job and data.
     *
     * @param string $job
     * @param mixed  $data
     * @param string $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        if (!$job instanceof DispatcherJob) {
            return parent::createPayload($job, $data, $queue);
        }

        return json_encode($job->getPayload());
    }

    /**
     * @param $queue
     * @return string
     */
    private function getClass($queue = null)
    {
        if (!$queue) {
            return Config::get('laravel-jobs-plain.sqs-plain.default-handler');
        }
        $queue = explode('/', $queue);
        $queue = end($queue);

        return (array_key_exists($queue, Config::get('laravel-jobs-plain.sqs-plain.handlers')))
            ? Config::get('laravel-jobs-plain.sqs-plain.handlers')[$queue]
            : Config::get('laravel-jobs-plain.sqs-plain.default-handler');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl'       => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if ($response->get('Messages') !== null && count($response->get('Messages')) > 0) {
            $class    = $this->getClass($queue);
            $response = $this->modifyPayload($response->get('Messages')[0], $class);

            if (preg_match('/5\.[4-8]\..*/', $this->container->version())) {
                return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
            }

            return new SqsJob($this->container, $this->sqs, $queue, $response);
        }
    }

    /**
     * @param string|array $payload
     * @param string       $class
     * @return array
     */
    private function modifyPayload($payload, $class)
    {
        if (!is_array($payload)) {
            $payload = json_decode($payload, true);
        }

        $body = json_decode($payload['Body'], true);

        $body = [
            'job'  => $class . '@handle',
            'data' => isset($body['data']) ? $body['data'] : $body,
        ];

        $payload['Body'] = json_encode($body);

        return $payload;
    }

    /**
     * @param string $payload
     * @param null   $queue
     * @param array  $options
     * @return mixed|null
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = json_decode($payload, true);

        if (isset($payload['data']) && isset($payload['job'])) {
            $payload = $payload['data'];
        }

        return parent::pushRaw(json_encode($payload), $queue, $options);
    }
}
