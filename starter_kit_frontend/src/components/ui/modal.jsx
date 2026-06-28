import { cn } from '@/lib/utils'
import { Icon } from './icon'
import { CloseIcon } from '@/lib/icons'

export function Modal({ open, onClose, title, description, children, footer, className }) {
  if (!open) return null
  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
      <div className="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onClick={onClose} />
      <div
        className={cn(
          'relative z-10 w-full max-w-md rounded-t-2xl border border-border bg-card shadow-xl sm:rounded-2xl',
          'animate-in fade-in zoom-in-95 duration-150',
          className,
        )}
      >
        <div className="flex items-start justify-between gap-4 px-5 pt-5">
          <div>
            {title && <h3 className="text-base font-semibold">{title}</h3>}
            {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
          </div>
          <button
            onClick={onClose}
            className="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
            aria-label="Close"
          >
            <Icon icon={CloseIcon} className="size-4" />
          </button>
        </div>
        {children && <div className="px-5 py-4">{children}</div>}
        {footer && <div className="flex justify-end gap-2 px-5 pb-5 pt-1">{footer}</div>}
      </div>
    </div>
  )
}
