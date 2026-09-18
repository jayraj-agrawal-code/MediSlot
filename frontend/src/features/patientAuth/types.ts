export interface Patient {
  id: number
  name: string
  email: string
}

export interface PatientRegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface PatientLoginPayload {
  email: string
  password: string
  remember?: boolean
}
