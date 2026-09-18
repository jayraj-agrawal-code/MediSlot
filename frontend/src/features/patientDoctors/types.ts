export interface PublicDoctor {
  id: number
  name: string
  specialization: string | null
}

export interface Slot {
  date: string
  start_time: string
  end_time: string
}
