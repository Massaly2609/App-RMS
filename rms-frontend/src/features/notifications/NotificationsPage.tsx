import { useEffect, useState } from 'react';
import { apiClient } from '../../api/client';

export function NotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadNotifications();
  }, []);

  async function loadNotifications(page = 1) {
    setLoading(true);
    try {
      const res = await apiClient.get('/notifications', { params: { page } });
      setNotifications(res.data.data.data);
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur chargement notifications');
    } finally {
      setLoading(false);
    }
  }

  async function markAllAsRead() {
    try {
      await apiClient.post('/notifications/mark-as-read');
      setNotifications(notifications.map(n => ({ ...n, read_at: new Date().toISOString() })));
    } catch (e: any) {
      alert('Erreur marquage lu');
    }
  }

  return (
    <div style={{ maxWidth: 600, margin: '24px auto', padding: 24 }}>
      <h1>Mes notifications</h1>

      <button onClick={markAllAsRead} style={{ marginBottom: 16 }}>
        Tout marquer comme lu
      </button>

      {loading ? (
        <p>Chargement...</p>
      ) : notifications.length === 0 ? (
        <p>Aucune notification</p>
      ) : (
        <ul style={{ listStyle: 'none', padding: 0 }}>
          {notifications.map((n: any) => (
            <li
              key={n.id}
              style={{
                padding: 12,
                borderBottom: '1px solid #eee',
                background: n.read_at ? '#f9f9f9' : '#fff',
              }}
            >
              <div dangerouslySetInnerHTML={{ __html: n.data.message || 'Activité récente' }} />
              <small>{new Date(n.created_at).toLocaleString()}</small>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
