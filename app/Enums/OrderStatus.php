<?php

namespace App\Enums;

final class OrderStatus
{
    public const PENDING  = 'pending';
    public const OPEN     = 'open';
    public const RUNNING  = 'running';
    public const CASHIER  = 'cashier';
    public const PAID     = 'paid';
    public const CLOSED   = 'closed';
    // add others you use: preparing, prepared, etc.

}
