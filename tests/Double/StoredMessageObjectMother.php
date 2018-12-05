<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Publisher\StoredMessage;

class StoredMessageObjectMother
{
    public static function create(): StoredMessage
    {
        return new StoredMessage(
            'a type',
            new \DateTimeImmutable(),
            [],
            'a stream id',
            'a correlation id',
            'a causation id',
            'a reply to',
            'an id',
            0,
            'a recipient',
            'a process id'
        );
    }
}
