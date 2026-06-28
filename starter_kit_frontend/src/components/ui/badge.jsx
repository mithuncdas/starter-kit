import { cn } from '@/lib/utils'

const tones = {
  neutral: 'bg-muted text-muted-foreground',
  success: 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400',
  warning: 'bg-amber-500/12 text-amber-600 dark:text-amber-400',
  danger: 'bg-destructive/12 text-destructive',
  info: 'bg-sky-500/12 text-sky-600 dark:text-sky-400',
  primary: 'bg-primary/12 text-primary',
  violet: 'bg-violet-500/12 text-violet-600 dark:text-violet-400',
}

export function Badge({ tone = 'neutral', className, children, dot, ...props }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
        tones[tone] || tones.neutral,
        className,
      )}
      {...props}
    >
      {dot && <span className="size-1.5 rounded-full bg-current" />}
      {children}
    </span>
  )
}
