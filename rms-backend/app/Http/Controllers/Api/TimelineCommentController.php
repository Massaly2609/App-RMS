<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\TimelinePost;
use Illuminate\Http\Request;

class TimelineCommentController extends Controller{

    /**
     * @OA\Get(
     *     path="/api/timeline/posts/{post}/comments",
     *     summary="Lister les commentaires d'un post",
     *     tags={"Timeline > Commentaires"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID du post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des commentaires",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="timeline_post_id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(
     *                         property="user",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(TimelinePost $post)
    {
        $comments = $post->comments()
            ->with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->latest()
            ->get();

        // Formate le nom complet
        $comments->each(function ($comment) {
            $comment->user->name = trim($comment->user->first_name . ' ' . $comment->user->last_name);
        });

        return response()->json([
            'data' => $comments,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/timeline/posts/{post}/comments",
     *     summary="Ajouter un commentaire à un post",
     *     tags={"Timeline > Commentaires"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID du post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Super témoignage !")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Commentaire créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request, TimelinePost $post)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::create([
            'timeline_post_id' => $post->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        // Charger les relations pour éviter erreurs
        $comment->load('user');
        $post->load('user');

        // Notifier l'auteur du post (sauf si c'est soi-même)
        if ($post->user_id !== $request->user()->id) {
            $post->user->notify(new \App\Notifications\PostCommented($post, $comment));
        }

        // Maintenant ça marche car la colonne existe
        $post->increment('comments_count');

        return response()->json([
            'data' => $comment->load(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name');
            }]),
        ], 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/timeline/posts/{post}/comments/{comment}",
     *     summary="Modifier un commentaire (propriétaire uniquement)",
     *     tags={"Timeline > Commentaires"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID du post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID du commentaire",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Super témoignage corrigé !")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commentaire modifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="content", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Non autorisé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, TimelinePost $post, Comment $comment)
    {
        if ($comment->timeline_post_id !== $post->id) {
            return response()->json(['message' => 'Commentaire invalide.'], 422);
        }

        // Vérification que c'est le propriétaire
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'data' => $comment->fresh()->load(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name');
            }]),
        ]);
    }

}
