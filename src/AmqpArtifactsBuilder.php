<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp;

trait AmqpArtifactsBuilder
{
    private function buildQueue(\AMQPChannel $channel, string $queueName, array $arguments): \AMQPQueue
    {
        $queue = new \AMQPQueue($channel);
        $queue->setFlags(AMQP_DURABLE);
        $queue->setName($queueName);
        $queue->setArguments($arguments);
        $queue->declareQueue();

        return $queue;
    }

    private function buildExchange(\AMQPChannel $channel, string $exchangeName): \AMQPExchange
    {
        $exchange = new \AMQPExchange($channel);
        $exchange->setType(AMQP_EX_TYPE_TOPIC);
        $exchange->setName($exchangeName);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();

        return $exchange;
    }

    private function buildChannel(array $credentials): \AMQPChannel
    {
        $connection = new \AMQPConnection($credentials);
        $connection->connect();

        return new \AMQPChannel($connection);
    }
}
