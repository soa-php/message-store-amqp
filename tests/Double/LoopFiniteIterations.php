<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

use Soa\MessageStore\Loop\LoopInterface;

class LoopFiniteIterations implements LoopInterface
{
    /**
     * @var int
     */
    private $iterations;

    /**
     * @var callable
     */
    private $runFunction;

    public function __construct(int $iterations, callable $runFunction)
    {
        $this->iterations  = $iterations;
        $this->runFunction = $runFunction;
    }

    public function run(): void
    {
        for ($i = 0; $i < $this->iterations; ++$i) {
            $runFunction = $this->runFunction;
            $runFunction();
        }
    }
}
