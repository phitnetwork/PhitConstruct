<?php

namespace App\Providers;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['it','en'])
                ->circular();
        });

        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([50, 100, 500]);
        });

        Form::configureUsing(function (Form $form): void {
            $form
                ->extraAttributes(['style'=>'gap:1rem']);
        });
    }
}
