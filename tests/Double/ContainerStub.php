<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Psr\Container\ContainerInterface;

class ContainerStub implements ContainerInterface
{
    private $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function get($id)
    {
        return $this->service;
    }

    public function has($id)
    {
        // TODO: Implement has() method.
    }
}
