import { isAxiosError } from 'axios'

export interface ApiErrorPayload {
  message?: string
  errors?: Record<string, string[]>
}

export function extractErrorMessage(error: unknown): string {
  if (isAxiosError<ApiErrorPayload>(error)) {
    const firstFieldError = Object.values(error.response?.data?.errors ?? {})[0]?.[0]

    return (
      firstFieldError ?? error.response?.data?.message ?? 'Something went wrong. Please try again.'
    )
  }

  return 'Something went wrong. Please try again.'
}
