<?php

namespace Myli\PlainSqs\Tests;
use Aws\Sqs\SqsClient;
use Myli\PlainSqs\Jobs\DispatcherJob;
use Myli\PlainSqs\Sqs\Queue;

/**
 * Class QueueTest
 * @package Myli\PlainSqs\Tests
 */
class QueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function class_named_is_derived_from_queue_name()
    {

        $content = [
            'test' => 'test'
        ];

        $job = new DispatcherJob($content);

        $queue = $this->getMockBuilder(Queue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $method = new \ReflectionMethod(
            'Myli\PlainSqs\Sqs\Queue', 'createPayload'
        );

        $method->setAccessible(true);

        //$response = $method->invokeArgs($queue, [$job]);
    }
}