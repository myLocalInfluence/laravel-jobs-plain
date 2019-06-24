<?php

namespace spec\Myli\PlainJobs\PubSub;

use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Queue\Jobs\Job;
use Myli\PlainJobs\Jobs\DispatcherJob;
use Myli\PlainJobs\PubSub\Queue;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueueSpec extends ObjectBehavior
{
    public function let($client)
    {
        $client->beADoubleOf('Google\Cloud\PubSub\PubSubClient');
        $client->topic('queue-test')->willReturn('ok');
        $this->beConstructedWith($client, 'default-queue-name');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Queue::class);
    }

    public function it_should_return_a_queue_size_of_zero()
    {
        $this->size()->shouldReturn(0);
    }
}
