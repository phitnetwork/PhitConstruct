<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Grid;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns\Summarizers\Sum;


use App\Filament\Resources\ExpenseResource\Pages;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationLabel(): string
    {
        return __('_section_expenses');
    }

    public static function getLabel(): string
    {
        return __('_section_expense');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_expenses');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_finances');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Grid::make(2)->schema([

                    Section::make([
                        DatePicker::make('date')
                            ->label('_expense_date')->translateLabel()
                            ->default(now())
                            ->required(),
                        Grid::make(3)
                            ->schema([
                                Select::make('expense_account_type_id')
                                    ->label('_expense_expense_account_type')->translateLabel()
                                    ->columnSpan(2)
                                    ->visible(fn ($get) => $get('is_internal_transaction') == false)
                                    ->relationship(
                                        name: 'expenseAccountType',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query
                                            ->where('type', 'liabilities')
                                            ->orwhere('type', 'expenses')
                                            ->orWhere('type', 'credit_card')
                                    )
                                    ->required(),
                                Select::make('expense_account_type_id')
                                    ->label('_expense_transfer_account_type')->translateLabel()
                                    ->columnSpan(2)
                                    ->visible(fn ($get) => $get('is_internal_transaction') == true)
                                    ->relationship(
                                        name: 'expenseAccountType',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query
                                            ->where('type', 'assets')
                                            ->orwhere('type', 'bank')
                                            ->orWhere('type', 'credit_card')
                                    )
                                    ->required(),
                                Toggle::make('is_internal_transaction')
                                    ->label('_expense_is_internal_transaction')->translateLabel()
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->live()
                                    ->inline(false)
                            ]),      
                        
                        TextInput::make('amount')
                            ->label('_expense_amount')->translateLabel()
                            ->required()                            
                            ->numeric()
                            ->prefix('€')
                            ->default(null),
                        Select::make('paid_account_type_id')
                            ->label('_expense_paid_account_type')->translateLabel()                            
                            ->relationship(
                                name: 'paidAccountType',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('type', 'assets')
                                    ->orwhere('type', 'bank')
                                    ->orWhere('type', 'credit_card')
                            )
                            ->required(),
                        TextInput::make('reference_number')
                            ->label('_expense_reference')->translateLabel()
                            ->maxLength(50)
                            ->default(null),
                        Textarea::make('notes')
                            ->label('_expense_notes')->translateLabel()
                            ->columnSpanFull(),
                        Select::make('supplier_id')
                            ->label('_expense_supplier')->translateLabel()
                            ->relationship('supplier', 'full_name')
                            ->default(null)
                            ->placeholder('Seleziona un fornitore (opzionale)')
                            ->searchable()
                            ->getSearchResultsUsing(function ($query) {
                                $results = \App\Models\Supplier::where(function (Builder $q) use ($query) {
                                    $q->where('first_name', 'like', "%{$query}%")
                                        ->orWhere('last_name', 'like', "%{$query}%")
                                        ->orWhere('company_name', 'like', "%{$query}%")
                                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$query}%"]);                                        
                                })
                                ->get()
                                ->mapWithKeys(function ($supplier) {
                                    return [$supplier->id => $supplier->full_name];
                                });            
                        
                                return $results;
                            })                        
                            ->getOptionLabelUsing(fn ($value) => \App\Models\Supplier::find($value)?->full_name)
                            ->createOptionForm([
                                Select::make('supplier_type')
                                    ->label('_supplier_type')->translateLabel()
                                    ->options([
                                        'company' => __('_company'),
                                        'individual' => __('_individual'),
                                    ])
                                    ->default('company')
                                    ->live(),

                                TextInput::make('company_name')
                                    ->label('_company_name')->translateLabel()
                                    ->maxLength(80)
                                    ->required(fn ($get) => $get('supplier_type') == 'company')
                                    ->visible(fn ($get) => $get('supplier_type') == 'company'),

                                TextInput::make('first_name')
                                    ->label('_first_name')->translateLabel()
                                    ->maxLength(60)
                                    ->required(fn ($get) => $get('supplier_type') == 'individual')
                                    ->visible(fn ($get) => $get('supplier_type') == 'individual'),

                                TextInput::make('last_name')
                                    ->label('_last_name')->translateLabel()
                                    ->maxLength(60)
                                    ->required(fn ($get) => $get('supplier_type') == 'individual')
                                    ->visible(fn ($get) => $get('supplier_type') == 'individual'),

                                Hidden::make('organization_id')->default(Filament::getTenant()->id),
                        ]),
                        Select::make('customer_id')
                            ->label('_expense_customer')->translateLabel()
                            ->relationship('customer', 'full_name')
                            ->default(null)
                            ->placeholder('Seleziona un cliente (opzionale)')
                            ->searchable()
                            ->getSearchResultsUsing(function ($query) {
                                $results = \App\Models\Customer::where(function (Builder $q) use ($query) {
                                    $q->where('first_name', 'like', "%{$query}%")
                                        ->orWhere('last_name', 'like', "%{$query}%")
                                        ->orWhere('company_name', 'like', "%{$query}%")
                                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$query}%"]);                                        
                                })
                                ->get()
                                ->mapWithKeys(function ($customer) {
                                    return [$customer->id => $customer->full_name];
                                });            
                        
                                return $results;
                            })                        
                            ->getOptionLabelUsing(fn ($value) => \App\Models\Customer::find($value)?->full_name)
                            ->createOptionForm([
                                Select::make('customer_type')
                                    ->label('_customer_type')->translateLabel()
                                    ->options([
                                        'company' => __('_company'),
                                        'individual' => __('_individual'),
                                    ])
                                    ->default('company')
                                    ->live(),

                                TextInput::make('company_name')
                                    ->label('_company_name')->translateLabel()
                                    ->maxLength(80)
                                    ->required(fn ($get) => $get('customer_type') == 'company')
                                    ->visible(fn ($get) => $get('customer_type') == 'company'),

                                TextInput::make('first_name')
                                    ->label('_first_name')->translateLabel()
                                    ->maxLength(60)
                                    ->required(fn ($get) => $get('customer_type') == 'individual')
                                    ->visible(fn ($get) => $get('customer_type') == 'individual'),

                                TextInput::make('last_name')
                                    ->label('_last_name')->translateLabel()
                                    ->maxLength(60)
                                    ->required(fn ($get) => $get('customer_type') == 'individual')
                                    ->visible(fn ($get) => $get('customer_type') == 'individual'),

                                Hidden::make('organization_id')->default(Filament::getTenant()->id),
                        ]),    
                        
                        Select::make('recurrent_expense_id')
                            ->label('_expense_recurrent_expense')->translateLabel()
                            ->relationship('recurrentExpense', 'name')
                            ->default(null)
                            ->searchable()    
                            ->preload()                    
                    ])->columnSpan(1)
                ])
            ]); 
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('_expense_date')->translateLabel()
                    ->alignment('right')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('expenseAccountType.name')
                    ->label('_expense_expense_account_type')->translateLabel()                    
                    ->badge(),

                TextColumn::make('paidAccountType.name')
                    ->label('_expense_paid_account_type')->translateLabel()
                    ->badge(),        

                TextColumn::make('supplier.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_expense_supplier')->translateLabel()
                    ->alignment('center')
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
                    ->alignment('center')
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

                TextColumn::make('RecurrentExpense.name')
                    ->label('_section_recurrent_expense')->translateLabel()
                    ->alignment('center')
                    ->limit(20)                    
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('amount')                    
                    ->label('_expense_amount')->translateLabel()
                    ->alignment('right')
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer
                    ->default('-')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('EUR', locale: 'it')
                    )
            ])
            ->filters([
                SelectFilter::make('paidAccountType')
                    ->label('_expense_paid_account_type')->translateLabel()                    
                    ->relationship('paidAccountType', 'name', fn (Builder $query) => $query->orderBy('name', 'asc')),
                SelectFilter::make('expenseAccountType')
                    ->label('_expense_expense_account_type')->translateLabel()                    
                    ->relationship('expenseAccountType', 'name', fn (Builder $query) => $query->orderBy('name', 'asc'))
                
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label(''),                    
            ])
            ->persistFiltersInSession()
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                ->orderBy('date', 'desc');
            })
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
