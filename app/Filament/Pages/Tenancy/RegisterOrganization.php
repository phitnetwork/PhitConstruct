<?php 

namespace App\Filament\Pages\Tenancy;
 
use LDAP\Result;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Invitation;
use App\Models\Organization;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\Section;
use Illuminate\Validation\Rules\Exists;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Tenancy\RegisterTenant;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create / Join Organization';
    }
 
    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Select::make('type')
                    ->options([
                        'join' => 'Join an Organization',
                        'create' => 'Create an Organization',
                    ])
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('dynamicTypeFields')
                        ->getChildComponentContainer()
                        ->fill()),
                    
                Grid::make(2)
                    ->schema(fn (Get $get): array => match ($get('type')) {
                        'join' => [
                            TextInput::make('invitation_code')
                                ->required()
                                ->columnSpanFull()
                                ->exists(table: Invitation::class, column: 'code', modifyRuleUsing: function (Exists $rule) {
                                    return $rule->where('used', 0);
                                })
                        ],
                        'create' => [
                            TextInput::make('name')
                                ->required(),
                            TextInput::make('slug')
                                ->required(),
                        ],
                        default => [],
                    })
                    ->key('dynamicTypeFields')                    

            ]);
    }
 
    protected function handleRegistration(array $data): Organization
    {

        if ($data['type'] === 'join') {
            $invitation = Invitation::where('code', $data['invitation_code'])->firstOrFail();            
            $organization = $invitation->organization;
            $organization->members()->attach(auth()->user()->id);

            $invitation->used = 1;
            $invitation->save();
        }

        if($data['type'] === 'create') {
            $organization = Organization::create($data);
            $organization->owner_id = auth()->id();
            $organization->save();
            $organization->members()->attach(auth()->user());            
        }

        return $organization;
    }
}