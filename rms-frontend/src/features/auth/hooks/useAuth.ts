import { useState } from 'react';
import { requestOtp, verifyOtp } from '../../../api/auth';

export function useAuth() {
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState<'phone' | 'code'>('phone');
  const [phone, setPhone] = useState('');
  const [error, setError] = useState<string | null>(null);

  async function handleRequestOtp(p: string) {
    setLoading(true);
    setError(null);
    try {
      await requestOtp(p);
      setPhone(p);
      setStep('code');
    } catch (e: any) {
      setError(e.response?.data?.message ?? 'Erreur lors de la demande d’OTP.');
    } finally {
      setLoading(false);
    }
  }

  async function handleVerifyOtp(code: string) {
    setLoading(true);
    setError(null);
    try {
      const { user } = await verifyOtp(phone, code);
      return user;
    } catch (e: any) {
      setError(e.response?.data?.message ?? 'Erreur lors de la vérification de l’OTP.');
      throw e;
    } finally {
      setLoading(false);
    }
  }

  function logout() {
    localStorage.removeItem('rms_token');
    localStorage.removeItem('rms_user');
  }

  return {
    loading,
    step,
    phone,
    error,
    requestOtp: handleRequestOtp,
    verifyOtp: handleVerifyOtp,
    logout,
  };
}
