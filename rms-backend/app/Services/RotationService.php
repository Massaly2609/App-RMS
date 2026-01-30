<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rotation;
use App\Models\Repayment;
use App\Models\UserState;
use App\Models\Transaction;
use App\Models\JackpotWallet;
use App\Models\FifoQueueEntry;
use App\Models\EligibleNextGain;
use App\Models\JackpotTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\RotationExecutedNotification;

class RotationService
{
    /**
     * Exécute UNE rotation si possible.
     * Retourne l'objet Rotation ou null si aucune rotation possible.
     */
    public function runSingleRotation(): ?Rotation
    {
        $rotation = null;

        DB::transaction(function () use (&$rotation) {
            // 1. Verrouiller la cagnotte
            $jackpot = JackpotWallet::lockForUpdate()->first();

            if (! $jackpot || $jackpot->balance < 120000) {
                return; // pas de rotation possible
            }

            // 2. Chercher un remboursé-éligible (prioritaire)
            $eligible = EligibleNextGain::lockForUpdate()
                ->where('processed', false)
                ->orderBy('became_eligible_at')
                ->first();

            $user = null;
            $queueEntry = null;

            if ($eligible) {
                $user = User::find($eligible->user_id);

                // marquer comme traité
                $eligible->processed = true;
                $eligible->save();

                $rotation = Rotation::create([
                    'user_id'               => $user->id,
                    'amount'                => 100000,
                    'source'                => 'eligible_next_gain',
                    'queue_entry_id'        => null,
                    'eligible_next_gain_id' => $eligible->id,
                    'triggered_at'          => now(),
                ]);
            } else {
                // 3. Sinon prendre le premier FIFO actif
                $queueEntry = FifoQueueEntry::lockForUpdate()
                    ->where('active', true)
                    ->orderBy('entered_at')
                    ->first();

                if (! $queueEntry) {
                    return; // file vide, pas de rotation
                }

                $user = User::find($queueEntry->user_id);

                // sortir le membre de la file
                $queueEntry->active = false;
                $queueEntry->save();

                $rotation = Rotation::create([
                    'user_id'               => $user->id,
                    'amount'                => 100000,
                    'source'                => 'fifo_queue',
                    'queue_entry_id'        => $queueEntry->id,
                    'eligible_next_gain_id' => null,
                    'triggered_at'          => now(),
                ]);
            }

            // 4. Débiter la cagnotte
            if ($jackpot->balance < 100000) {
                // sécurité supplémentaire
                throw new \DomainException('Cagnotte insuffisante pour payer le gain.');
            }

            $jackpot->balance -= 100000;
            $jackpot->save();

            JackpotTransaction::create([
                'jackpot_wallet_id'    => $jackpot->id,
                'type'                 => 'rotation_payout',
                'direction'            => 'out',
                'amount'               => 100000,
                'related_transaction_id' => null, // rempli après
                'related_rotation_id'    => $rotation->id,
            ]);

            // 5. Créditer le wallet du user + transaction gain_payout
            $wallet = $user->wallet()->lockForUpdate()->first();

            $wallet->balance += 100000;
            $wallet->save();

            $tx = Transaction::create([
                'user_id'     => $user->id,
                'wallet_id'   => $wallet->id,
                'provider_id' => null,
                'type'        => 'gain_payout',
                'amount'      => 100000,
                'currency'    => 'XOF',
                'direction'   => 'in',
                'status'      => 'succeeded',
                'external_reference' => null,
                'metadata'    => null,
            ]);

            // lier la transaction au mouvement de cagnotte
            $jackpotTx = JackpotTransaction::where('jackpot_wallet_id', $jackpot->id)
                ->where('related_rotation_id', $rotation->id)
                ->latest('id')
                ->first();

            if ($jackpotTx) {
                $jackpotTx->related_transaction_id = $tx->id;
                $jackpotTx->save();
            }

            // 6. Créer un cycle de remboursement
            Repayment::create([
                'user_id'       => $user->id,
                'target_amount' => 100000,
                'amount_paid'   => 0,
                'status'        => 'in_progress',
                'started_at'    => now(),
            ]);

            // 7. Mettre à jour l'état du membre
            $state = UserState::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();

            if ($state) {
                $state->queue_state = 'waiting_repayment';
                $state->last_state_changed_at = now();
                $state->save();
            }

           $user->notify(new RotationExecutedNotification($rotation));
        });

        return $rotation;
    }

     public function processEligibleGain(EligibleNextGain $eligible)
    {
        DB::transaction(function () use ($eligible) {
            $amount = 100000; // 100k XOF gain prioritaire

            // Créditer wallet
            $eligible->user->wallet->increment('balance', $amount);

            // Créer rotation
            Rotation::create([
                'user_id' => $eligible->user_id,
                'amount' => $amount,
                'source' => 'eligible_next_gain',
                'triggered_at' => now(),
            ]);

            // Marquer comme traité
            $eligible->update(['processed' => true]);

            Log::info('Rotation prioritaire', [
                'user_id' => $eligible->user_id,
                'amount' => $amount,
            ]);
        });
    }

    public function processFifoGain(FifoQueueEntry $entry)
    {
        DB::transaction(function () use ($entry) {
            $amount = 100000; // 100k XOF gain FIFO

            // Créditer wallet
            $entry->user->wallet->increment('balance', $amount);

            // Créer rotation
            Rotation::create([
                'user_id' => $entry->user_id,
                'amount' => $amount,
                'source' => 'fifo_queue',
                'queue_entry_id' => $entry->id,
                'triggered_at' => now(),
            ]);

            // Sortir de la file
            $entry->update(['active' => false]);

            Log::info('Rotation FIFO', [
                'user_id' => $entry->user_id,
                'position' => $entry->position_snapshot ?? 1,
                'amount' => $amount,
            ]);
        });
    }
}
