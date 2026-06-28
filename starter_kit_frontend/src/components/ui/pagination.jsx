import { Button } from './button'
import { Icon } from './icon'
import { ArrowLeftIcon, ArrowRightIcon } from '@/lib/icons'

export function Pagination({ page = 1, lastPage = 1, total = 0, perPage = 10 }) {
  const from = total === 0 ? 0 : (page - 1) * perPage + 1
  const to = Math.min(page * perPage, total)
  return (
    <div className="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 sm:flex-row">
      <p className="text-xs text-muted-foreground">
        Showing <span className="font-medium text-foreground">{from}</span>–
        <span className="font-medium text-foreground">{to}</span> of{' '}
        <span className="font-medium text-foreground">{total}</span>
      </p>
      <div className="flex items-center gap-1">
        <Button variant="outline" size="sm" disabled={page <= 1}>
          <Icon icon={ArrowLeftIcon} className="size-4" /> Prev
        </Button>
        <div className="flex items-center gap-1 px-1">
          {Array.from({ length: lastPage }).map((_, i) => (
            <button
              key={i}
              className={
                'size-8 rounded-lg text-xs font-medium transition-colors ' +
                (i + 1 === page
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-muted')
              }
            >
              {i + 1}
            </button>
          ))}
        </div>
        <Button variant="outline" size="sm" disabled={page >= lastPage}>
          Next <Icon icon={ArrowRightIcon} className="size-4" />
        </Button>
      </div>
    </div>
  )
}
