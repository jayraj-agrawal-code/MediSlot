import { apiClient } from '../../api/client'
import type {
  AvailabilityEntry,
  CreateBreakPayload,
  CreateBreakResult,
  Doctor,
  DoctorPayload,
} from './types'

interface DoctorListResponse {
  data: Doctor[]
}

interface DoctorResourceResponse {
  data: Doctor
}

export async function fetchDoctors(): Promise<Doctor[]> {
  const { data } = await apiClient.get<DoctorListResponse>('/admin/doctors')

  return data.data
}

export async function createDoctor(payload: DoctorPayload): Promise<Doctor> {
  const { data } = await apiClient.post<DoctorResourceResponse>('/admin/doctors', payload)

  return data.data
}

export async function updateDoctor(id: number, payload: DoctorPayload): Promise<Doctor> {
  const { data } = await apiClient.put<DoctorResourceResponse>(`/admin/doctors/${id}`, payload)

  return data.data
}

export async function deleteDoctor(id: number): Promise<void> {
  await apiClient.delete(`/admin/doctors/${id}`)
}

export async function setDoctorAvailability(
  id: number,
  availabilities: AvailabilityEntry[],
): Promise<void> {
  await apiClient.put(`/admin/doctors/${id}/availabilities`, { availabilities })
}

export async function createDoctorBreak(
  doctorId: number,
  payload: CreateBreakPayload,
): Promise<CreateBreakResult> {
  const { data } = await apiClient.post<{
    data: CreateBreakResult['break']
    rescheduled_appointments: CreateBreakResult['rescheduled']
    cancelled_appointments: CreateBreakResult['cancelled']
  }>(`/admin/doctors/${doctorId}/breaks`, payload)

  return {
    break: data.data,
    rescheduled: data.rescheduled_appointments,
    cancelled: data.cancelled_appointments,
  }
}

export async function deleteDoctorBreak(doctorId: number, breakId: number): Promise<void> {
  await apiClient.delete(`/admin/doctors/${doctorId}/breaks/${breakId}`)
}
