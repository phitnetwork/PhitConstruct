<?php

namespace App\Filament\Resources\AccountTypeResource;

use Carbon\Carbon;
use App\Models\Project;
use Livewire\Component;
use App\Models\Customer;
use App\Models\TimeEntry;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Tables\Grouping\Group;

use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;


class RelatedTrackingTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $dynamicId;
    public string $classType;

    public function mount($dynamicId, string $classType)
    {
        if (!in_array($classType, [Customer::class, Project::class])) {
            throw new \InvalidArgumentException("Invalid class type provided: $classType");
        }

        $this->dynamicId = $dynamicId;
        $this->classType = $classType;
    }

    protected function getTableQuery()
    {
        // Verifica la classe e costruisce la query di conseguenza
        if ($this->classType === Customer::class) {
            return TimeEntry::where('customer_id', $this->dynamicId);
        } elseif ($this->classType === Project::class) {
            return TimeEntry::where('project_id', $this->dynamicId);
        }

        // In caso di classType non gestito
        throw new \InvalidArgumentException("Unhandled class type: $this->classType");
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->groups([
                Group::make('start_time')
                    ->label('Data')
                    ->date()
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->orderQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('start_time', 'desc')),
            ])
            ->groupingSettingsHidden()
            ->defaultGroup('start_time')
            ->columns([
                TextColumn::make('description')
                    ->label('')
                    ->extraAttributes(['style' => 'width: 100%;']),

                TextColumn::make('customer.full_name')
                    ->label('')
                    ->badge()
                    ->alignment('center')
                    ->tooltip(__('_section_customer'))
                    ->formatStateUsing(function(string $state, TimeEntry $record)
                    {
                        $customer = $record->customer;
                        $project = $record->project;

                        if($customer && $project)
                            return new HtmlString($customer->full_name . ' <br> ' . $project->name);
                        else if($customer)
                            return $customer->full_name;
                        else if($project)
                            return $project->name;
                        else
                            return '';
                    }),

                IconColumn::make('billable')
                    ->label('')                  
                    ->alignment('center')
                    ->trueIcon('heroicon-o-currency-euro')
                    ->falseIcon('heroicon-o-currency-euro')
                    ->trueColor('primary')
                    ->falseColor('secondary')
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
            ->filters([
                // Aggiungi filtri se necessario
            ])
            ->actions([
                // Aggiungi azioni se necessario
            ])
            ->bulkActions([
                // Aggiungi azioni bulk se necessario
            ])
            ->defaultSort('start_time', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption('all');
    }

    public function render(): View
    {
        return view('livewire.related-tracking-table');
    }
}