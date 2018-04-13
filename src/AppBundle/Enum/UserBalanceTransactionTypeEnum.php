<?php
declare(strict_types=1);

namespace AppBundle\Enum;

class UserBalanceTransactionTypeEnum
{
    public const DEBIT = 1;
    public const CREDIT = 2;
    public const TRANSFER = 3;
    public const COMMIT = 4;
    public const ROLLBACK = 5;
}