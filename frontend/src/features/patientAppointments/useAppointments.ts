import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { bookAppointment, cancelAppointment, fetchAppointments } from './appointmentsApi'
import type { BookAppointmentPayload } from './types'

export const APPOINTMENTS_QUERY_KEY = ['patient', 'appointments'] as const

export function useAppointments() {
  return useQuery({
    queryKey: APPOINTMENTS_QUERY_KEY,
    queryFn: fetchAppointments,
  })
}

export function useBookAppointment() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: BookAppointmentPayload) => bookAppointment(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: APPOINTMENTS_QUERY_KEY })
      queryClient.invalidateQueries({ queryKey: ['patient', 'doctors'] })
    },
  })
}

export function useCancelAppointment() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => cancelAppointment(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: APPOINTMENTS_QUERY_KEY })
      queryClient.invalidateQueries({ queryKey: ['patient', 'doctors'] })
    },
  })
}
