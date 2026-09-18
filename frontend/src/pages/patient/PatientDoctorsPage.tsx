import { Link } from 'react-router-dom'
import { SpinnerIcon, StethoscopeIcon } from '../../components/icons'
import { usePatientDoctors } from '../../features/patientDoctors/usePatientDoctors'

export function PatientDoctorsPage() {
  const { data: doctors, isLoading, isError } = usePatientDoctors()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold text-text-h">Doctors</h1>
        <p className="text-sm text-text">Choose a doctor to see their available appointment slots.</p>
      </div>

      {isLoading && (
        <div className="flex items-center justify-center gap-2 p-10 text-sm text-text">
          <SpinnerIcon className="h-5 w-5" />
          Loading doctors…
        </div>
      )}

      {isError && <p className="p-10 text-center text-sm text-danger">Failed to load doctors.</p>}

      {!isLoading && !isError && doctors && doctors.length === 0 && (
        <p className="rounded-xl border border-border bg-bg p-10 text-center text-sm text-text">
          No doctors are available right now. Please check back later.
        </p>
      )}

      {!isLoading && !isError && doctors && doctors.length > 0 && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {doctors.map((doctor) => (
            <Link
              key={doctor.id}
              to={`/patient/doctors/${doctor.id}`}
              className="flex flex-col gap-3 rounded-xl border border-border bg-bg p-5 transition-colors hover:border-accent-border"
            >
              <span className="flex h-10 w-10 items-center justify-center rounded-full bg-accent-bg text-accent">
                <StethoscopeIcon className="h-5 w-5" />
              </span>
              <div>
                <p className="font-medium text-text-h">{doctor.name}</p>
                <p className="text-sm text-text">{doctor.specialization ?? 'General'}</p>
              </div>
              <span className="mt-auto text-sm font-medium text-accent">View available slots →</span>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
