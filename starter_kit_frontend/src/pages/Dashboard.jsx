import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar } from '@/components/ui/avatar'
import { Icon } from '@/components/ui/icon'
import { Link } from '@/router'
import { StatusBadge } from '@/components/common/StatusBadge'
import {
  dashboardStats, adminUsers, auditLogs, currentAdmin,
} from '@/lib/mock'
import {
  AdminsIcon, RolesIcon, PermissionsIcon, GlobeIcon, TrendUpIcon,
  ArrowRightIcon, UserCircleIcon, AddIcon, ProfileIcon,
} from '@/lib/icons'

const STAT_ICON = {
  admins: AdminsIcon,
  roles: RolesIcon,
  permissions: PermissionsIcon,
  countries: GlobeIcon,
}

const QUICK_ACTIONS = [
  { label: 'Add admin', to: '/admin-users/new', icon: AddIcon, tone: 'bg-primary text-primary-foreground' },
  { label: 'Manage admins', to: '/admin-users', icon: AdminsIcon, tone: 'bg-primary/10 text-primary' },
  { label: 'Manage roles', to: '/roles', icon: RolesIcon, tone: 'bg-violet-500/12 text-violet-600 dark:text-violet-400' },
  { label: 'Edit profile', to: '/profile', icon: ProfileIcon, tone: 'bg-muted text-muted-foreground' },
]

const ACTION_TONE = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  login: 'neutral',
  password_reset: 'warning',
}
const actionVerb = (action) => {
  const v = action.split('.')[1] || action
  return v.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
}
const actionTone = (action) => ACTION_TONE[action.split('.')[1]] || 'primary'
const humanize = (action) => action.replace(/[._]/g, ' ').replace(/^\w/, (c) => c.toUpperCase())

export default function Dashboard() {
  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })

  return (
    <div className="space-y-6">
      {/* Welcome banner */}
      <div className="relative overflow-hidden rounded-2xl bg-linear-to-br from-slate-900 via-slate-900 to-indigo-950 p-6 text-white shadow-sm sm:p-7">
        <div className="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] bg-size-[22px_22px]" />
        <div className="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-indigo-500/20 blur-3xl" />

        <div className="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          {/* Left — greeting */}
          <div className="flex items-center gap-4">
            <span className="flex size-14 items-center justify-center rounded-full bg-white/10 ring-2 ring-white/20 ring-offset-2 ring-offset-slate-900 backdrop-blur">
              <Icon icon={UserCircleIcon} className="size-8 text-indigo-200" />
            </span>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-indigo-300/80">Welcome back</p>
              <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{currentAdmin.name}</h1>
              <p className="mt-0.5 text-sm text-slate-300">Here's what's happening in your admin workspace.</p>
            </div>
          </div>

          {/* Right — role + date */}
          <div className="flex flex-row items-center gap-3 sm:flex-col sm:items-end">
            <span className="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-sm font-medium backdrop-blur">
              <Icon icon={RolesIcon} className="size-4 text-indigo-200" />
              {currentAdmin.roles[0]?.name}
            </span>
            <p className="text-sm text-slate-400">{today}</p>
          </div>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {dashboardStats.map((s) => (
          <Card key={s.key}>
            <CardContent className="flex items-start justify-between">
              <div>
                <p className="text-sm text-muted-foreground">{s.label}</p>
                <p className="mt-1 text-3xl font-bold tracking-tight">{s.value}</p>
                <p className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                  {s.trend === 'up' && <Icon icon={TrendUpIcon} className="size-3.5 text-emerald-500" />}
                  {s.delta}
                </p>
              </div>
              <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <Icon icon={STAT_ICON[s.key]} className="size-5" />
              </span>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        {/* Recent admins */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex-row items-center justify-between">
            <div>
              <CardTitle>Recent admins</CardTitle>
              <CardDescription>The 5 most recently added accounts.</CardDescription>
            </div>
            <Link to="/admin-users" className="flex shrink-0 items-center gap-1 text-xs font-medium text-primary hover:underline">
              View all <Icon icon={ArrowRightIcon} className="size-3.5" />
            </Link>
          </CardHeader>
          <CardContent className="p-0">
            <ul className="divide-y divide-border">
              {adminUsers.slice(0, 5).map((u) => (
                <li key={u.id} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-muted/40">
                  <Avatar name={u.name} size="sm" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{u.name}</p>
                    <p className="truncate text-xs text-muted-foreground">{u.email}</p>
                  </div>
                  <StatusBadge status={u.status} />
                  <span className="hidden whitespace-nowrap text-xs text-muted-foreground sm:block">{u.created_at}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>

        {/* Quick actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick actions</CardTitle>
            <CardDescription>Jump straight into common tasks.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {QUICK_ACTIONS.map((a) => (
              <Link
                key={a.label}
                to={a.to}
                className="group flex items-center gap-3 rounded-lg border border-border px-3 py-2.5 transition-colors hover:border-primary/40 hover:bg-muted/50"
              >
                <span className={'flex size-8 items-center justify-center rounded-lg ' + a.tone}>
                  <Icon icon={a.icon} className="size-4" />
                </span>
                <span className="flex-1 text-sm font-medium">{a.label}</span>
                <Icon icon={ArrowRightIcon} className="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
              </Link>
            ))}
          </CardContent>
        </Card>
      </div>

      {/* Recent activity (full width) */}
      <Card>
        <CardHeader className="flex-row items-center justify-between">
          <div>
            <CardTitle>Recent activity</CardTitle>
            <CardDescription>The latest 5 actions recorded across the application.</CardDescription>
          </div>
          <Link to="/audit-logs" className="flex shrink-0 items-center gap-1 text-xs font-medium text-primary hover:underline">
            View all <Icon icon={ArrowRightIcon} className="size-3.5" />
          </Link>
        </CardHeader>
        <CardContent className="p-0">
          <ul className="divide-y divide-border">
            {auditLogs.slice(0, 5).map((log) => (
              <li key={log.id} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-muted/40">
                <Avatar name={log.actor.name} size="sm" />
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium">{log.actor.name}</span>
                    <Badge tone="info">{log.subject.type}</Badge>
                    <Badge tone={actionTone(log.action)}>{actionVerb(log.action)}</Badge>
                  </div>
                  <p className="mt-0.5 truncate text-sm text-muted-foreground">
                    {humanize(log.action)} — {log.subject.name}
                  </p>
                </div>
                <span className="hidden whitespace-nowrap text-xs text-muted-foreground sm:block">{log.created_at}</span>
                <Link to="/audit-logs" className="text-xs font-medium text-primary hover:underline">
                  View
                </Link>
              </li>
            ))}
          </ul>
        </CardContent>
      </Card>
    </div>
  )
}
