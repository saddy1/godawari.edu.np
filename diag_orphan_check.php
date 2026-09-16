<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::with('roles', 'student')->find(63);

if (! $u) {
    echo "user 63 not found\n";
    exit(1);
}

echo 'name: '.$u->name."\n";
echo 'roles: '.$u->roles->pluck('name')->implode(',')."\n";
echo 'has student: '.($u->student ? 'YES (student id '.$u->student->id.')' : 'NO')."\n";

$orphans = App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'staff']))
    ->whereDoesntHave('student')
    ->count();
echo 'total orphan users on this server: '.$orphans."\n";

if (function_exists('opcache_get_status')) {
    $s = @opcache_get_status(false);
    echo 'opcache enabled: '.($s ? 'YES' : 'NO')."\n";
    if ($s && function_exists('opcache_reset')) {
        opcache_reset();
        echo "opcache reset: DONE\n";
    }
} else {
    echo "opcache: not available\n";
}
