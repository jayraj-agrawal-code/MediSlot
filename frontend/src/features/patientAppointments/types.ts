import type { PublicDoctor } from '../patientDoctors/types'

export interface Appointment {
  id: number
  doctor: PublicDoctor
  appointment_date: string
  start_time: string
  end_time: string
  status: 'booked' | 'cancelled'
  can_cancel: boolean
}

export interface BookAppointmentPayload {
  doctor_id: number
  date: string
  start_time: string
}
