import { cn } from '@/lib/utils'
import { Icon } from './icon'
import { ChevronDownIcon } from '@/lib/icons'

/** Native select styled to match the design system. */
export function Select({ className, children, ...props }) {
  return (
    <div className="relative">
      <select
        className={cn(
          'flex h-9 w-full appearance-none rounded-lg border border-border bg-background pl-3 pr-9 text-sm shadow-sm transition-colors',
          'focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none',
          'disabled:cursor-not-allowed disabled:opacity-50',
          className,
        )}
        {...props}
      >
        {children}
      </select>
      <Icon
        icon={ChevronDownIcon}
        className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
      />
    </div>
  )
}
