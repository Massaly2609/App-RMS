import { apiClient } from './client';

export type RotationItem = {
  id: number;
  amount: number;
  source: 'eligible_next_gain' | 'fifo_queue';
  triggered_at: string;
};

export async function getRotations() {
  const response = await apiClient.get('/rotations');
  return response.data as {
    rotations: RotationItem[];
    eligible_next_gain: boolean;
  };
}
