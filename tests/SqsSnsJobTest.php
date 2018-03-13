<?php

namespace Zackyjack\LaravelSqsSns\Tests;

use PHPUnit\Framework\TestCase;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Zackyjack\LaravelSqsSns\Queue\Jobs\SqsSnsJob;

class SqsSnsJobTest extends TestCase
{
    private $sqsClient;
    private $container;

    protected function setUp()
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->createMock(Container::class);
    }

    private function createSqsSnsJob($routes = [])
    {
        $body = [
            'TopicArn' => 'TopicArn:123456',
            'Subject' => 'Subject#action',
            'Message' => 'The Message',
        ];
        $payload = [
            'Body' => json_encode($body),
        ];

        return new SqsSnsJob(
            $this->container,
            $this->sqsClient,
            $payload,
            'connection_name',
            'default_queue',
            $routes
        );
    }

    private function getSqsSnsJobSubjectRoute()
    {
        return $this->createSqsSnsJob([
            'Subject#action' => '\\stdClass',
        ]);
    }

    private function getSqsSnsJobTopicRoute()
    {
        return $this->createSqsSnsJob([
            'TopicArn:123456' => '\\stdClass',
        ]);
    }

    public function testWillResolveSqsSubscriptionJob()
    {
        $jobPayload = $this->getSqsSnsJobSubjectRoute()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    public function testWillResolveSqsSubscriptionCommandName()
    {
        $jobPayload = $this->getSqsSnsJobSubjectRoute()->payload();

        $this->assertEquals('\\stdClass', $jobPayload['data']['commandName']);
    }

    public function testWillResolveSqsSubscriptionCommand()
    {
        $jobPayload = $this->getSqsSnsJobSubjectRoute()->payload();
        $expectedCommand = serialize(new \stdClass);

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    public function testWillResolveSqsSubscriptionJobTopicRoute()
    {
        $jobPayload = $this->getSqsSnsJobTopicRoute()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    public function testWillResolveSqsSubscriptionCommandNameTopicRoute()
    {
        $jobPayload = $this->getSqsSnsJobTopicRoute()->payload();

        $this->assertEquals('\\stdClass', $jobPayload['data']['commandName']);
    }

    public function testWillResolveSqsSubscriptionCommandTopicRoute()
    {
        $jobPayload = $this->getSqsSnsJobTopicRoute()->payload();
        $expectedCommand = serialize(new \stdClass);

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    public function testWillLeaveDefaultSqsJobUntouched()
    {
        $body = [
            'Message' => 'The Message',
        ];

        $defaultSqsJob = new SqsSnsJob(
            $this->container,
            $this->sqsClient,
            [
                'Body' => json_encode($body),
            ],
            'connection_name',
            'default_queue',
            []
        );

        $jobPayload = $defaultSqsJob->payload();

        $this->assertEquals($body, $jobPayload);
    }
}
