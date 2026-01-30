import { useEffect, useState } from 'react';
import {
  adhesion,
  getCurrentRepayment,
  remboursement,
  type RepaymentInfo
} from '../../api/wallet';
import { getQueuePosition } from '../../api/queue';
import { Link } from 'react-router-dom';

type QueueInfo = {
  position: number | null;
  queue_state: string | null;
};

export function DashboardPage() {
  const [queueInfo, setQueueInfo] = useState<QueueInfo | null>(null);
  const [repayment, setRepayment] = useState<RepaymentInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [adhesionLoading, setAdhesionLoading] = useState(false);
  const [repaymentLoading, setRepaymentLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [messages, setMessages] = useState<string[]>([]);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    setLoading(true);
    try {
      const [queueRes, repaymentRes] = await Promise.all([
        getQueuePosition(),
        getCurrentRepayment(),
      ]);
      setQueueInfo(queueRes);
      setRepayment(repaymentRes.repayment);
    } catch (e: any) {
      setError(e.response?.data?.message ?? 'Erreur chargement dashboard');
    } finally {
      setLoading(false);
    }
  }

  async function handleAdhesion() {
    setAdhesionLoading(true);
    try {
      const data = await adhesion();
      setMessages(prev => [...prev, `Adhésion OK ! Position: ${data.position ?? 'calculée'}`]);
      loadData();
    } catch (e: any) {
      setError(e.response?.data?.message ?? 'Erreur adhésion');
    } finally {
      setAdhesionLoading(false);
    }
  }

  async function handleRepayment() {
    const amount = prompt('Montant (XOF):', '20000');
    if (!amount || isNaN(Number(amount))) return;

    setRepaymentLoading(true);
    try {
      await remboursement(Number(amount));
      setMessages(prev => [...prev, `Remboursement ${amount} XOF effectué`]);
      loadData();
    } catch (e: any) {
      setError(e.response?.data?.message ?? 'Erreur remboursement');
    } finally {
      setRepaymentLoading(false);
    }
  }

  if (loading) return <div style={{ padding: 64, textAlign: 'center' }}>Chargement...</div>;

  return (
    <div style={{ maxWidth: 900, margin: '24px auto', padding: 24 }}>
      <h1>🏠 Dashboard RMS</h1>

      {/* Messages */}
      {messages.length > 0 && (
        <div style={{
          background: '#E8F5E8',
          border: '1px solid #4CAF50',
          padding: 12,
          borderRadius: 8,
          marginBottom: 24
        }}>
          {messages.map((msg, i) => (
            <p key={i} style={{ margin: 0, color: '#2E7D32' }}>{msg}</p>
          ))}
        </div>
      )}

      {error && (
        <div style={{
          background: '#FFEBEE',
          border: '1px solid #F44336',
          padding: 12,
          borderRadius: 8,
          marginBottom: 24
        }}>
          <p style={{ margin: 0, color: '#C62828' }}>{error}</p>
        </div>
      )}

      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
        gap: 24,
        marginBottom: 32
      }}>
        {/* File */}
        <section style={{ padding: 20, borderRadius: 12, background: '#F3E5F5', border: '1px solid #E1BEE7' }}>
          <h3>📋 File FIFO</h3>
          {queueInfo && queueInfo.position !== null ? (
            <p style={{ fontSize: 32, color: '#7B1FA2', fontWeight: 'bold' }}>
              Position #{queueInfo.position}
            </p>
          ) : (
            <p>Pas encore en file → <button onClick={handleAdhesion} disabled={adhesionLoading}>Adhérer</button></p>
          )}
          <Link to="/queue">Détails →</Link>
        </section>

        {/* Remboursement */}
        <section style={{ padding: 20, borderRadius: 12, background: '#E8F5E8', border: '1px solid #C8E6C9' }}>
          <h3>💳 Remboursement</h3>
          {repayment ? (
            <>
              <p><strong>{repayment.target_amount.toLocaleString()} XOF</strong> à payer</p>
              <p>Payé : {repayment.amount_paid.toLocaleString()} ({Math.round((repayment.amount_paid / repayment.target_amount) * 100)}%)</p>
              <button onClick={handleRepayment} disabled={repaymentLoading}>Payer 20k</button>
            </>
          ) : (
            <p>Aucun remboursement en cours</p>
          )}
          <Link to="/wallet">Wallet →</Link>
        </section>

        {/* Rotations */}
        <section style={{ padding: 20, borderRadius: 12, background: '#FFF3E0', border: '1px solid #FFCC80' }}>
          <h3>🔄 Rotations</h3>
          <Link to="/rotations" style={{ fontSize: 20, color: '#F57C00' }}>Historique →</Link>
        </section>

        {/* Timeline */}
        <section style={{ padding: 20, borderRadius: 12, background: '#E3F2FD', border: '1px solid #BBDEFB' }}>
          <h3>📱 Communauté</h3>
          <Link to="/timeline" style={{ fontSize: 20, color: '#1976D2' }}>Timeline →</Link>
        </section>
      </div>
    </div>
  );
}
