<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Todo;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;

use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\TodoResource\Pages;

class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil';

    public static function getNavigationLabel(): string
    {
        return __('_section_todos');
    }

    public static function getLabel(): string
    {
        return __('_section_todo');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_todos');
    }
    
    public static function getNavigationGroup(): string
    {
        return __('_section_group_works');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_completed', 0)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('_todo_title')->translateLabel()
                            ->required()
                            ->maxLength(255),
                        Grid::make(3)
                            ->schema([
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
                                TextInput::make('category')
                                    ->label('_todo_category')->translateLabel()
                                    ->datalist(fn () => Todo::distinct('category')->pluck('category')->toArray())
                                    ->extraAttributes(['style' => 'text-transform: lowercase;']),
                                Select::make('priority')
                                    ->label('_project_priority')->translateLabel()
                                    ->options([
                                        'low' => __('_priority_low'),
                                        'medium' => __('_priority_medium'),
                                        'high' => __('_priority_high')
                                    ])
                                    ->default('low'),
                            ]),
                            MarkdownEditor::make('description')
                                ->label('_todo_description')->translateLabel()
                                ->disableToolbarButtons(['attachFiles'])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table        
            ->groups([
                Group::make('category')
                    ->label(__('_todo_category'))
                    ->collapsible()
            ])->groupingDirectionSettingHidden()
            ->defaultGroup('category')
            ->columns([
                TextColumn::make('title')
                    ->label('_todo_title')->translateLabel()
                    ->extraAttributes(['style' => 'width: 100%'])
                    ->searchable()
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
                TextColumn::make('category')
                    ->label('_todo_category')->translateLabel()
                    ->alignment('center')
                    ->badge()
                    ->default('-')                    
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('_todo_priority')->translateLabel()
                    ->alignment('center')
                    ->badge(),
                ToggleColumn::make('is_completed')
                    ->label('_todo_completed')->translateLabel()
                    ->alignment('center')
                    ->onColor('success')
                    ->offColor('gray')
            ])
            ->persistFiltersInSession()
            ->filters([
                Filter::make('is_completed')
                    ->label('_todo_filter_hide_completed')->translateLabel()
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', false))->default(),
                SelectFilter::make('category')       
                    ->multiple()          
                    ->options(function () {
                        // Recupera tutte le categorie distinte dalla tabella Todo
                        $categories = Todo::distinct('category')
                            ->whereNotNull('category')
                            ->pluck('category')
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
                    ->action(function (Todo $record) {
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
                            'description' => 'Todo: '.$record->title,
                            'user_id' => auth()->user()->id,
                            'start_time' => Carbon::now($timezone),
                            'end_time' => null
                        ]);

                        return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));
                    })
                    ->color('success')
                    ->size('xl'),
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
                ->orderBy('is_completed', 'asc')
                ->orderBy('priority', 'desc');
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
            'index' => Pages\ListTodos::route('/'),
            'create' => Pages\CreateTodo::route('/create'),
            'edit' => Pages\EditTodo::route('/{record}/edit'),
        ];
    }
}
