<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Loop\LoopFactory;
use Soa\MessageStore\Loop\LoopInterface;

class LoopFactoryPublishMessages implements LoopFactory
{
    /**
     * @var array
     */
    private $storedMessages;

    /**
     * @var array
     */
    private $publishers;

    /**
     * @var array
     */
    private $notSubscribedMessages;

    public function __construct(array $storedMessages, array $notSubscribedMessages, array $publishers)
    {
        $this->storedMessages        = $storedMessages;
        $this->publishers            = $publishers;
        $this->notSubscribedMessages = $notSubscribedMessages;
    }

    public function create(int $secondsInterval, callable $runFunction, callable $stopFunction): LoopInterface
    {
        return new LoopPublishMessages($runFunction, $this->storedMessages, $this->notSubscribedMessages, $this->publishers);
    }
}
