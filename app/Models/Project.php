<?php

namespace App\Models;

use App\Enums\PriorityStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'organization_id',

        'name',
        'description',
        'customer_id',
        'deadline',
        'estimated_hours_client',
        'status',
        'project_type',
        'budget',
        'prepayment_percentage',
        'prepayment_amount',
        'hours_worked',
        'priority',
        'milestones',
        'attachments',
        'notes',
    ];

    protected $casts = [
        'milestones' => 'array',  // Casting the milestones attribute to an array
        'attachments' => 'array', // Casting the attachments attribute to an array (if used)
        'deadline' => 'date',
        'budget' => 'decimal:2',
        'priority' => PriorityStatus::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}
}