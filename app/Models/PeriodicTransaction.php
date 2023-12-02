<?php

namespace App\Models;

use App\Traits\HasCustomId;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodicTransaction extends Model
{
    use HasFactory, HasCustomId;
    protected string $id_prefix = "periodic_transaction_";
    protected $guarded = [];
    public $incrementing = false;
    
    public function getNextDate(){
        $next_date = Carbon::createFromFormat("Y-m-d",date("Y-m-d"));
        
        $age_in_days = Carbon::createFromFormat("Y-m-d",$this->started_on)->diffInDays($next_date);
        $remainder = $age_in_days%$this->frequency_in_days;
        if($remainder>0){
            $next_date = $next_date->addDays($this->frequency_in_days - $remainder);
        }
        
        return $next_date;
    }
}
