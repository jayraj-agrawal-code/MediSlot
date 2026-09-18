import { isAxiosError } from 'axios'
import { apiClient, ensureCsrfCookie } from '../../api/client'
import type { Patient, PatientLoginPayload, PatientRegisterPayload } from './types'

interface PatientResourceResponse {
  data: Patient
}

export async function fetchCurrentPatient(): Promise<Patient | null> {
  try {
    const { data } = await apiClient.get<PatientResourceResponse>('/patient/me')

    return data.data
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 401) {
      return null
    }

    throw error
  }
}

export async function registerPatient(payload: PatientRegisterPayload): Promise<Patient> {
  const { data } = await apiClient.post<PatientResourceResponse>('/patient/register', payload)

  return data.data
}

export async function loginPatient(payload: PatientLoginPayload): Promise<Patient> {
  await ensureCsrfCookie()

  const { data } = await apiClient.post<PatientResourceResponse>('/patient/login', payload)

  return data.data
}

export async function logoutPatient(): Promise<void> {
  await apiClient.post('/patient/logout')
}
