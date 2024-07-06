<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    // Specifica esplicitamente il nome della tabella
    protected $table = 'time_entries';

    protected $fillable = [
        'organization_id',
        'description',
        'user_id',
        'project_id',
        'customer_id',
        'start_time',
        'end_time',
        'tags',
        'billable',
    ];

    protected $casts = [
        'tags' => 'array',
        'billable' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
