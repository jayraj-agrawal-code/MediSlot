export interface DoctorAvailability {
  id: number
  day_of_week: number
  day_label: string
  start_time: string
  end_time: string
}

export interface DoctorBreak {
  id: number
  break_date: string
  start_time: string
  end_time: string
}

export interface Doctor {
  id: number
  name: string
  email: string | null
  phone: string | null
  specialization: string | null
  is_active: boolean
  availabilities: DoctorAvailability[]
  breaks: DoctorBreak[]
  created_at: string
}

export interface CreateBreakPayload {
  break_date: string
  start_time: string
  end_time: string
}

export interface AffectedAppointment {
  id: number
  appointment_date: string
  start_time: string
  end_time: string
  status: 'booked' | 'cancelled'
}

export interface CreateBreakResult {
  break: DoctorBreak
  rescheduled: AffectedAppointment[]
  cancelled: AffectedAppointment[]
}

export interface DoctorPayload {
  name: string
  email?: string | null
  phone?: string | null
  specialization?: string | null
  is_active?: boolean
}

export interface AvailabilityEntry {
  day_of_week: number
  start_time: string
  end_time: string
}

export interface DayOption {
  value: number
  label: string
  short: string
}

export const DAYS_OF_WEEK: DayOption[] = [
  { value: 1, label: 'Monday', short: 'Mon' },
  { value: 2, label: 'Tuesday', short: 'Tue' },
  { value: 3, label: 'Wednesday', short: 'Wed' },
  { value: 4, label: 'Thursday', short: 'Thu' },
  { value: 5, label: 'Friday', short: 'Fri' },
  { value: 6, label: 'Saturday', short: 'Sat' },
  { value: 7, label: 'Sunday', short: 'Sun' },
]
