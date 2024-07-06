<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Campaign;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\CampaignResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CampaignResource\RelationManagers;
use Filament\Forms\Components\RichEditor;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationLabel(): string
    {
        return __('_section_campaigns');
    }

    public static function getLabel(): string
    {
        return __('_section_campaign');
    }

    public static function getPluralLabel(): string
    {
        return __('_section_campaigns');
    }

    public static function getNavigationGroup(): string
    {
        return __('_section_group_activities');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('_campaign_name')->translateLabel()
                            ->required()
                            ->maxLength(255),

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

                        DatePicker::make('start_date')
                            ->label('_campaign_start_date')->translateLabel()
                            ->required(),

                        DatePicker::make('end_date')
                            ->label('_campaign_end_date')->translateLabel(),

                        TextInput::make('budget')
                            ->label('_campaign_budget')->translateLabel()
                            ->numeric()      
                            ->prefix('€')
                            ->live()
                            ->default(null),

                        Select::make('status')                
                            ->label('_campaign_status')->translateLabel()
                            ->options([
                                'active' => __('_campaign_status_active'),
                                'inactive' => __('_campaign_status_inactive'),
                                'suspended' => __('_campaign_status_suspended'),
                                'terminated' => __('_campaign_status_terminated'),
                            ])
                            ->selectablePlaceholder(false)
                            ->default('active'),

                        TextInput::make('objective')
                            ->label('_campaign_objective')->translateLabel()
                            ->maxLength(255)
                            ->default(null),

                        Select::make('channel')
                            ->label('_campaign_channel')->translateLabel()
                            ->required()
                            ->options([
                                __('_campaign_channel_category_digital') => [
                                    'paid_ads' => __('_campaign_channel_paid_ads'),
                                    'website' => __('_campaign_channel_website'),
                                    'social_media' => __('_campaign_channel_social_media'),
                                    'email' => __('_campaign_channel_emails'),
                                    'seo_sem' => __('_campaign_channel_seo_sem'),
                                    'influencer' => __('_campaign_channel_influencers'),
                                ],
                                __('_campaign_channel_category_offline') => [
                                    'print' => __('_campaign_channel_print'),
                                    'tv_radio' => __('_campaign_channel_tv_radio'),
                                    'events' => __('_campaign_channel_events'),
                                    'outdoor' => __('_campaign_channel_outdoor'),
                                ],
                                __('_campaign_channel_category_other') => [
                                    'affiliate' => __('_campaign_channel_affiliates'),
                                    'content_marketing' => __('_campaign_channel_content_marketing'),
                                ],
                            ]),

                        RichEditor::make('notes')
                            ->label('_campaign_notes')->translateLabel()
                            ->columnSpanFull(),
                    ])                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('_campaign_name')->translateLabel()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('_campaign_start_date')->translateLabel()
                    ->alignment('right')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('_campaign_end_date')->translateLabel()
                    ->alignment('right')
                    ->date()
                    ->sortable(),
                TextColumn::make('budget')
                    ->label('_campaign_budget')->translateLabel()
                    ->money('EUR', locale: 'it')                    // TODO: locale e valuta presi da customer
                    ->alignment('right')
                    ->label('_project_budget')->translateLabel()
                    ->default('-')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('EUR', locale: 'it')
                    ),
                IconColumn::make('status')
                    ->label('_campaign_status')->translateLabel()
                    ->alignment('center')
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-clock',
                        'inactive' => 'heroicon-o-x-circle',
                        'suspended' => 'heroicon-o-exclamation-triangle',
                        'terminated' => 'heroicon-o-check',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning',
                        'terminated' => 'success',
                    })
                    ->searchable(),
                TextColumn::make('objective')
                    ->label('_campaign_objective')->translateLabel()
                    ->searchable(),
                TextColumn::make('channel')                    
                    ->label('_campaign_channel')->translateLabel()
                    ->alignment('center')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return __('_campaign_channel_'.$state);
                    })
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
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
                ->orderBy('status', 'asc')
                ->orderBy('channel', 'desc')
                ->orderBy('start_date', 'desc')
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
