import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { ArrowLeftIcon, EyeIcon, EyeOffIcon } from '@/lib/icons'

export default function PasswordUpdate() {
  const [showPw, setShowPw] = useState(false)

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/profile">
          <Button variant="outline" size="icon">
            <Icon icon={ArrowLeftIcon} className="size-4" />
          </Button>
        </Link>
        <PageHeader title="Change password" description="Choose a strong password you don't use elsewhere." />
      </div>

      <Card className="max-w-xl">
        <CardHeader>
          <CardTitle>Update password</CardTitle>
          <CardDescription>Changing your password signs out all other sessions.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Field label="Current password" htmlFor="current" required>
            <div className="relative">
              <Input id="current" type={showPw ? 'text' : 'password'} className="pr-9" placeholder="••••••••" />
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
            <Input id="new" type={showPw ? 'text' : 'password'} placeholder="••••••••" />
          </Field>
          <Field label="Confirm new password" htmlFor="confirm" required>
            <Input id="confirm" type={showPw ? 'text' : 'password'} placeholder="••••••••" />
          </Field>
        </CardContent>
        <CardFooter className="justify-end">
          <Link to="/profile"><Button variant="outline">Cancel</Button></Link>
          <Button onClick={() => navigate('/profile')}>Update password</Button>
        </CardFooter>
      </Card>
    </div>
  )
}
