<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\TimeEntry;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Filament\Notifications\Notification;

class TimeTrackerWidget extends Component
{
    public $currentPage;

    public function mount()
    {
        // Inizializza $currentPage con il nome della route corrente
        $this->currentPage = Route::currentRouteName();
    }

    public function render()
    {
        $activeTimeEntry = TimeEntry::where('user_id', auth()->user()->id)
            ->whereNull('end_time')
            ->first();
        
        if ($activeTimeEntry) 
        {
            $organization = Filament::getTenant(); // Utilizza Filament per ottenere il tenant
            $timezone = $organization->getSetting('timezone', 'UTC');
    
            $now = Carbon::now($timezone);
            $start = Carbon::parse($activeTimeEntry->start_time, $timezone);
    
            $diff = $now->diff($start);
            $diffInSeconds = $start->diffInSeconds($now);
            $formattedDuration = $diff->format('%H:%I:%S');

            return view('livewire.time-tracker-widget', [
                'activeTimeEntry' => $activeTimeEntry,
                'diffInSeconds' => $diffInSeconds,
                'formattedDuration' => $formattedDuration
            ]);
        }

        return view('livewire.time-tracker-widget');
    }

    public function stopTimer()
    {
        $activeTimeEntry = TimeEntry::where('user_id', auth()->user()->id)
            ->whereNull('end_time')
            ->first();

        if ($activeTimeEntry) {
            $organization = Filament::getTenant();
            $timezone = $organization->getSetting('timezone', 'UTC');

            $now = Carbon::now($timezone);

            $activeTimeEntry->end_time = $now->format('Y-m-d H:i:s');
            $activeTimeEntry->save();

            Notification::make()
                ->title(__('_saved'))
                ->success()
                ->send();

            if ($this->currentPage === 'filament.admin.resources.time-entries.index') {
                return redirect()->to(route('filament.admin.resources.time-entries.index', ['tenant' => Filament::getTenant()->name]));
            }
        }
    }
}
