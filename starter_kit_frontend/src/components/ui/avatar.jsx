import { cn } from '@/lib/utils'

const palette = [
  'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300',
  'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300',
  'bg-amber-500/15 text-amber-600 dark:text-amber-300',
  'bg-rose-500/15 text-rose-600 dark:text-rose-300',
  'bg-sky-500/15 text-sky-600 dark:text-sky-300',
  'bg-violet-500/15 text-violet-600 dark:text-violet-300',
]

function initials(name = '') {
  return name
    .split(' ')
    .map((w) => w[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

export function Avatar({ name, className, size = 'default' }) {
  const tone = palette[(name?.length || 0) % palette.length]
  const sizes = { sm: 'size-7 text-[11px]', default: 'size-9 text-xs', lg: 'size-11 text-sm' }
  return (
    <span
      className={cn('inline-flex items-center justify-center rounded-full font-semibold', tone, sizes[size], className)}
    >
      {initials(name)}
    </span>
  )
}
