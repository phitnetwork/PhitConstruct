<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Filament\Resources\Resource;

use Filament\Forms\Components\Grid;
use function getVatNumberFieldLabel;
use Illuminate\Support\Facades\Hash;

use App\Filament\Config\CurrencyList;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Livewire;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColorColumn;
use Filament\Forms\Components\RichEditor;

use Filament\Forms\Components\ColorPicker;
use App\Filament\Resources\CustomerResource\Pages;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;
use App\Filament\Resources\AccountTypeResource\RelatedTrackingTable;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('_section_customers');
    }

    public static function getLabel(): string
    {
        return __('_section_customer');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_customers');
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

                ColorPicker::make('color'),

                // Sezione Informazioni personali
                Section::make(__('_section_personal_info'))
                    ->columns(3)
                    ->schema([
                        Select::make('customer_type')
                        ->label('_customer_type')->translateLabel()
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
                            ->required(fn ($get) => $get('customer_type') == 'individual'),

                        TextInput::make('last_name')
                            ->label('_last_name')->translateLabel()
                            ->maxLength(60)
                            ->required(fn ($get) => $get('customer_type') == 'individual'),

                        Grid::make(4)
                            ->schema([
                                            
                            TextInput::make('email')
                                ->label('_email')->translateLabel()
                                ->email()
                                ->unique('customers', 'email', null, 'id', function($rule){
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
                                ->unique('customers', 'fiscal_code', null, 'id', function($rule){
                                    return $rule->where('organization_id', filament()->getTenant()->id);
                                })
                                ->required(fn ($get) => $get('customer_type') == 'individual')
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
                        ->live()
                        ->default('IT'),

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
                    ->visible(fn ($get) => $get('customer_type') == 'company')
                    ->schema([

                        TextInput::make('company_name')
                            ->label('_company_name')->translateLabel()
                            ->maxLength(80)
                            ->required(fn ($get) => $get('customer_type') == 'company')
                            ->visible(fn ($get) => $get('customer_type') == 'company'),

                        TextInput::make('recipient_code')
                            ->label('_recipient_code')->translateLabel()
                            ->length(7)
                            ->visible(fn ($get) => $get('customer_type') == 'company')
                            ->debounce(500)
                            ->afterStateUpdated(function (?string $state, callable $set) {
                                if ($state !== null) {
                                    $set('recipient_code', strtoupper($state));
                                }
                            }),

                        TextInput::make('vat_number')
                            ->label( fn ($get) => getVatNumberFieldLabel($get('country')) )->translateLabel()
                            ->maxLength(28)
                            ->unique('customers', 'vat_number', null, 'id', function($rule){
                                return $rule->where('organization_id', filament()->getTenant()->id);
                            })
                            ->visible(fn ($get) => $get('customer_type') == 'company')
                            ->validationMessages([
                                'unique' => __('_vat_exists'),
                            ])
                            ->required(fn ($get) => $get('customer_type') == 'company'),

                        
                        TextInput::make('fiscal_code')
                            ->label('_fiscal_code')->translateLabel()
                            ->minLength(11)
                            ->maxLength(16)
                            ->debounce(500)
                            ->unique('customers', 'fiscal_code', null, 'id', function($rule){
                                return $rule->where('organization_id', filament()->getTenant()->id);
                            })
                            ->required(fn ($get) => $get('customer_type') == 'individual')
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
                ColorColumn::make('color')->default('white'),

                TextColumn::make('nome')
                    ->label('_filter_name')->translateLabel()
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->company_name ?? $record->first_name . ' ' . $record->last_name;
                    }),

                TextColumn::make('recipient_code')
                    ->label('_filter_identification')->translateLabel()
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
            ])
            ->actions([                
                Tables\Actions\Action::make('details')
                    ->label('')
                    ->icon('heroicon-o-clock')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => TimeEntry::where('customer_id', $record->id)->exists())
                    ->badge(fn($record) => TimeEntry::where('customer_id', $record->id)->count())
                    ->form(function (Customer $record) {
                        return [
                            Livewire::make(RelatedTrackingTable::class, [
                                'dynamicId' => $record->id,
                                'classType' => get_class($record),
                            ]),
                        ];
                    }),           
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
