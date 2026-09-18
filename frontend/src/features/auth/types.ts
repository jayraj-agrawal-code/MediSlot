export interface Admin {
  id: number
  name: string
  email: string
}

export interface AdminLoginPayload {
  email: string
  password: string
  remember?: boolean
}
