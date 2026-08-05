@props([
    'heading' => null,
    'logo' => true,
    'subheading' => null,
])

{{--
    Override khusus untuk halaman auth (Login, dll). Sebelumnya komponen ini
    memanggil <x-filament-panels::logo /> yang merender view "filament.brand-logo"
    apa adanya — view itu berisi DUA elemen gambar sekaligus (versi "mini" untuk
    sidebar collapsed + versi "full" untuk sidebar expanded), dan karena halaman
    Login tidak berada di dalam konteks sidebar, keduanya tampil bersamaan =
    logo terlihat duplikat. Di sini logo perusahaan HANYA dirender satu kali,
    rata tengah & responsif (tanpa label teks tambahan di bawahnya — logo
    resminya sudah membawa nama perusahaan sendiri).
--}}
<header class="fi-simple-header simarsip-auth-header flex flex-col items-center">
    @if ($logo)
        <div class="simarsip-auth-brand">
            <img
                src="{{ asset('images/logo-rajawali-full.png') }}"
                alt="PT PG Rajawali I - Sugar Industry and Derivatives"
                class="simarsip-auth-logo"
            >
        </div>
    @endif

    @if (filled($heading))
        <h1
            class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white"
        >
            {{ $heading }}
        </h1>
    @endif

    @if (filled($subheading))
        <p
            class="fi-simple-header-subheading mt-2 text-center text-sm text-gray-500 dark:text-gray-400"
        >
            {{ $subheading }}
        </p>
    @endif
</header>

@once
    @verbatim
    <style>
        .simarsip-auth-header {
            width: 100%;
            text-align: center;
        }

        .simarsip-auth-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem auto;
            width: 100%;
            max-width: 100%;
        }

        .simarsip-auth-logo {
            width: min(230px, 100%);
            height: auto;
            max-width: 100%;
            object-fit: contain;
            display: block;
        }

        /* Responsive: layar kecil (HP) — logo sedikit diperkecil supaya
           tetap proporsional dan tidak memenuhi layar. */
        @media (max-width: 480px) {
            .simarsip-auth-logo {
                width: min(190px, 100%);
            }

            .simarsip-auth-brand {
                margin-bottom: 0.75rem;
            }
        }
    </style>
    @endverbatim
@endonce