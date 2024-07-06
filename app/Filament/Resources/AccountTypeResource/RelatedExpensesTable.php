<?php

namespace App\Filament\Resources\AccountTypeResource;

use App\Models\Expense;
use App\Models\AccountType;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Filament\Support\Colors\Color;

use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Query\Builder;

use App\Filament\Imports\ExpenseImporter;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Validation\Rules\File;


class RelatedExpensesTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $accountTypeId;

    public function mount($accountTypeId)
    {
        $this->accountTypeId = $accountTypeId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(function () {
                $record = AccountType::findOrFail($this->accountTypeId);
                return __('_account_type_initial_balance') . ': ' . $record->initial_balance . ' ' . $record->currency;
            })
            ->query(Expense::where('expense_account_type_id', $this->accountTypeId)->orWhere('paid_account_type_id', $this->accountTypeId))
            ->columns([

                TextColumn::make('date')
                    ->label('_expense_date')->translateLabel()
                    ->date()
                    ->sortable(),
                TextColumn::make('expenseAccountType.name')
                    ->label('_expense_expense_account_type')->translateLabel()                    
                    ->badge(),     

                TextColumn::make('supplier.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_expense_supplier')->translateLabel()
                    ->limit(20)                    
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('customer.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_expense_customer')->translateLabel()
                    ->limit(20)                    
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->badge()
                    ->color(fn (Expense $record) => Color::hex($record->customer->color) ?? 'gray')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer
                    ->label('_expense_amount')->translateLabel()
                    ->default('-')
                    ->sortable()
                    ->summarize(Summarizer::make()
                        ->label('_account_type_final_balance')->translateLabel()
                        ->money('EUR', locale: 'it')
                        ->using(function (Builder $query, Expense $record) {

                            $initialBalance = AccountType::findOrFail($this->accountTypeId)->initial_balance;
                            return $initialBalance + $query->selectRaw("SUM(CASE WHEN paid_account_type_id = ? THEN amount * -1 ELSE amount END) as total", [$this->accountTypeId])
                                ->value('total');
                        })
                    )
                    ->getStateUsing(function (Expense $record) {
                        if($record->paid_account_type_id == $this->accountTypeId) {
                            return $record->amount * -1;
                        }
                        return $record->amount;
                    })
                    ->color(function (Expense $record) {
                        if ($record->paid_account_type_id == $this->accountTypeId)
                            return Color::Amber;
                        else
                            return Color::Green;

                        return Color::Zinc;
                    })
            ])
            ->filters([
                // Aggiungi filtri se necessario
            ])
            ->actions([
                // Aggiungi azioni se necessario
            ])
            ->headerActions([
                ImportAction::make()
                    ->label('Importa Spese da Intesa San Paolo')
                    ->importer(ExpenseImporter::class)
                    ->csvDelimiter(';')
                    ->options([
                        'accountTypeId' => $this->accountTypeId,
                    ])
            ])
            ->bulkActions([
                // Aggiungi azioni bulk se necessario
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all');
    }

    public function render(): View
    {
        return view('livewire.related-expenses-table');
    }
}