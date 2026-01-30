import { apiClient } from './client';

export async function getQueuePosition() {
  const response = await apiClient.get('/queue/position');
  return response.data as {
    position: number | null;
    queue_state: string | null;
  };
}
