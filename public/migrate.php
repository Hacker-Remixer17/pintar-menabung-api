<?php
require __DIR__.'/../vendor/autoload.php';
 $app = require_once __DIR__.'/../bootstrap/app.php';

 $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
 $kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

echo "Migrasi database berhasil! Anda bisa menutup halaman ini.";
