<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;

class LevelController extends Controller
{
    public function index(Request $request)
    {
        $levels = Level::all();
        return response()->json($levels);
    }
}
