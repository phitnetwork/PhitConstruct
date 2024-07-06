<?php

namespace App\Filament\Imports;

use App\Models\Expense;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

use Filament\Notifications\Notification;

use Carbon\Carbon;

class ExpenseImporter extends Importer
{
    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('date')
                ->label('Data Contabile')
                ->castStateUsing(function (?string  $state) {
                    if (blank($state)) {
                        return today();
                    }
                    // Convert to Carbon instance
                    $carbon_date = Carbon::createFromFormat('d/m/y', $state);

                    // Format the date as yyyy/mm/dd
                    $formatted_date = $carbon_date->format('Y-m-d');

                    return $formatted_date;
                })
                ->rules(['required']),
            ImportColumn::make('debits')
                ->label('Addebiti')
                ->castStateUsing(function (?string  $state) {
                    $state = str_replace(',', '.', $state);
                    $state = trim($state);
                    $amount = abs((float) $state);
                    return $amount;
                }),
            ImportColumn::make('credits')
                ->label('Accrediti')
                ->castStateUsing(function (?string  $state) {
                    $state = str_replace(',', '.', $state);
                    $state = trim($state);
                    $amount = abs((float) $state);
                    return $amount;
                }),
            ImportColumn::make('notes')
                ->label('Descrizione'),
        ];
    }

    public function resolveRecord(): ?Expense
    {
        // Abbiamo l'account id di AccountType. Ora dobbiamo capire se il record csv che stiamo importando è un valore positivo o negativo
        // in base al valore di credits e debits, di conseguenza cambierà anche la colonna del database dove andrà il dato
        // ovvero se su expense_account_type_id (per i debits) o su paid_account_type_id (per i credits)

        if($this->data['credits'] != 0.0) {
            $amount = $this->data['credits'];
            $accountColumn = 'expense_account_type_id';
        } else {
            $amount = $this->data['debits'];
            $accountColumn = 'paid_account_type_id';       
        }

        unset($this->data['debits']);
        unset($this->data['credits']);

        $expense = new Expense();
        $expense->date = $this->data['date'];
        $expense->amount = $amount;
        $expense->$accountColumn = $this->options['accountTypeId'];
        $expense->notes = $this->data['notes'];
        
        return $expense;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your expense import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    function formatCurrency($number, $locale = 'it_IT', $currency = 'EUR') 
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($number, $currency);
    }
}
