<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp\Subscriber;

class AmqpSubscriberConfig
{
    /**
     * @var array
     */
    private $credentials;

    /**
     * @var string
     */
    private $name;

    public function __construct(array $credentials, string $name)
    {
        $this->credentials = $credentials;
        $this->name        = $name;
    }

    public function credentials(): array
    {
        return $this->credentials;
    }

    public function name(): string
    {
        return $this->name;
    }
}
