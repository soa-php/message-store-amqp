<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Publisher\StoredMessage;

class MessageDummy extends StoredMessage
{
    public function __construct()
    {
    }
}
