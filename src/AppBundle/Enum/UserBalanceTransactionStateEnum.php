<?php
declare(strict_types=1);

namespace AppBundle\Enum;

class UserBalanceTransactionStateEnum
{
    public const DIRTY = 1;
    public const LOCKED = 2;
    public const ROLLEDBACK = 3;
    public const COMMITED = 4;
    public const FAILED = 5;
}