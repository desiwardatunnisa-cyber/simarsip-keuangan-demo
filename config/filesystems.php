<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Diarahkan ke route /file (FileServeController) alih-alih /storage supaya
            // foto profil & preview berkas tetap tampil walau symlink "public/storage"
            // belum/tidak dibuat di server (php artisan storage:link tidak dijalankan).
            'url' => env('APP_URL').'/file',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Disk untuk failover penyimpanan dokumen: dipakai kalau storage lokal
        // (disk "public" di atas) bermasalah saat upload (folder/drive penuh, rusak,
        // atau permission error).
        //
        // PENTING: root-nya "...\storage" (BUKAN "...\storage\app\public") karena
        // struktur nyata di share \\192.168.1.10\SIMARSIP_STORAGE adalah:
        //   storage\documents\...   (folder "documents" langsung di bawah "storage")
        // bukan storage\app\public\documents\... seperti struktur lokal Laravel.
        // Path relatif yang disimpan di kolom path_file (mis. "documents/xxx.pdf")
        // tetap sama untuk disk "public" maupun "cadangan" — cuma root disknya beda.
        'cadangan' => [
            'driver' => 'local',
            'root' => rtrim(env('BACKUP_NETWORK_PATH', '\\\\192.168.1.10\\simarsip_storage'), '\\/') . '\\storage',
            'url' => env('APP_URL').'/file',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
