import { useState } from 'react'
import type { FormEvent } from 'react'
import { extractErrorMessage } from '../../../api/errors'
import { Modal } from '../../../components/ui/Modal'
import { PlusIcon, TrashIcon } from '../../../components/icons'
import { DAYS_OF_WEEK } from '../types'
import type { Doctor } from '../types'
import { useSetDoctorAvailability } from '../useDoctors'

interface Period {
  start: string
  end: string
}

interface DayRowState {
  enabled: boolean
  periods: Period[]
}

interface DoctorAvailabilityModalProps {
  doctor: Doctor
  onClose: () => void
}

function buildInitialState(doctor: Doctor): Record<number, DayRowState> {
  const state: Record<number, DayRowState> = {}

  for (const day of DAYS_OF_WEEK) {
    const existing = doctor.availabilities
      .filter((availability) => availability.day_of_week === day.value)
      .sort((a, b) => a.start_time.localeCompare(b.start_time))
      .map((availability) => ({ start: availability.start_time, end: availability.end_time }))

    state[day.value] = {
      enabled: existing.length > 0,
      periods: existing,
    }
  }

  return state
}

export function DoctorAvailabilityModal({ doctor, onClose }: DoctorAvailabilityModalProps) {
  const [rows, setRows] = useState<Record<number, DayRowState>>(() => buildInitialState(doctor))
  const [validationError, setValidationError] = useState<string | null>(null)
  const setAvailability = useSetDoctorAvailability()

  function toggleDay(day: number, enabled: boolean) {
    setRows((previous) => ({
      ...previous,
      [day]: {
        enabled,
        periods: enabled && previous[day].periods.length === 0
          ? [{ start: '09:00', end: '17:00' }]
          : previous[day].periods,
      },
    }))
  }

  function addPeriod(day: number) {
    setRows((previous) => ({
      ...previous,
      [day]: { ...previous[day], periods: [...previous[day].periods, { start: '09:00', end: '17:00' }] },
    }))
  }

  function removePeriod(day: number, index: number) {
    setRows((previous) => {
      const periods = previous[day].periods.filter((_, i) => i !== index)

      return {
        ...previous,
        [day]: { enabled: periods.length > 0, periods },
      }
    })
  }

  function updatePeriod(day: number, index: number, patch: Partial<Period>) {
    setRows((previous) => ({
      ...previous,
      [day]: {
        ...previous[day],
        periods: previous[day].periods.map((period, i) => (i === index ? { ...period, ...patch } : period)),
      },
    }))
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setValidationError(null)

    const enabledDays = DAYS_OF_WEEK.filter((day) => rows[day.value].enabled)

    for (const day of enabledDays) {
      const periods = [...rows[day.value].periods].sort((a, b) => a.start.localeCompare(b.start))

      for (const period of periods) {
        if (period.start >= period.end) {
          setValidationError(`${day.label}: each period's end time must be after its start time.`)

          return
        }
      }

      for (let i = 0; i < periods.length - 1; i++) {
        if (periods[i].end > periods[i + 1].start) {
          setValidationError(`${day.label}: periods overlap. Adjust the times so they don't overlap.`)

          return
        }
      }
    }

    const availabilities = enabledDays.flatMap((day) =>
      rows[day.value].periods.map((period) => ({
        day_of_week: day.value,
        start_time: period.start,
        end_time: period.end,
      })),
    )

    setAvailability.mutate({ id: doctor.id, availabilities }, { onSuccess: onClose })
  }

  return (
    <Modal title={`Availability — ${doctor.name}`} onClose={onClose} maxWidth="max-w-xl">
      <form className="space-y-4" onSubmit={handleSubmit} noValidate>
        <p className="text-sm text-text">
          Turn a day on and add one or more availability periods (e.g. 9–1 and 2–5 for a lunch break).
        </p>

        {(validationError || setAvailability.isError) && (
          <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
            {validationError ?? extractErrorMessage(setAvailability.error)}
          </p>
        )}

        <div className="space-y-2">
          {DAYS_OF_WEEK.map((day) => {
            const row = rows[day.value]

            return (
              <div key={day.value} className="rounded-lg border border-border px-3 py-2.5">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <label className="flex items-center gap-2 text-sm font-medium text-text-h">
                    <input
                      type="checkbox"
                      checked={row.enabled}
                      onChange={(event) => toggleDay(day.value, event.target.checked)}
                    />
                    {day.label}
                  </label>

                  {row.enabled && (
                    <button
                      type="button"
                      className="flex items-center gap-1 text-xs font-medium text-accent hover:text-accent-hover"
                      onClick={() => addPeriod(day.value)}
                    >
                      <PlusIcon className="h-3.5 w-3.5" />
                      Add period
                    </button>
                  )}
                </div>

                {row.enabled && (
                  <div className="mt-2 space-y-2">
                    {row.periods.map((period, index) => (
                      <div key={index} className="flex flex-wrap items-center gap-2">
                        <input
                          type="time"
                          className="rounded-lg border border-border bg-bg px-2 py-1.5 text-sm text-text-h"
                          value={period.start}
                          onChange={(event) => updatePeriod(day.value, index, { start: event.target.value })}
                        />
                        <span className="text-sm text-text">to</span>
                        <input
                          type="time"
                          className="rounded-lg border border-border bg-bg px-2 py-1.5 text-sm text-text-h"
                          value={period.end}
                          onChange={(event) => updatePeriod(day.value, index, { end: event.target.value })}
                        />
                        <button
                          type="button"
                          className="rounded-lg p-1.5 text-text hover:bg-danger-bg hover:text-danger"
                          title="Remove period"
                          onClick={() => removePeriod(day.value, index)}
                        >
                          <TrashIcon className="h-4 w-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )
          })}
        </div>

        <div className="flex justify-end gap-3 pt-2">
          <button
            type="button"
            className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text-h hover:border-accent-hover"
            onClick={onClose}
          >
            Cancel
          </button>
          <button
            type="submit"
            className="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover disabled:opacity-60"
            disabled={setAvailability.isPending}
          >
            {setAvailability.isPending ? 'Saving…' : 'Save availability'}
          </button>
        </div>
      </form>
    </Modal>
  )
}
