<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use Exception;


class WalletController extends Controller
{
    

    public function createWalletForUser()
    {
        $response = Http::post('http://localhost:3001/generate-wallet');
        $data = $response->json();

        $user = auth()->user();

        $user->deposit_address = $data['address'];
        $user->encrypted_private_key = encrypt($data['privateKey']);
        $user->save();

        return response()->json(['address' => $data['address']]);
    }





public function sweepUserFunds(Request $request)
{
    try {
        $user = auth()->user();
        

        if (!$user || !$user->encrypted_private_key) {
            return response()->json([
                'success' => false,
                'error' => 'User has no wallet linked.'
            ], 400);
        }

        try {
            $privateKey = decrypt($user->encrypted_private_key);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or corrupted private key.'
            ], 400);
        }

        // Validate incoming request data
        $request->validate([
            'to' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
        ]);

        $recipient = $request->input('to');
        $amount = $request->input('amount');

        // Call your Node.js wallet service
        $response = Http::post('http://localhost:3001/send-funds', [
            'privateKey' => $privateKey,
            'to' => $recipient,
            'amount' => (string)$amount,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'txHash' => $data['txHash'],
                'message' => $data['message'] ?? 'Funds swept successfully'
            ]);
        } else {
            $data = $response->json();
            return response()->json([
                'success' => false,
                'error' => $data['error'] ?? 'Failed to send transaction'
            ], 500);
        }

    } catch (\Illuminate\Validation\ValidationException $ve) {
        return response()->json([
            'success' => false,
            'error' => 'Validation failed.',
            'messages' => $ve->errors(),
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => 'Unexpected server error: ' . $e->getMessage()
        ], 500);
    }
}



public function getUserWalletBalance()
{
    $user = auth()->user();

    if (!$user || !$user->deposit_address) {
        return response()->json([
            'success' => false,
            'error' => 'User wallet address not found.'
        ], 400);
    }

    try {
        $response = Http::post('http://localhost:3001/get-balance', [
            'address' => $user->deposit_address
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'balance' => $response['balance']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch balance from wallet service.'
            ], 500);
        }
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => 'Unexpected error: ' . $e->getMessage()
        ], 500);
    }
}







}
