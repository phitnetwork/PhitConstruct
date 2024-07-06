<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'code', 'used'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
