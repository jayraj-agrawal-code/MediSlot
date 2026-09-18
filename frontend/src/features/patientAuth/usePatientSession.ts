import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchCurrentPatient, loginPatient, logoutPatient, registerPatient } from './patientAuthApi'
import type { Patient, PatientLoginPayload, PatientRegisterPayload } from './types'

export const PATIENT_SESSION_QUERY_KEY = ['patient', 'session'] as const

export function usePatientSession() {
  return useQuery({
    queryKey: PATIENT_SESSION_QUERY_KEY,
    queryFn: fetchCurrentPatient,
    staleTime: 5 * 60 * 1000,
    retry: false,
  })
}

export function usePatientRegister() {
  return useMutation({
    mutationFn: (payload: PatientRegisterPayload) => registerPatient(payload),
  })
}

export function usePatientLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: PatientLoginPayload) => loginPatient(payload),
    onSuccess: (patient: Patient) => {
      queryClient.setQueryData(PATIENT_SESSION_QUERY_KEY, patient)
    },
  })
}

export function usePatientLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logoutPatient,
    onSuccess: () => {
      queryClient.setQueryData(PATIENT_SESSION_QUERY_KEY, null)
    },
  })
}
