import { HugeiconsIcon } from '@hugeicons/react'

/**
 * Thin wrapper around HugeiconsIcon with sensible defaults so call-sites
 * stay short:  <Icon icon={SearchIcon} className="size-4" />
 */
export function Icon({ icon, size = 18, strokeWidth = 1.8, className, ...props }) {
  return (
    <HugeiconsIcon
      icon={icon}
      size={size}
      strokeWidth={strokeWidth}
      className={className}
      {...props}
    />
  )
}
