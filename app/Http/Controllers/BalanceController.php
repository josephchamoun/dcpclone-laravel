<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\User;

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

    public function addMoneyToBalance(Request $request)
    {
        $amount = 1;
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->balance += $amount;
        $user->save();

        return response()->json(['balance' => $user->balance], 200);
    }

    public function addDailyMoneyToBalance(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user_levelId = $user->level_id;
        $amount = Level::where('id', $user_levelId)->value('money_per_day');

        if ($amount === null) {
            return response()->json(['error' => 'Invalid level'], 400);
        }

        $user->balance += $amount;
        $user->balance_from_referrals += $amount * 0.1;
        $user->save();

        $referrer = $user->referred_by;
        if ($referrer) {
            $referrerUser = User::find($referrer);
            if ($referrerUser) {
                // 10 % of the amount
                $referrerUser->balance += $amount * 0.1;
                $referrerUser->save();

            }
        }


        return response()->json(['balance' => $user->balance], 200);
    }
}
