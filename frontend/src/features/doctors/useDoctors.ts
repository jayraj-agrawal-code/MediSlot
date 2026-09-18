import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createDoctor,
  createDoctorBreak,
  deleteDoctor,
  deleteDoctorBreak,
  fetchDoctors,
  setDoctorAvailability,
  updateDoctor,
} from './doctorsApi'
import type { AvailabilityEntry, CreateBreakPayload, DoctorPayload } from './types'

export const DOCTORS_QUERY_KEY = ['doctors'] as const

export function useDoctors() {
  return useQuery({
    queryKey: DOCTORS_QUERY_KEY,
    queryFn: fetchDoctors,
  })
}

export function useCreateDoctor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: DoctorPayload) => createDoctor(payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}

export function useUpdateDoctor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: DoctorPayload }) =>
      updateDoctor(id, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}

export function useDeleteDoctor() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => deleteDoctor(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}

export function useSetDoctorAvailability() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, availabilities }: { id: number; availabilities: AvailabilityEntry[] }) =>
      setDoctorAvailability(id, availabilities),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}

export function useCreateDoctorBreak() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ doctorId, payload }: { doctorId: number; payload: CreateBreakPayload }) =>
      createDoctorBreak(doctorId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}

export function useDeleteDoctorBreak() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ doctorId, breakId }: { doctorId: number; breakId: number }) =>
      deleteDoctorBreak(doctorId, breakId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: DOCTORS_QUERY_KEY }),
  })
}
