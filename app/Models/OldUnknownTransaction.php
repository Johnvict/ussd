<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldUnknownTransaction extends Model
{
    protected $fillable = ["id","data"];

    public function getDataAttribute()
    {
        return json_decode($this->attributes['data']);
    }
}
