<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Carbon;

class BalanceController extends Controller
{
    public function showBalance(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
         
        return response()->json(['balance' => $user->balance], 200);
    }





public function addDailyMoneyToBalance(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    if($user->level_id === null) {
        return response()->json(['error' => 'You must be at least level 1 to claim rewards'], 404);
    }

    $now = Carbon::now();

    // Check if user has claimed in last 24 hours
    if ($user->last_daily_claim && $user->last_daily_claim->diffInHours($now) < 24) {
        $remaining = 24 - $user->last_daily_claim->diffInHours($now);
        return response()->json([
            'error' => "You must wait {$remaining} more hours before claiming again."
        ], 403);
    }

    // Get user level and reward
    $user_levelId = $user->level_id;
    $amount = Level::where('id', $user_levelId)->value('money_per_day');

    if ($amount === null) {
        return response()->json(['error' => 'Invalid level'], 400);
    }

    // Add reward
    $user->balance += $amount;
    $user->balance_from_referrals += $amount * 0.1;
    $user->last_daily_claim = $now; // ✅ store last claim time
    $user->save();

    // Add referral bonus (if exists)
    $referrer = $user->referred_by;
    if ($referrer) {
        $referrerUser = User::find($referrer);
        if ($referrerUser) {
            $referrerUser->balance += $amount * 0.1;
            $referrerUser->save();
        }
    }

    return response()->json([
        'balance' => $user->balance,
        'next_claim_available_at' => $now->addHours(24)->toDateTimeString()
    ], 200);
}

}
