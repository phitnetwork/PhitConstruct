<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        
        'recurrent_expense_id',
        'date',
        'expense_account_type_id',
        'paid_account_type_id',
        'amount',
        'supplier_id',
        'reference_number',
        'notes',
        'customer_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function recurrentExpense()
    {
        return $this->belongsTo(RecurrentExpense::class);
    }

    public function expenseAccountType()
    {
        return $this->belongsTo(AccountType::class, 'expense_account_type_id');
    }

    public function paidAccountType()
    {
        return $this->belongsTo(AccountType::class, 'paid_account_type_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    
}