import { Badge } from '@/components/ui/badge'

/** status: 1 = Active, 0 = Inactive */
export function StatusBadge({ status, labels }) {
  const active = status === 1 || status === true
  const text = labels ? labels[active ? 'active' : 'inactive'] : active ? 'Active' : 'Inactive'
  return (
    <Badge tone={active ? 'success' : 'neutral'} dot>
      {text}
    </Badge>
  )
}
