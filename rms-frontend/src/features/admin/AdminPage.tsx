import { useEffect, useState } from 'react';
import { getAdminStats, runAdminRotation } from '../../api/admin';

export function AdminPage() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [rotationLoading, setRotationLoading] = useState(false);

  useEffect(() => {
    loadStats();
  }, []);

  async function loadStats() {
    setLoading(true);
    try {
      const data = await getAdminStats();
      setStats(data);
    } catch {
      alert('Erreur stats admin');
    } finally {
      setLoading(false);
    }
  }

  async function handleRunRotation() {
    setRotationLoading(true);
    try {
      await runAdminRotation();
      alert('✅ Rotation exécutée !');
      loadStats();
    } catch (e: any) {
      alert(`Erreur: ${e.response?.data?.message || e.message}`);
    } finally {
      setRotationLoading(false);
    }
  }

  if (loading) return <div style={{ padding: 64, textAlign: 'center' }}>Chargement...</div>;

  return (
    <div style={{ maxWidth: 1000, margin: '24px auto', padding: 24 }}>
      <h1>⚙️ Admin RMS</h1>

      <button
        onClick={handleRunRotation}
        disabled={rotationLoading}
        style={{
          padding: '16px 40px',
          fontSize: 18,
          background: rotationLoading ? '#ccc' : '#FF5722',
          color: 'white',
          border: 'none',
          borderRadius: 8,
          cursor: rotationLoading ? 'not-allowed' : 'pointer',
          marginBottom: 32,
          fontWeight: 'bold'
        }}
      >
        {rotationLoading ? '⏳ Rotation...' : '🚀 Lancer rotation manuelle'}
      </button>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: 24 }}>
        <div style={{ padding: 24, borderRadius: 12, background: '#E3F2FD', border: '2px solid #2196F3' }}>
          <h3>👥 Utilisateurs</h3>
          <div style={{ fontSize: 36, fontWeight: 'bold', color: '#1976D2' }}>
            {stats?.users_count ?? 0}
          </div>
        </div>

        <div style={{ padding: 24, borderRadius: 12, background: '#E8F5E8', border: '2px solid #4CAF50' }}>
          <h3>📊 File active</h3>
          <div style={{ fontSize: 36, fontWeight: 'bold', color: '#388E3C' }}>
            {stats?.queue_active ?? 0}
          </div>
        </div>

        <div style={{ padding: 24, borderRadius: 12, background: '#FFF3E0', border: '2px solid #FF9800' }}>
          <h3>⭐ Éligibles prioritaire</h3>
          <div style={{ fontSize: 36, fontWeight: 'bold', color: '#F57C00' }}>
            {stats?.eligible_count ?? 0}
          </div>
        </div>

        <div style={{ padding: 24, borderRadius: 12, background: '#F3E5F5', border: '2px solid #9C27B0' }}>
          <h3>💰 Total rotations</h3>
          <div style={{ fontSize: 36, fontWeight: 'bold', color: '#7B1FA2' }}>
            {stats?.total_rotations_amount?.toLocaleString() ?? 0} XOF
          </div>
        </div>
      </div>

      <div style={{ textAlign: 'center', marginTop: 32 }}>
        <button
          onClick={loadStats}
          style={{
            padding: '12px 24px',
            background: '#2196F3',
            color: 'white',
            border: 'none',
            borderRadius: 8,
            cursor: 'pointer'
          }}
        >
          🔄 Actualiser stats
        </button>
      </div>
    </div>
  );
}
