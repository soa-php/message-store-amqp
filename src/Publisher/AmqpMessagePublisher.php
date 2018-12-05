<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp\Publisher;

use Soa\Clock\Clock;
use Soa\MessageStore\Publisher\MessagePublisher;
use Soa\MessageStore\Publisher\StoredMessage;
use Soa\MessageStoreAmqp\AmqpArtifactsBuilder;

class AmqpMessagePublisher implements MessagePublisher
{
    use AmqpArtifactsBuilder;

    /**
     * @var \AMQPExchange[]
     */
    private $exchanges;

    /**
     * @var AmqpPublisherConfig
     */
    private $config;

    public function __construct(AmqpPublisherConfig $config)
    {
        $this->config = $config;
    }

    public function connect(): void
    {
        $channel = $this->buildChannel($this->config->credentials());

        foreach ($this->config->availableExchangesToPublish() as $exchange) {
            $this->exchanges[$exchange] = $this->buildExchange($channel, $exchange);
        }
    }

    public function publishMessages(array $storedMessages): int
    {
        foreach ($storedMessages as $storedMessage) {
            $this->publish($storedMessage);
        }

        return count($storedMessages);
    }

    private function publish(StoredMessage $storedMessage): void
    {
        $this->exchanges[$storedMessage->recipient()]->publish(
            json_encode($storedMessage->body()),
            $storedMessage->type(),
            AMQP_NOPARAM,
            [
                'type'           => $storedMessage->type(),
                'timestamp'      => $storedMessage->occurredOn()->getTimestamp(),
                'message_id'     => $storedMessage->id(),
                'correlation_id' => $storedMessage->correlationId(),
                'headers'        => [
                    'stream_id'    => $storedMessage->streamId(),
                    'causation_id' => $storedMessage->causationId(),
                    'occurred_on'  => $storedMessage->occurredOn()->format(Clock::MICROSECONDS_FORMAT),
                    'process_id'   => $storedMessage->processId(),
                ],
                'reply_to'       => $storedMessage->replyTo(),
                'delivery_mode'  => AMQP_DURABLE,
            ]
        );
    }

    public function disconnect(): void
    {
        foreach ($this->exchanges as $exchange) {
            $exchange->getConnection()->disconnect();
        }
    }

    public function name(): string
    {
        return $this->config->name();
    }
}
