import { isAxiosError } from 'axios'
import { apiClient, ensureCsrfCookie } from '../../api/client'
import type { Admin, AdminLoginPayload } from './types'

interface AdminResourceResponse {
  data: Admin
}

export async function fetchCurrentAdmin(): Promise<Admin | null> {
  try {
    const { data } = await apiClient.get<AdminResourceResponse>('/admin/me')

    return data.data
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 401) {
      return null
    }

    throw error
  }
}

export async function loginAdmin(payload: AdminLoginPayload): Promise<Admin> {
  await ensureCsrfCookie()

  const { data } = await apiClient.post<AdminResourceResponse>('/admin/login', payload)

  return data.data
}

export async function logoutAdmin(): Promise<void> {
  await apiClient.post('/admin/logout')
}
