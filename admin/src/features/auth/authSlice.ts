import { createSlice, type PayloadAction } from '@reduxjs/toolkit';
import { STORAGE_KEYS } from '@/app/constants';

export type AdminUser = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  role: string;
  is_active: boolean;
  email_verified_at: string | null;
};

export type AuthStatus = 'hydrating' | 'ready';

export type AuthState = {
  status: AuthStatus;
  token: string | null;
  user: AdminUser | null;
};

function readStoredUser(): AdminUser | null {
  if (typeof window === 'undefined') {
    return null;
  }

  const raw = window.localStorage.getItem(STORAGE_KEYS.user);

  if (!raw) {
    return null;
  }

  try {
    const parsed: unknown = JSON.parse(raw);

    if (parsed !== null && typeof parsed === 'object' && 'id' in parsed && 'email' in parsed) {
      return parsed as AdminUser;
    }
  } catch {
    return null;
  }

  return null;
}

const initialState: AuthState = {
  status: 'hydrating',
  token: typeof window === 'undefined' ? null : window.localStorage.getItem(STORAGE_KEYS.token),
  user: readStoredUser(),
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setCredentials(state, action: PayloadAction<{ token: string; user: AdminUser }>) {
      state.token = action.payload.token;
      state.user = action.payload.user;
      window.localStorage.setItem(STORAGE_KEYS.token, action.payload.token);
      window.localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(action.payload.user));
    },
    clearCredentials(state) {
      state.token = null;
      state.user = null;
      window.localStorage.removeItem(STORAGE_KEYS.token);
      window.localStorage.removeItem(STORAGE_KEYS.user);
    },
    setReady(state) {
      state.status = 'ready';
    },
  },
});

export const { setCredentials, clearCredentials, setReady } = authSlice.actions;
export const authReducer = authSlice.reducer;
