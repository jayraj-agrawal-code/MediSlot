import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { extractErrorMessage } from '../../../api/errors'
import { useAdminLogin } from '../useAdminSession'

export function AdminLoginForm() {
  const navigate = useNavigate()
  const loginMutation = useAdminLogin()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    loginMutation.mutate(
      { email, password, remember },
      {
        onSuccess: () => navigate('/admin/dashboard', { replace: true }),
      },
    )
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit} noValidate>
      {loginMutation.isError && (
        <p className="form-error" role="alert">
          {extractErrorMessage(loginMutation.error)}
        </p>
      )}

      <label className="form-field">
        <span>Email</span>
        <input
          type="email"
          name="email"
          autoComplete="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
      </label>

      <label className="form-field">
        <span>Password</span>
        <input
          type="password"
          name="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
      </label>

      <label className="form-checkbox">
        <input
          type="checkbox"
          checked={remember}
          onChange={(event) => setRemember(event.target.checked)}
        />
        <span>Remember me</span>
      </label>

      <button type="submit" className="btn-primary" disabled={loginMutation.isPending}>
        {loginMutation.isPending ? 'Signing in…' : 'Sign in'}
      </button>
    </form>
  )
}
