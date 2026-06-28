import { RouterProvider, useRoute, matchRoute } from '@/router'
import { useTheme } from '@/lib/useTheme'
import { AppShell } from '@/components/layout/AppShell'

import Login from '@/pages/auth/Login'
import ForgotPassword from '@/pages/auth/ForgotPassword'
import ResetPassword from '@/pages/auth/ResetPassword'
import Dashboard from '@/pages/Dashboard'
import AdminUsersList from '@/pages/admins/AdminUsersList'
import AdminUserForm from '@/pages/admins/AdminUserForm'
import RolesList from '@/pages/roles/RolesList'
import RoleForm from '@/pages/roles/RoleForm'
import Profile from '@/pages/Profile'
import AuditLogs from '@/pages/audit/AuditLogs'

const AUTH_ROUTES = ['/login', '/forgot-password', '/reset-password']

function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center gap-2 py-24 text-center">
      <p className="text-5xl font-bold">404</p>
      <p className="text-muted-foreground">This page doesn't exist.</p>
      <a href="#/" className="mt-2 text-sm font-medium text-primary hover:underline">Back to dashboard</a>
    </div>
  )
}

function resolvePage(path) {
  if (path === '/') return <Dashboard />
  if (path === '/admin-users') return <AdminUsersList />
  if (path === '/admin-users/new') return <AdminUserForm />
  if (path === '/roles') return <RolesList />
  if (path === '/roles/new') return <RoleForm />
  if (path === '/audit-logs') return <AuditLogs />
  if (path === '/profile') return <Profile />

  let m
  if ((m = matchRoute('/admin-users/:id/edit', path))) return <AdminUserForm id={m.id} />
  if ((m = matchRoute('/roles/:id/edit', path))) return <RoleForm id={m.id} />

  return <NotFound />
}

function Routed({ theme, toggle }) {
  const { path } = useRoute()

  if (AUTH_ROUTES.includes(path)) {
    const props = { theme, toggleTheme: toggle }
    if (path === '/forgot-password') return <ForgotPassword {...props} />
    if (path === '/reset-password') return <ResetPassword {...props} />
    return <Login {...props} />
  }

  return (
    <AppShell theme={theme} toggleTheme={toggle}>
      {resolvePage(path)}
    </AppShell>
  )
}

export default function App() {
  const { theme, toggle } = useTheme()
  return (
    <RouterProvider>
      <Routed theme={theme} toggle={toggle} />
    </RouterProvider>
  )
}
