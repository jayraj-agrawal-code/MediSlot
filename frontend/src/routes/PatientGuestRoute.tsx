import { Navigate, Outlet } from 'react-router-dom'
import { FullscreenLoader } from '../components/FullscreenLoader'
import { usePatientSession } from '../features/patientAuth/usePatientSession'

export function PatientGuestRoute() {
  const { data: patient, isLoading } = usePatientSession()

  if (isLoading) {
    return <FullscreenLoader />
  }

  if (patient) {
    return <Navigate to="/patient/doctors" replace />
  }

  return <Outlet />
}
