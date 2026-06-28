import { useRef, useState } from 'react'
import { AuthLayout } from '@/components/layout/AuthLayout'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { EyeIcon, EyeOffIcon, ArrowLeftIcon } from '@/lib/icons'

function OtpInput() {
  const [vals, setVals] = useState(Array(6).fill(''))
  const refs = useRef([])

  const onChange = (i, v) => {
    const d = v.replace(/\D/g, '').slice(-1)
    const next = [...vals]
    next[i] = d
    setVals(next)
    if (d && i < 5) refs.current[i + 1]?.focus()
  }
  const onKey = (i, e) => {
    if (e.key === 'Backspace' && !vals[i] && i > 0) refs.current[i - 1]?.focus()
  }

  return (
    <div className="flex justify-between gap-2">
      {vals.map((v, i) => (
        <input
          key={i}
          ref={(el) => (refs.current[i] = el)}
          value={v}
          onChange={(e) => onChange(i, e.target.value)}
          onKeyDown={(e) => onKey(i, e)}
          inputMode="numeric"
          maxLength={1}
          className="size-12 rounded-lg border border-border bg-background text-center text-lg font-semibold shadow-sm focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none"
        />
      ))}
    </div>
  )
}

export default function ResetPassword({ theme, toggleTheme }) {
  const [show, setShow] = useState(false)
  const submit = (e) => {
    e.preventDefault()
    navigate('/login')
  }

  return (
    <AuthLayout
      title="Reset password"
      subtitle="Enter the OTP sent to your email and choose a new password."
      theme={theme}
      toggleTheme={toggleTheme}
    >
      <form className="space-y-4" onSubmit={submit}>
        <Field label="One-time password" required hint="Expires in 5 minutes.">
          <OtpInput />
        </Field>

        <Field label="New password" htmlFor="password" required>
          <div className="relative">
            <Input id="password" type={show ? 'text' : 'password'} placeholder="••••••••" className="pr-9" />
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

        <Field label="Confirm password" htmlFor="password_confirmation" required>
          <Input id="password_confirmation" type={show ? 'text' : 'password'} placeholder="••••••••" />
        </Field>

        <Button type="submit" size="lg" className="w-full">
          Reset password
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
