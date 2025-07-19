<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    function getallusers()
    {
        $users = User::all();
        //send without private key
        $users->makeHidden(['encrypted_private_key']);
        return response()->json($users);
    }
}
