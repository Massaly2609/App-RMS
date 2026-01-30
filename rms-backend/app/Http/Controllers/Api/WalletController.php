<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Repayment;
use App\Models\UserState;
use App\Models\FifoQueueEntry;
use App\Services\PaymentService;

class WalletController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/wallet/adhesion",
     *     summary="Payer l'adhésion (simulation) et entrer dans la file FIFO",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Adhésion enregistrée, entrée dans la file"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Utilisateur non éligible ou déjà en file"
     *     )
     * )
     */
    public function adhesion(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->paymentService->adhesionSimulee($user);
        } catch (\DomainException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }

        $entry = FifoQueueEntry::where('user_id', $user->id)
            ->where('active', true)
            ->first();

        $position = null;

        if ($entry) {
            $position = FifoQueueEntry::where('active', true)
                ->where('entered_at', '<=', $entry->entered_at)
                ->count();
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user_id'  => $user->id,
                'position' => $position,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/wallet/remboursement",
     *     summary="Payer une tranche de remboursement (simulation)",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=20000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Remboursement enregistré"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur métier"
     *     )
     * )
     */
    public function remboursement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'  => ['required', 'numeric', 'min:1'],
        ]);

        $user   = $request->user();
        $amount = (float) $validated['amount'];

        try {
            $this->paymentService->remboursementSimule($user, $amount);
        } catch (\DomainException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }

        $repayment = Repayment::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $state = UserState::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user_id'     => $user->id,
                'queue_state' => $state?->queue_state,
                'repayment'   => $repayment ? [
                    'id'            => $repayment->id,
                    'target_amount' => $repayment->target_amount,
                    'amount_paid'   => $repayment->amount_paid,
                    'status'        => $repayment->status,
                ] : null,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/wallet/repayment",
     *     summary="Remboursement RMS en cours",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Infos remboursement (ou null)"
     *     )
     * )
     */
    public function currentRepayment(Request $request): JsonResponse
    {
        $user = $request->user();

        $repayment = Repayment::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $state = UserState::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'queue_state' => $state?->queue_state,
                'repayment'   => $repayment ? [
                    'id'            => $repayment->id,
                    'target_amount' => $repayment->target_amount,
                    'amount_paid'   => $repayment->amount_paid,
                    'status'        => $repayment->status,
                    'started_at'    => $repayment->started_at,
                    'completed_at'  => $repayment->completed_at,
                ] : null,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/wallet",
     *     summary="État du wallet + 10 dernières transactions",
     *     tags={"Wallet"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallet info",
     *         @OA\JsonContent(
     *             @OA\Property(property="balance", type="number", example=150000),
     *             @OA\Property(property="currency", type="string", example="XOF"),
     *             @OA\Property(
     *                 property="transactions",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="amount", type="number"),
     *                     @OA\Property(property="created_at", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $wallet = $user->wallet; // relation Eloquent

        $transactions = $user->transactions()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'balance' => $wallet->balance ?? 0,
            'currency' => $wallet->currency ?? 'XOF',
            'transactions' => $transactions,
        ]);
    }
}
