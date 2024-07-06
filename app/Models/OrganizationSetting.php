<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'timezone',
        'currency'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
