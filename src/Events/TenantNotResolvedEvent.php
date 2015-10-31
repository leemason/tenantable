<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 31/10/15
 * Time: 22:18
 */

namespace LeeMason\Tenantable\Events;


use LeeMason\Tenantable\Resolver;

class TenantNotResolvedEvent
{
    public $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }
}