import { Link } from 'react-router-dom'
import { PatientRegisterForm } from '../../features/patientAuth/components/PatientRegisterForm'

export function PatientRegisterPage() {
  return (
    <main className="auth-page">
      <div className="auth-card">
        <img src="/logo.png" alt="MediSlot" className="mx-auto mb-3 h-14 w-14" />
        <h1>Create your account</h1>
        <p className="auth-subtitle">Book appointments with our doctors.</p>

        <PatientRegisterForm />

        <p className="mt-2 text-sm text-text">
          Already have an account?{' '}
          <Link to="/patient/login" className="font-medium text-accent hover:text-accent-hover">
            Sign in
          </Link>
        </p>
      </div>
    </main>
  )
}
