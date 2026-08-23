<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
if ($user) {
    $tokenObj = $user->createToken('test');
    $token = $tokenObj->plainTextToken;
    echo "Created token: {$token}\n";
    $found = App\Models\PersonalAccessToken::findToken($token);
    echo "Found token instance: " . ($found ? "YES (tokenable ID: " . $found->tokenable_id . ")" : "NO") . "\n";
    if ($found) {
        $tokenable = $found->tokenable;
        echo "Tokenable name: " . ($tokenable ? $tokenable->name : "NULL") . "\n";
    }
} else {
    echo "No users in DB.\n";
}
