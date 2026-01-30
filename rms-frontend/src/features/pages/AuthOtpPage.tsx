import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/hooks/useAuth';

export function AuthOtpPage() {
  const { loading, step, phone, error, requestOtp, verifyOtp } = useAuth();
  const [phoneInput, setPhoneInput] = useState(phone || '');
  const [codeInput, setCodeInput] = useState('');
  const navigate = useNavigate();

  async function handleSubmitPhone(e: FormEvent) {
    e.preventDefault();
    await requestOtp(phoneInput);
  }

  async function handleSubmitCode(e: FormEvent) {
    e.preventDefault();
    const user = await verifyOtp(codeInput);
    if (user) {
      navigate('/dashboard');
    }
  }

  return (
    <div style={{ maxWidth: 400, margin: '40px auto' }}>
      <h2>Connexion RMS par OTP</h2>

      {error && <p style={{ color: 'red' }}>{error}</p>}

      {step === 'phone' && (
        <form onSubmit={handleSubmitPhone}>
          <label>
            Téléphone
            <input
              type="text"
              value={phoneInput}
              onChange={(e) => setPhoneInput(e.target.value)}
              placeholder="+221770000001"
            />
          </label>
          <button type="submit" disabled={loading}>
            {loading ? 'Envoi...' : 'Recevoir un code'}
          </button>
        </form>
      )}

      {step === 'code' && (
        <form onSubmit={handleSubmitCode}>
          <p>Code envoyé au {phone}</p>
          <label>
            Code OTP
            <input
              type="text"
              value={codeInput}
              onChange={(e) => setCodeInput(e.target.value)}
              placeholder="123456"
            />
          </label>
          <button type="submit" disabled={loading}>
            {loading ? 'Vérification...' : 'Se connecter'}
          </button>
        </form>
      )}
    </div>
  );
}
