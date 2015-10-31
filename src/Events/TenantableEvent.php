<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 31/10/15
 * Time: 22:10
 */

namespace LeeMason\Tenantable\Events;

use Illuminate\Queue\SerializesModels;
use LeeMason\Tenantable\Tenant;

abstract class TenantableEvent
{
    use SerializesModels;

    public $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

}