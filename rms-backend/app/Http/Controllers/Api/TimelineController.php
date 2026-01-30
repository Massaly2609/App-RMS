<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimelinePost;
use App\Models\TimelineReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/timeline/posts",
     *     summary="Créer un post dans la timeline",
     *     tags={"Timeline"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Grâce au RMS, j'ai lancé mon petit commerce."),
     *             @OA\Property(property="type", type="string", example="text"),
     *             @OA\Property(property="media_url", type="string", example="https://example.com/photo.jpg")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Post créé"),
     *     @OA\Response(response=400, description="Erreur de validation")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'   => ['nullable', 'string'],
            'type'      => ['nullable', 'in:text,photo,video,audio'],
            'media_url' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (empty($validated['content']) && empty($validated['media_url'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Le contenu ou le média est obligatoire.',
            ], 400);
        }

        $post = TimelinePost::create([
            'user_id'   => $user->id,
            'type'      => $validated['type'] ?? 'text',
            'content'   => $validated['content'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'metadata'  => null,
            'visibility'=> 'public',
            'country'   => $user->country,
            'city'      => $user->city,
            'status'    => 'published',
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'post' => $post,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/timeline/posts",
     *     summary="Lister les posts de la timeline (feed paginé)",
     *     tags={"Timeline"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filtrer par type de post",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Liste paginée des posts")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = TimelinePost::withCount([
                'likes as likes_count',
                'comments as comments_count',
            ])
            ->where('visibility', 'public')
            ->where('status', 'published')
            ->orderByDesc('created_at');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $posts = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $posts,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/timeline/posts/{post}/like",
     *     summary="Liker ou déliker un post",
     *     tags={"Timeline"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Like togglé")
     * )
     */
    public function toggleLike(Request $request, TimelinePost $post): JsonResponse
    {
        $user = $request->user();

        $existing = TimelineReaction::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->where('type', 'like')
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'liked' => false,
                ],
            ]);
        }

        TimelineReaction::create([
            'post_id'      => $post->id,
            'user_id'      => $user->id,
            'type'         => 'like',
            'comment_text' => null,
        ]);

          // Notifier l'auteur du post (sauf si c'est soi-même)
        if ($post->user_id !== $user->id) {
            $post->load('user'); // charge l'utilisateur pour éviter N+1
            $post->user->notify(new \App\Notifications\PostLiked($post));
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'liked' => true,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/timeline/posts/{post}/comment",
     *     summary="Ajouter un commentaire sur un post",
     *     tags={"Timeline"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(property="comment", type="string", example="Bravo pour ta réussite !")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Commentaire créé")
     * )
     */
    public function comment(Request $request, TimelinePost $post): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        $user = $request->user();

        $reaction = TimelineReaction::create([
            'post_id'      => $post->id,
            'user_id'      => $user->id,
            'type'         => 'comment',
            'comment_text' => $validated['comment'],
        ]);



        return response()->json([
            'status' => 'success',
            'data'   => [
                'comment' => $reaction,
            ],
        ], 201);
    }
}
