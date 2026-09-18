import type { ReactNode } from 'react'
import { CloseIcon } from '../icons'

interface ModalProps {
  title: string
  onClose: () => void
  children: ReactNode
  maxWidth?: string
}

export function Modal({ title, onClose, children, maxWidth = 'max-w-md' }: ModalProps) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div className={`w-full ${maxWidth} rounded-xl border border-border bg-bg shadow-xl`}>
        <div className="flex items-center justify-between border-b border-border px-5 py-4">
          <h2 className="text-base font-semibold text-text-h">{title}</h2>
          <button
            type="button"
            className="rounded-lg p-1.5 text-text hover:bg-bg-subtle hover:text-text-h"
            onClick={onClose}
            aria-label="Close"
          >
            <CloseIcon className="h-5 w-5" />
          </button>
        </div>
        <div className="max-h-[75vh] overflow-y-auto px-5 py-4">{children}</div>
      </div>
    </div>
  )
}
