import { PageHeader } from '@/components/common/PageHeader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Avatar } from '@/components/ui/avatar'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { currentAdmin } from '@/lib/mock'
import { ArrowLeftIcon, LockCheckIcon } from '@/lib/icons'

export default function ProfileEdit() {
  const admin = currentAdmin

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/profile">
          <Button variant="outline" size="icon">
            <Icon icon={ArrowLeftIcon} className="size-4" />
          </Button>
        </Link>
        <PageHeader title="Edit profile" description="Update your name and email address." />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Account details</CardTitle>
          <CardDescription>These details identify you across the admin panel.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-4">
            <Avatar name={admin.name} size="lg" className="size-14 text-base" />
            <div className="text-sm text-muted-foreground">
              Your avatar is generated from your name.
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Full name" htmlFor="name" required>
              <Input id="name" defaultValue={admin.name} placeholder="Jane Manager" />
            </Field>
            <Field label="Email" htmlFor="email" required>
              <Input id="email" type="email" defaultValue={admin.email} placeholder="jane@example.com" />
            </Field>
          </div>
        </CardContent>
        <CardFooter className="justify-end">
          <Link to="/profile"><Button variant="outline">Cancel</Button></Link>
          <Button onClick={() => navigate('/profile')}>Save changes</Button>
        </CardFooter>
      </Card>

      <Card>
        <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-start gap-3">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Icon icon={LockCheckIcon} className="size-4" />
            </span>
            <div>
              <p className="text-sm font-medium">Password</p>
              <p className="text-xs text-muted-foreground">Change the password used to sign in.</p>
            </div>
          </div>
          <Link to="/profile/change-password">
            <Button variant="outline">Change password</Button>
          </Link>
        </CardContent>
      </Card>
    </div>
  )
}
