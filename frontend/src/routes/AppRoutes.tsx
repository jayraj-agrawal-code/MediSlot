import { Navigate, Route, Routes } from 'react-router-dom'
import { AdminLayout } from '../components/layout/AdminLayout'
import { PatientLayout } from '../components/layout/PatientLayout'
import { AdminDashboardPage } from '../pages/admin/AdminDashboardPage'
import { AdminLoginPage } from '../pages/admin/AdminLoginPage'
import { DoctorsListPage } from '../pages/admin/doctors/DoctorsListPage'
import { PatientAppointmentsPage } from '../pages/patient/PatientAppointmentsPage'
import { PatientDoctorSlotsPage } from '../pages/patient/PatientDoctorSlotsPage'
import { PatientDoctorsPage } from '../pages/patient/PatientDoctorsPage'
import { PatientLoginPage } from '../pages/patient/PatientLoginPage'
import { PatientRegisterPage } from '../pages/patient/PatientRegisterPage'
import { AdminGuestRoute } from './AdminGuestRoute'
import { AdminProtectedRoute } from './AdminProtectedRoute'
import { PatientGuestRoute } from './PatientGuestRoute'
import { PatientProtectedRoute } from './PatientProtectedRoute'

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/patient/login" replace />} />

      <Route element={<AdminGuestRoute />}>
        <Route path="/admin/login" element={<AdminLoginPage />} />
      </Route>

      <Route element={<AdminProtectedRoute />}>
        <Route element={<AdminLayout />}>
          <Route path="/admin/dashboard" element={<AdminDashboardPage />} />
          <Route path="/admin/doctors" element={<DoctorsListPage />} />
        </Route>
      </Route>

      <Route element={<PatientGuestRoute />}>
        <Route path="/patient/login" element={<PatientLoginPage />} />
        <Route path="/patient/register" element={<PatientRegisterPage />} />
      </Route>

      <Route element={<PatientProtectedRoute />}>
        <Route element={<PatientLayout />}>
          <Route path="/patient/doctors" element={<PatientDoctorsPage />} />
          <Route path="/patient/doctors/:doctorId" element={<PatientDoctorSlotsPage />} />
          <Route path="/patient/appointments" element={<PatientAppointmentsPage />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/patient/login" replace />} />
    </Routes>
  )
}
