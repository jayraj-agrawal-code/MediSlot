import axios from 'axios'

export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'

export const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Laravel Sanctum SPA auth requires a CSRF cookie before any stateful
 * (session-authenticating) request such as login. This hits the backend's
 * root domain, not the /api/v1 prefix.
 */
export async function ensureCsrfCookie(): Promise<void> {
  await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`, { withCredentials: true })
}
