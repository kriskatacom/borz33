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

export type AuthState = {
  token: string | null;
  user: AdminUser | null;
};

const initialState: AuthState = {
  token: typeof window === 'undefined' ? null : window.localStorage.getItem(STORAGE_KEYS.token),
  user: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setCredentials(state, action: PayloadAction<{ token: string; user: AdminUser }>) {
      state.token = action.payload.token;
      state.user = action.payload.user;
      window.localStorage.setItem(STORAGE_KEYS.token, action.payload.token);
    },
    clearCredentials(state) {
      state.token = null;
      state.user = null;
      window.localStorage.removeItem(STORAGE_KEYS.token);
    },
  },
});

export const { setCredentials, clearCredentials } = authSlice.actions;
export const authReducer = authSlice.reducer;
