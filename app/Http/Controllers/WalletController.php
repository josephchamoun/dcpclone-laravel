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
        $userthatreceives = User::where('deposit_address', $recipient)->first();




        if ($response->successful()) {
            $data = $response->json();
            if ($userthatreceives) {
                $userthatreceives->balance += $amount;
                $userthatreceives->save();
            }

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
        $response = Http::withHeaders([
            'x-api-key' => env('NODE_API_KEY'),
        ])->post(env('NODE_API_URL') . '/get-balance', [
            'address' => $user->deposit_address
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'balance' => $response->json()['balance']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $response->json()['error'] ?? 'Failed to fetch balance'
            ], 500);
        }
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => 'Unexpected error: ' . $e->getMessage()
        ], 500);
    }
}



public function sweepAllWallets()
    {
        // Your safe admin wallet
        $adminWallet = "0xb51447AC939095F2Be65dE4dBeb51B91D4Cb4086";

        // Loop over all users
        $users = User::all();

        foreach ($users as $user) {
            try {
                // 1. Decrypt private key
                $privateKey = decrypt($user->encrypted_private_key);

                // 2. Call Node.js API to send funds
                $response = Http::withHeaders([
                    'x-api-key' => env('NODE_API_KEY'), // Protects your API
                ])->post(env('NODE_API_URL') . '/send-funds', [
                    'fromPrivateKey' => $privateKey,
                    'toAddress'      => $adminWallet,
                    'amount'         => 'ALL', // Node.js will fetch balance
                ]);

                if ($response->failed()) {
                    \Log::error("Failed to sweep wallet for user {$user->id}", [
                        'response' => $response->body()
                    ]);
                } else {
                    \Log::info("Swept wallet for user {$user->id}", [
                        'txHash' => $response->json()['txHash'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error("Error sweeping wallet for user {$user->id}: " . $e->getMessage());
            }
        }

        return response()->json(['status' => 'done']);
    }




    public function sendFundsBetweenUsers(Request $request)
{
    try {
        // Validate input
        $request->validate([
            'from_user_id' => ['required', 'integer', 'exists:users,id'],
            'to_address'   => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'amount'       => ['required', 'numeric', 'min:0.00000001'],
        ]);

        $fromUser = User::find($request->input('from_user_id'));

        if (!$fromUser || !$fromUser->encrypted_private_key) {
            return response()->json([
                'success' => false,
                'error' => 'Sender wallet not found.'
            ], 400);
        }

        // Decrypt private key
        try {
            $privateKey = decrypt($fromUser->encrypted_private_key);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or corrupted private key.'
            ], 400);
        }

        $toAddress = $request->input('to_address');
        $amount = $request->input('amount');

        // Send request to Node.js wallet service
        $response = Http::withHeaders([
            'x-api-key' => env('NODE_API_KEY'),
        ])->post(env('NODE_API_URL') . '/send-funds', [
            'fromPrivateKey' => $privateKey,
            'toAddress'      => $toAddress,
            'amount'         => (string) $amount,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'txHash'  => $data['txHash'],
                'message' => 'Funds sent successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error'   => $response->json()['error'] ?? 'Transaction failed'
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



}
