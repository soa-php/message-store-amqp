<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp\Subscriber\Error;

use Soa\MessageStore\Message;
use Soa\MessageStore\Subscriber\Error\ErrorMessageHandler;

class AmqpErrorMessageHandler extends ErrorMessageHandler
{
    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var \AMQPEnvelope
     */
    private $envelope;

    public function injectQueueAndEnvelope(\AMQPQueue $queue, \AMQPEnvelope $envelope)
    {
        $this->queue    = $queue;
        $this->envelope = $envelope;
    }

    protected function requeueMessage(Message $message): void
    {
        $this->queue->reject($this->envelope->getDeliveryTag(), AMQP_REQUEUE);
    }

    protected function deadLetterMessage(Message $message): void
    {
        $this->queue->nack($this->envelope->getDeliveryTag());
    }
}
