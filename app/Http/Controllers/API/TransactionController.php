<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    private $coinbaseApiKey = '9c2733be-4700-4c03-82ff-24d3234ea203';

    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    public function createTransaction(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
        
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'type' => 'required|in:BUY,SELL',
            'crypto_currency' => 'required|in:BTC,ETH',
            'fiat_amount' => 'required|numeric|min:500',
            'payment_method' => 'required|in:TMONEY,FLOOZ,ORANGE',
            'phone_number' => 'required|regex:/^228[0-9]{8}$/',
            'address' => 'required|string'
        ]);

        $rate = $this->getCryptoRate($validated['crypto_currency']);
        if (!$rate || $rate <= 0) {
            return response()->json(['error' => 'Taux de change indisponible'], 400);
        }

        $cryptoAmount = $validated['fiat_amount'] / $rate;

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'crypto_amount' => $cryptoAmount,
            'fiat_amount' => $validated['fiat_amount'],
            'status' => 'PENDING',
            'payment_method' => $validated['payment_method'],
            'phone_number' => $validated['phone_number'],
            'crypto_currency' => $validated['crypto_currency'],
            'type' => $validated['type'],
            'address' => $validated['address']
        ]);

        $success = $validated['type'] === 'BUY' 
            ? $this->sendCrypto($validated['address'], $cryptoAmount, $validated['crypto_currency']) 
            : $this->receiveCrypto($validated['address'], $cryptoAmount);

        if (!$success) {
            $transaction->update(['status' => 'FAILED']);
            return response()->json(['error' => 'Échec de la transaction'], 400);
        }

        if ($this->processMobilePayment($validated['payment_method'], $validated['phone_number'], $validated['fiat_amount'])) {
            $transaction->update(['status' => 'COMPLETED']);
            return response()->json($transaction);
        }

        $transaction->update(['status' => 'FAILED']);
        return response()->json(['error' => 'Paiement échoué'], 400);
    }

    private function sendCrypto($address, $amount, $currency)
    {
        $response = Http::withToken($this->coinbaseApiKey)->post('https://api.commerce.coinbase.com/charges', [
            'name' => 'Achat de ' . strtoupper($currency),
            'description' => "Envoi de {$amount} {$currency} à {$address}",
            'pricing_type' => 'fixed_price',
            'local_price' => ['amount' => $amount, 'currency' => strtoupper($currency)],
            'metadata' => ['address' => $address]
        ]);

        return $response->successful();
    }

    private function receiveCrypto($address, $amount)
    {
        return true;
    }

    private function processMobilePayment($method, $phone, $amount)
    {
        return true;
    }

    private function getCryptoRate($crypto)
    {
        $cacheKey = 'crypto_rate_' . strtolower($crypto);
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($crypto) {
            return $this->fetchCryptoRate($crypto);
        });
    }

    private function fetchCryptoRate($crypto)
    {
        try {
            $response = Http::get("https://api.coingecko.com/api/v3/simple/price", [
                'ids' => strtolower($crypto),
                'vs_currencies' => 'xof'
            ]);

            return $response->successful() ? $response->json()[strtolower($crypto)]['xof'] ?? $this->getDefaultRate($crypto) : $this->getDefaultRate($crypto);
        } catch (\Exception $e) {
            \Log::error("Erreur taux de change : " . $e->getMessage());
            return $this->getDefaultRate($crypto);
        }
    }

    private function getDefaultRate($crypto)
    {
        return match(strtolower($crypto)) {
            'btc' => 40000000,
            'eth' => 2500000,
            default => null
        };
    }
}
