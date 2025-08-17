<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Level;

class UserController extends Controller
{
    function getallusers()
    {
        $users = User::all();
        //send without private key, email_verified_at
        $users->makeHidden(['encrypted_private_key', 'email_verified_at']);
        return response()->json($users);
    }



    //referrals


function getUserReferrals($userId)
{
    $user = User::find($userId);    

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $referrals = $user->referrals()
        ->with('level') // eager load the level relation
        ->get()
        ->makeHidden([
            'encrypted_private_key',
            'email_verified_at',
            'referred_by',
            'created_at',
            'updated_at',
            'deposit_address',
            'balance',
            'is_admin',
            'level_id' // hide raw level_id if you want
        ])
        ->map(function ($referral) {
            return [
                'id' => $referral->id,
                'name' => $referral->name,
                'email' => $referral->email,
                'is_admin' => $referral->is_admin,
                'referral_amount' => $referral->balance_from_referrals,
                'level_number' => $referral->level->level_number ?? null,
            ];
        });

    return response()->json($referrals);
}




public function updateUserLevel(Request $request, $level_number)
{
    $nextLevel = Level::where('level_number', $level_number)->first();

    if (!$nextLevel) {
        return response()->json(['message' => 'Next level not found'], 404);
    }

    $user = auth()->user();
    $currentLevel = $user->level; // This is a Level model or null

    // If user has no level, treat as level_number 0
    $currentLevelNumber = $currentLevel ? $currentLevel->level_number : 0;

    if ($nextLevel->level_number <= $currentLevelNumber) {
        return response()->json(['message' => 'You are already at this level or higher'], 403);
    }
    if ($nextLevel->level_number - $currentLevelNumber > 1) {
        return response()->json(['message' => 'You must unlock levels in order'], 403);
    }

    $userBalance = $user->balance;

    if ($userBalance < $nextLevel->unlock_price) {
        return response()->json(['message' => 'Insufficient balance to unlock this level'], 403);
    }

    $user->balance -= $nextLevel->unlock_price;
    $user->level_id = $nextLevel->id;
    $user->save();

    return response()->json(['message' => 'Level updated successfully']);
}

}
