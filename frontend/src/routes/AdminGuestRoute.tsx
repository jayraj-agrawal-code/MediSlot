import { Navigate, Outlet } from 'react-router-dom'
import { FullscreenLoader } from '../components/FullscreenLoader'
import { useAdminSession } from '../features/auth/useAdminSession'

export function AdminGuestRoute() {
  const { data: admin, isLoading } = useAdminSession()

  if (isLoading) {
    return <FullscreenLoader />
  }

  if (admin) {
    return <Navigate to="/admin/dashboard" replace />
  }

  return <Outlet />
}
