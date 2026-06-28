import { AuthLayout } from '@/components/layout/AuthLayout'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { MailIcon, ArrowLeftIcon } from '@/lib/icons'

export default function ForgotPassword({ theme, toggleTheme }) {
  const submit = (e) => {
    e.preventDefault()
    navigate('/reset-password')
  }

  return (
    <AuthLayout
      title="Forgot password?"
      subtitle="Enter your email and we'll send you a 6-digit OTP to reset it."
      theme={theme}
      toggleTheme={toggleTheme}
    >
      <form className="space-y-4" onSubmit={submit}>
        <Field label="Email" htmlFor="email" required hint="Rate limited to 3 requests per minute.">
          <div className="relative">
            <Icon icon={MailIcon} className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input id="email" type="email" placeholder="you@example.com" className="pl-9" />
          </div>
        </Field>

        <Button type="submit" size="lg" className="w-full">
          Send OTP
        </Button>

        <Link
          to="/login"
          className="flex items-center justify-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"
        >
          <Icon icon={ArrowLeftIcon} className="size-4" /> Back to sign in
        </Link>
      </form>
    </AuthLayout>
  )
}
