<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level; // ✅ Import Level model

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
        $user->save();

        return response()->json(['balance' => $user->balance], 200);
    }
}
