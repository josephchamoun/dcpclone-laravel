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
    public function addLevel(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_number' => 'required|integer|unique:levels,level_number',
            'money_per_day' => 'required|numeric|min:0',
            'unlock_price' => 'required|numeric|min:0',

        ]);

        $level = Level::create([
            'name' => $request->name,
            'description' => $request->description,
            'level_number' => $request->level_number,
            'money_per_day' => $request->money_per_day,
            'unlock_price' => $request->unlock_price,
        ]);

        return response()->json($level, 201);
    }
}
