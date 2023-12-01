<?php

namespace App\Models;

use App\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BamboraBatch extends Model
{
    use HasFactory, HasCustomId;
    protected $id_prefix = "bambora_batch_";
    protected $guarded = [];
    
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
