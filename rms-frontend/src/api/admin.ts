import { apiClient } from './client';

export async function getAdminStats() {
  const res = await apiClient.get('/admin/stats');
  return res.data as {
    users_count: number;
    queue_active: number;
    eligible_count: number;
    total_rotations_amount: number;
  };
}

export async function runAdminRotation() {
  const res = await apiClient.post('/admin/rotations/run-once');
  return res.data;
}
