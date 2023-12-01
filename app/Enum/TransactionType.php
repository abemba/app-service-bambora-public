<?php

namespace App\Enum;

enum TransactionType: string{
    case DEBIT = "debit";
    case CREDIT = "credit";

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getBamboraType(){
        switch ($this){
            case TransactionType::DEBIT:
                return "D";
                break;
            case TransactionType::CREDIT:
                return "C";
                break;
        }
        return "D";
    }
}