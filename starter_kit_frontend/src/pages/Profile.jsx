import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar } from '@/components/ui/avatar'
import { Icon } from '@/components/ui/icon'
import { StatusBadge } from '@/components/common/StatusBadge'
import { currentAdmin } from '@/lib/mock'
import { EyeIcon, EyeOffIcon } from '@/lib/icons'

const TABS = [
  { key: 'profile', label: 'Profile' },
  { key: 'password', label: 'Password' },
]

export default function Profile() {
  const [tab, setTab] = useState('profile')
  const [showPw, setShowPw] = useState(false)

  return (
    <div className="space-y-6">
      <PageHeader title="My Profile" description="Manage your account details and password." />

      {/* Identity card */}
      <Card>
        <CardContent className="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
          <Avatar name={currentAdmin.name} size="lg" />
          <div className="text-center sm:text-left">
            <div className="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
              <h2 className="text-lg font-semibold">{currentAdmin.name}</h2>
              <StatusBadge status={currentAdmin.status} />
            </div>
            <p className="text-sm text-muted-foreground">{currentAdmin.email}</p>
            <div className="mt-2 flex flex-wrap justify-center gap-1.5 sm:justify-start">
              {currentAdmin.roles.map((r) => (
                <Badge key={r.name} tone="violet">{r.name}</Badge>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tabs */}
      <div className="flex gap-1 border-b border-border">
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={
              '-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition-colors ' +
              (tab === t.key
                ? 'border-primary text-foreground'
                : 'border-transparent text-muted-foreground hover:text-foreground')
            }
          >
            {t.label}
          </button>
        ))}
      </div>

      {tab === 'profile' ? (
        <div className="grid gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Account details</CardTitle>
              <CardDescription>Update your name and email address.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <Field label="Full name" htmlFor="name" required>
                <Input id="name" defaultValue={currentAdmin.name} />
              </Field>
              <Field label="Email" htmlFor="email" required>
                <Input id="email" type="email" defaultValue={currentAdmin.email} />
              </Field>
            </CardContent>
            <CardFooter className="justify-end">
              <Button>Save changes</Button>
            </CardFooter>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Effective permissions</CardTitle>
              <CardDescription>{currentAdmin.permissions.length} granted</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-wrap gap-1.5">
              {currentAdmin.permissions.map((p) => (
                <code key={p} className="rounded bg-muted px-1.5 py-0.5 text-xs">{p}</code>
              ))}
            </CardContent>
          </Card>
        </div>
      ) : (
        <Card className="max-w-xl">
          <CardHeader>
            <CardTitle>Change password</CardTitle>
            <CardDescription>Changing your password signs out all other sessions.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <Field label="Current password" htmlFor="current" required>
              <div className="relative">
                <Input id="current" type={showPw ? 'text' : 'password'} className="pr-9" />
                <button
                  type="button"
                  onClick={() => setShowPw((s) => !s)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground hover:text-foreground"
                >
                  <Icon icon={showPw ? EyeOffIcon : EyeIcon} className="size-4" />
                </button>
              </div>
            </Field>
            <Field label="New password" htmlFor="new" required hint="Must differ from your current password.">
              <Input id="new" type={showPw ? 'text' : 'password'} />
            </Field>
            <Field label="Confirm new password" htmlFor="confirm" required>
              <Input id="confirm" type={showPw ? 'text' : 'password'} />
            </Field>
          </CardContent>
          <CardFooter className="justify-end">
            <Button>Update password</Button>
          </CardFooter>
        </Card>
      )}
    </div>
  )
}
