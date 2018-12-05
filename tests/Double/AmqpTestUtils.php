<?php

declare(strict_types=1);

namespace Soa\MessageStoreAmqpTest\Double;

class AmqpTestUtils
{
    private const HOST = 'rabbitmq';

    private const V_HOST = 'devhost';

    private const USER = 'devuser';

    private const PASS = 'devpass';

    public static function credentials(): array
    {
        return [
            'host'     => self::HOST,
            'vhost'    => self::V_HOST,
            'login'    => self::USER,
            'password' => self::PASS,
        ];
    }

    public static function clean(): void
    {
        $credentials = sprintf('rabbitmqadmin -H %s --vhost=%s -u %s -p %s', self::HOST, self::V_HOST, self::USER, self::PASS);
        exec("{$credentials} -f bash list queues | xargs -n1 | xargs -I{} {$credentials} delete queue name={}");
        exec("{$credentials} -f bash list exchanges | xargs -n1 | xargs -I{} {$credentials} delete exchange name={}");
        exec("{$credentials} -f bash list connections | xargs -n1 | xargs -I{} {$credentials} close connection name={}");
    }
}
