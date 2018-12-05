<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Message;
use Soa\MessageStore\Subscriber\Listener\MessageListener;

class MessageListenerSpy implements MessageListener
{
    /**
     * @var int
     */
    private $timesHandleCalled = 0;

    public function handle(Message $message): void
    {
        ++$this->timesHandleCalled;
    }

    public function timesHandleCalled(): int
    {
        return $this->timesHandleCalled;
    }
}
