import { cn } from '@/lib/utils'

export function Table({ className, ...props }) {
  return (
    <div className="w-full overflow-x-auto">
      <table className={cn('w-full caption-bottom text-sm', className)} {...props} />
    </div>
  )
}

export function THead({ className, ...props }) {
  return <thead className={cn('border-b border-border bg-muted/40', className)} {...props} />
}

export function TBody({ className, ...props }) {
  return <tbody className={cn('divide-y divide-border', className)} {...props} />
}

export function TR({ className, ...props }) {
  return <tr className={cn('transition-colors hover:bg-muted/40', className)} {...props} />
}

export function TH({ className, ...props }) {
  return (
    <th
      className={cn('px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground', className)}
      {...props}
    />
  )
}

export function TD({ className, ...props }) {
  return <td className={cn('px-4 py-3 align-middle', className)} {...props} />
}
