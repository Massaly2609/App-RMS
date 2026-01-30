<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Lister les notifications de l'utilisateur",
     *     tags={"Notifications"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Liste des notifications")
     * )
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $notifications,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-as-read",
     *     summary="Marquer des notifications comme lues",
     *     tags={"Notifications"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"uuid-1", "uuid-2"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Notifications mises à jour")
     * )
     */


    public function markAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications marquées comme lues.',
        ]);
    }

}
