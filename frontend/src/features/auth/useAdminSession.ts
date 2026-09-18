import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchCurrentAdmin, loginAdmin, logoutAdmin } from './adminAuthApi'
import type { Admin, AdminLoginPayload } from './types'

export const ADMIN_SESSION_QUERY_KEY = ['admin', 'session'] as const

export function useAdminSession() {
  return useQuery({
    queryKey: ADMIN_SESSION_QUERY_KEY,
    queryFn: fetchCurrentAdmin,
    staleTime: 5 * 60 * 1000,
    retry: false,
  })
}

export function useAdminLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: AdminLoginPayload) => loginAdmin(payload),
    onSuccess: (admin: Admin) => {
      queryClient.setQueryData(ADMIN_SESSION_QUERY_KEY, admin)
    },
  })
}

export function useAdminLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logoutAdmin,
    onSuccess: () => {
      queryClient.setQueryData(ADMIN_SESSION_QUERY_KEY, null)
    },
  })
}
