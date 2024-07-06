<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Hash;

use App\Filament\Config\CurrencyList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SupplierResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SupplierResource\RelationManagers;

use Parfaitementweb\FilamentCountryField\Tables\CountryColumn;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationLabel(): string
    {
        return __('_section_suppliers');
    }

    public static function getLabel(): string
    {
        return __('_section_supplier');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_suppliers');
    }

    public static function getNavigationGroup(): string
    {
        return __('_section_group_anagraphics');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sezione Informazioni personali
                Section::make(__('_section_personal_info'))
                ->columns(3)
                ->schema([
                    Select::make('supplier_type')
                    ->label('_supplier_type')->translateLabel()
                    ->options([
                        'company' => __('_company'),
                        'individual' => __('_individual'),
                    ])
                    ->default('company')
                    ->live()
                    ->required(),

                    TextInput::make('first_name')
                        ->label('_first_name')->translateLabel()
                        ->maxLength(60)
                        ->required(fn ($get) => $get('supplier_type') == 'individual'),

                    TextInput::make('last_name')
                        ->label('_last_name')->translateLabel()
                        ->maxLength(60)
                        ->required(fn ($get) => $get('supplier_type') == 'individual'),

                    Grid::make(4)
                        ->schema([
                                        
                        TextInput::make('email')
                            ->label('_email')->translateLabel()
                            ->email()
                            ->unique('suppliers', 'email', null, 'id', function($rule){
                                return $rule->where('organization_id', filament()->getTenant()->id);
                            })
                            ->validationMessages([
                                'unique' => __('_mail_exists'),
                            ])
                            ->autocomplete(false),

                        TextInput::make('fiscal_code')
                            ->label('_fiscal_code')->translateLabel()
                            ->minLength(11)
                            ->maxLength(16)
                            ->debounce(500)
                            ->unique('suppliers', 'fiscal_code', null, 'id', function($rule){
                                return $rule->where('organization_id', filament()->getTenant()->id);
                            })
                            ->required(fn ($get) => $get('supplier_type') == 'individual')
                            ->validationMessages([
                                'unique' => __('_fiscal_code_exits'),
                            ])
                            ->afterStateUpdated(function (?string $state, callable $set) {
                                if ($state !== null) {
                                    $set('fiscal_code', strtoupper($state));
                                }
                            }),

                            TextInput::make('pec')
                                ->label('_pec')->translateLabel()
                                ->minLength(7)
                                ->maxLength(255),

                                TextInput::make('phone')
                                ->label('_phone')->translateLabel()
                                ->tel(),

                            Grid::make(1)
                                ->schema([
                                    RichEditor::make('notes')
                                    ->label('_notes')->translateLabel()
                                ]),
                    ]),
            ]),

            // Sezione Domicilio Fiscale
            Section::make(__('_section_tax_residence'))->columns(2)->schema([
                
                Grid::make(3)
                    ->schema([
                        TextInput::make('address')
                        ->label('_address')->translateLabel()
                        ->maxLength(80),

                        TextInput::make('postal_code')
                            ->label('_postal_code')->translateLabel()
                            ->maxLength(10),

                        TextInput::make('city')
                            ->label('_city')->translateLabel(),
                    ]),


                TextInput::make('province')
                    ->label('_province')->translateLabel(),
                    
                Country::make('country')
                    ->label('_country')->translateLabel()
                    ->default('IT')

            ]),

            // Sezione Altre Informazioni
            Section::make(__('_section_other_info'))
                ->columns(2)
                ->schema([

                Select::make('currency')           
                    ->options(function () {
                        return collect(CurrencyList::options())
                            ->map(function ($currencyName, $currencyCode) {
                                return "$currencyCode ($currencyName)";
                            })
                            ->toArray();
                    })
                    ->label('_currency')->translateLabel()
                    ->default('EUR')
                    ->searchable(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('email_copy')
                            ->label('_email_copy')->translateLabel()
                            ->minLength(7)
                            ->maxLength(255)
                            ->helperText(__('_helper_email_copy')),

                        TextInput::make('pec_copy')
                            ->label('_pec_copy')->translateLabel()
                            ->helperText(__('_helper_pec_copy'))
                            ->minLength(7)
                            ->maxLength(255),
                    ])
            ]),

            // Sezione Informazioni Aziendali
            Section::make(__('_section_tax_info'))
                ->columns(2)
                ->visible(fn ($get) => $get('supplier_type') == 'company')
                ->schema([

                    TextInput::make('company_name')
                        ->label('_company_name')->translateLabel()
                        ->maxLength(80)
                        ->required(fn ($get) => $get('supplier_type') == 'company')
                        ->visible(fn ($get) => $get('supplier_type') == 'company'),

                    TextInput::make('recipient_code')
                        ->label('_recipient_code')->translateLabel()
                        ->length(7)
                        ->visible(fn ($get) => $get('supplier_type') == 'company')
                        ->debounce(500)
                        ->afterStateUpdated(function (?string $state, callable $set) {
                            if ($state !== null) {
                                $set('recipient_code', strtoupper($state));
                            }
                        }),

                    TextInput::make('vat_number')
                        ->label('_vat_number')->translateLabel()
                        ->maxLength(28)
                        ->unique('suppliers', 'vat_number', null, 'id', function($rule){
                            return $rule->where('organization_id', filament()->getTenant()->id);
                        })
                        ->visible(fn ($get) => $get('supplier_type') == 'company')
                        ->validationMessages([
                            'unique' => __('_vat_exists'),
                        ])
                        ->required(fn ($get) => $get('supplier_type') == 'company'),

                    
                    TextInput::make('fiscal_code')
                        ->label('_fiscal_code')->translateLabel()
                        ->minLength(11)
                        ->maxLength(16)
                        ->debounce(500)
                        ->unique('suppliers', 'fiscal_code', null, 'id', function($rule){
                            return $rule->where('organization_id', filament()->getTenant()->id);
                        })
                        ->required(fn ($get) => $get('supplier_type') == 'individual')
                        ->validationMessages([
                            'unique' => __('_fiscal_code_exits'),
                        ])
                        ->afterStateUpdated(function (?string $state, callable $set) {
                            if ($state !== null) {
                                $set('fiscal_code', strtoupper($state));
                            }
                        }),
                    
                    TextInput::make('admin_reference')
                        ->maxLength(40)
                        ->label('_admin_reference')->translateLabel(),
            ]),

            // Sezione Accesso
            Section::make(__('_section_access_data'))->schema([
                TextInput::make('password')
                    ->label('_password')->translateLabel()
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->autocomplete(false)
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('_filter_name')->translateLabel()
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->company_name ?? $record->first_name . ' ' . $record->last_name;
                    }),

                TextColumn::make('currency')
                    ->label('_filter_currency')->translateLabel()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vat_number')
                    ->label('_filter_vat_fiscal_code')->translateLabel()
                    ->searchable(['vat_number', 'fiscal_code'])
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->vat_number ?? $record->fiscal_code;
                    }),

                TextColumn::make('email')
                    ->label('_filter_email')->translateLabel()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('_filter_country')->translateLabel()
                    ->searchable()
                    ->sortable(),                
            ])
            ->filters([
                /*
                Filter::make('search')->label('Search')->default('Search...'),
                Filter::make('nome')->label('Nome/Denominazione')->default('Nome/Denominazione'),
                Filter::make('identificativo')->label('Identificativo')->default('Identificativo'),
                Filter::make('codice_fiscale')->label('Codice Fiscale')->default('Codice Fiscale'),
                Filter::make('partita_iva')->label('P.IVA')->default('P.IVA'),
                Filter::make('email')->label('Email')->default('Email'),
                Filter::make('nazione')->label('Nazione')->default('Nazione')
                */
            ])
            ->actions([               
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
