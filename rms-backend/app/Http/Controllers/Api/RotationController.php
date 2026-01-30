<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EligibleNextGain;
use App\Models\Rotation;
use App\Services\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\FifoQueueEntry;

class RotationController extends Controller
{
    private RotationService $rotationService;

    public function __construct(RotationService $rotationService)
    {
        $this->rotationService = $rotationService;
    }

    /**
     * @OA\Post(
     *     path="/api/admin/rotations/run-once",
     *     tags={"Admin - Rotations"},
     *     summary="Déclenche une rotation RMS unique",
     *     description="Exécute une seule rotation : sélectionne un membre éligible (remboursé-éligible ou premier de la file FIFO), crédite son gain de 100 000 FCFA et met à jour les historiques.",
     *     operationId="adminRunSingleRotation",
     *     @OA\Response(
     *         response=201,
     *         description="Rotation exécutée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "success": true,
     *                 "message": "Rotation exécutée avec succès.",
     *                 "data": {
     *                     "rotation_id": 1,
     *                     "user_id": 3,
     *                     "amount": 100000,
     *                     "source": "jackpot"
     *                 }
     *             },
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="rotation_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="amount", type="integer"),
     *                 @OA\Property(property="source", type="string", description="Source des fonds (ex: jackpot)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Aucune rotation possible pour le momKent",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "success": false,
     *                 "message": "Aucune rotation possible pour le moment (cagnotte ou candidats insuffisants)."
     *             },
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */

    // POST /api/admin/rotations/run-once
    // public function runOnce(): JsonResponse
    // {
    //     $rotation = $this->rotationService->runSingleRotation();

    //     if (! $rotation) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Aucune rotation possible pour le moment (cagnotte ou candidats insuffisants).',
    //         ], 200);
    //     }

    //     return response()->json([
    //         'success'  => true,
    //         'message'  => 'Rotation exécutée avec succès.',
    //         'data'     => [
    //             'rotation_id' => $rotation->id,
    //             'user_id'     => $rotation->user_id,
    //             'amount'      => $rotation->amount,
    //             'source'      => $rotation->source,
    //         ],
    //     ], 201);
    // }

     public function runOnce(Request $request, RotationService $rotationService)
    {
        // Rotation prioritaire (eligible_next_gain)
        $eligible = EligibleNextGain::where('processed', false)
            ->with('user.wallet')
            ->first();

        if ($eligible) {
            $rotationService->processEligibleGain($eligible);
        }

        // Rotation FIFO (top 1 file)
        $topEntry = FifoQueueEntry::where('active', true)
            ->orderBy('entered_at')
            ->with('user.wallet')
            ->first();

        if ($topEntry) {
            $rotationService->processFifoGain($topEntry);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rotation exécutée',
            'processed_prioritaire' => $eligible ? true : false,
            'processed_fifo' => $topEntry ? true : false,
        ]);
    }

      /**
     * @OA\Get(
     *     path="/api/rotations",
     *     summary="Mes rotations (historique + éligibilité)",
     *     tags={"Rotations"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Historique des rotations et info éligibilité"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $rotations = Rotation::where('user_id', $user->id)
            ->orderByDesc('triggered_at')
            ->get([
                'id',
                'amount',
                'source',
                'triggered_at',
            ]);

        $eligible = EligibleNextGain::where('user_id', $user->id)
            ->where('processed', false)
            ->exists();

        return response()->json([
            'rotations' => $rotations,
            'eligible_next_gain' => $eligible,
        ]);
    }

}
