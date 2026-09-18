import { Navigate, Outlet } from 'react-router-dom'
import { FullscreenLoader } from '../components/FullscreenLoader'
import { useAdminSession } from '../features/auth/useAdminSession'

export function AdminProtectedRoute() {
  const { data: admin, isLoading } = useAdminSession()

  if (isLoading) {
    return <FullscreenLoader />
  }

  if (!admin) {
    return <Navigate to="/admin/login" replace />
  }

  return <Outlet />
}
