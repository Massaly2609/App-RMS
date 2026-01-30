import { useEffect, useState } from 'react';
import { getRotations, type RotationItem } from '../../api/rotations';

export function RotationsPage() {
  const [rotations, setRotations] = useState<RotationItem[]>([]);
  const [eligible, setEligible] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    load();
  }, []);

  async function load() {
    setLoading(true);
    try {
      const res = await getRotations();
      setRotations(res.rotations);
      setEligible(res.eligible_next_gain);
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur chargement rotations');
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return <div style={{ textAlign: 'center', padding: 64 }}>Chargement rotations...</div>;
  }

  return (
    <div style={{ maxWidth: 600, margin: '24px auto', padding: 24 }}>
      <h1>🔄 Mes Rotations RMS</h1>

      {eligible ? (
        <div style={{
          background: '#FFF3E0',
          border: '2px solid #FF9800',
          padding: 20,
          borderRadius: 12,
          textAlign: 'center',
          marginBottom: 24,
        }}>
          <div style={{ fontSize: 40 }}>⭐</div>
          <h2 style={{ margin: 8 }}>Éligible au prochain gain prioritaire</h2>
          <p style={{ margin: 0 }}>Tu seras servi en priorité à la prochaine rotation.</p>
        </div>
      ) : (
        <div style={{
          background: '#F5F5F5',
          padding: 16,
          borderRadius: 12,
          textAlign: 'center',
          marginBottom: 24,
        }}>
          Pas encore éligible au prochain gain prioritaire.
        </div>
      )}

      {rotations.length === 0 ? (
        <div style={{ textAlign: 'center', padding: 48, opacity: 0.6 }}>
          Aucune rotation reçue pour l’instant.
        </div>
      ) : (
        <>
          <h3>Historique des gains</h3>
          <ul style={{ listStyle: 'none', padding: 0 }}>
            {rotations.map((r) => (
              <li
                key={r.id}
                style={{
                  padding: 12,
                  borderRadius: 8,
                  border: '1px solid #ddd',
                  marginBottom: 8,
                  display: 'flex',
                  justifyContent: 'space-between',
                }}
              >
                <div>
                  <div style={{ fontWeight: 'bold', color: '#4CAF50' }}>
                    + {r.amount.toLocaleString()} XOF
                  </div>
                  <small>
                    {r.source === 'eligible_next_gain' ? 'Gain prioritaire' : 'Gain FIFO'} •{' '}
                    {new Date(r.triggered_at).toLocaleString()}
                  </small>
                </div>
              </li>
            ))}
          </ul>
        </>
      )}

      <div style={{ textAlign: 'center', marginTop: 16 }}>
        <button
          onClick={load}
          style={{
            padding: '10px 24px',
            background: '#2196F3',
            color: '#fff',
            border: 'none',
            borderRadius: 8,
            cursor: 'pointer',
          }}
        >
          🔄 Actualiser
        </button>
      </div>
    </div>
  );
}
