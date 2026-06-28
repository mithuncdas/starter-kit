import { useState } from 'react'
import { AuthLayout } from '@/components/layout/AuthLayout'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { MailIcon, EyeIcon, EyeOffIcon, InfoIcon } from '@/lib/icons'

export default function Login({ theme, toggleTheme }) {
  const [show, setShow] = useState(false)

  const submit = (e) => {
    e.preventDefault()
    navigate('/')
  }

  return (
    <AuthLayout
      title="Welcome back"
      subtitle="Sign in to your admin account to continue."
      theme={theme}
      toggleTheme={toggleTheme}
    >
      <form className="space-y-4" onSubmit={submit}>
        <Field label="Email" htmlFor="email" required>
          <div className="relative">
            <Icon icon={MailIcon} className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input id="email" type="email" defaultValue="admin@example.com" placeholder="you@example.com" className="pl-9" />
          </div>
        </Field>

        <Field label="Password" htmlFor="password" required>
          <div className="relative">
            <Input
              id="password"
              type={show ? 'text' : 'password'}
              defaultValue="password"
              placeholder="••••••••"
              className="pr-9"
            />
            <button
              type="button"
              onClick={() => setShow((s) => !s)}
              className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground hover:text-foreground"
              aria-label="Toggle password"
            >
              <Icon icon={show ? EyeOffIcon : EyeIcon} className="size-4" />
            </button>
          </div>
        </Field>

        <div className="flex items-center justify-between">
          <label className="flex items-center gap-2 text-sm text-muted-foreground">
            <input type="checkbox" className="size-4 rounded border-border accent-primary" /> Remember me
          </label>
          <Link to="/forgot-password" className="text-sm font-medium text-primary hover:underline">
            Forgot password?
          </Link>
        </div>

        <Button type="submit" size="lg" className="w-full">
          Sign in
        </Button>

        <div className="flex items-start gap-2 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
          <Icon icon={InfoIcon} className="mt-0.5 size-4 shrink-0" />
          <span>
            Demo credentials are pre-filled — <span className="font-medium text-foreground">admin@example.com</span> /{' '}
            <span className="font-medium text-foreground">password</span>.
          </span>
        </div>
      </form>
    </AuthLayout>
  )
}
