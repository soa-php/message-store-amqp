<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Loop\LoopFactory;
use Soa\MessageStore\Loop\LoopInterface;

class LoopFactoryFiniteIterations implements LoopFactory
{
    /**
     * @var int
     */
    private $iterations;

    public function __construct(int $iterations)
    {
        $this->iterations = $iterations;
    }

    public function create(int $secondsInterval, callable $runFunction, callable $stopFunction): LoopInterface
    {
        return new LoopFiniteIterations($this->iterations, $runFunction);
    }
}
