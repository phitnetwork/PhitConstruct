<?php

namespace App\Filament\Pages\Tenancy;
 
use DateTimeZone;
use Filament\Forms\Form;
use App\Models\Organization;
use Filament\Facades\Filament;
use App\Models\OrganizationSetting;
use Filament\Forms\Components\Tabs;
use App\Filament\Config\CurrencyList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
 
class EditOrganizationProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Organization profile';
    }
 
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        
                        Tab::make('General')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Organization Name')
                                    ->required(),

                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()                                
                            ]),

                        Tab::make('Settings')
                            ->schema([

                                Select::make('settings.timezone')
                                    ->label('Timezone')
                                    ->searchable()
                                    ->options(function () {
                                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

                                        // Costruisci un array di opzioni con il nome della timezone come chiave e valore
                                        $options = [];
                                        foreach ($timezones as $timezone) {
                                            $options[$timezone] = $timezone;
                                        }

                                        return $options;
                                    })
                                    ->required()
                                    ->default(function (Organization $record) {
                                        return $record->getSetting('timezone', 'UTC');
                                    }),

                                Select::make('settings.currency')                    
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
                            ])
                    ])                
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $organization = Organization::find(Filament::getTenant()->id);

        $organization->settings()->updateOrCreate(
            [],
            [
                'timezone' => $data['settings']['timezone'],
                'currency' => $data['settings']['currency'],
            ]
        );

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {        
        $organization = Organization::find(Filament::getTenant()->id);

        if ($organization && $organization->settings) {
            $data['settings'] = [
                'timezone' => $organization->settings->timezone,
                'currency' => $organization->settings->currency,
            ];
        }

        return $data;
    }
}