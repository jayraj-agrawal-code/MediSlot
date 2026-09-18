import { useAdminSession } from '../../features/auth/useAdminSession'
import { useDoctors } from '../../features/doctors/useDoctors'

export function AdminDashboardPage() {
  const { data: admin } = useAdminSession()
  const { data: doctors } = useDoctors()

  const totalDoctors = doctors?.length ?? 0
  const activeDoctors = doctors?.filter((doctor) => doctor.is_active).length ?? 0

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-text-h">Welcome back, {admin?.name}.</h2>
        <p className="text-sm text-text">Here's a quick look at your practice.</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div className="rounded-xl border border-border bg-bg p-5">
          <p className="text-sm text-text">Total doctors</p>
          <p className="mt-1 text-2xl font-semibold text-text-h">{totalDoctors}</p>
        </div>
        <div className="rounded-xl border border-border bg-bg p-5">
          <p className="text-sm text-text">Active doctors</p>
          <p className="mt-1 text-2xl font-semibold text-text-h">{activeDoctors}</p>
        </div>
      </div>
    </div>
  )
}
