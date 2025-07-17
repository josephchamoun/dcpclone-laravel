<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationController;
use App\Services\BinanceService;
use App\Http\Controllers\WalletController;

//hayde betjib l balance bi binance 
Route::get('/wallet', function (App\Services\BinanceService $binance) {
    $data = $binance->getBalances();
    $nonZero = collect($data['balances'])
        ->filter(fn($asset) => floatval($asset['free']) > 0 || floatval($asset['locked']) > 0)
        ->values(); // Reset keys
    return response()->json($nonZero);
});


Route::middleware('auth:sanctum')->post('/wallet/generate', [WalletController::class, 'createWalletForUser']);
Route::middleware('auth:sanctum')->post('/wallet/sweep', [WalletController::class, 'sweepUserFunds']);
Route::middleware('auth:sanctum')->get('/wallet/balance', [WalletController::class, 'getUserWalletBalance']);




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [App\Http\Controllers\LoginController::class, 'login'])
    ->name('login');
Route::post('/register', [App\Http\Controllers\RegistrationController::class, 'register']);





Route::middleware('auth:sanctum')->get('/debug/private-key', function () {
    $user = auth()->user();

    if (!$user || !$user->encrypted_private_key) {
        return response()->json(['error' => 'No wallet found for user'], 404);
    }

    try {
        $privateKey = decrypt($user->encrypted_private_key);
        return response()->json([
            'address' => $user->deposit_address,
            'privateKey' => $privateKey
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to decrypt private key'], 500);
    }
});



