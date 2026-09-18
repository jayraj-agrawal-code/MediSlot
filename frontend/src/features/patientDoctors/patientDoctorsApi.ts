import { apiClient } from '../../api/client'
import type { PublicDoctor, Slot } from './types'

export async function fetchPatientDoctors(): Promise<PublicDoctor[]> {
  const { data } = await apiClient.get<{ data: PublicDoctor[] }>('/patient/doctors')

  return data.data
}

export async function fetchDoctorSlots(doctorId: number): Promise<Slot[]> {
  const { data } = await apiClient.get<{ data: Slot[] }>(`/patient/doctors/${doctorId}/slots`)

  return data.data
}
