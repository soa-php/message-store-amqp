<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\Clock\Clock;
use Soa\MessageStore\Loop\LoopInterface;
use Soa\MessageStore\Publisher\StoredMessage;

class LoopPublishMessages implements LoopInterface
{
    /**
     * @var callable
     */
    private $runFunction;

    /**
     * @var StoredMessage[]
     */
    private $subscribedMessages;

    /**
     * @var \AMQPExchange[]
     */
    private $publishers;

    /**
     * @var array
     */
    private $notSubscribedMessages;

    public function __construct(callable $runFunction, array $subscribedMessages, array $notSubscribedMessages, array $publishers)
    {
        $this->runFunction           = $runFunction;
        $this->subscribedMessages    = $subscribedMessages;
        $this->publishers            = $publishers;
        $this->notSubscribedMessages = $notSubscribedMessages;
    }

    public function run(): void
    {
        foreach ($this->subscribedMessages as $subscribedMessage) {
            $this->publishers[$subscribedMessage->recipient()]->publish(
                json_encode($subscribedMessage->body()),
                $subscribedMessage->type(),
                AMQP_NOPARAM,
                [
                    'type'           => $subscribedMessage->type(),
                    'timestamp'      => $subscribedMessage->occurredOn()->getTimestamp(),
                    'message_id'     => $subscribedMessage->id(),
                    'correlation_id' => $subscribedMessage->correlationId(),
                    'headers'        => [
                        'stream_id'    => $subscribedMessage->streamId(),
                        'causation_id' => $subscribedMessage->causationId(),
                        'occurred_on'  => $subscribedMessage->occurredOn()->format(Clock::MICROSECONDS_FORMAT),
                        'process_id'   => $subscribedMessage->processId(),
                    ],
                    'reply_to'       => $subscribedMessage->replyTo(),
                    'delivery_mode'  => AMQP_DURABLE,
                ]
            );

            $runFunction = $this->runFunction;
            $runFunction();
        }

        foreach ($this->notSubscribedMessages as $notSubscribedMessage) {
            $this->publishers[$notSubscribedMessage->recipient()]->publish(
                json_encode($notSubscribedMessage->body()),
                $notSubscribedMessage->type(),
                AMQP_NOPARAM,
                [
                    'type'           => $notSubscribedMessage->type(),
                    'timestamp'      => $notSubscribedMessage->occurredOn()->getTimestamp(),
                    'message_id'     => $notSubscribedMessage->id(),
                    'correlation_id' => $notSubscribedMessage->correlationId(),
                    'headers'        => [
                        'stream_id'    => $notSubscribedMessage->streamId(),
                        'causation_id' => $notSubscribedMessage->causationId(),
                        'occurred_on'  => $notSubscribedMessage->occurredOn()->format(Clock::MICROSECONDS_FORMAT),
                        'process_id'   => $notSubscribedMessage->processId(),
                    ],
                    'reply_to'       => $notSubscribedMessage->replyTo(),
                    'delivery_mode'  => AMQP_DURABLE,
                ]
            );
        }
    }
}
