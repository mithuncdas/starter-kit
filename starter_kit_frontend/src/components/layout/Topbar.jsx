import { useEffect, useRef, useState } from 'react'
import { Icon } from '@/components/ui/icon'
import { Avatar } from '@/components/ui/avatar'
import { ThemeToggle } from '@/components/common/ThemeToggle'
import { navigate } from '@/router'
import { currentAdmin } from '@/lib/mock'
import {
  MenuIcon, ProfileIcon, LogoutIcon, ChevronDownIcon,
} from '@/lib/icons'

export function Topbar({ onMenu, theme, toggleTheme }) {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    const onClick = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setOpen(false)
    }
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    document.addEventListener('mousedown', onClick)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onClick)
      document.removeEventListener('keydown', onKey)
    }
  }, [])

  const go = (to) => {
    setOpen(false)
    navigate(to)
  }

  return (
    <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border bg-background/90 px-4 backdrop-blur sm:px-6">
      <button
        onClick={onMenu}
        className="flex size-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted lg:hidden"
        aria-label="Open menu"
      >
        <Icon icon={MenuIcon} className="size-5" />
      </button>

      <div className="ml-auto flex items-center gap-1">
        <ThemeToggle theme={theme} toggle={toggleTheme} />

        {/* Profile dropdown */}
        <div className="relative ml-1" ref={ref}>
          <button
            onClick={() => setOpen((o) => !o)}
            aria-haspopup="menu"
            aria-expanded={open}
            className="flex items-center gap-2.5 rounded-lg p-1 pr-2 transition-colors hover:bg-muted"
          >
            <Avatar name={currentAdmin.name} size="sm" />
            <div className="hidden text-left leading-tight sm:block">
              <p className="text-xs font-semibold">{currentAdmin.name}</p>
              <p className="text-[11px] text-muted-foreground">{currentAdmin.roles[0]?.name}</p>
            </div>
            <Icon
              icon={ChevronDownIcon}
              className={'hidden size-4 text-muted-foreground transition-transform sm:block ' + (open ? 'rotate-180' : '')}
            />
          </button>

          {open && (
            <div
              role="menu"
              className="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-border bg-popover p-1 text-popover-foreground shadow-lg animate-in fade-in zoom-in-95 duration-100"
            >
              {/* Header */}
              <div className="flex items-center gap-2.5 px-2 py-2">
                <Avatar name={currentAdmin.name} size="sm" />
                <div className="min-w-0 leading-tight">
                  <p className="truncate text-sm font-semibold">{currentAdmin.name}</p>
                  <p className="truncate text-xs text-muted-foreground">{currentAdmin.email}</p>
                </div>
              </div>
              <div className="my-1 h-px bg-border" />

              <button
                role="menuitem"
                onClick={() => go('/profile')}
                className="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-sm transition-colors hover:bg-muted"
              >
                <Icon icon={ProfileIcon} className="size-4 text-muted-foreground" />
                Profile
              </button>
              <button
                role="menuitem"
                onClick={() => go('/login')}
                className="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-sm text-destructive transition-colors hover:bg-destructive/10"
              >
                <Icon icon={LogoutIcon} className="size-4" />
                Sign out
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  )
}
