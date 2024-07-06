<?php

namespace App\Models;

use App\Enums\PriorityStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'title',
        'description',
        'is_completed',
        'priority',
        'category',
        'project_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'priority' => PriorityStatus::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function organization(): BelongsTo
	{
		return $this->belongsTo(Organization::class);
	}
}
