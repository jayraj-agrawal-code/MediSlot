import { apiClient } from '../../api/client'
import type { Appointment, BookAppointmentPayload } from './types'

interface AppointmentResourceResponse {
  data: Appointment
}

export async function fetchAppointments(): Promise<Appointment[]> {
  const { data } = await apiClient.get<{ data: Appointment[] }>('/patient/appointments')

  return data.data
}

export async function bookAppointment(payload: BookAppointmentPayload): Promise<Appointment> {
  const { data } = await apiClient.post<AppointmentResourceResponse>('/patient/appointments', payload)

  return data.data
}

export async function cancelAppointment(id: number): Promise<Appointment> {
  const { data } = await apiClient.post<AppointmentResourceResponse>(`/patient/appointments/${id}/cancel`)

  return data.data
}
