import { useQuery } from '@tanstack/react-query'
import { fetchDoctorSlots, fetchPatientDoctors } from './patientDoctorsApi'

export function usePatientDoctors() {
  return useQuery({
    queryKey: ['patient', 'doctors'],
    queryFn: fetchPatientDoctors,
  })
}

export function useDoctorSlots(doctorId: number) {
  return useQuery({
    queryKey: ['patient', 'doctors', doctorId, 'slots'],
    queryFn: () => fetchDoctorSlots(doctorId),
    // Admin-side changes (breaks, availability) or other patients booking
    // can shift what's free without any local invalidation signal.
    refetchInterval: 15_000,
    refetchOnWindowFocus: true,
    refetchOnMount: 'always',
  })
}
