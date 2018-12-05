<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqp\Publisher;

class AmqpPublisherConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var array
     */
    private $availableExchanges;

    public function __construct(string $name, array $credentials, array $availableExchanges)
    {
        $this->name               = $name;
        $this->credentials        = $credentials;
        $this->availableExchanges = $availableExchanges;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function credentials(): array
    {
        return $this->credentials;
    }

    public function availableExchangesToPublish(): array
    {
        return $this->availableExchanges;
    }
}
