import { Modal } from './Modal'

interface ConfirmDialogProps {
  title: string
  message: string
  confirmLabel?: string
  isPending?: boolean
  onConfirm: () => void
  onCancel: () => void
}

export function ConfirmDialog({
  title,
  message,
  confirmLabel = 'Confirm',
  isPending = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  return (
    <Modal title={title} onClose={onCancel} maxWidth="max-w-sm">
      <p className="text-sm text-text">{message}</p>

      <div className="mt-5 flex justify-end gap-3">
        <button
          type="button"
          className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text-h hover:border-accent-hover"
          onClick={onCancel}
        >
          Cancel
        </button>
        <button
          type="button"
          className="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60"
          onClick={onConfirm}
          disabled={isPending}
        >
          {isPending ? 'Please wait…' : confirmLabel}
        </button>
      </div>
    </Modal>
  )
}
