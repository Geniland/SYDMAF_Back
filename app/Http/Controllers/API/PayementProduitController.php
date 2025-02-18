<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PayementProduit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PayementProduitController extends Controller
{
    /**
     * Initiation d'une transaction de paiement.
     *
     * L'utilisateur doit être authentifié.
     * La requête doit contenir :
     * - amount : montant à payer (numérique, min. 1)
     * - currency : devise (ex : FCFA, USD, EUR)
     * - payment_method : méthode de paiement (TMONEY, FLOOZ, ORANGE)
     *
     * L'API génère un identifiant unique et simule une URL de checkout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        // Vérifier que l'utilisateur est authentifié
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Valider les données reçues
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:1',
            'currency'       => 'required|string|in:FCFA,USD,EUR',
            'payment_method' => 'required|string|in:TMONEY,FLOOZ,ORANGE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'    => 'Données invalides',
                'messages' => $validator->errors()
            ], 422);
        }

        // Générer un identifiant unique pour la transaction
        $transactionId = (string) Str::uuid();

        // Création de l'enregistrement dans la table payement_produits
        $payment = PayementProduit::create([
            'user_id'        => $user->id,
            'transaction_id' => $transactionId,
            'amount'         => $request->amount,
            'currency'       => $request->currency,
            'payment_method' => $request->payment_method,
            'status'         => 'PENDING',
        ]);

        // Simuler une URL de checkout (exemple d'URL à adapter à vos besoins)
        $checkoutUrl = url("/checkout/{$transactionId}");

        return response()->json([
            'message'        => 'Transaction initiée avec succès.',
            'transaction_id' => $transactionId,
            'checkout_url'   => $checkoutUrl
        ], 201);
    }

    /**
     * Récupère le statut d'une transaction.
     *
     * @param string $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentStatus($transactionId)
    {
        $payment = PayementProduit::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Transaction non trouvée'], 404);
        }

        return response()->json([
            'transaction_id' => $payment->transaction_id,
            'status'         => $payment->status,
            'amount'         => $payment->amount,
            'currency'       => $payment->currency,
            'payment_method' => $payment->payment_method,
        ]);
    }

    /**
     * Endpoint Webhook pour mettre à jour le statut d'une transaction.
     * Ce endpoint est appelé par le prestataire de paiement lorsqu'une transaction est confirmée.
     *
     * Exemple de payload attendu :
     * {
     *    "transaction_id": "uuid-de-la-transaction",
     *    "status": "COMPLETED"
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        // Vous pouvez ajouter ici une vérification de signature ou un secret pour sécuriser ce endpoint
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|uuid',
            'status'         => 'required|string|in:PENDING,COMPLETED,FAILED'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Données invalides'], 422);
        }

        $payment = PayementProduit::where('transaction_id', $request->transaction_id)->first();
        if (!$payment) {
            return response()->json(['error' => 'Transaction non trouvée'], 404);
        }

        // Mettre à jour le statut de la transaction
        $payment->update(['status' => $request->status]);

        return response()->json(['message' => 'Webhook traité avec succès'], 200);
    }
}
