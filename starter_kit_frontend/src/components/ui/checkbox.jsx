import { cn } from '@/lib/utils'
import { Icon } from './icon'
import { CheckIcon } from '@/lib/icons'

export function Checkbox({ checked, onChange, className, id, ...props }) {
  return (
    <button
      type="button"
      id={id}
      role="checkbox"
      aria-checked={checked}
      onClick={() => onChange?.(!checked)}
      className={cn(
        'flex size-4 shrink-0 items-center justify-center rounded border transition-colors',
        checked
          ? 'border-primary bg-primary text-primary-foreground'
          : 'border-border bg-background hover:border-primary/60',
        className,
      )}
      {...props}
    >
      {checked && <Icon icon={CheckIcon} className="size-3.5" strokeWidth={2.5} />}
    </button>
  )
}
