@php
    $bagianLabel = match ($user?->bagian) {
        'admin_it' => 'Admin IT',
        'kabag' => 'Kepala Bagian',
        'admin_bagian' => 'Admin Bagian',
        'staff' => 'Staff',
        default => null,
    };

    $departemenLabel = match ($user?->departemen) {
        'akuntan' => 'Akuntansi',
        'keuangan' => 'Keuangan',
        default => null,
    };

    $jabatan = collect([$bagianLabel, $departemenLabel])->filter()->implode(' · ');

    $avatarUrl = $user?->avatar
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar)
        : null;

    $inisial = $user ? strtoupper(substr($user->name, 0, 1)) : '?';
@endphp

<div style="display:flex; align-items:center; gap:8px; text-align:left; flex-wrap:wrap; max-width:100%;">
    @if ($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
             style="width:32px; height:32px; border-radius:9999px; object-fit:cover; flex-shrink:0;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div style="display:none; width:32px; height:32px; border-radius:9999px; background:#E5E7EB; color:#475569; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">
            {{ $inisial }}
        </div>
    @else
        <div style="width:32px; height:32px; border-radius:9999px; background:#E5E7EB; color:#475569; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">
            {{ $inisial }}
        </div>
    @endif
    <div style="line-height:1.2; min-width:0;">
        <div style="font-weight:600; font-size:13px; color:#1E293B; overflow-wrap:break-word;">{{ $user?->name ?? '-' }}</div>
        @if ($jabatan)
            <div style="font-size:11px; color:#64748B; overflow-wrap:break-word;">{{ $jabatan }}</div>
        @endif
    </div>
</div>
