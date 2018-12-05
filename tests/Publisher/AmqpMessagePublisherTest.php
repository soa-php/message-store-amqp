<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Publisher;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Soa\MessageStore\Publisher\MessageDeliveryService;
use Soa\MessageStore\Publisher\PublisherApplication;
use Soa\MessageStoreAmqp\AmqpArtifactsBuilder;
use Soa\MessageStoreAmqp\Publisher\AmqpMessagePublisher;
use Soa\MessageStoreAmqp\Publisher\AmqpPublisherConfig;
use Soa\MessageStoreAmqpTest\Double\AmqpTestUtils;
use Soa\MessageStoreAmqpTest\Double\LoopFactoryFiniteIterations;
use Soa\MessageStoreAmqpTest\Double\MessageStoreInMemory;
use Soa\MessageStoreAmqpTest\Double\PublishedMessageTrackerInMemory;
use Soa\MessageStoreAmqpTest\Double\StoredMessageObjectMother;

class AmqpMessagePublisherTest extends TestCase
{
    use AmqpArtifactsBuilder;

    public function setUp()
    {
        AmqpTestUtils::clean();
    }

    /**
     * @test
     */
    public function shouldCreateExchanges()
    {
        $isExceptionThrownIfExchangesDontExist = false;

        $exchange1 = 'exchange1';
        $exchange2 = 'exchange2';

        try {
            $channel           = $this->buildChannel(AmqpTestUtils::credentials());
            $queueSpyExchange1 = $this->buildQueue($channel, 'queue1', []);
            $queueSpyExchange1->bind($exchange1, '#');
        } catch (\AMQPQueueException $exception) {
            $isExceptionThrownIfExchangesDontExist = true;
        }

        $this->assertTrue($isExceptionThrownIfExchangesDontExist);

        $publisherApp = new PublisherApplication(
            new AmqpMessagePublisher(new AmqpPublisherConfig('publisher', AmqpTestUtils::credentials(), [$exchange1, $exchange2])),
            new MessageDeliveryService(MessageStoreInMemory::withStoredMessages([]), new PublishedMessageTrackerInMemory([])),
            new NullLogger(),
            new LoopFactoryFiniteIterations(1)
        );

        $publisherApp->startPublishing();

        $channel           = $this->buildChannel(AmqpTestUtils::credentials());
        $queueSpyExchange1 = $this->buildQueue($channel, 'queue1', []);
        $queueSpyExchange1->bind($exchange1, '#');
    }

    /**
     * @test
     */
    public function shouldPublishMessages()
    {
        $exchange1  = 'exchange1';
        $exchange2  = 'exchange2';
        $routingKey = 'a.routing.key';

        $channel = $this->buildChannel(AmqpTestUtils::credentials());
        $this->buildExchange($channel, $exchange1);
        $this->buildExchange($channel, $exchange2);
        $queueSpyExchange1 = $this->buildQueue($channel, 'queue1', []);
        $queueSpyExchange1->bind($exchange1, $routingKey);
        $queueSpyExchange2 = $this->buildQueue($channel, 'queue2', []);
        $queueSpyExchange2->bind($exchange2, '#');

        $messagesForExchange1 = [
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType($routingKey),
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType($routingKey),
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType($routingKey),
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType($routingKey),
        ];

        $messagesWithWrongRoutingKeyForExchange1 = [
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType('wrong.routing.key'),
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType('wrong.routing.key'),
            StoredMessageObjectMother::create()->withRecipient($exchange1)->withType('wrong.routing.key'),
        ];

        $messagesForExchange2 = [
            StoredMessageObjectMother::create()->withRecipient($exchange2),
            StoredMessageObjectMother::create()->withRecipient($exchange2),
        ];

        $publisherApp = new PublisherApplication(
            new AmqpMessagePublisher(new AmqpPublisherConfig('publisher', AmqpTestUtils::credentials(), [$exchange1, $exchange2])),
            new MessageDeliveryService(MessageStoreInMemory::withStoredMessages(array_merge($messagesWithWrongRoutingKeyForExchange1, $messagesForExchange1, $messagesForExchange2)), new PublishedMessageTrackerInMemory([])),
            new NullLogger(),
            new LoopFactoryFiniteIterations(1)
        );

        $publisherApp->startPublishing();

        foreach ($messagesForExchange1 as $message) {
            $this->assertInstanceOf(\AMQPEnvelope::class, $queueSpyExchange1->get());
        }

        $this->assertFalse($queueSpyExchange1->get());

        foreach ($messagesForExchange2 as $message) {
            $this->assertInstanceOf(\AMQPEnvelope::class, $queueSpyExchange2->get());
        }

        $this->assertFalse($queueSpyExchange2->get());
    }
}
