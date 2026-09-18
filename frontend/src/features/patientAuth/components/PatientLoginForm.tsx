import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { extractErrorMessage } from '../../../api/errors'
import { usePatientLogin } from '../usePatientSession'

export function PatientLoginForm() {
  const navigate = useNavigate()
  const loginMutation = usePatientLogin()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    loginMutation.mutate(
      { email, password },
      { onSuccess: () => navigate('/patient/doctors', { replace: true }) },
    )
  }

  return (
    <form className="space-y-4" onSubmit={handleSubmit} noValidate>
      {loginMutation.isError && (
        <p className="rounded-lg bg-danger-bg px-3 py-2 text-sm text-danger" role="alert">
          {extractErrorMessage(loginMutation.error)}
        </p>
      )}

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
          autoComplete="current-password"
          className="mt-1 w-full rounded-lg border border-border bg-bg px-3 py-2 text-sm text-text-h focus:border-accent focus:outline-none"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
      </label>

      <button
        type="submit"
        className="w-full rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover disabled:opacity-60"
        disabled={loginMutation.isPending}
      >
        {loginMutation.isPending ? 'Signing in…' : 'Sign in'}
      </button>
    </form>
  )
}
