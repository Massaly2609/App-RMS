import { BrowserRouter, Routes, Route, Navigate, Link } from 'react-router-dom';
import { AuthOtpPage } from '../features/pages/AuthOtpPage';
import { DashboardPage } from '../features/pages/DashboardPage';
import type { JSX } from 'react';
import { TimelinePage } from '../features/timeline/pages/TimelinePage';
import { WalletPage } from '../features/wallet/WalletPage';
import { QueuePage } from '../features/queue/QueuePage';
import { RotationsPage } from '../features/rotations/RotationsPage';
import { AdminPage } from '../features/admin/AdminPage';



function RequireAuth({ children }: { children: JSX.Element }) {
  const token = localStorage.getItem('rms_token');
  if (!token) {
    return <Navigate to="/auth" replace />;
  }
  return children;
}

const navStyle = {
  textDecoration: 'none',
  color: '#1976D2',
  fontWeight: '500',
  padding: '8px 12px',
  borderRadius: 6,
  transition: 'background 0.2s'
};

function Layout({ children }: { children: JSX.Element }) {
  return (
    <div>
      <nav style={{
        padding: 16,
        borderBottom: '2px solid #2196F3',
        background: '#FAFAFA',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center'
      }}>
        <div style={{ display: 'flex', gap: 24 }}>
          <Link to="/dashboard" style={navStyle}>🏠 Dashboard</Link>
          <Link to="/timeline" style={navStyle}>📱 Timeline</Link>
          <Link to="/wallet" style={navStyle}>💰 Wallet</Link>
          <Link to="/queue" style={navStyle}>📋 File</Link>
          <Link to="/rotations" style={navStyle}>🔄 Rotations</Link>
          {(() => {
            const user = localStorage.getItem('rms_user');
            if (user) {
              const u = JSON.parse(user);
              if (u.is_admin) {
                return <Link to="/admin" style={navStyle}>⚙️ Admin</Link>;
              }
            }
            return null;
          })()}
        </div>

        <button
          onClick={() => {
            localStorage.removeItem('rms_token');
            localStorage.removeItem('rms_user');
            window.location.href = '/auth';
          }}
          style={{
            padding: '8px 16px',
            background: '#f44336',
            color: 'white',
            border: 'none',
            borderRadius: 6,
            cursor: 'pointer',
            fontWeight: '500'
          }}
        >
          🚪 Déconnexion
        </button>
      </nav>

      {children}

    </div>
  );
}

export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/auth" element={<AuthOtpPage />} />
        <Route
          path="/dashboard"
          element={
            <RequireAuth>
              <Layout>
                <DashboardPage />
              </Layout>
            </RequireAuth>
          }
        />
        <Route
          path="/timeline"
          element={
            <RequireAuth>
              <Layout>
                <TimelinePage />
              </Layout>
            </RequireAuth>
          }
        />
        <Route
          path="/wallet"
          element={
            <RequireAuth>
              <Layout>
                <WalletPage />
              </Layout>
            </RequireAuth>
          }
        />
        <Route
          path="/queue"
          element={
            <RequireAuth>
              <Layout>
                <QueuePage />
              </Layout>
            </RequireAuth>
          }
        />
        <Route
          path="/rotations"
          element={
            <RequireAuth>
              <Layout>
                <RotationsPage />
              </Layout>
            </RequireAuth>
          }
        />

       <Route
        path="/admin"
        element={
          <RequireAuth>
            <Layout>
              <AdminPage />
            </Layout>
          </RequireAuth>
        }
        />
        <Route path="*" element={<Navigate to="/auth" replace />} />

      </Routes>


    </BrowserRouter>
  );
}