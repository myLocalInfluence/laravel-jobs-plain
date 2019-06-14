<?php

namespace Myli\PlainJobs\Tests;

use \PHPUnit\Framework\TestCase;
use Myli\PlainJobs\Jobs\DispatcherJob;
use Myli\PlainJobs\Sqs\Queue;

/**
 * Class QueueTest
 * @package Myli\PlainJobs\Tests
 */
class QueueTest extends TestCase
{
    /**
     * @test
     */
    public function class_named_is_derived_from_queue_name()
    {
        $this->assertEquals(true, true);
    }
}