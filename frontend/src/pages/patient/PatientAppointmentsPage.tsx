import { useState } from 'react'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { SpinnerIcon } from '../../components/icons'
import { useAppointments, useCancelAppointment } from '../../features/patientAppointments/useAppointments'
import type { Appointment } from '../../features/patientAppointments/types'

const STATUS_STYLES: Record<Appointment['status'], string> = {
  booked: 'bg-accent-bg text-accent',
  cancelled: 'bg-danger-bg text-danger',
}

export function PatientAppointmentsPage() {
  const { data: appointments, isLoading, isError } = useAppointments()
  const cancelAppointment = useCancelAppointment()
  const [pendingCancel, setPendingCancel] = useState<Appointment | null>(null)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold text-text-h">My appointments</h1>
        <p className="text-sm text-text">View and manage your booked appointments.</p>
      </div>

      {isLoading && (
        <div className="flex items-center justify-center gap-2 p-10 text-sm text-text">
          <SpinnerIcon className="h-5 w-5" />
          Loading appointments…
        </div>
      )}

      {isError && <p className="p-10 text-center text-sm text-danger">Failed to load appointments.</p>}

      {!isLoading && !isError && appointments && appointments.length === 0 && (
        <p className="rounded-xl border border-border bg-bg p-10 text-center text-sm text-text">
          You haven't booked any appointments yet.
        </p>
      )}

      {!isLoading && !isError && appointments && appointments.length > 0 && (
        <div className="space-y-3">
          {appointments.map((appointment) => (
            <div
              key={appointment.id}
              className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-bg p-4"
            >
              <div>
                <p className="font-medium text-text-h">{appointment.doctor.name}</p>
                <p className="text-sm text-text">{appointment.doctor.specialization ?? 'General'}</p>
                <p className="mt-1 text-sm text-text">
                  {appointment.appointment_date} · {appointment.start_time}–{appointment.end_time}
                </p>
              </div>

              <div className="flex items-center gap-3">
                <span
                  className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${STATUS_STYLES[appointment.status]}`}
                >
                  {appointment.status}
                </span>

                {appointment.can_cancel && (
                  <button
                    type="button"
                    className="rounded-lg border border-border px-3 py-2 text-sm font-medium text-text-h hover:border-danger hover:text-danger"
                    onClick={() => setPendingCancel(appointment)}
                  >
                    Cancel
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {pendingCancel && (
        <ConfirmDialog
          title="Cancel appointment"
          message={`Cancel your appointment with ${pendingCancel.doctor.name} on ${pendingCancel.appointment_date} at ${pendingCancel.start_time}?`}
          confirmLabel="Cancel appointment"
          isPending={cancelAppointment.isPending}
          onConfirm={() =>
            cancelAppointment.mutate(pendingCancel.id, { onSuccess: () => setPendingCancel(null) })
          }
          onCancel={() => setPendingCancel(null)}
        />
      )}
    </div>
  )
}
