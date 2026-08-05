@props(['navigation', 'livewire' => null])

@php
    // Ambil judul halaman dari Livewire/Filament Page (jika tersedia) supaya
    // judul selalu sejajar dengan Search & Profile pada satu baris topbar,
    // konsisten di semua halaman panel.
    $pageHeading = null;

    if ($livewire && method_exists($livewire, 'getHeading')) {
        try {
            $pageHeading = $livewire->getHeading();
        } catch (\Throwable $e) {
            $pageHeading = null;
        }
    }
@endphp

<div class="fi-topbar sticky top-0 z-20">
    <nav class="flex h-16 items-center gap-3 border-b border-[#C2C6D3] bg-[#F8F9FA] px-4 md:px-6 lg:px-8">
        <x-filament::icon-button
            color="gray"
            icon="heroicon-o-bars-3"
            icon-size="lg"
            label="Buka menu"
            x-data="{}"
            x-on:click="$store.sidebar.open()"
            x-show="! $store.sidebar.isOpen"
            class="lg:hidden"
        />

        <x-filament::icon-button
            color="gray"
            icon="heroicon-o-x-mark"
            icon-size="lg"
            label="Tutup menu"
            x-data="{}"
            x-on:click="$store.sidebar.close()"
            x-show="$store.sidebar.isOpen"
            class="lg:hidden"
        />

        @if (filled($pageHeading))
            <h1 class="rajawali-topbar-heading">{{ $pageHeading }}</h1>
        @endif

        <div class="ms-auto flex items-center gap-4">
            @if (filament()->isGlobalSearchEnabled())
                @livewire(Filament\Livewire\GlobalSearch::class)
            @endif

            @if (filament()->auth()->check())
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                <x-filament-panels::user-menu />

                <form
                    action="{{ filament()->getLogoutUrl() }}"
                    method="post"
                    class="rajawali-logout-form"
                    x-data="{}"
                    x-on:submit.prevent="
                        $el.querySelector('button').disabled = true;
                        fetch($el.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'X-CSRF-TOKEN': $el.querySelector('input[name=_token]').value },
                        }).finally(() => {
                            window.location.href = '{{ filament()->getLoginUrl() }}';
                        });
                    "
                >
                    @csrf
                    <button type="submit" class="rajawali-logout-btn" title="Logout">
                        <span class="material-symbols-outlined" style="font-size:20px;">logout</span>
                        <span class="rajawali-logout-label">Logout</span>
                    </button>
                </form>
            @endif
        </div>
    </nav>
</div>

@verbatim
<style>
    .rajawali-topbar-heading {
        margin: 0;
        min-width: 0;
        flex-shrink: 1;
        font-size: 1.375rem;
        font-weight: 800;
        letter-spacing: -0.01em;
        color: #1E293B;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    @media (max-width: 640px) {
        .rajawali-topbar-heading {
            font-size: 1rem;
            max-width: 45vw;
        }
    }
    .rajawali-logout-form {
        display: flex;
        margin: 0;
    }
    .rajawali-logout-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #E2E5EA;
        background: #ffffff;
        color: #B3261E;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color .15s ease, border-color .15s ease;
    }
    .rajawali-logout-btn:hover {
        background: #FCEAEA;
        border-color: #F3C6C4;
    }
    @media (max-width: 640px) {
        .rajawali-logout-label {
            display: none;
        }
        .rajawali-logout-btn {
            padding: 8px;
        }
    }
</style>
@endverbatim
