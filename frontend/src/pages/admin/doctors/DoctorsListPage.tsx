import { useState } from 'react'
import { ConfirmDialog } from '../../../components/ui/ConfirmDialog'
import {
  ClockIcon,
  PauseIcon,
  PencilIcon,
  PlusIcon,
  SpinnerIcon,
  TrashIcon,
} from '../../../components/icons'
import { DoctorAvailabilityModal } from '../../../features/doctors/components/DoctorAvailabilityModal'
import { DoctorBreaksModal } from '../../../features/doctors/components/DoctorBreaksModal'
import { DoctorFormModal } from '../../../features/doctors/components/DoctorFormModal'
import { DAYS_OF_WEEK } from '../../../features/doctors/types'
import type { Doctor } from '../../../features/doctors/types'
import { useDeleteDoctor, useDoctors } from '../../../features/doctors/useDoctors'

type ModalState =
  | { type: 'create' }
  | { type: 'edit'; doctor: Doctor }
  | { type: 'availability'; doctor: Doctor }
  | { type: 'breaks'; doctor: Doctor }
  | { type: 'delete'; doctor: Doctor }
  | null

export function DoctorsListPage() {
  const { data: doctors, isLoading, isError } = useDoctors()
  const deleteDoctor = useDeleteDoctor()
  const [modal, setModal] = useState<ModalState>(null)

  function closeModal() {
    setModal(null)
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-text-h">Doctors</h2>
          <p className="text-sm text-text">Manage doctors and their weekly availability.</p>
        </div>

        <button
          type="button"
          className="flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover"
          onClick={() => setModal({ type: 'create' })}
        >
          <PlusIcon className="h-4 w-4" />
          Add doctor
        </button>
      </div>

      <div className="overflow-hidden rounded-xl border border-border bg-bg">
        {isLoading && (
          <div className="flex items-center justify-center gap-2 p-10 text-sm text-text">
            <SpinnerIcon className="h-5 w-5" />
            Loading doctors…
          </div>
        )}

        {isError && <p className="p-10 text-center text-sm text-danger">Failed to load doctors.</p>}

        {!isLoading && !isError && doctors && doctors.length === 0 && (
          <p className="p-10 text-center text-sm text-text">
            No doctors yet. Add your first doctor to get started.
          </p>
        )}

        {!isLoading && !isError && doctors && doctors.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[760px] text-left text-sm">
              <thead className="border-b border-border bg-bg-subtle text-xs uppercase tracking-wide text-text">
                <tr>
                  <th className="px-4 py-3 font-medium">Doctor</th>
                  <th className="px-4 py-3 font-medium">Contact</th>
                  <th className="px-4 py-3 font-medium">Weekly availability</th>
                  <th className="px-4 py-3 font-medium">Status</th>
                  <th className="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {doctors.map((doctor) => (
                  <tr key={doctor.id}>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-bg text-sm font-semibold text-accent">
                          {doctor.name.charAt(0).toUpperCase()}
                        </span>
                        <div>
                          <p className="font-medium text-text-h">{doctor.name}</p>
                          <p className="text-xs text-text">{doctor.specialization ?? 'General'}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-text">
                      <p>{doctor.email ?? '—'}</p>
                      <p className="text-xs">{doctor.phone ?? ''}</p>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap gap-1">
                        {DAYS_OF_WEEK.map((day) => {
                          const periods = doctor.availabilities.filter(
                            (entry) => entry.day_of_week === day.value,
                          )

                          return (
                            <span
                              key={day.value}
                              title={
                                periods.length > 0
                                  ? `${day.label}: ${periods
                                      .map((period) => `${period.start_time}–${period.end_time}`)
                                      .join(', ')}`
                                  : `${day.label}: unavailable`
                              }
                              className={`rounded px-1.5 py-0.5 text-[11px] font-medium ${
                                periods.length > 0 ? 'bg-accent-bg text-accent' : 'bg-bg-subtle text-text'
                              }`}
                            >
                              {day.short}
                            </span>
                          )
                        })}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                          doctor.is_active ? 'bg-accent-bg text-accent' : 'bg-danger-bg text-danger'
                        }`}
                      >
                        {doctor.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-1.5">
                        <button
                          type="button"
                          className="rounded-lg p-2 text-text hover:bg-bg-subtle hover:text-accent"
                          title="Manage availability"
                          onClick={() => setModal({ type: 'availability', doctor })}
                        >
                          <ClockIcon className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-lg p-2 text-text hover:bg-bg-subtle hover:text-accent"
                          title="Manage breaks"
                          onClick={() => setModal({ type: 'breaks', doctor })}
                        >
                          <PauseIcon className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-lg p-2 text-text hover:bg-bg-subtle hover:text-accent"
                          title="Edit doctor"
                          onClick={() => setModal({ type: 'edit', doctor })}
                        >
                          <PencilIcon className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          className="rounded-lg p-2 text-text hover:bg-danger-bg hover:text-danger"
                          title="Delete doctor"
                          onClick={() => setModal({ type: 'delete', doctor })}
                        >
                          <TrashIcon className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {(modal?.type === 'create' || modal?.type === 'edit') && (
        <DoctorFormModal doctor={modal.type === 'edit' ? modal.doctor : null} onClose={closeModal} />
      )}

      {modal?.type === 'availability' && (
        <DoctorAvailabilityModal doctor={modal.doctor} onClose={closeModal} />
      )}

      {modal?.type === 'breaks' && <DoctorBreaksModal doctor={modal.doctor} onClose={closeModal} />}

      {modal?.type === 'delete' && (
        <ConfirmDialog
          title="Delete doctor"
          message={`Remove ${modal.doctor.name}? This also deletes their availability schedule.`}
          confirmLabel="Delete"
          isPending={deleteDoctor.isPending}
          onConfirm={() => deleteDoctor.mutate(modal.doctor.id, { onSuccess: closeModal })}
          onCancel={closeModal}
        />
      )}
    </div>
  )
}
