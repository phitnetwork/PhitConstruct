<?php

namespace App\Models;

use App\Enums\RepeatInterval;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurrentExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'name',
        'repeat_interval',
        'start_date',
        'end_date',
        'amount',
        'supplier_id',
    ];

    protected $casts = [
        'repeat_interval' => RepeatInterval::class,
    ];

    // Definizione della relazione con il fornitore
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

}