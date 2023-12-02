<?php

namespace App\Enum;

enum PeriodicTransactionStatus: string{
    case ACTIVE = "ACTIVE";
    case PAUSED = "PAUSED";
    case COMPLETED = "COMPLETED";
    case CANCELLED = "CANCELLED";
}