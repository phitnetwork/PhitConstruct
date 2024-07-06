<?php

namespace App\Providers\Filament;

use livewire;
use Carbon\Carbon;
use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\TimeEntry;
use Filament\PanelProvider;
use App\Models\Organization;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;

use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Filament\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Blade;
use App\Http\Middleware\ApplyTenantScopes;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Pages\Tenancy\RegisterOrganization;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Tenancy\EditOrganizationProfile;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\Resources\TimeEntryResource\Widgets\TimeTrackingWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentView::registerRenderHook(
            'panels::TOPBAR_START',
            fn (): View => view('filament.resources.time-entry-resource.widgets.time-tracking-widget')
        );

        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::End);

        $panel->sidebarWidth('15rem');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Purple,
            ])
            //->topNavigation()
            ->sidebarFullyCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Settings')
                    ->url('/admin/settings')
                    ->icon('heroicon-o-cog-6-tooth')
            ])                
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->breadcrumbs(false)
            ->tenant(Organization::class, ownershipRelationship: 'organization', slugAttribute: 'slug')
            ->tenantRegistration(RegisterOrganization::class)
            ->tenantProfile(EditOrganizationProfile::class)
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ], isPersistent: true)
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                function () {

                    $render = '';

                    // Invite member func
                    $createInvitationUrl = route('filament.admin.resources.invitations.index', ['tenant' => Filament::getTenant()->name]);
                    $render .= "<a href=".$createInvitationUrl.">".__('_invite_member')."</a>";
                    
                    $render .= Blade::render('@livewire(\'TimeTrackerWidget\')');

                    return "<div style='width:100%; display: flex; justify-content: space-between;'>".$render."</div>";
                }
            );
    }
}
