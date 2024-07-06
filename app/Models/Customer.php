<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'email',
        'password',
        
        // Dati fiscali
        'company_name',
        'first_name',
        'last_name',
        'country_id',
        'vat_number',
        'fiscal_code',
        'recipient_code',
        'pec',
        'email_copy',
        'pec_copy',
        'phone',
        'admin_reference',
        'currency',

        // Indirizzo
        'country',
        'postal_code',
        'province',
        'city',
        'address',

        'notes',
        'color',
        'customer_type'
    ];

    protected $hidden = ['password'];

    /*
    public function getFullNameAttribute()
    {
        if($this->customer_type == "individual")
        {
            return "{$this->first_name} {$this->last_name}";
        }
        else
        {
            if($this->first_name && $this->last_name)
                return "{$this->company_name} ({$this->first_name} {$this->last_name})";
            else
                return $this->company_name;
        }
    }
    */
    
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    
}