<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasCustomId{
    
    public function generateUniqueId()
    {
        $id_length = $this->id_length ?? 16;
        $id_prefix = $this->id_prefix ?? "id_";
        $id = $id_prefix.Str::random($id_length);
        return self::whereId($id)->exists() ? $this->generateUniqueId() : $id; 
    }
}