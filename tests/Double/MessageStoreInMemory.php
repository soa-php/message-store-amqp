<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Message;
use Soa\MessageStore\MessageStore;
use Soa\MessageStore\Publisher\StoredMessage;

class MessageStoreInMemory implements MessageStore
{
    /**
     * @var StoredMessage[]
     */
    private $messages;

    public static function withStoredMessages(array $messages): self
    {
        return new self($messages);
    }

    private function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    public function appendMessages(Message ...$messages): void
    {
    }

    public function messagesSince(int $offset): array
    {
        return array_slice($this->messages, $offset);
    }
}
