<x-filament-panels::page.simple>
    <img
        src="{{ asset('images/logo-rajawali-full.png') }}"
        alt="Rajawali I — Sugar Industry and Derivatives, Unit PG Krebet Baru"
        class="rajawali-login-logo"
    >

    <p class="rajawali-login-desc">
        Platform digital pengelolaan arsip dokumen akuntansi &amp; keuangan
        PT PG Rajawali I Unit Krebet Baru secara aman, cepat, dan terintegrasi.
    </p>

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
