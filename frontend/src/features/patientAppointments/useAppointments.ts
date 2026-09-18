import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { bookAppointment, cancelAppointment, fetchAppointments } from './appointmentsApi'
import type { BookAppointmentPayload } from './types'

export const APPOINTMENTS_QUERY_KEY = ['patient', 'appointments'] as const

export function useAppointments() {
  return useQuery({
    queryKey: APPOINTMENTS_QUERY_KEY,
    queryFn: fetchAppointments,
    // An admin can move/cancel an appointment (e.g. by adding a doctor break)
    // from a completely different browser session, so this patient's cache
    // has no invalidation signal for that change. Refetch on a short
    // interval and whenever the tab regains focus/is revisited so the list
    // doesn't go stale while the patient is sitting on this page.
    refetchInterval: 15_000,
    refetchOnWindowFocus: true,
    refetchOnMount: 'always',
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
