<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the user
$user = DB::table('users')->where('email', 'john@opf-cd.test')->first();
if (!$user) {
    die("User not found!\n");
}

// Get the Admin role
$adminRole = DB::table('roles')->where('name', 'Admin')->first();
if (!$adminRole) {
    die("Admin role not found!\n");
}

// Check if already assigned
$existing = DB::table('user_roles')
    ->where('user_id', $user->id)
    ->where('role_id', $adminRole->id)
    ->exists();

if ($existing) {
    echo "User already has Admin role!\n";
} else {
    // Assign Admin role to user
    DB::table('user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $adminRole->id,
        'created_at' => now(),
    ]);
    echo "Admin role assigned to john@opf-cd.test!\n";
}

// Show user's permissions
$permissions = DB::table('permissions')
    ->join('user_roles', 'permissions.role_id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->select('permissions.resource', 'permissions.action')
    ->get();

echo "\nUser has " . count($permissions) . " permissions:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm->resource}: {$perm->action}\n";
}
