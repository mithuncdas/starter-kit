import { cn } from '@/lib/utils'
import { Icon } from '@/components/ui/icon'
import { Link, useRoute } from '@/router'
import {
  DashboardIcon, AdminsIcon, RolesIcon,
  AuditIcon, CloseIcon,
} from '@/lib/icons'

const NAV = [
  { section: 'Overview', items: [{ to: '/', label: 'Dashboard', icon: DashboardIcon }] },
  {
    section: 'Access Control',
    items: [
      { to: '/admin-users', label: 'Admin Users', icon: AdminsIcon, match: '/admin-users' },
      { to: '/roles', label: 'Roles', icon: RolesIcon, match: '/roles' },
    ],
  },
  {
    section: 'Reference',
    items: [
      { to: '/audit-logs', label: 'Audit Logs', icon: AuditIcon },
    ],
  },
]

function isActive(item, path) {
  if (item.to === '/') return path === '/'
  return path === item.to || (item.match && path.startsWith(item.match))
}

export function Sidebar({ open, onClose }) {
  const { path } = useRoute()

  return (
    <>
      <div
        className={cn(
          'fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden',
          open ? 'block' : 'hidden',
        )}
        onClick={onClose}
      />
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:static lg:translate-x-0',
          open ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        {/* Brand */}
        <div className="flex h-16 items-center gap-2.5 border-b border-border px-5">
          <span className="flex size-9 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-violet-600 text-white shadow-sm">
            <Icon icon={DashboardIcon} className="size-5" />
          </span>
          <div className="leading-tight">
            <p className="text-sm font-bold tracking-tight">Starter Kit</p>
            <p className="text-[11px] text-muted-foreground">Admin Panel</p>
          </div>
          <button
            onClick={onClose}
            className="ml-auto rounded-md p-1 text-muted-foreground hover:bg-muted lg:hidden"
            aria-label="Close menu"
          >
            <Icon icon={CloseIcon} className="size-4" />
          </button>
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto px-3 py-4">
          {NAV.map((group) => (
            <div key={group.section} className="mb-5">
              <p className="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground/70">
                {group.section}
              </p>
              <ul className="space-y-0.5">
                {group.items.map((item) => {
                  const active = isActive(item, path)
                  return (
                    <li key={item.to}>
                      <Link
                        to={item.to}
                        onClick={onClose}
                        className={cn(
                          'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                          active
                            ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                        )}
                      >
                        <Icon icon={item.icon} className="size-[18px]" />
                        {item.label}
                      </Link>
                    </li>
                  )
                })}
              </ul>
            </div>
          ))}
        </nav>
      </aside>
    </>
  )
}
