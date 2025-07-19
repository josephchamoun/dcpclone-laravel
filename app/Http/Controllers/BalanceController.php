<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
