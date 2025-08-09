<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
        $referrals = $user->referrals()->get()->makeHidden(['encrypted_private_key', 'email_verified_at', 'referred_by', 'created_at', 'updated_at', 'deposit_address', 'referred_by','balance', 'is_admin']);

        return response()->json($referrals);
    }
}
