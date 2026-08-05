@if (in_array($tipe, ['jpg', 'jpeg', 'png']))
    <img src="{{ $url }}" alt="Preview Dokumen" class="w-full rounded-lg border border-gray-200">
@elseif ($tipe === 'pdf')
    <iframe src="{{ $url }}" class="w-full rounded-lg border border-gray-200" style="height: 70vh;"></iframe>
@else
    <div class="text-center py-10 text-gray-500">
        <p class="mb-3">Tipe file <strong>.{{ $tipe }}</strong> tidak bisa ditampilkan langsung di sini (misalnya Excel).</p>
        <a href="{{ $url }}" target="_blank" class="fi-btn fi-color-primary inline-flex items-center px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-semibold">
            Buka / Download File
        </a>
    </div>
@endif
