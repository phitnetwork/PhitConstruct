<?php

namespace App\Models;

use App\Enums\PriorityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'project_id',
        'priority',
        'status',
        'problem_notes',
        'is_solved',
        'resolution_notes',
        'created_by',
        'assigned_to',
        'bug_type',
        'software_version',
        'environment',
        'steps_to_reproduce',
        'attachments',
        'labels',
        'deadline',
    ];

    protected $casts = [
        'attachments' => 'array',
        'labels' => 'array',
        'is_solved' => 'boolean',
        'deadline' => 'date',
        'priority' => PriorityStatus::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relazione con l'utente che ha creato il bug
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relazione con l'utente assegnato al bug
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
