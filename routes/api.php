<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationController;
use App\Services\BinanceService;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\BalanceController;
use App\Http\Middleware\IsAdmin;
use App\Http\Controllers\UserController;
use App\Models\Level;
use App\Http\Controllers\LevelController;

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

//balance
Route::middleware('auth:sanctum')->get('/balance', [BalanceController::class, 'showBalance']);
Route::middleware('auth:sanctum')->put('/balance/add-money', [BalanceController::class, 'addMoneyToBalance']);


Route::middleware('auth:sanctum')->get('/levels', [
    App\Http\Controllers\LevelController::class, 'index'
]);

Route::post('/login', [App\Http\Controllers\LoginController::class, 'login'])
    ->name('login');
Route::post('/register', [App\Http\Controllers\RegistrationController::class, 'register']);




Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/users', [UserController::class, 'getallusers']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'username' => $request->user()->name,
        'level' => $request->user()->level_id,       // or any user attribute
        'isAdmin' => $request->user()->is_admin === 1,
    ]);
});

Route::middleware('auth:sanctum')->get('/levels/{id}', function (Request $request, $id) {
    $user = auth()->user();
    $level_id = $user->level_id;
    $level_number = Level::where('id', $level_id)->value('level_number');
    $daily_reward = Level::where('id', $level_id)->value('money_per_day');
    return response()->json([
        'level_number' => $level_number,
        'daily_reward' => $daily_reward
    ]);
});

Route::middleware('auth:sanctum', 'admin')->post('/add_level', [LevelController::class, 'addLevel']);

Route::middleware('auth:sanctum', 'admin')->delete('/delete_level/{level_number}', [LevelController::class, 'deleteLevel']);
Route::middleware('auth:sanctum')->get('/user/{userId}/referrals',[UserController::class, 'getUserReferrals']);
Route::middleware('auth:sanctum')->put('/update/level/{level_number}', [UserController::class, 'updateUserLevel']);
Route::middleware('auth:sanctum')->put('/addmoney', [BalanceController::class, 'addDailyMoneyToBalance']);
Route::middleware('auth:sanctum')->get('/user/last-claim', [UserController::class, 'UserLastClaim']);
