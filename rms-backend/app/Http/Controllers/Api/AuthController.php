<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\UserState;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Auth\VerifyOtpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    // Vérifie si le service d'authentification est accessible
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'auth',
            'message' => 'L\'API d\'authentification est accessible.',
        ]);
    }

    // Swagger Documentation for Register Endpoint
        /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Inscrire un nouveau membre RMS",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","first_name","last_name"},
     *             @OA\Property(property="phone", type="string", example="+221770000001"),
     *             @OA\Property(property="first_name", type="string", example="Fatou"),
     *             @OA\Property(property="last_name", type="string", example="Diop"),
     *             @OA\Property(property="country", type="string", example="SN"),
     *             @OA\Property(property="city", type="string", example="Dakar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec état et wallet",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="phone", type="string", example="+221770000001"),
     *                     @OA\Property(property="first_name", type="string", example="Fatou"),
     *                     @OA\Property(property="last_name", type="string", example="Diop")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */



    // Enregistre un nouvel utilisateur
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = null;

        DB::transaction(function () use (&$user, $data) {
            // 1. Créer l'utilisateur
            $user = User::create([
                'phone'      => $data['phone'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'country'    => $data['country'] ?? null,
                'city'       => $data['city'] ?? null,
                'is_admin'   => $data['is_admin'] ?? false,

            ]);

            // 2. Créer son état RMS
            UserState::create([
                'user_id'               => $user->id,
                'queue_state'           => 'none',
                'last_state_changed_at' => now(),
            ]);

            // 3. Créer son wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency'=> 'XOF',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => [
                    'id'         => $user->id,
                    'phone'      => $user->phone,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'is_admin'   => $user->is_admin,
                ],
            ],
        ], 201);
    }



    // Swagger Documentation for Request OTP Endpoint
        /**
     * @OA\Post(
     *     path="/api/auth/request-otp",
     *     summary="Demander un code OTP par SMS",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+221770000001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP généré et (simulé) envoyé"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de demandes d'OTP pour ce numéro"
     *     )
     * )
     */

    // Demande un code OTP pour un numéro de téléphone
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->validated()['phone'];

        // Limitation simple : max 5 OTP actifs dans les 15 dernières minutes
        $recentCount = OtpCode::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes(15))
            ->count();

        if ($recentCount >= 5) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Trop de demandes d’OTP. Réessaie plus tard.',
            ], 429);
        }

        // Générer un code à 6 chiffres
        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($phone, $code) {
            // Invalider les anciens OTP pour ce numéro
            OtpCode::where('phone', $phone)
                ->where('used', false)
                ->update(['used' => true]);

            // Créer le nouvel OTP
            OtpCode::create([
                'phone'      => $phone,
                'code'       => $code,
                'expires_at' => now()->addMinutes(5),
                'attempts'   => 0,
                'used'       => false,
            ]);
        });

        // Pour l’instant : simulation de l’envoi SMS (log)
        Log::info('OTP RMS envoyé', [
            'phone' => $phone,
            'code'  => $code,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP généré. (Envoi SMS simulé en environnement de dev)',
        ]);
    }

    // Swagger Documentation for Verify OTP Endpoint
        /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     summary="Vérifier un code OTP et se connecter",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","code"},
     *             @OA\Property(property="phone", type="string", example="+221770000001"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP valide, utilisateur connecté"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP invalide ou expiré"
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data  = $request->validated();
        $phone = $data['phone'];
        $code  = $data['code'];

        $otp = OtpCode::valid($phone)
            ->orderByDesc('created_at')
            ->first();

        if (! $otp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Aucun OTP valide pour ce numéro (ou OTP expiré).',
            ], 400);
        }

        // Incrémenter les tentatives
        $otp->attempts += 1;

        if ($otp->attempts > 5) {
            $otp->used = true;
            $otp->save();

            return response()->json([
                'status'  => 'error',
                'message' => 'Trop de tentatives invalides. Demande un nouvel OTP.',
            ], 400);
        }

        if ($otp->code !== $code) {
            $otp->save();

            return response()->json([
                'status'  => 'error',
                'message' => 'Code OTP incorrect.',
            ], 400);
        }

        // OTP correct
        $otp->used = true;
        $otp->save();

        // Créer ou récupérer l'utilisateur + état + wallet
        $user = null;

        DB::transaction(function () use (&$user, $phone) {
            $user = User::where('phone', $phone)->first();

            if (! $user) {
                $user = User::create([
                    'phone'      => $phone,
                    'first_name' => 'Membre',
                    'last_name'  => 'RMS',
                    'password'   => Hash::make(Str::random(16)),
                ]);

                UserState::create([
                    'user_id'               => $user->id,
                    'queue_state'           => 'none',
                    'last_state_changed_at' => now(),
                ]);

                Wallet::create([
                    'user_id'  => $user->id,
                    'balance'  => 0,
                    'currency' => 'XOF',
                ]);
            }
        });

        // Utilisation de Laravel Sanctum pour le token d'API
        $token = $user->createToken('rms_api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user'  => [
                    'id'         => $user->id,
                    'phone'      => $user->phone,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'is_admin'   => $user->is_admin,
                ],
                'token' => $token,
            ],
        ]);
    }


}
