<?php

namespace App\Enum;

enum BamboraBatchStatus:string{
    case CREATED = "CREATED";
    case COMPLETED = "COMPLETED";
    case PENDING_BAMBORA_PROCESSING  = "PENDING_BAMBORA_PROCESSING";
}