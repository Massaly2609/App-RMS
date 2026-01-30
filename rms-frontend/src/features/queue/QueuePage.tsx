import { useEffect, useState } from 'react';
import { apiClient } from '../../api/client';

export function QueuePage() {
  const [position, setPosition] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [state, setState] = useState<string | null>(null);

  useEffect(() => {
    loadPosition();
  }, []);

  async function loadPosition() {
    setLoading(true);
    try {
      // À implémenter selon ton API
      const res = await apiClient.get('/queue/position');
      setPosition(res.data.position);
      setState(res.data.queue_state);
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur position file');
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: 64 }}>
        Calcul position file...
      </div>
    );
  }

  return (
    <div style={{ maxWidth: 500, margin: '24px auto', padding: 24 }}>
      <h1>📋 Ma position dans la file RMS</h1>

      <div style={{
        textAlign: 'center',
        padding: 48,
        border: '3px dashed #2196F3',
        borderRadius: 16,
        margin: '48px 0',
        background: position ? '#E3F2FD' : '#FAFAFA'
      }}>
        {position !== null ? (
          <>
            <div style={{
              fontSize: 64,
              fontWeight: 'bold',
              color: '#2196F3',
              lineHeight: 1
            }}>
              #{position}
            </div>
            <h2 style={{ color: '#1976D2', margin: 8 }}>
              C’est ton tour dans {position}ème position
            </h2>
            <p style={{ opacity: 0.8 }}>
              État : <strong>{state ?? 'En file'}</strong>
            </p>
          </>
        ) : (
          <>
            <div style={{ fontSize: 48, opacity: 0.3 }}>?</div>
            <h3>Pas encore en file</h3>
            <p>Fais ton adhésion pour entrer !</p>
          </>
        )}
      </div>

      <div style={{ textAlign: 'center' }}>
        <button
          onClick={loadPosition}
          style={{
            padding: '12px 32px',
            fontSize: 16,
            background: '#4CAF50',
            color: 'white',
            border: 'none',
            borderRadius: 8,
            cursor: 'pointer'
          }}
          disabled={loading}
        >
          🔄 Actualiser
        </button>
      </div>
    </div>
  );
}
