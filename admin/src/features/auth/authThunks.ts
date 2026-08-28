import { createAsyncThunk } from '@reduxjs/toolkit';
import { fetchSession, logoutSession } from '@/api/auth';
import { ApiError } from '@/api/client';
import { clearCredentials, setCredentials, setReady } from '@/features/auth/authSlice';
import type { RootState } from '@/app/store';

export const hydrateSession = createAsyncThunk('auth/hydrate', async (_, { dispatch, getState }) => {
  const { token } = (getState() as RootState).auth;

  if (!token) {
    dispatch(clearCredentials());
    dispatch(setReady());
    return;
  }

  try {
    const response = await fetchSession(token);
    const user = response.data.user;

    if (user.role !== 'admin' || !user.is_active) {
      dispatch(clearCredentials());
      dispatch(setReady());
      return;
    }

    dispatch(setCredentials({ token, user }));
  } catch (error) {
    if (error instanceof ApiError && (error.status === 401 || error.status === 403)) {
      dispatch(clearCredentials());
    }
  } finally {
    dispatch(setReady());
  }
});

export const logout = createAsyncThunk('auth/logout', async (_, { dispatch, getState }) => {
  const { token } = (getState() as RootState).auth;

  if (token) {
    try {
      await logoutSession(token);
    } catch {
      // Local session is cleared regardless of network errors.
    }
  }

  dispatch(clearCredentials());
});
