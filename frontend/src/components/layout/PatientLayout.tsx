import { NavLink, Outlet } from 'react-router-dom'
import { usePatientLogout, usePatientSession } from '../../features/patientAuth/usePatientSession'

const NAV_ITEMS = [
  { to: '/patient/doctors', label: 'Doctors' },
  { to: '/patient/appointments', label: 'My appointments' },
]

export function PatientLayout() {
  const { data: patient } = usePatientSession()
  const logoutMutation = usePatientLogout()

  return (
    <div className="min-h-svh bg-bg-subtle">
      <header className="border-b border-border bg-bg">
        <div className="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 md:px-6">
          <div className="flex items-center gap-6">
            <div className="flex items-center gap-2.5">
              <img src="/logo.png" alt="MediSlot" className="h-8 w-8" />
              <span className="hidden text-lg font-semibold text-text-h sm:inline">MediSlot</span>
            </div>
            <nav className="flex items-center gap-1">
              {NAV_ITEMS.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) =>
                    `rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                      isActive
                        ? 'bg-accent-bg text-accent'
                        : 'text-text hover:bg-bg-subtle hover:text-text-h'
                    }`
                  }
                >
                  {item.label}
                </NavLink>
              ))}
            </nav>
          </div>

          <div className="flex items-center gap-3">
            <span className="hidden text-sm text-text sm:inline">{patient?.name}</span>
            <button
              type="button"
              className="rounded-lg border border-border px-3 py-2 text-sm font-medium text-text-h hover:border-accent-hover disabled:opacity-60"
              onClick={() => logoutMutation.mutate()}
              disabled={logoutMutation.isPending}
            >
              {logoutMutation.isPending ? 'Signing out…' : 'Sign out'}
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-5xl p-4 md:p-6">
        <Outlet />
      </main>
    </div>
  )
}
