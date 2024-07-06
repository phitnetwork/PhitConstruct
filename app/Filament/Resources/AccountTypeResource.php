<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountTypeResource\Pages;
use App\Filament\Resources\AccountTypeResource\RelationManagers;
use App\Models\AccountType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;

use App\Filament\Config\CurrencyList;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Livewire;


use App\Filament\Resources\AccountTypeResource\RelatedExpensesTable;


class AccountTypeResource extends Resource
{
    protected static ?string $model = AccountType::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationLabel(): string
    {
        return __('_section_account_types');
    }

    public static function getLabel(): string
    {
        return __('_section_account_type');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_account_types');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_finances');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([

                        Tab::make('Manage')
                            ->schema([

                                Select::make('type')
                                    ->label('_account_type_type')->translateLabel()                                    
                                    ->options([                        
                                        'assets' => __('_account_type_assets'),
                                        'bank' => __('_account_type_bank'),
                                        'credit_card' => __('_account_type_credit_card'),
                                        'liabilities' => __('_account_type_liabilities'),
                                        'revenues' => __('_account_type_revenues'),
                                        'expenses' => __('_account_type_expenses'),
                                    ])
                                    ->default('assets')
                                    ->live()
                                    ->required(),
                                TextInput::make('name')
                                    ->label('_account_type_name')->translateLabel()
                                    ->required(),
                                TextInput::make('account_number')
                                    ->label('_account_type_account_number')->translateLabel()
                                    ->required()
                                    ->visible(fn ($get) => $get('type') == 'bank' || $get('type') == 'credit_card'),
                                Select::make('currency')      
                                    ->label('_account_type_currency')->translateLabel()
                                    ->options(function () {
                                        return collect(CurrencyList::options())
                                            ->map(function ($currencyName, $currencyCode) {
                                                return "$currencyCode ($currencyName)";
                                            })
                                            ->toArray();
                                    })
                                    ->label('_currency')->translateLabel()
                                    ->default('EUR')
                                    ->visible(fn ($get) => $get('type') == 'bank' || $get('type') == 'credit_card')
                                    ->searchable(),
                                TextInput::make('initial_balance')
                                    ->label('_account_type_initial_balance')->translateLabel()
                                    ->numeric()
                                    ->live()
                                    ->prefix('€')
                                    ->default(0.00),
                                Textarea::make('description')
                                    ->label('_account_type_description')->translateLabel()
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->columnSpanFull(),                                
                                ]),

                        Tab::make('details')
                            ->label('_account_type_details')->translateLabel()
                            ->visible(fn($operation, $record) => 'edit' === $operation && \App\Models\Expense::where('expense_account_type_id', $record->id)->exists())
                            ->badge(fn($record) => \App\Models\Expense::where('expense_account_type_id', $record->id)->count())
                            ->schema([

                                Livewire::make(RelatedExpensesTable::class, [
                                    'accountTypeId' => request()->route()->parameter('record'),
                                ])

                            ])
                    ])
                                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('name')
                    ->label('_account_type_name')->translateLabel()
                    ->searchable(),
                TextColumn::make('account_number')
                    ->label('_account_type_account_number')->translateLabel()
                    ->searchable(),
                TextColumn::make('current_balance')
                    ->label('_account_type_current_balance')->translateLabel()
                    ->alignment('right')
                    ->getStateUsing(fn ($record) => $record->getCurrentBalance())
                    ->money('EUR', locale: 'it')
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('_currency')->translateLabel()
                    ->alignment('center')
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([                
                Tables\Actions\Action::make('details')
                    ->label('_account_type_details')->translateLabel()
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => \App\Models\Expense::where('expense_account_type_id', $record->id)
                        ->orWhere('paid_account_type_id', $record->id)->exists())
                    ->badge(fn($record) => \App\Models\Expense::where('expense_account_type_id', $record->id)
                        ->orWhere('paid_account_type_id', $record->id)->count())
                    ->form(function (AccountType $record) {
                        return [
                            Livewire::make(RelatedExpensesTable::class, [
                                'accountTypeId' => $record->id,
                            ]),
                        ];
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'index' => Pages\ListAccountTypes::route('/'),
            'create' => Pages\CreateAccountType::route('/create'),
            'edit' => Pages\EditAccountType::route('/{record}/edit'),
        ];
    }
}
