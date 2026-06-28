import { Icon } from '@/components/ui/icon'
import { ThemeToggle } from '@/components/common/ThemeToggle'
import { Card } from '@/components/ui/card'
import { DashboardIcon } from '@/lib/icons'

export function AuthLayout({ title, subtitle, children, theme, toggleTheme }) {
  return (
    <div className="relative flex min-h-screen flex-col bg-muted/40">
      {/* Subtle dotted backdrop */}
      <div className="pointer-events-none absolute inset-0 opacity-[0.04] bg-[radial-gradient(circle_at_1px_1px,currentColor_1px,transparent_0)] bg-size-[22px_22px]" />

      {/* Theme toggle, top-right */}
      <div className="absolute right-4 top-4 z-10 sm:right-6 sm:top-6">
        <ThemeToggle theme={theme} toggle={toggleTheme} />
      </div>

      {/* Centered column */}
      <div className="relative flex flex-1 items-center justify-center px-4 py-10">
        <div className="w-full max-w-sm">
          {/* Brand */}
          <div className="mb-8 flex flex-col items-center text-center">
            <span className="flex size-12 items-center justify-center rounded-2xl bg-linear-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/20">
              <Icon icon={DashboardIcon} className="size-6" />
            </span>
            <h1 className="mt-5 text-2xl font-bold tracking-tight">{title}</h1>
            {subtitle && <p className="mt-2 text-sm text-muted-foreground">{subtitle}</p>}
          </div>

          {/* Form card */}
          <Card className="p-6 shadow-sm sm:p-8">{children}</Card>

          <p className="mt-6 text-center text-xs text-muted-foreground">
            © 2026 Starter Kit. Static design preview.
          </p>
        </div>
      </div>
    </div>
  )
}
