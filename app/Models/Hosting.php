<?php

namespace App\Models;

use App\Enums\HostingStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'name',
        'customer_id',
        'project_id',
        'annual_payment',
        'domain',
        'expiration_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => HostingStatus::class,
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}

}