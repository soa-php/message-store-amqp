<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp\Subscriber;

use Soa\Clock\Clock;
use Soa\MessageStore\Message;
use Soa\MessageStore\Subscriber\Listener\MessageRouter;
use Soa\MessageStore\Subscriber\MessageSubscriber;
use Soa\MessageStoreAmqp\AmqpArtifactsBuilder;
use Soa\MessageStoreAmqp\Subscriber\Error\AmqpErrorMessageHandler;

class AmqpMessageSubscriber implements MessageSubscriber
{
    use AmqpArtifactsBuilder;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var AmqpErrorMessageHandler
     */
    private $errorHandler;

    /**
     * @var MessageRouter
     */
    private $router;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * @var AmqpSubscriberConfig
     */
    private $config;

    public function __construct(MessageRouter $router, AmqpSubscriberConfig $config, AmqpErrorMessageHandler $errorHandler)
    {
        $this->errorHandler    = $errorHandler;
        $this->router          = $router;
        $this->config          = $config;
    }

    public function connect(): void
    {
        $this->channel      = $this->buildChannel($this->config->credentials());
        $deadLetterExchange = $this->buildExchange($this->channel, $this->config->name() . '_dead_letter_exchange');
        $deadLetterQueue    = $this->buildQueue($this->channel, $this->config->name() . '_dead_letter_queue', []);
        $deadLetterQueue->bind($deadLetterExchange->getName(), '#');
        $this->queue = $this->buildQueue($this->channel, $this->config->name(), ['x-dead-letter-exchange' => $deadLetterExchange->getName()]);
    }

    public function subscribeTo(string $exchange, array $routingKeys): void
    {
        $this->buildExchange($this->channel, $exchange);
        foreach ($routingKeys as $routingKey) {
            $this->queue->bind($exchange, $routingKey);
        }
    }

    public function onConsume(): void
    {
        $adaptMessageFn = function (\AMQPEnvelope $envelope, \AMQPQueue $queue) {
            $message     = (new Message(
                $envelope->getType(),
                \DateTimeImmutable::createFromFormat(Clock::MICROSECONDS_FORMAT, $envelope->getHeader('occurred_on'))->format(Clock::MICROSECONDS_FORMAT),
                json_decode($envelope->getBody(), true),
                $envelope->getHeader('stream_id'),
                $envelope->getCorrelationId(),
                $envelope->getHeader('causation_id'),
                $envelope->getReplyTo(),
                $envelope->getMessageId(),
                $envelope->getExchangeName(),
                $envelope->getHeader('process_id')
            ));

            try {
                $this->router->dispatch($message);

                $queue->ack($envelope->getDeliveryTag());

                return false;
            } catch (\Throwable $exception) {
                $this->errorHandler->injectQueueAndEnvelope($queue, $envelope);
                $this->errorHandler->handleMessage($message);

                return false;
            }
        };

        $this->queue->consume($adaptMessageFn, AMQP_NOPARAM);
    }

    public function nextMessage()
    {
        return $this->queue->get();
    }

    public function disconnect(): void
    {
        $this->channel->getConnection()->disconnect();
    }
}
