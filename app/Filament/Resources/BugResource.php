<?php

namespace App\Filament\Resources;

use stdClass;
use Carbon\Carbon;
use App\Models\Bug;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BugResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use App\Filament\Resources\BugResource\RelationManagers;

class BugResource extends Resource
{
    protected static ?string $model = Bug::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    public static function getNavigationLabel(): string
    {
        return __('_section_bugs');
    }

    public static function getLabel(): string
    {
        return __('_section_bug');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_bugs');
    }

    public static function getNavigationGroup(): string
    {
        return __('_section_group_works');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_solved', 0)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([

                        TextInput::make('title')
                            ->label('_bug_title')->translateLabel()
                            ->required()
                            ->maxLength(255),

                        Grid::make(3)
                            ->schema([
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

                                Select::make('priority')
                                    ->label('_bug_priority')->translateLabel()
                                    ->options([
                                        'low' => __('_priority_low'),
                                        'medium' => __('_priority_medium'),
                                        'high' => __('_priority_high')
                                    ])
                                    ->default('low')
                                    ->selectablePlaceholder(false),

                                Select::make('status')
                                    ->label('_bug_status')->translateLabel()
                                    ->options([
                                        'open' => __('_bug_status_open'),
                                        'clsoed' => __('_bug_status_closed')
                                    ])
                                    ->default('open')
                                    ->selectablePlaceholder(false),
                            ]),

                        Grid::make(2)
                            ->schema([                               

                                Select::make('created_by')
                                    ->label('_bug_created_by')->translateLabel()
                                    ->relationship('createdBy', 'name')
                                    ->default(fn () => auth()->user()->id)
                                    ->searchable(),
                                    
                                Select::make('assigned_to')
                                    ->label('_bug_assigned_to')->translateLabel()
                                    ->relationship('assignedTo', 'name')
                                    ->default(null)
                                    ->searchable(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TagsInput::make('labels')
                                    ->label('_bug_labels')->translateLabel()
                                    ->reorderable(),
                                    
                                DatePicker::make('deadline')
                                    ->label('_bug_deadline')->translateLabel(),

                            ]),
                            
                        Textarea::make('problem_notes')
                            ->label('_bug_problem_notes')->translateLabel()
                            ->rows(10)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('steps_to_reproduce')
                            ->label('_bug_steps_to_reproduce')->translateLabel()
                            ->rows(10)
                            ->columnSpanFull(),

                        
                    ]),

                Section::make(__('_section_group_bug_details'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('software_version')
                            ->label('_bug_software_version')->translateLabel()
                            ->maxLength(255)
                            ->default(null),

                        Select::make('environment')
                            ->label('_bug_environment')->translateLabel()
                            ->options([
                                'development' => __('_bug_environment_development'),
                                'production' => __('_bug_environment_production'),
                            ])
                            ->default('development')
                            ->selectablePlaceholder(false),

                        Select::make('type')
                            ->label('_bug_type')->translateLabel()
                            ->options([
                                'functional' => __('_bug_type_functional'),
                                'visual' => __('_bug_type_visual'),
                                'security' => __('_bug_type_security'),
                                'other' => __('_bug_type_other'),
                            ])
                            ->default('functional')
                            ->selectablePlaceholder(false),

                        FileUpload::make('attachments')
                            ->label('_bug_attachments')->translateLabel()
                            ->maxSize(1024 * 1024 * 5) // 5mb
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'])
                            ->disk('public')
                            ->directory('bug_attachments')
                            ->placeholder('Drag and drop files here or click to upload')
                            ->multiple()
                            ->columnSpanFull(),
                        Textarea::make('resolution_notes')
                            ->label('_bug_resolution_notes')->translateLabel()
                            ->rows(10)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('_bug_id')->translateLabel()
                    ->tooltip(__('_bug_tooltip_clicktocopy'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('_copied'))
                    ->copyMessageDuration(1500)
                    ->getStateUsing(function (Bug $record) {
                        return new HtmlString('<span style="display: inline !important; font-weight: bold">#'.$record->id.'</span>');
                    }),

                TextColumn::make('title')
                    ->label('_bug_title')->translateLabel()
                    ->extraAttributes(['style' => 'width: 100%'])
                    ->searchable()
                    ->getStateUsing(function (Bug $record) {
                        $project = $record->project;
                        $html = $record->title;
                        
                        if($record->attachments)
                            $html .= '&nbsp;'.Blade::render('<x-heroicon-s-document-arrow-down class="w-5 h-5 inline"/>');

                        return new HtmlString('<span style="display: inline !important">'.$html.'</span>');
                    })
                    ->prefix(function ($record) {
                        $prefix = optional($record->project)->name ? $record->project->name : '';
                        
                        // Check if the project has a customer associated
                        if (isset($record->project->customer)) {
                            $color = $record->project->customer->color;
                            if ($color) {
                                $prefix = '<span style="color: white; padding: 4px 8px; border-radius: 6px; background-color: ' . $color . '; border: 1px solid #242427">' . $prefix . '</span> ';
                            }
                        }
                        
                        return new HtmlString($prefix);
                    }),
                TextColumn::make('priority')
                    ->label('_bug_priority')->translateLabel()
                    ->alignment('center')
                    ->badge(),

                ToggleIconColumn::make('is_solved')                    
                    ->label(__('_bug_is_solved').'?')
                    ->alignment('center')
                    ->tooltip(fn($state) => $state ? __('_bug_tooltip_set_unsolved') : __('_bug_tooltip_set_solved'))
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('primary')
                    ->offColor('secondary'),
            ])
            ->filters([
                Filter::make('is_solved')
                    ->label('_bug_filter_hide_solved')->translateLabel()
                    ->query(fn (Builder $query): Builder => $query->where('is_solved', false))->default(),
                SelectFilter::make('category')       
                    ->multiple()          
                    ->options(function () {
                        // Recupera tutte le categorie distinte dalla tabella Todo
                        $categories = Bug::distinct('project_id')
                            ->whereNotNull('project_id')
                            ->pluck('project_id')
                            ->toArray();
                
                        // Costruisci un array associativo chiave-valore per le opzioni
                        $options = [];
                        foreach ($categories as $category) {
                            $options[$category] = $category;
                        }
                
                        return $options;
                    })
            ])
            ->actions([
                Action::make('fast_entry')
                    ->label('')
                    ->icon('heroicon-s-play-circle')
                    ->tooltip(fn($record) => __('_bug_tooltip_time_entry'))
                    ->action(function (Bug $record) {
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
                            'project_id' => $record->project_id,
                            'description' => 'Bug #'.$record->id.' - '.$record->title,
                            'user_id' => auth()->user()->id,
                            'start_time' => Carbon::now($timezone),
                            'end_time' => null
                        ]);

                        return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));
                    })
                    ->color('success')
                    ->size('xl'),
                Tables\Actions\DeleteAction::make()
                ->label(__('')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort(function (Builder $query): Builder {
                return $query
                ->orderBy('is_solved', 'asc')
                ->orderBy('priority', 'desc');
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
            'index' => Pages\ListBugs::route('/'),
            'create' => Pages\CreateBug::route('/create'),
            'edit' => Pages\EditBug::route('/{record}/edit'),
        ];
    }
}
