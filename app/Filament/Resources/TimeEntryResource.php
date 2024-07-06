<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Illuminate\Database\Query\Builder;

use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;

use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextInputColumn;

use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\Summarizers\Range;
use App\Filament\Resources\TimeEntryResource\Pages;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $tenantRelationshipName = 'time_entries';

    public static function getNavigationLabel(): string
    {
        return __('_section_time_entries');
    }

    public static function getLabel(): string
    {
        return __('_section_time_entry');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_time_entries');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_works');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('description')
                            ->label('_time_entry_description')->translateLabel()
                            ->required()
                            ->maxLength(255),

                TagsInput::make('tags')
                    ->label('_time_entry_tags')->translateLabel()
                    ->reorderable(),

                Select::make('project_id')
                    ->label('_section_project')->translateLabel()
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

                Select::make('customer_id')
                    ->label('_section_customer')->translateLabel()
                    ->relationship('customer', 'full_name')
                    ->default(null)
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
                            ]),

                DateTimePicker::make('start_time')
                    ->label('_time_entry_start_time')->translateLabel()
                    ->default(function () {
                        $organization = Filament::getTenant();
                        $timezone = $organization->getSetting('timezone', 'UTC');
                        return Carbon::now($timezone)->format('Y-m-d H:i:s');
                    })
                    ->required(),

                DateTimePicker::make('end_time')
                    ->label('_time_entry_end_time')->translateLabel(),

                Hidden::make('user_id')->default(auth()->user()->id)
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('start_time')
                    ->label('Data')
                    ->date()
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->orderQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('start_time', 'desc')),
            ])
            ->groupingDirectionSettingHidden()
            ->defaultGroup('start_time')
            ->columns([
                TextInputColumn::make('description')
                    ->label('')
                    ->extraAttributes(['style' => 'width: 100%;'])
                    ->rules(['required', 'max:255'])
                    ->afterStateUpdated(function ($record, $state) {

                        Notification::make()
                            ->title(__('_saved'))
                            ->success()
                            ->send();

                        return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));

                        return $state;}),

                TextColumn::make('customer_project')
                    ->label(__('_section_customer') . ' / ' . __('_section_project'))
                    ->state(function (TimeEntry $record) {
                        $customerName = $record->customer ? $record->customer->full_name : '';
                        $projectName = $record->project ? $record->project->name : '';
                        
                        if ($customerName && $projectName) {
                            return new HtmlString("{$customerName} <br> {$projectName}");
                        } elseif ($customerName) {
                            return $customerName;
                        } elseif ($projectName) {
                            return $projectName;
                        } else {
                            return '';
                        }
                    })
                    ->badge()
                    ->alignment('center'),

                ToggleIconColumn::make('billable')
                    ->label('')                  
                    ->alignment('center')
                    ->onIcon('heroicon-o-currency-euro')
                    ->offIcon('heroicon-o-currency-euro')
                    ->onColor('primary')
                    ->offColor('secondary')
                    ->tooltip(__('_time_entry_billable')),
                    
                TextColumn::make('start_time')
                    ->label('')
                    ->alignment('center')
                    ->formatStateUsing(function(string $state, TimeEntry $record)
                    {
                        $start = Carbon::parse($record->start_time);
                        $stop = Carbon::parse($record->end_time);
                        if($record->end_time)
                            return $start->format('H:i') . ' -> ' . $stop->format('H:i');
                        else
                            return $start->format('H:i') . ' -> ' . __('_time_entry_in_progress');
                    }),

                TextColumn::make('duration')
                    ->label('')
                    ->alignment('center')
                    ->summarize(Summarizer::make()
                        ->using(function (Builder $query) 
                        {
                            $state = $query->sum('duration');
                            $duration = Carbon::createFromTimestampUTC(0)->addSeconds(intval($state));
                            return new HtmlString('<span style="font-size: 18px; font-weight: 700">' . $duration->format('H:i') . '</span>');
                        })    
                    )
                    ->formatStateUsing(function(string $state, TimeEntry $record)
                    {
                        $duration = Carbon::createFromTimestampUTC($state);
                        return new HtmlString('<span style="font-size: 18px; font-weight: 700">' . $duration->format('H:i') . '</span>');
                    })
            ])
            ->persistFiltersInSession()            
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('_section_customer')->translateLabel()                    
                    ->relationship('customer', 'full_name'),

                SelectFilter::make('project_id')
                    ->label('_section_project')->translateLabel()                    
                    ->relationship('project', 'name')
            ])
            ->actions([
                Action::make('duplicate')
                    ->label('')
                    ->icon('heroicon-s-play-circle')
                    ->action(function (TimeEntry $record) {
                        $organization = Filament::getTenant();
                        $timezone = $organization->getSetting('timezone', 'UTC');
                        
                        // Cerca se c'è un record attivo (senza end_time)
                        $activeRecord = TimeEntry::where('user_id', auth()->user()->id)
                            ->whereNull('end_time')
                            ->first();

                        if ($activeRecord) {
                            // Ferma il record attivo
                            $activeRecord->end_time = Carbon::now($timezone);
                            $activeRecord->save();
                        }
                        
                        // creo nuovo
                        $newRecord = $record->replicate(['duration']);
                        $newRecord->start_time = Carbon::now($timezone);
                        $newRecord->end_time = null;
                        $newRecord->save();
                    })
                    ->color('success')
                    ->size('xl')
                    ->visible(fn (TimeEntry $record) => $record->end_time !== null),

                Action::make('stop')
                    ->label('')
                    ->icon('heroicon-s-stop-circle')
                    ->action(function (TimeEntry $record) {
                        $organization = Filament::getTenant();
                        $timezone = $organization->getSetting('timezone', 'UTC');

                        $record->end_time = Carbon::now($timezone);
                        $record->save();
                    })
                    ->color('danger')
                    ->size('xl')
                    ->visible(fn (TimeEntry $record) => $record->end_time === null),

                Tables\Actions\DeleteAction::make()
                    ->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'desc')
            ->headerActions([
                Action::make('fast_entry')
                    ->label('_time_entry_create_fast_entry')->translateLabel()
                    ->icon('heroicon-s-plus-circle')
                    ->action(function () {
                        $organization = Filament::getTenant();
                        $timezone = $organization->getSetting('timezone', 'UTC');

                        // Cerca se c'è un record attivo (senza end_time)
                        $activeRecord = TimeEntry::where('user_id', auth()->user()->id)
                            ->whereNull('end_time')
                            ->first();

                        if ($activeRecord) {
                            // Ferma il record attivo
                            $activeRecord->end_time = Carbon::now($timezone);
                            $activeRecord->save();
                        }

                        TimeEntry::create([
                            'organization_id' => Filament::getTenant()->id,
                            'description' => 'New Tracking',
                            'user_id' => auth()->user()->id,
                            'start_time' => Carbon::now($timezone),
                            'end_time' => null
                        ]);

                        return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));
                    })
                    ->color('success')
                    ->size('xl'),

                Action::make('fast_stop')
                    ->label('_time_entry_create_fast_stop')->translateLabel()
                    ->icon('heroicon-s-stop-circle')
                    ->action(function () {
                        $organization = Filament::getTenant();
                        $timezone = $organization->getSetting('timezone', 'UTC');

                        // Cerca se c'è un record attivo (senza end_time)
                        $activeRecord = TimeEntry::where('user_id', auth()->user()->id)
                            ->whereNull('end_time')
                            ->first();

                        if ($activeRecord) {
                            // Ferma il record attivo
                            $activeRecord->end_time = Carbon::now($timezone);
                            $activeRecord->save();
                        }

                        return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));
                    })
                    ->color('danger')
                    ->size('xl')
                    ->visible(function() {
                        $activeRecord = TimeEntry::where('user_id', auth()->user()->id);
                        return $activeRecord->whereNull('end_time')->exists();
                    })
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
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
