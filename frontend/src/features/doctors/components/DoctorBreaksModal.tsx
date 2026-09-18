import { useState } from 'react'
import type { FormEvent } from 'react'
import { extractErrorMessage } from '../../../api/errors'
import { Modal } from '../../../components/ui/Modal'
import { TrashIcon } from '../../../components/icons'
import type { CreateBreakResult, Doctor } from '../types'
import { useCreateDoctorBreak, useDeleteDoctorBreak, useDoctors } from '../useDoctors'

interface DoctorBreaksModalProps {
  doctor: Doctor
  onClose: () => void
}

export function DoctorBreaksModal({ doctor, onClose }: DoctorBreaksModalProps) {
  const { data: doctors } = useDoctors()
  const liveBreaks = doctors?.find((entry) => entry.id === doctor.id)?.breaks ?? doctor.breaks

  const createBreak = useCreateDoctorBreak()
  const deleteBreak = useDeleteDoctorBreak()
  const [date, setDate] = useState('')
  const [start, setStart] = useState('')
  const [end, setEnd] = useState('')
  const [lastResult, setLastResult] = useState<CreateBreakResult | null>(null)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLastResult(null)

    createBreak.mutate(
      { doctorId: doctor.id, payload: { break_date: date, start_time: start, end_time: end } },
      {
        onSuccess: (result) => {
          setLastResult(result)
          setDate('')
          setStart('')
          setEnd('')
        },
      },
    )
  }

  return (
    <Modal title={`Breaks — ${doctor.name}`} onClose={onClose} maxWidth="max-w-lg">
      <div className="space-y-5">
        <div>
          <h3 className="mb-2 text-sm font-semibold text-text-h">Upcoming breaks</h3>

          {liveBreaks.length === 0 && <p className="text-sm text-text">No breaks scheduled.</p>}

          {liveBreaks.length > 0 && (
            <ul className="space-y-1.5">
              {liveBreaks.map((brk) => (
                <li
                  key={brk.id}
                  className="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"
                >
                  <span className="text-text-h">
                    {brk.break_date} · {brk.start_time}–{brk.end_time}
                  </span>
                  <button
                    type="button"
                    className="rounded-lg p-1.5 text-text hover:bg-danger-bg hover:text-danger"
                    title="Remove break"
                    onClick={() => deleteBreak.mutate({ doctorId: doctor.id, breakId: brk.id })}
                    disabled={deleteBreak.isPending}
                  >
                    <TrashIcon className="h-4 w-4" />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <form className="space-y-3 border-t border-border pt-4" onSubmit={handleSubmit} noValidate>
          <h3 className="text-sm font-semibold text-text-h">Add a break</h3>

          {createBreak.isError && (
            <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
              {extractErrorMessage(createBreak.error)}
            </p>
          )}

          {lastResult && (lastResult.rescheduled.length > 0 || lastResult.cancelled.length > 0) && (
            <p className="rounded-lg bg-accent-bg px-3 py-2 text-sm text-accent">
              {lastResult.rescheduled.length > 0 &&
                `${lastResult.rescheduled.length} appointment(s) moved to a new time. `}
              {lastResult.cancelled.length > 0 &&
                `${lastResult.cancelled.length} appointment(s) had no free slot left and were cancelled.`}
            </p>
          )}

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <label className="text-sm font-medium text-text-h">
              Date
              <input
                type="date"
                className="mt-1 w-full rounded-lg border border-border bg-bg px-2 py-1.5 text-sm text-text-h"
                value={date}
                onChange={(event) => setDate(event.target.value)}
                required
              />
            </label>
            <label className="text-sm font-medium text-text-h">
              Start
              <input
                type="time"
                className="mt-1 w-full rounded-lg border border-border bg-bg px-2 py-1.5 text-sm text-text-h"
                value={start}
                onChange={(event) => setStart(event.target.value)}
                required
              />
            </label>
            <label className="text-sm font-medium text-text-h">
              End
              <input
                type="time"
                className="mt-1 w-full rounded-lg border border-border bg-bg px-2 py-1.5 text-sm text-text-h"
                value={end}
                onChange={(event) => setEnd(event.target.value)}
                required
              />
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-1">
            <button
              type="button"
              className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text-h hover:border-accent-hover"
              onClick={onClose}
            >
              Close
            </button>
            <button
              type="submit"
              className="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover disabled:opacity-60"
              disabled={createBreak.isPending}
            >
              {createBreak.isPending ? 'Adding…' : 'Add break'}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  )
}
