<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TransactionController extends Controller
{
    private $coinbaseApiKey = '9c2733be-4700-4c03-82ff-24d3234ea203';

    public function createTransaction(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Validation de la requête
        $validated = $request->validate([
            'type' => 'required|in:BUY,SELL',
            'crypto_currency' => 'required|in:BTC,ETH',
            'fiat_amount' => 'required|numeric|min:500',
            'payment_method' => 'required|in:TMONEY,FLOOZ,ORANGE',
            'phone_number' => 'required|regex:/^228[0-9]{8}$/',
            'address' => 'required|string'
        ]);

        // Récupération du taux de change
        $rate = $this->getCryptoRate($validated['crypto_currency']);
        if (!$rate || $rate <= 0) {
            return response()->json(['error' => 'Taux de change indisponible'], 400);
        }

        // Calcul du montant en crypto
        $cryptoAmount = $validated['fiat_amount'] / $rate;

        // Création de la transaction en base de données
        $transaction = Transaction::create([
            'user_id' => $userId,
            'crypto_amount' => $cryptoAmount,
            'fiat_amount' => $validated['fiat_amount'],
            'status' => 'PENDING',
            'payment_method' => $validated['payment_method'],
            'phone_number' => $validated['phone_number'],
            'crypto_currency' => $validated['crypto_currency'],
            'type' => $validated['type'],
            'address' => $validated['address']
        ]);

        // Processus d'achat ou de vente
        if ($validated['type'] === 'BUY') {
            $success = $this->sendCrypto($validated['address'], $cryptoAmount, $validated['crypto_currency']);
        } else {
            $success = $this->receiveCrypto($validated['address'], $cryptoAmount);
        }

        if (!$success) {
            $transaction->update(['status' => 'FAILED']);
            return response()->json(['error' => 'Échec de la transaction'], 400);
        }

        // Simuler le paiement mobile
        if ($this->processMobilePayment($validated['payment_method'], $validated['phone_number'], $validated['fiat_amount'])) {
            $transaction->update(['status' => 'COMPLETED']);
            return response()->json($transaction);
        }

        $transaction->update(['status' => 'FAILED']);
        return response()->json(['error' => 'Paiement échoué'], 400);
    }

    // 🚀 Envoi de cryptos via l'API Coinbase Commerce
    private function sendCrypto($address, $amount, $currency)
    {
        $response = Http::withToken($this->coinbaseApiKey)->post('https://api.commerce.coinbase.com/charges', [
            'name' => 'Achat de ' . strtoupper($currency),
            'description' => "Envoi de {$amount} {$currency} à {$address}",
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => $amount,
                'currency' => strtoupper($currency)
            ],
            'metadata' => [
                'address' => $address
            ]
        ]);

        return $response->successful();
    }

    // 🚀 Simulation de réception de cryptos (à personnaliser si nécessaire)
    private function receiveCrypto($address, $amount)
    {
        return true;
    }

    // 🚀 Simulation de paiement mobile
    private function processMobilePayment($method, $phone, $amount)
    {
        return true;
    }

    // 🚀 Récupération du taux de change depuis une API externe
    private function getCryptoRate($crypto)
    {
        $cacheKey = 'crypto_rate_' . strtolower($crypto);
        $rate = Cache::get($cacheKey);

        if (!$rate) {
            $rate = $this->fetchCryptoRate($crypto);
            Cache::put($cacheKey, $rate, now()->addMinutes(10));
        }

        return $rate;
    }

    // 🚀 Requête API pour récupérer le taux en XOF
    private function fetchCryptoRate($crypto)
    {
        try {
            $response = Http::get("https://api.coingecko.com/api/v3/simple/price", [
                'ids' => strtolower($crypto),
                'vs_currencies' => 'xof'
            ]);

            if ($response->failed()) {
                return $this->getDefaultRate($crypto);
            }

            $data = $response->json();
            return $data[strtolower($crypto)]['xof'] ?? $this->getDefaultRate($crypto);
        } catch (\Exception $e) {
            \Log::error("Erreur taux de change : " . $e->getMessage());
            return $this->getDefaultRate($crypto);
        }
    }

    // 🚀 Taux de change par défaut
    private function getDefaultRate($crypto)
    {
        return match(strtolower($crypto)) {
            'btc' => 40000000,
            'eth' => 2500000,
            default => null
        };
    }
}
