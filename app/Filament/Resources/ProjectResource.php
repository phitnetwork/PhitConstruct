<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Project;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;

use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Livewire;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\AccountTypeResource\RelatedTrackingTable;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('_section_projects');
    }

    public static function getLabel(): string
    {
        return __('_section_project');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_projects');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_works');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('_project_section_info_project'))->schema([

                    Forms\Components\Grid::make(2)->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('_project_name')->translateLabel()
                            ->required()
                            ->maxLength(255),

                        Select::make('customer_id')
                            ->label('_project_customer')->translateLabel()
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
                            ->getOptionLabelUsing(fn ($value) => \App\Models\Customer::find($value)?->company_name)
                            ->createOptionForm(                                
                                [
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

                                Forms\Components\Hidden::make('organization_id')->default(Filament::getTenant()->id),
                            ])
                    ]),

                    Forms\Components\Grid::make(2)->schema([

                        Forms\Components\DatePicker::make('deadline')
                            ->label('_project_deadline')->translateLabel(),

                        Forms\Components\TextInput::make('estimated_hours_client')
                            ->label('_project_estimated_hours_client')->translateLabel()
                            ->reactive()
                            ->rules([
                                'regex:/^([0-9]{1,4})(:[0-5][0-9])?$/'
                            ])
                            ->afterStateUpdated(function (?string $state, callable $set, $get) {
                                if ($state !== null) {
                                    // Rimuovi eventuali caratteri non validi dallo stato
                                    $cleanedState = preg_replace('/[^0-9:]/', '', $state);

                                    if (preg_match('/^\d{1,2}(:\d{0,2})?$/', $cleanedState)) 
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    elseif (preg_match('/^\d{3}(:\d{0,2})?$/', $cleanedState))
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    elseif (preg_match('/^\d{4}(:\d{0,2})?$/', $cleanedState))
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    else
                                    {
                                        $modifiedState = "";
                                    }
                        
                                    // Imposta il valore manipolato
                                    $set('estimated_hours_client', $modifiedState);
                                }
                            })
                            ->afterStateHydrated(function ($component, $state) {
                                // Rimuove i secondi dal valore se presenti
                                if (strpos($state, ':') !== false) {
                                    list($hours, $minutes) = explode(':', $state);
                                    $component->state(sprintf('%02d:%02d', $hours, $minutes));
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                // Se lo stato è nullo o vuoto, ritorna nullo o vuoto
                                if ($state === null || $state === '') {
                                    return $state;
                                }
                        
                                // Se il valore non contiene ":", aggiunge ":00"
                                if (strpos($state, ':') === false) {
                                    return $state . ':00';
                                }
                        
                                return $state;
                            })
                            ->default(null)
                    ]),

                    Forms\Components\Grid::make(2)->schema([

                        TextInput::make('project_type')
                                    ->label('_project_type')->translateLabel()
                                    ->datalist(fn () => Project::distinct('project_type')->pluck('project_type')->toArray())
                                    ->extraAttributes(['style' => 'text-transform: lowercase;']),
                        
                        Forms\Components\Select::make('priority')
                            ->label('_project_priority')->translateLabel()
                            ->options([
                                'low' => __('_priority_low'),
                                'medium' => __('_priority_medium'),
                                'high' => __('_priority_high')
                            ])
                            ->default('low'),

                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('_project_description')->translateLabel()                    
                                        
                ]),

                Forms\Components\Section::make(__('_project_section_financial_info'))->schema([

                    Forms\Components\Grid::make(3)->schema([

                        Forms\Components\TextInput::make('budget')
                            ->label('_project_budget')->translateLabel()
                            ->numeric()      
                            ->prefix('€')
                            ->live()
                            ->default(null),

                        Forms\Components\TextInput::make('prepayment_percentage')
                            ->label('_project_prepayment_percentage')->translateLabel()
                            ->numeric()
                            ->live()
                            ->suffix('%')
                            ->default(50)
                            ->minValue(0)
                            ->maxValue(100)
                            ->inputMode('numeric') // Assicura che la tastiera numerica sia mostrata sui dispositivi mobili
                            ->step(1),
                            
                        Forms\Components\Placeholder::make('prepayment_amount')
                            ->content(function ($get) {
                                $budget = floatval($get('budget')); // Converti budget in float
                                $prepayment_percentage = $get('prepayment_percentage');
                        
                                if ($prepayment_percentage != 0) {
                                    return $budget * ($prepayment_percentage / 100) . " €";
                                } else {
                                    return "0 €"; // O qualsiasi altra gestione che preferisci quando prepayment_percentage è zero
                                }
                            }),
                    ]),

                    Forms\Components\Repeater::make('milestones')
                        ->label('_project_milestones')->translateLabel()
                        ->itemLabel(fn (array $state): ?string => $state['objective'] ?? null)
                        ->schema([

                            Forms\Components\TextInput::make('objective')
                                ->label('_project_milestones_objective')->translateLabel()
                                ->required(),
    
                            Forms\Components\Grid::make(3)
                                ->schema([

                                    Forms\Components\DatePicker::make('deadline')
                                        ->label('_project_milestones_deadline')->translateLabel(),

                                    Forms\Components\Select::make('Status')
                                        ->label('_project_milestones_status')->translateLabel()
                                        ->options([
                                            'planned' => __('_project_milestones_status_planned'),
                                            'in_progress' => __('_project_milestones_status_in_progress'),
                                            'completed' => __('_project_milestones_status_completed'),
                                            'on_hold' => __('_project_milestones_status_on_hold'),
                                            'canceled' => __('_project_milestones_status_canceled')
                                        ])
                                        ->default('planned'),

                                    Forms\Components\TextInput::make('partial_budget')
                                        ->label('_project_milestones_partial_budget')->translateLabel()
                                        ->numeric()      
                                        ->prefix('€')
                                        ->live()
                                        ->default(null),
                                ])
                        ])
                        ->cloneable()
                        ->collapsible()
                        ->collapsed()
                        ->columns(1),

                ]),

                Forms\Components\Section::make(__('_project_section_status_and_progress'))->schema([

                    Forms\Components\Grid::make(2)->schema([
                        
                        Forms\Components\Select::make('status')
                            ->label('_project_status')->translateLabel()
                            ->options([
                                'planned' => __('_project_status_planned'),
                                'waiting_for_quote' => __('_project_status_waiting_for_quote'),
                                'waiting_for_advance' => __('_project_status_waiting_for_advance'),
                                'in_progress' => __('_project_status_in_progress'),
                                'on_hold' => __('_project_status_on_hold'),
                                'invoiced' => __('_project_status_invoiced'),
                                'paid' => __('_project_status_paid'),
                                'canceled_by_client' => __('_project_status_canceled_by_client'),
                                'quote_refused' => __('_project_status_quote_refused')
                            ])
                            ->default('planned'),
                                                                        
                        Forms\Components\TextInput::make('hours_worked')
                            ->label('_project_hours_worked')->translateLabel()
                            ->reactive()
                            ->rules([
                                'regex:/^([0-9]{1,3})(:[0-5][0-9])?$/'
                            ])
                            ->afterStateUpdated(function (?string $state, callable $set, $get) {
                                if ($state !== null) {
                                    // Rimuovi eventuali caratteri non validi dallo stato
                                    $cleanedState = preg_replace('/[^0-9:]/', '', $state);

                                    if (preg_match('/^\d{1,2}(:\d{0,2})?$/', $cleanedState)) 
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    elseif (preg_match('/^\d{3}(:\d{0,2})?$/', $cleanedState))
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    elseif (preg_match('/^\d{4}(:\d{0,2})?$/', $cleanedState))
                                    {
                                        $modifiedState = $cleanedState;
                                    }
                                    else
                                    {
                                        $modifiedState = "";
                                    }
                        
                                    // Imposta il valore manipolato
                                    $set('estimated_hours_client', $modifiedState);
                                }
                            })
                            ->afterStateHydrated(function ($component, $state) {
                                // Rimuove i secondi dal valore se presenti
                                if (strpos($state, ':') !== false) {
                                    list($hours, $minutes) = explode(':', $state);
                                    $component->state(sprintf('%02d:%02d', $hours, $minutes));
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                // Se lo stato è nullo o vuoto, ritorna nullo o vuoto
                                if ($state === null || $state === '') {
                                    return $state;
                                }
                        
                                // Se il valore non contiene ":", aggiunge ":00"
                                if (strpos($state, ':') === false) {
                                    return $state . ':00';
                                }
                        
                                return $state;
                            })
                            ->default(null)
                    ])

                ]),
                
                Forms\Components\Section::make(__('_project_section_other_info'))->schema([
                    
                    Forms\Components\FileUpload::make('attachments')
                        ->label('_project_attachments')->translateLabel()
                        ->maxSize(1024 * 1024 * 5) // 5mb
                        ->maxFiles(5)
                        ->acceptedFileTypes(['application/pdf', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'])
                        ->disk('public')
                        ->directory('project_attachments')
                        ->placeholder('Drag and drop files here or click to upload')
                        ->multiple(),

                    Forms\Components\Textarea::make('notes')
                        ->label('_project_notes')->translateLabel(),
                ])                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('_project_name')->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function (Project $record) {
                        $html = $record->name;
                        if($record->description)
                            $html .= '&nbsp;'.Blade::render('<x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 inline"/>');
                        if($record->attachments)
                            $html .= '&nbsp;'.Blade::render('<x-heroicon-s-document-arrow-down class="w-5 h-5 inline"/>');

                        return new HtmlString('<span style="display: inline !important">'.$html.'</span>');
                    }),

                TextColumn::make('customer.company_name') // TODO: transformare scelta company_name o first_name . last_name in base a company_type
                    ->label('_project_customer')->translateLabel()
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
                    ->color(fn (Project $record) => Color::hex($record->customer->color) ?? 'gray')
                    ->sortable(), 

                TextColumn::make('estimated_hours_client')
                    ->label('_project_estimated_hours_client')->translateLabel()
                    ->alignment('center')
                    ->default('00:00')
                    ->badge()
                    ->color(function ($record) 
                    {
                        $state = $record->estimated_hours_client;

                        if($state == '00:00' || $state == null)
                            return 'gray';
                        else
                            return 'success';
                    })
                    ->sortable()
                    ->formatStateUsing(function ($state) {

                        if($state == '00:00' || $state == null) return;

                        if (strpos($state, ':') !== false) {
                            list($hours, $minutes) = explode(':', $state);
                            return sprintf('%02d:%02d', $hours, $minutes);
                        }
                    })
                    ->extraAttributes(function ($record) {
                        // Calcolo deadline formattato
                        $formattedDeadline = $record->deadline ? Carbon::parse($record->deadline)->isoFormat('DD MMM, YYYY') : '';
                        if(!$formattedDeadline) $formattedDeadline = '<i>non disponibile</i>';

                        // Calcolo ore lavorative totali
                        $estimatedHours = 0;
                        if ($record->estimated_hours_client) {
                            list($hours, $minutes) = explode(':', $record->estimated_hours_client);
                            $estimatedHours = $hours + ($minutes / 60);
                        }                        
                
                        // Calcolo giorni e ore previste
                        $days = floor($estimatedHours / 8);
                        $remainingHours = $estimatedHours % 8;
                        $remainingMinutes = ($estimatedHours - floor($estimatedHours)) * 60;
                
                        // Costruzione della stringa per giorni e ore previste
                        $daysString = $days > 0 ? ($days == 1 ? '1 giorno' : $days . ' giorni') : '';
                        $hoursString = $remainingHours > 0 ? ($remainingHours == 1 ? '1 ora' : $remainingHours . ' ore') : '';
                        $minutesString = $remainingMinutes > 0 ? ($remainingMinutes == 1 ? '1 minuto' : $remainingMinutes . ' minuti') : '';
                
                        // Costruzione del contenuto HTML per il tooltip
                        $tooltipContent = '<strong>' . __('_project_deadline') . ':</strong> ' . $formattedDeadline . '<br><br>'
                                        . '<strong>' . __('_project_estimated_time_client') . ':</strong> ';
                
                        if ($days > 0) {
                            $tooltipContent .= $daysString;
                            if ($remainingHours > 0) {
                                $tooltipContent .= ', ' . $hoursString;
                                if ($remainingMinutes > 0) {
                                    $tooltipContent .= ' e ' . $minutesString;
                                }
                            } elseif ($remainingMinutes > 0) {
                                $tooltipContent .= $minutesString;
                            }
                        } else {
                            if ($remainingHours > 0) {
                                $tooltipContent .= $hoursString;
                                if ($remainingMinutes > 0) {
                                    $tooltipContent .= ' e ' . $minutesString;
                                }
                            } elseif ($remainingMinutes > 0) {
                                $tooltipContent .= $minutesString;                            
                            } else {
                                $tooltipContent .= '<i>non disponibile</i>';
                            }
                        }
                
                        return [
                            'x-tooltip.html' => new HtmlString(),
                            'x-tooltip.raw' => new HtmlString($tooltipContent),
                        ];
                    }),

                TextColumn::make('hours_worked')
                    ->label('_project_hours_worked')->translateLabel()
                    ->alignment('center')
                    ->default('00:00')
                    ->formatStateUsing(function ($state) {

                        if($state == '00:00' || $state == null) return;

                        if (strpos($state, ':') !== false) {
                            list($hours, $minutes) = explode(':', $state);
                            return sprintf('%02d:%02d', $hours, $minutes);
                        }
                    })
                    ->badge()
                    ->color(function ($record)
                    {
                        $state = $record->hours_worked;

                        if($state == '00:00' || $state == null)
                            return 'gray';
                        elseif($state < $record->hours_worked)
                            return 'warning';
                        else
                            return 'success';
                    })
                    ->sortable(),

                SelectColumn::make('status')
                    ->label('_project_status')->translateLabel()
                    ->alignment('center')
                    ->options([
                        'planned' => __('_project_status_planned'),
                        'waiting_for_quote' => __('_project_status_waiting_for_quote'),
                        'waiting_for_advance' => __('_project_status_waiting_for_advance'),
                        'in_progress' => __('_project_status_in_progress'),
                        'on_hold' => __('_project_status_on_hold'),
                        'invoiced' => __('_project_status_invoiced'),
                        'paid' => __('_project_status_paid'),
                        'canceled_by_client' => __('_project_status_canceled_by_client'),
                        'quote_refused' => __('_project_status_quote_refused')
                    ])
                    ->selectablePlaceholder(false),

                TextColumn::make('budget')                    
                    ->label('_project_budget')->translateLabel()
                    ->alignment('right')
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer
                    ->default('-')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('EUR', locale: 'it')
                    ),

                TextColumn::make('priority')
                    ->label('_project_priority')->translateLabel()
                    ->alignment('center')
                    ->badge()
            ])
            ->filters([
                Filter::make('default')
                    ->label('_project_filter_hide_paid_or_lost')->translateLabel()
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', '!=', 'paid')
                        ->Where('status', '!=', 'canceled_by_client')
                        ->Where('status', '!=', 'quote_refused'))
                        ->default(),
                SelectFilter::make('priority')
                    ->label('_project_priority')->translateLabel()
                    ->options([
                        'low' => __('_priority_low'),
                        'medium' => __('_priority_medium'),
                        'high' => __('_priority_high')
                    ]),
                SelectFilter::make('status')
                    ->label('_project_status')->translateLabel()
                    ->options([
                        'planned' => __('_project_status_planned'),
                        'waiting_for_quote' => __('_project_status_waiting_for_quote'),
                        'waiting_for_advance' => __('_project_status_waiting_for_advance'),
                        'in_progress' => __('_project_status_in_progress'),
                        'on_hold' => __('_project_status_on_hold'),
                        'invoiced' => __('_project_status_invoiced'),
                        'paid' => __('_project_status_paid'),
                        'canceled_by_client' => __('_project_status_canceled_by_client'),
                        'quote_refused' => __('_project_status_quote_refused')
                    ]),
                SelectFilter::make('customer')
                    ->label('_project_customer')->translateLabel()                    
                    ->relationship('customer', 'fullname', fn (Builder $query) => $query->orderBy('first_name', 'asc'))
                    ->searchable()
                    ->getSearchResultsUsing(function ($query) {
                        $results = Customer::where(function (Builder $q) use ($query) {
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
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('details')
                    ->label('')
                    ->icon('heroicon-o-clock')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn($record) => TimeEntry::where('project_id', $record->id)->exists())
                    ->badge(fn($record) => TimeEntry::where('project_id', $record->id)->count())
                    ->form(function (Project $record) {
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
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                ->orderBy('status', 'asc')
                ->orderBy('priority', 'desc')
                ->orderBy('name', 'desc');
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
