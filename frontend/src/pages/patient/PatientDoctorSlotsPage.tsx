import { useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { extractErrorMessage } from '../../api/errors'
import { SpinnerIcon } from '../../components/icons'
import { useBookAppointment } from '../../features/patientAppointments/useAppointments'
import { useDoctorSlots, usePatientDoctors } from '../../features/patientDoctors/usePatientDoctors'
import type { Slot } from '../../features/patientDoctors/types'

function formatDateHeading(date: string): string {
  return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
  })
}

export function PatientDoctorSlotsPage() {
  const { doctorId } = useParams<{ doctorId: string }>()
  const id = Number(doctorId)
  const navigate = useNavigate()

  const { data: doctors } = usePatientDoctors()
  const doctor = doctors?.find((entry) => entry.id === id)

  const { data: slots, isLoading, isError, refetch } = useDoctorSlots(id)
  const bookAppointment = useBookAppointment()
  const [selectedSlot, setSelectedSlot] = useState<Slot | null>(null)
  const [pendingSlot, setPendingSlot] = useState<Slot | null>(null)

  const slotsByDate = useMemo(() => {
    const grouped = new Map<string, Slot[]>()

    for (const slot of slots ?? []) {
      const existing = grouped.get(slot.date) ?? []
      existing.push(slot)
      grouped.set(slot.date, existing)
    }

    return grouped
  }, [slots])

  function handleConfirmBook() {
    if (!pendingSlot) return
    const slot = pendingSlot
    setSelectedSlot(slot)
    setPendingSlot(null)

    bookAppointment.mutate(
      { doctor_id: id, date: slot.date, start_time: slot.start_time },
      {
        onSuccess: () => navigate('/patient/appointments'),
        onError: () => refetch(),
        onSettled: () => setSelectedSlot(null),
      },
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <Link to="/patient/doctors" className="text-sm font-medium text-accent hover:text-accent-hover">
          ← Back to doctors
        </Link>
        <h1 className="mt-2 text-lg font-semibold text-text-h">{doctor?.name ?? 'Doctor'}</h1>
        <p className="text-sm text-text">{doctor?.specialization ?? 'General'}</p>
      </div>

      {bookAppointment.isError && (
        <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
          {extractErrorMessage(bookAppointment.error)}
        </p>
      )}

      {isLoading && (
        <div className="flex items-center justify-center gap-2 p-10 text-sm text-text">
          <SpinnerIcon className="h-5 w-5" />
          Loading available slots…
        </div>
      )}

      {isError && <p className="p-10 text-center text-sm text-danger">Failed to load slots.</p>}

      {!isLoading && !isError && slotsByDate.size === 0 && (
        <p className="rounded-xl border border-border bg-bg p-10 text-center text-sm text-text">
          No available slots for this doctor right now.
        </p>
      )}

      <div className="space-y-5">
        {Array.from(slotsByDate.entries()).map(([date, daySlots]) => (
          <div key={date} className="rounded-xl border border-border bg-bg p-5">
            <h2 className="mb-3 text-sm font-semibold text-text-h">{formatDateHeading(date)}</h2>
            <div className="flex flex-wrap gap-2">
              {daySlots.map((slot) => {
                const isBookingThisSlot =
                  bookAppointment.isPending &&
                  selectedSlot?.date === slot.date &&
                  selectedSlot.start_time === slot.start_time

                return (
                  <button
                    key={slot.start_time}
                    type="button"
                    className="rounded-lg border border-border px-3 py-2 text-sm font-medium text-text-h transition-colors hover:border-accent hover:text-accent disabled:opacity-60"
                    onClick={() => setPendingSlot(slot)}
                    disabled={bookAppointment.isPending}
                  >
                    {isBookingThisSlot ? 'Booking…' : slot.start_time}
                  </button>
                )
              })}
            </div>
          </div>
        ))}
      </div>

      {pendingSlot && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
          <div className="w-full max-w-sm rounded-xl border border-border bg-bg p-6 shadow-lg">
            <h3 className="text-base font-semibold text-text-h">Confirm appointment</h3>
            <p className="mt-2 text-sm text-text">
              Book {doctor?.name ?? 'this doctor'} on {formatDateHeading(pendingSlot.date)} at{' '}
              {pendingSlot.start_time}?
            </p>
            <div className="mt-5 flex justify-end gap-2">
              <button
                type="button"
                className="rounded-lg border border-border px-3 py-2 text-sm font-medium text-text-h hover:border-accent hover:text-accent"
                onClick={() => setPendingSlot(null)}
              >
                Cancel
              </button>
              <button
                type="button"
                className="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover disabled:opacity-60"
                onClick={handleConfirmBook}
                disabled={bookAppointment.isPending}
              >
                Confirm
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
