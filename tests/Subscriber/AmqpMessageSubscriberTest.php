<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Subscriber;

use PHPUnit\Framework\TestCase;
use Soa\Clock\ClockFake;
use Soa\MessageStore\Subscriber\Listener\MessageRouter;
use Soa\MessageStore\Subscriber\SubscriberApplication;
use Soa\MessageStoreAmqp\AmqpArtifactsBuilder;
use Soa\MessageStoreAmqp\Subscriber\AmqpMessageSubscriber;
use Soa\MessageStoreAmqp\Subscriber\AmqpSubscriberConfig;
use Soa\MessageStoreAmqp\Subscriber\Error\AmqpErrorMessageHandler;
use Soa\MessageStoreAmqpTest\Double\AmqpTestUtils;
use Soa\MessageStoreAmqpTest\Double\ContainerStub;
use Soa\MessageStoreAmqpTest\Double\ErrorMessageTimeoutTrackerStub;
use Soa\MessageStoreAmqpTest\Double\LoopFactoryPublishMessages;
use Soa\MessageStoreAmqpTest\Double\MessageListenerException;
use Soa\MessageStoreAmqpTest\Double\MessageListenerSpy;
use Soa\MessageStoreAmqpTest\Double\StoredMessageObjectMother;

class AmqpMessageSubscriberTest extends TestCase
{
    use AmqpArtifactsBuilder;

    public function setUp()
    {
        AmqpTestUtils::clean();
    }

    /**
     * @test
     */
    public function shouldSendMessagesToDeadLetter()
    {
        $messageListener     = new MessageListenerException();
        $trackedTimestamp    = new \DateTimeImmutable('2019-01-01 00:00:00');
        $exchange1           = 'exchange1';
        $exchange2           = 'exchange2';
        $routingKey          = 'a.routing.key';
        $anotherRoutingKey   = 'another.routing.key';

        $channel                        = $this->buildChannel(AmqpTestUtils::credentials());
        $exchangesPublisher             = [];
        $exchangesPublisher[$exchange1] = $this->buildExchange($channel, $exchange1);

        $messagesForExchange1 = [
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange1),
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange1),
        ];

        $messageRouter = new MessageRouter(new ContainerStub($messageListener));
        $consumerName  = 'consumer';
        $subscriber1   = new AmqpMessageSubscriber(
            $messageRouter,
            new AmqpSubscriberConfig(AmqpTestUtils::credentials(), $consumerName),
            new AmqpErrorMessageHandler(
                ErrorMessageTimeoutTrackerStub::withTrackedAt($trackedTimestamp),
                new ClockFake('2019-01-01 00:00:06.358'),
                5
            )
        );
        $subscriber = new SubscriberApplication(
            $messageRouter,
            $subscriber1,
            new LoopFactoryPublishMessages($messagesForExchange1, [], $exchangesPublisher)
        );

        $subscriber->addSubscription($exchange1, $routingKey, 'a listener');
        $subscriber->addSubscription($exchange2, $routingKey, 'a listener');
        $subscriber->addSubscription($exchange2, $anotherRoutingKey, 'a listener');

        $subscriber->startConsuming();
        usleep(500000);
        $queue = $this->buildQueue($channel, $consumerName . '_dead_letter_queue', []);
        foreach ($messagesForExchange1 as $message) {
            $this->assertInstanceOf(\AMQPEnvelope::class, $queue->get());
        }
        $this->assertFalse($queue->get());
    }

    /**
     * @test
     */
    public function shouldConsumeMessages()
    {
        $messageListener     = new MessageListenerSpy();
        $trackedTimestamp    = new \DateTimeImmutable('2019-01-01 00:00:00');
        $exchange1           = 'exchange1';
        $exchange2           = 'exchange2';
        $routingKey          = 'a.routing.key';
        $anotherRoutingKey   = 'another.routing.key';

        $channel                        = $this->buildChannel(AmqpTestUtils::credentials());
        $exchangesPublisher             = [];
        $exchangesPublisher[$exchange1] = $this->buildExchange($channel, $exchange1);
        $exchangesPublisher[$exchange2] = $this->buildExchange($channel, $exchange2);

        $messagesForExchange1 = [
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange1),
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange1),
        ];

        $messagesWithWrongRoutingKeyForExchange1 = [
            StoredMessageObjectMother::create()->withType('wrong.routing.key')->withRecipient($exchange1),
            StoredMessageObjectMother::create()->withType('wrong.routing.key')->withRecipient($exchange1),
        ];

        $messagesForExchange2 = [
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange2),
            StoredMessageObjectMother::create()->withType($routingKey)->withRecipient($exchange2),
            StoredMessageObjectMother::create()->withType($anotherRoutingKey)->withRecipient($exchange2),
            StoredMessageObjectMother::create()->withType($anotherRoutingKey)->withRecipient($exchange2),
        ];

        $correctMessages = array_merge($messagesForExchange1, $messagesForExchange2);

        $messageRouter = new MessageRouter(new ContainerStub($messageListener));
        $consumerName  = 'consumer';
        $subscriber1   = new AmqpMessageSubscriber(
            $messageRouter,
            new AmqpSubscriberConfig(AmqpTestUtils::credentials(), $consumerName),
            new AmqpErrorMessageHandler(
                ErrorMessageTimeoutTrackerStub::withTrackedAt($trackedTimestamp),
                new ClockFake('2019-01-01 00:00:01.358'),
                100
            )
        );
        $subscriber = new SubscriberApplication(
            $messageRouter,
            $subscriber1,
            new LoopFactoryPublishMessages($correctMessages, $messagesWithWrongRoutingKeyForExchange1, $exchangesPublisher)
        );

        $subscriber->addSubscription($exchange1, $routingKey, 'a listener');
        $subscriber->addSubscription($exchange2, $routingKey, 'a listener');
        $subscriber->addSubscription($exchange2, $anotherRoutingKey, 'a listener');

        $subscriber->startConsuming();

        $this->assertEquals(count($messagesForExchange1) + count($messagesForExchange2), $messageListener->timesHandleCalled());
        $this->assertFalse($subscriber1->nextMessage());
    }
}
