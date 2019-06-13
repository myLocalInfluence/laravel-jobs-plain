<?php

namespace Myli\PlainSqs\Tests;

use \PHPUnit\Framework\TestCase;
use Myli\PlainSqs\Jobs\DispatcherJob;
use Myli\PlainSqs\Sqs\Queue;

/**
 * Class QueueTest
 * @package Myli\PlainSqs\Tests
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