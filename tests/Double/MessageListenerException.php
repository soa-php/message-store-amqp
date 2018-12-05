<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Message;
use Soa\MessageStore\Subscriber\Listener\MessageListener;

class MessageListenerException implements MessageListener
{
    public function handle(Message $message): void
    {
        throw new \Exception();
    }
}
