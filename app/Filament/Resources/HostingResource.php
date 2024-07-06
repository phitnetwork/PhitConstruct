<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Hosting;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;

use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\HostingResource\Pages;

class HostingResource extends Resource
{
    protected static ?string $model = Hosting::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    public static function getNavigationLabel(): string
    {
        return __('_section_hosting');
    }

    public static function getLabel(): string
    {
        return __('_section_hosting');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_hostings');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_server');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([

                    Split::make([

                        Grid::make(1)->schema([

                            Grid::make(2)->schema([

                                TextInput::make('name')
                                    ->label('_hosting_name')->translateLabel()
                                    ->required(),

                                Select::make('project_id')
                                    ->label('_todo_project')->translateLabel()
                                    ->relationship('project', 'name')
                                    ->default(null)
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('_project_name')
                                            ->translateLabel()
                                            ->required()
                                            ->maxLength(255),

                                        Hidden::make('organization_id')->default(Filament::getTenant()->id),
                                    ]),
                            ]),
                            
                            Select::make('customer_id')
                                ->label('_project_customer')->translateLabel()
                                ->relationship('customer', 'full_name')
                                ->default(null)
                                ->required()
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
                                    Forms\Components\Select::make('customer_type')
                                        ->label('_customer_type')->translateLabel()
                                        ->options([
                                            'company' => __('_company'),
                                            'individual' => __('_individual'),
                                        ])
                                        ->default('company')
                                        ->live(),

                                    Forms\Components\TextInput::make('company_name')
                                        ->label('_company_name')->translateLabel()
                                        ->maxLength(80)
                                        ->required(fn ($get) => $get('customer_type') == 'company')
                                        ->visible(fn ($get) => $get('customer_type') == 'company'),

                                    Forms\Components\TextInput::make('first_name')
                                        ->label('_first_name')->translateLabel()
                                        ->maxLength(60)
                                        ->required(fn ($get) => $get('customer_type') == 'individual')
                                        ->visible(fn ($get) => $get('customer_type') == 'individual'),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label('_last_name')->translateLabel()
                                        ->maxLength(60)
                                        ->required(fn ($get) => $get('customer_type') == 'individual')
                                        ->visible(fn ($get) => $get('customer_type') == 'individual'),

                                    Hidden::make('organization_id')->default(Filament::getTenant()->id),
                                ]),

                            TextInput::make('domain')
                                ->label('_hosting_domain')->translateLabel()
                                ->required()
                                ->suffixIcon('heroicon-m-globe-alt')
                                ->default(null),

                            Grid::make(3)->schema([
                                Select::make('status')
                                    ->label('_hosting_status')->translateLabel()
                                    ->options([
                                        'active' => __('_hosting_status_active'),
                                        'inactive' => __('_hosting_status_inactive'),
                                        'suspended' => __('_hosting_status_suspended'),
                                        'terminated' => __('_hosting_status_terminated'),
                                        ])
                                    ->default('active'),

                                TextInput::make('annual_payment')
                                    ->label('_hosting_annual_payment')->translateLabel()
                                    ->numeric()      
                                    ->prefix('€')
                                    ->live()
                                    ->default(null),
                                DatePicker::make('expiration_date')
                                    ->label('_hosting_expiration_date')->translateLabel(),
                            ])
                        ]),
                        Grid::make(1)->schema([
                            Textarea::make('notes')
                                ->label('_hosting_notes')->translateLabel()
                                ->rows(8) // Imposta l'altezza del campo note
                        ])
                    ])
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('customer.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_hosting_customer')->translateLabel()
                    ->alignment('center')
                    ->limit(20)                    
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                
                        // Only render the tooltip if the column content exceeds the length limit.
                        return $state;
                    })
                    ->badge()
                    ->color(fn ($record) => Color::hex($record->customer->color) ?? 'gray')
                    ->sortable(),
                TextColumn::make('annual_payment')                
                    ->label('_hosting_annual_payment')->translateLabel()
                    ->alignment('right')
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer
                    ->default('-')
                    ->sortable(),
                TextColumn::make('domain')
                    ->label('_hosting_domain')->translateLabel()
                    ->badge()
                    ->searchable(),
                TextColumn::make('expiration_date')
                    ->label('_hosting_expiration_date')->translateLabel()
                    ->alignment('right')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('_hosting_status')->translateLabel()
                    ->alignment('center')
                    ->badge()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->label(''),
                Tables\Actions\DeleteAction::make()
                    ->label(''),                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListHostings::route('/'),
            'create' => Pages\CreateHosting::route('/create'),
            'edit' => Pages\EditHosting::route('/{record}/edit'),
        ];
    }
}
