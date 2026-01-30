<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FifoQueueEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;


class QueueController extends Controller
{

    //swagger doc for position method
     /**
     * @OA\Get(
     *     path="/api/queue/position",
     *     summary="Obtenir la position d'un membre dans la file FIFO",
     *     tags={"Queue"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID de l'utilisateur"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position retournée"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur ou entrée de file introuvable"
     *     )
     * )

     */

  // Get the position of a user in the FIFO queue
    // public function position(Request $request): JsonResponse
    // {
    //     $user = $request->user();

    //     $entry = FifoQueueEntry::where('user_id', $user->id)
    //         ->where('active', true)
    //         ->first();

    //     if (! $entry) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'L’utilisateur n’est pas actuellement dans la file.',
    //         ], 404);
    //     }

    //     $position = FifoQueueEntry::where('active', true)
    //         ->where('entered_at', '<=', $entry->entered_at)
    //         ->count();

    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => [
    //             'user_id'  => $user->id,
    //             'position' => $position,
    //         ],
    //     ]);
    // }


    public function position(Request $request)
    {
        $user = $request->user();

        $entry = FifoQueueEntry::where('user_id', $user->id)
            ->where('active', true)
            ->first();

        $position = null;
        $queue_state = $user->state?->queue_state ?? 'none';

        if ($entry) {
            $position = FifoQueueEntry::where('active', true)
                ->where('entered_at', '<=', $entry->entered_at)
                ->count();
        }

        return response()->json([
            'position' => $position,
            'queue_state' => $queue_state,
        ]);
    }


}
