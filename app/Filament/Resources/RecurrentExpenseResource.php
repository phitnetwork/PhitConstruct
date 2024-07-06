<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\RecurrentExpense;
use Filament\Resources\Resource;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;

use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\RecurrentExpenseResource\Pages;


class RecurrentExpenseResource extends Resource
{
    protected static ?string $model = RecurrentExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    public static function getNavigationLabel(): string
    {
        return __('_section_recurrent_expenses');
    }

    public static function getLabel(): string
    {
        return __('_section_recurrent_expense');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_recurrent_expenses');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_finances');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('_recurrent_expense_name')->translateLabel()
                    ->required()
                    ->maxLength(255),
                Select::make('repeat_interval')
                    ->label('_recurrent_expense_repeat_interval')->translateLabel()
                    ->options([                        
                        'every_day' => __('_recurrent_expense_repeat_interval_every_day'),
                        'every_week' => __('_recurrent_expense_repeat_interval_every_week'),
                        'every_2_weeks' => __('_recurrent_expense_repeat_interval_every_2_weeks'),
                        'every_month' => __('_recurrent_expense_repeat_interval_every_month'),
                        'every_2_months' => __('_recurrent_expense_repeat_interval_every_2_months'),
                        'every_3_months' => __('_recurrent_expense_repeat_interval_every_3_months'),
                        'every_6_months' => __('_recurrent_expense_repeat_interval_every_6_months'),
                        'every_year' => __('_recurrent_expense_repeat_interval_every_year'),
                        'every_2_years' => __('_recurrent_expense_repeat_interval_every_2_years'),
                        'every_3_years' => __('_recurrent_expense_repeat_interval_every_3_years'),
                    ])
                    ->selectablePlaceholder(false)
                    ->default('every_month')
                    ->required(),
                DatePicker::make('start_date')
                    ->label('_recurrent_expense_start_date')->translateLabel()
                    ->default(now())
                    ->required(),
                DatePicker::make('end_date')
                    ->label('_recurrent_expense_end_date')->translateLabel(),                                        
                TextInput::make('amount')
                    ->label('_recurrent_expense_amount')->translateLabel()
                    ->required()                            
                    ->numeric()
                    ->prefix('€')
                    ->default(null),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('repeat_interval')
                    ->label(__('_recurrent_expense_repeat_interval'))
                    ->collapsible()
            ])->groupingDirectionSettingHidden()          
            ->defaultGroup('repeat_interval')
            ->columns([
                TextColumn::make('name')
                    ->label('_recurrent_expense_name')->translateLabel()
                    ->searchable(),
                TextColumn::make('repeat_interval')
                    ->label('_recurrent_expense_repeat_interval')->translateLabel()
                    ->alignment('center')
                    ->badge(),
                TextColumn::make('start_date')
                    ->label('_recurrent_expense_start_date')->translateLabel()
                    ->alignment('right')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('_recurrent_expense_amount')->translateLabel()
                    ->alignment('right')
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer                    
                    ->default('-')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('EUR', locale: 'it')
                    ),
                TextColumn::make('supplier.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_recurrent_expense_supplier')->translateLabel()
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                ->orderBy('repeat_interval', 'asc')
                ->orderBy('start_date', 'asc')                
                ->orderBy('name', 'desc');
            })
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all');;
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
            'index' => Pages\ListRecurrentExpenses::route('/'),
            'create' => Pages\CreateRecurrentExpense::route('/create'),
            'edit' => Pages\EditRecurrentExpense::route('/{record}/edit'),
        ];
    }
}
