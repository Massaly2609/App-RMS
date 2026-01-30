<?php

namespace App\Services;

use App\Models\User;
use App\Models\Repayment;
use App\Models\UserState;
use App\Models\Transaction;
use App\Models\JackpotWallet;
use App\Models\FifoQueueEntry;
use App\Models\EligibleNextGain;
use App\Models\JackpotTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\RepaymentInstallment;
use App\Notifications\RepaymentCompletedNotification;


class PaymentService
{
    /**
     * Gère une adhésion simulée de 10 000 FCFA :
     * - crée une transaction d'adhésion
     * - crédite la cagnotte globale
     * - crée une entrée dans la file FIFO
     * - met à jour l'état du membre
     */
    public function adhesionSimulee(User $user): void
    {
        DB::transaction(function () use ($user) {
            // 1. Vérifier l'état actuel du membre
            $state = UserState::lockForUpdate()
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($state->queue_state !== 'none') {
                throw new \DomainException('Utilisateur déjà en file ou en cycle RMS.');
            }

            // 2. Créer la transaction d'adhésion
            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'wallet_id'   => null,          // on crédite la cagnotte globale, pas son wallet
                'provider_id' => null,          // pas de prestataire pour l’instant (simulation)
                'type'        => 'adhesion',
                'amount'      => 10000,
                'currency'    => 'XOF',
                'direction'   => 'in',
                'status'      => 'succeeded',
                'external_reference' => null,
                'metadata'    => null,
            ]);

            // 3. Créditer la cagnotte globale
            $jackpot = JackpotWallet::lockForUpdate()->first();

            if (! $jackpot) {
                $jackpot = JackpotWallet::create([
                    'balance'  => 0,
                    'currency' => 'XOF',
                ]);
            }

            $jackpot->balance += 10000;
            $jackpot->save();

            JackpotTransaction::create([
                'jackpot_wallet_id'   => $jackpot->id,
                'type'                => 'contribution',
                'direction'           => 'in',
                'amount'              => 10000,
                'related_transaction_id' => $transaction->id,
                'related_rotation_id'    => null,
            ]);

            // 4. Créer une entrée dans la file FIFO
            FifoQueueEntry::create([
                'user_id'          => $user->id,
                'entered_at'       => now(),
                'active'           => true,
                'position_snapshot'=> null,
            ]);

            // 5. Mettre à jour l'état du membre
            $state->queue_state = 'in_fifo';
            $state->last_state_changed_at = now();
            $state->save();
        });
    }

     /**
     * Traite un remboursement (simulation) d'un montant donné pour un utilisateur.
     *
     * - Crée une transaction "remboursement" vers la cagnotte.
     * - Alimente la cagnotte (jackpot_transactions).
     * - Crée un installment lié au Repayment en cours.
     * - Met à jour le Repayment (amount_paid, status, completed_at).
     * - Si remboursement terminé :
     *      - Met user_state à "rembourse_eligible".
     *      - Soit déclenche immédiatement un gain prioritaire si cagnotte >= 120000,
     *      - Soit ajoute le membre à eligible_next_gains.
     */
    public function remboursementSimule(User $user, float $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            if ($amount <= 0) {
                throw new \DomainException('Le montant de remboursement doit être positif.');
            }

            // 1. Récupérer/valider l'état RMS
            $state = UserState::lockForUpdate()
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (! in_array($state->queue_state, ['waiting_repayment', 'repaying'], true)) {
                throw new \DomainException('Utilisateur non en phase de remboursement.');
            }

            // 2. Récupérer le remboursement en cours
            $repayment = Repayment::lockForUpdate()
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->first();

            if (! $repayment) {
                throw new \DomainException('Aucun remboursement en cours pour cet utilisateur.');
            }

            // 3. Créer la transaction de remboursement (argent vers RMS)
            $tx = Transaction::create([
                'user_id'     => $user->id,
                'wallet_id'   => null,
                'provider_id' => null,
                'type'        => 'remboursement',
                'amount'      => $amount,
                'currency'    => 'XOF',
                'direction'   => 'in',
                'status'      => 'succeeded',
                'external_reference' => null,
                'metadata'    => null,
            ]);

            // 4. Créditer la cagnotte globale
            $jackpot = JackpotWallet::lockForUpdate()->first();

            if (! $jackpot) {
                $jackpot = JackpotWallet::create([
                    'balance'  => 0,
                    'currency' => 'XOF',
                ]);
            }

            $jackpot->balance += $amount;
            $jackpot->save();

            JackpotTransaction::create([
                'jackpot_wallet_id'     => $jackpot->id,
                'type'                  => 'contribution',
                'direction'             => 'in',
                'amount'                => $amount,
                'related_transaction_id'=> $tx->id,
                'related_rotation_id'   => null,
            ]);

            // 5. Créer l'installment lié au repayment
            RepaymentInstallment::create([
                'repayment_id'   => $repayment->id,
                'transaction_id' => $tx->id,
                'amount'         => $amount,
                'paid_at'        => now(),
            ]);

            // 6. Mettre à jour le remboursement
            $repayment->amount_paid += $amount;

            if ($repayment->amount_paid >= $repayment->target_amount) {
                $repayment->status       = 'completed';
                $repayment->completed_at = now();
            }

            $repayment->save();

            // 7. Si le remboursement N'EST PAS terminé
            if ($repayment->status !== 'completed') {
                $state->queue_state = 'repaying';
                $state->last_state_changed_at = now();
                $state->save();

                return;
            }

            // 8. Remboursement terminé : utilisateur devient "rembourse_eligible"
            $state->queue_state = 'rembourse_eligible';
            $state->last_state_changed_at = now();
            $state->save();

            // notification au membre
            $user->notify(new RepaymentCompletedNotification($repayment));

            // 9. Vérifier la cagnotte
            $jackpot->refresh();

            // Dans tous les cas, on le place dans la liste prioritaire
            EligibleNextGain::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'became_eligible_at' => now(),
                    'processed'          => false,
                ]
            );
            // La rotation effective sera faite par RotationService::runSingleRotation()
        });
    }

}
