import { apiClient } from './client';

export async function adhesion() {
  const response = await apiClient.post('/wallet/adhesion');
  return response.data.data as { user_id: number; position: number | null };
}

export type RepaymentInfo = {
  id: number;
  target_amount: number;
  amount_paid: number;
  status: 'in_progress' | 'completed';
  started_at: string | null;
  completed_at: string | null;
};

export async function getCurrentRepayment() {
  const response = await apiClient.get('/wallet/repayment');
  return response.data.data as {
    queue_state: string | null;
    repayment: RepaymentInfo | null;
  };
}

export async function remboursement(amount: number) {
  const response = await apiClient.post('/wallet/remboursement', { amount });
  return response.data.data as {
    user_id: number;
    queue_state: string | null;
    repayment: RepaymentInfo | null;
  };
}

export async function getWallet() {
  const response = await apiClient.get('/wallet');
  return response.data as {
    balance: number;
    currency: string;
    transactions: Array<{
      id: number;
      type: string;
      amount: number;
      created_at: string;
    }>;
  };
}