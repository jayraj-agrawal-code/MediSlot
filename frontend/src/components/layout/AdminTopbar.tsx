import { useLocation } from 'react-router-dom'
import { useAdminLogout, useAdminSession } from '../../features/auth/useAdminSession'
import { MenuIcon } from '../icons'

const PAGE_TITLES: Record<string, string> = {
  '/admin/dashboard': 'Dashboard',
  '/admin/doctors': 'Doctors',
}

interface AdminTopbarProps {
  onMenuClick: () => void
}

export function AdminTopbar({ onMenuClick }: AdminTopbarProps) {
  const location = useLocation()
  const { data: admin } = useAdminSession()
  const logoutMutation = useAdminLogout()

  const title = PAGE_TITLES[location.pathname] ?? 'Admin Portal'

  return (
    <header className="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border bg-bg px-4 md:px-6">
      <div className="flex items-center gap-3">
        <button
          type="button"
          className="rounded-lg p-2 text-text hover:bg-bg-subtle md:hidden"
          onClick={onMenuClick}
          aria-label="Open menu"
        >
          <MenuIcon className="h-5 w-5" />
        </button>
        <h1 className="text-lg font-semibold text-text-h">{title}</h1>
      </div>

      <div className="flex items-center gap-3">
        <div className="hidden text-right sm:block">
          <p className="text-sm font-medium text-text-h">{admin?.name}</p>
          <p className="text-xs text-text">{admin?.email}</p>
        </div>
        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-accent-bg text-sm font-semibold text-accent">
          {admin?.name?.charAt(0).toUpperCase()}
        </span>
        <button
          type="button"
          className="rounded-lg border border-border px-3 py-2 text-sm font-medium text-text-h hover:border-accent-hover disabled:opacity-60"
          onClick={() => logoutMutation.mutate()}
          disabled={logoutMutation.isPending}
        >
          {logoutMutation.isPending ? 'Signing out…' : 'Sign out'}
        </button>
      </div>
    </header>
  )
}
