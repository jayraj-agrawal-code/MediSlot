import { useState } from 'react'
import type { FormEvent } from 'react'
import { extractErrorMessage } from '../../../api/errors'
import { Modal } from '../../../components/ui/Modal'
import type { Doctor } from '../types'
import { useCreateDoctor, useUpdateDoctor } from '../useDoctors'

interface DoctorFormModalProps {
  doctor?: Doctor | null
  onClose: () => void
}

export function DoctorFormModal({ doctor, onClose }: DoctorFormModalProps) {
  const isEditing = Boolean(doctor)
  const createDoctor = useCreateDoctor()
  const updateDoctor = useUpdateDoctor()

  const [name, setName] = useState(doctor?.name ?? '')
  const [email, setEmail] = useState(doctor?.email ?? '')
  const [phone, setPhone] = useState(doctor?.phone ?? '')
  const [specialization, setSpecialization] = useState(doctor?.specialization ?? '')
  const [isActive, setIsActive] = useState(doctor?.is_active ?? true)

  const mutation = isEditing ? updateDoctor : createDoctor

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const payload = {
      name,
      email: email.trim() === '' ? null : email,
      phone: phone.trim() === '' ? null : phone,
      specialization: specialization.trim() === '' ? null : specialization,
      is_active: isActive,
    }

    if (doctor) {
      updateDoctor.mutate({ id: doctor.id, payload }, { onSuccess: onClose })
    } else {
      createDoctor.mutate(payload, { onSuccess: onClose })
    }
  }

  return (
    <Modal title={isEditing ? 'Edit doctor' : 'Add doctor'} onClose={onClose}>
      <form className="space-y-4" onSubmit={handleSubmit} noValidate>
        {mutation.isError && (
          <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
            {extractErrorMessage(mutation.error)}
          </p>
        )}

        <label className="block text-sm font-medium text-text-h">
          Name
          <input
            className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />
        </label>

        <label className="block text-sm font-medium text-text-h">
          Specialization
          <input
            className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
            value={specialization}
            onChange={(event) => setSpecialization(event.target.value)}
            placeholder="e.g. Cardiologist"
          />
        </label>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label className="block text-sm font-medium text-text-h">
            Email
            <input
              type="email"
              className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
            />
          </label>

          <label className="block text-sm font-medium text-text-h">
            Phone
            <input
              className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
            />
          </label>
        </div>

        <label className="flex items-center gap-2 text-sm font-medium text-text-h">
          <input
            type="checkbox"
            checked={isActive}
            onChange={(event) => setIsActive(event.target.checked)}
          />
          Active
        </label>

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
            disabled={mutation.isPending}
          >
            {mutation.isPending ? 'Saving…' : isEditing ? 'Save changes' : 'Add doctor'}
          </button>
        </div>
      </form>
    </Modal>
  )
}
