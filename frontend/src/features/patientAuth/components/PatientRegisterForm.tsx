import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { extractErrorMessage } from '../../../api/errors'
import { usePatientRegister } from '../usePatientSession'

export function PatientRegisterForm() {
  const navigate = useNavigate()
  const registerMutation = usePatientRegister()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    registerMutation.mutate(
      { name, email, password, password_confirmation: passwordConfirmation },
      {
        onSuccess: () => {
          navigate('/patient/login', {
            replace: true,
            state: { registered: true },
          })
        },
      },
    )
  }

  return (
    <form className="space-y-4" onSubmit={handleSubmit} noValidate>
      {registerMutation.isError && (
        <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
          {extractErrorMessage(registerMutation.error)}
        </p>
      )}

      <label className="block text-sm font-medium text-text-h">
        Full name
        <input
          className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
          value={name}
          onChange={(event) => setName(event.target.value)}
          required
        />
      </label>

      <label className="block text-sm font-medium text-text-h">
        Email
        <input
          type="email"
          autoComplete="email"
          className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
      </label>

      <label className="block text-sm font-medium text-text-h">
        Password
        <input
          type="password"
          autoComplete="new-password"
          className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          minLength={8}
          required
        />
      </label>

      <label className="block text-sm font-medium text-text-h">
        Confirm password
        <input
          type="password"
          autoComplete="new-password"
          className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          minLength={8}
          required
        />
      </label>

      <button
        type="submit"
        className="w-full rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover disabled:opacity-60"
        disabled={registerMutation.isPending}
      >
        {registerMutation.isPending ? 'Creating account…' : 'Create account'}
      </button>
    </form>
  )
}
