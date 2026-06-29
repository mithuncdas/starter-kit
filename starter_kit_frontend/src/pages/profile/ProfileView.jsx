import { useMemo, useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { StatusBadge } from '@/components/common/StatusBadge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar } from '@/components/ui/avatar'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { currentAdmin } from '@/lib/mock'
import {
  EditIcon, LockCheckIcon, MailIcon, CopyIcon, CheckIcon,
  RolesIcon, PermissionsIcon, ProfileIcon, ChevronDownIcon, InfoIcon,
} from '@/lib/icons'

/** Group flat "group.action" permission strings into { group, items[] }. */
function groupPermissions(permissions) {
  const map = new Map()
  for (const p of permissions) {
    const [group] = p.split('.')
    if (!map.has(group)) map.set(group, [])
    map.get(group).push(p)
  }
  return [...map.entries()]
    .map(([group, items]) => ({ group, items: items.sort() }))
    .sort((a, b) => a.group.localeCompare(b.group))
}

function StatCard({ icon, label, value, tone = 'primary' }) {
  const tones = {
    primary: 'bg-primary/10 text-primary',
    violet: 'bg-violet-500/10 text-violet-600 dark:text-violet-300',
    emerald: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
  }
  return (
    <Card>
      <CardContent className="flex items-center gap-3">
        <span className={'flex size-10 shrink-0 items-center justify-center rounded-lg ' + tones[tone]}>
          <Icon icon={icon} className="size-5" />
        </span>
        <div className="min-w-0">
          <p className="text-2xl font-bold leading-none">{value}</p>
          <p className="mt-1 truncate text-xs text-muted-foreground">{label}</p>
        </div>
      </CardContent>
    </Card>
  )
}

function InfoRow({ icon, label, children }) {
  return (
    <div className="flex items-start gap-3 px-5 py-3">
      <Icon icon={icon} className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
      <div className="min-w-0 flex-1">
        <p className="text-xs text-muted-foreground">{label}</p>
        <div className="mt-0.5 text-sm font-medium">{children}</div>
      </div>
    </div>
  )
}

export default function ProfileView() {
  const admin = currentAdmin
  const groups = useMemo(() => groupPermissions(admin.permissions), [admin.permissions])

  const [copied, setCopied] = useState(false)
  // All permission groups start expanded; clicking a header toggles it.
  const [collapsed, setCollapsed] = useState({})

  const copyEmail = async () => {
    try {
      await navigator.clipboard.writeText(admin.email)
    } catch {
      /* clipboard may be unavailable — fail silently */
    }
    setCopied(true)
    setTimeout(() => setCopied(false), 1500)
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="My Profile"
        description="Your account details, roles and effective permissions."
      />

      {/* Identity hero — Facebook-style cover + overlapping avatar */}
      <Card className="overflow-hidden">
        {/* Cover photo */}
        <div className="h-36 bg-linear-to-r from-primary/25 via-violet-500/20 to-sky-500/20 sm:h-48" />

        <CardContent className="pb-4 pt-0">
          <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-end sm:gap-5">
            {/* Avatar overlaps the cover */}
            <Avatar
              name={admin.name}
              className="-mt-16 size-32 shrink-0 text-3xl ring-4 ring-card sm:-mt-20 sm:size-40 sm:text-4xl"
            />

            {/* Name + meta */}
            <div className="flex-1 text-center sm:pb-2 sm:text-left">
              <div className="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                <h2 className="text-2xl font-bold tracking-tight">{admin.name}</h2>
                <StatusBadge status={admin.status} />
              </div>
              <p className="mt-1 text-sm text-muted-foreground">
                {admin.roles.map((r) => r.name).join(' · ')} · {admin.permissions.length} permissions
              </p>
              <div className="mt-2 flex items-center justify-center gap-2 text-sm text-muted-foreground sm:justify-start">
                <Icon icon={MailIcon} className="size-4" />
                <span className="truncate">{admin.email}</span>
                <button
                  onClick={copyEmail}
                  className="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-primary transition-colors hover:bg-primary/10"
                  title="Copy email"
                >
                  <Icon icon={copied ? CheckIcon : CopyIcon} className="size-3.5" />
                  {copied ? 'Copied' : 'Copy'}
                </button>
              </div>
            </div>

            {/* Actions, pushed right on desktop like Facebook */}
            <div className="flex w-full gap-2 sm:w-auto sm:pb-2">
              <Link to="/profile/edit" className="flex-1 sm:flex-none">
                <Button className="w-full">
                  <Icon icon={EditIcon} className="size-4" /> Edit profile
                </Button>
              </Link>
              <Link to="/profile/change-password" className="flex-1 sm:flex-none">
                <Button variant="outline" className="w-full">
                  <Icon icon={LockCheckIcon} className="size-4" /> Password
                </Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Quick stats */}
      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard icon={RolesIcon} label="Assigned roles" value={admin.roles.length} tone="violet" />
        <StatCard icon={PermissionsIcon} label="Effective permissions" value={admin.permissions.length} tone="primary" />
        <StatCard icon={ProfileIcon} label="Account type" value={admin.user_type_label} tone="emerald" />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Account details */}
        <Card className="lg:col-span-1">
          <CardHeader className="flex-row items-center justify-between">
            <div>
              <CardTitle>Account</CardTitle>
              <CardDescription>Read-only details</CardDescription>
            </div>
            <Link to="/profile/edit">
              <Button variant="ghost" size="icon-sm" title="Edit profile">
                <Icon icon={EditIcon} className="size-4" />
              </Button>
            </Link>
          </CardHeader>
          <CardContent className="divide-y divide-border p-0">
            <InfoRow icon={ProfileIcon} label="Full name">{admin.name}</InfoRow>
            <InfoRow icon={MailIcon} label="Email">{admin.email}</InfoRow>
            <InfoRow icon={InfoIcon} label="User type">{admin.user_type_label}</InfoRow>
            <InfoRow icon={CheckIcon} label="Status">
              <StatusBadge status={admin.status} />
            </InfoRow>
          </CardContent>
        </Card>

        {/* Permissions explorer */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Effective permissions</CardTitle>
            <CardDescription>
              {admin.permissions.length} granted across {groups.length} groups
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {groups.map((g) => {
              const isCollapsed = collapsed[g.group]
              return (
                <div key={g.group} className="rounded-lg border border-border">
                  <button
                    onClick={() => setCollapsed((c) => ({ ...c, [g.group]: !c[g.group] }))}
                    className="flex w-full items-center justify-between gap-2 px-3 py-2 text-left"
                  >
                    <span className="flex items-center gap-2 text-sm font-medium capitalize">
                      {g.group}
                      <Badge tone="neutral">{g.items.length}</Badge>
                    </span>
                    <Icon
                      icon={ChevronDownIcon}
                      className={'size-4 text-muted-foreground transition-transform ' + (isCollapsed ? '-rotate-90' : '')}
                    />
                  </button>
                  {!isCollapsed && (
                    <div className="flex flex-wrap gap-1.5 border-t border-border px-3 py-2.5">
                      {g.items.map((p) => (
                        <code key={p} className="rounded bg-muted px-1.5 py-0.5 text-xs">{p}</code>
                      ))}
                    </div>
                  )}
                </div>
              )
            })}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
