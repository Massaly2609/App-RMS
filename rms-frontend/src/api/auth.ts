import { apiClient } from './client';

export async function requestOtp(phone: string) {
  return apiClient.post('/auth/request-otp', { phone });
}

export async function verifyOtp(phone: string, code: string) {
  const response = await apiClient.post('/auth/verify-otp', { phone, code });
  const { token, user } = response.data.data;

  localStorage.setItem('rms_token', token);
  localStorage.setItem('rms_user', JSON.stringify(user));

  // Redirige direct sans reload
  window.location.href = '/dashboard';

  return { token, user };
}
