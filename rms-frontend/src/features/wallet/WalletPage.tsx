import { useEffect, useState } from 'react';
import { adhesion, getCurrentRepayment, getWallet, remboursement } from '../../api/wallet';

export function WalletPage() {
  const [wallet, setWallet] = useState<any>(null);
  const [repayment, setRepayment] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    setLoading(true);
    try {
      const [walletRes, repaymentRes] = await Promise.all([
        getWallet(),
        getCurrentRepayment(),
      ]);
      setWallet(walletRes);
      setRepayment(repaymentRes);
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur chargement wallet');
    } finally {
      setLoading(false);
    }
  }

  async function handleAdhesion() {
    setActionLoading(true);
    try {
      const res = await adhesion();
      alert(`Adhésion OK ! Position: ${res.position ?? 'calculée'}`);
      loadData(); // refresh
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur adhésion');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleRemboursement() {
    const amount = prompt('Montant remboursement (XOF):', '20000');
    if (!amount || isNaN(Number(amount))) return;

    setActionLoading(true);
    try {
      await remboursement(Number(amount));
      alert('Remboursement enregistré !');
      loadData();
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur remboursement');
    } finally {
      setActionLoading(false);
    }
  }

  if (loading) return <div style={{ textAlign: 'center', padding: 64 }}>Chargement wallet...</div>;

  return (
    <div style={{ maxWidth: 600, margin: '24px auto', padding: 24 }}>
      <h1>💰 Mon Wallet RMS</h1>

      {/* Solde */}
      <div style={{
        background: '#4CAF50',
        color: 'white',
        padding: 24,
        borderRadius: 12,
        textAlign: 'center',
        marginBottom: 24,
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
      }}>
        <h2 style={{ margin: 0, fontSize: 32 }}>
          {wallet.balance?.toLocaleString()} {wallet.currency}
        </h2>
        <p style={{ margin: 4, opacity: 0.9 }}>Solde disponible</p>
      </div>

      {/* Actions */}
      <div style={{ display: 'grid', gap: 12, marginBottom: 24 }}>
        <button
          onClick={handleAdhesion}
          disabled={actionLoading}
          style={{
            padding: 16,
            fontSize: 16,
            background: '#2196F3',
            color: 'white',
            border: 'none',
            borderRadius: 8,
            cursor: actionLoading ? 'not-allowed' : 'pointer'
          }}
        >
          {actionLoading ? '...' : '💎 Adhésion (100k XOF)'}
        </button>

        <button
          onClick={handleRemboursement}
          disabled={actionLoading}
          style={{
            padding: 16,
            fontSize: 16,
            background: '#FF9800',
            color: 'white',
            border: 'none',
            borderRadius: 8,
            cursor: actionLoading ? 'not-allowed' : 'pointer'
          }}
        >
          💳 Remboursement
        </button>
      </div>

      {/* Remboursement en cours */}
      {repayment?.repayment ? (
        <div style={{
          background: '#E3F2FD',
          padding: 16,
          borderRadius: 8,
          borderLeft: '4px solid #2196F3',
          marginBottom: 24
        }}>
          <h3>📊 Remboursement en cours</h3>
          <p><strong>Cible :</strong> {repayment.repayment.target_amount.toLocaleString()} XOF</p>
          <p><strong>Payé :</strong> {repayment.repayment.amount_paid.toLocaleString()} XOF</p>
          <p><strong>Statut :</strong> <span style={{
            color: repayment.repayment.status === 'completed' ? '#4CAF50' : '#FF9800',
            fontWeight: 'bold'
          }}>{repayment.repayment.status}</span></p>
          <p><strong>État file :</strong> {repayment.queue_state}</p>
        </div>
      ) : (
        <div style={{ padding: 16, opacity: 0.7 }}>
          Pas de remboursement en cours
        </div>
      )}

      {/* Transactions récentes */}
      {wallet?.transactions?.length > 0 ? (
        <>
          <h3>Dernières transactions</h3>
          <ul style={{ listStyle: 'none', padding: 0 }}>
            {wallet.transactions.map((t: any) => (
              <li key={t.id} style={{
                padding: 12,
                borderBottom: '1px solid #eee',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}>
                <div>
                  <div style={{ fontWeight: '500' }}>{t.type}</div>
                  <small>{new Date(t.created_at).toLocaleDateString()}</small>
                </div>
                <span style={{
                  fontSize: 18,
                  fontWeight: 'bold',
                  color: t.amount > 0 ? '#4CAF50' : '#F44336'
                }}>
                  {t.amount.toLocaleString()} XOF
                </span>
              </li>
            ))}
          </ul>
        </>
      ) : (
        <div style={{ textAlign: 'center', padding: 32, opacity: 0.5 }}>
          Aucune transaction
        </div>
      )}
    </div>
  );
}
