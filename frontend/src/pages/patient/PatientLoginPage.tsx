import { Link, useLocation } from 'react-router-dom'
import { PatientLoginForm } from '../../features/patientAuth/components/PatientLoginForm'

export function PatientLoginPage() {
  const location = useLocation()
  const registered = Boolean((location.state as { registered?: boolean } | null)?.registered)

  return (
    <main className="auth-page">
      <div className="auth-card">
        <img src="/logo.png" alt="MediSlot" className="mx-auto mb-3 h-14 w-14" />
        <h1>Patient Portal</h1>
        <p className="auth-subtitle">Sign in to book and manage your appointments.</p>

        {registered && (
          <p className="rounded-lg bg-accent-bg px-3 py-2 text-sm text-accent">
            Account created. Please sign in.
          </p>
        )}

        <PatientLoginForm />

        <p className="mt-2 text-sm text-text">
          New here?{' '}
          <Link to="/patient/register" className="font-medium text-accent hover:text-accent-hover">
            Create an account
          </Link>
        </p>
      </div>
    </main>
  )
}
