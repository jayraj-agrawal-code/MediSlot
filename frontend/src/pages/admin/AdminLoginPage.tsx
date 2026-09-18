import { AdminLoginForm } from '../../features/auth/components/AdminLoginForm'

export function AdminLoginPage() {
  return (
    <main className="auth-page">
      <div className="auth-card">
        <img src="/logo.png" alt="MediSlot" className="mx-auto mb-3 h-14 w-14" />
        <h1>Admin Portal</h1>
        <p className="auth-subtitle">Sign in to manage appointments.</p>
        <AdminLoginForm />
      </div>
    </main>
  )
}
