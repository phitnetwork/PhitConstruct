<?php

namespace App\Models;

use App\Enums\AccountTypeEnum;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',

        'type',
        'name',
        'account_number',
        'currency',
        'initial_balance',
        'description',
    ];

    protected $casts = [
        'type' => AccountTypeEnum::class,
    ];

    public function getCurrentBalance()
    {
        $initialBalance = $this->initial_balance ?? 0;
        $expensesTotal = $this->expenses()->sum('amount');
        $incomesTotal = $this->incomes()->sum('amount');
        
        return $initialBalance + $incomesTotal - $expensesTotal;
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'paid_account_type_id');
    }
    public function incomes()
    {
        return $this->hasMany(Expense::class, 'expense_account_type_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

}