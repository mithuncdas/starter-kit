import { cn } from '@/lib/utils'

export function Label({ className, children, required, ...props }) {
  return (
    <label className={cn('text-sm font-medium text-foreground', className)} {...props}>
      {children}
      {required && <span className="ml-0.5 text-destructive">*</span>}
    </label>
  )
}
