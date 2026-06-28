import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Icon } from '@/components/ui/icon'
import { Link, navigate } from '@/router'
import { permissionGroups, roles } from '@/lib/mock'
import { ArrowLeftIcon } from '@/lib/icons'

export default function RoleForm({ id }) {
  const editing = id != null
  const role = editing ? roles.find((r) => String(r.id) === String(id)) : null

  const [status, setStatus] = useState(role ? role.status === 1 : true)
  const [selected, setSelected] = useState(
    () => new Set((role?.permissions ?? []).map((p) => p.id)),
  )

  const allIds = permissionGroups.flatMap((g) => g.permissions.map((p) => p.id))
  const toggle = (pid) =>
    setSelected((prev) => {
      const next = new Set(prev)
      next.has(pid) ? next.delete(pid) : next.add(pid)
      return next
    })
  const toggleGroup = (group, on) =>
    setSelected((prev) => {
      const next = new Set(prev)
      group.permissions.forEach((p) => (on ? next.add(p.id) : next.delete(p.id)))
      return next
    })
  const allOn = selected.size === allIds.length
  const toggleAll = () => setSelected(allOn ? new Set() : new Set(allIds))

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/roles">
          <Button variant="outline" size="icon" aria-label="Back">
            <Icon icon={ArrowLeftIcon} className="size-4" />
          </Button>
        </Link>
        <PageHeader
          title={editing ? `Edit ${role?.name ?? 'role'}` : 'New role'}
          description="Set a name, status and choose the permissions for this role."
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardHeader className="flex-row items-center justify-between">
              <div>
                <CardTitle>Permissions</CardTitle>
                <CardDescription>{selected.size} of {allIds.length} selected</CardDescription>
              </div>
              <label className="flex items-center gap-2 text-sm">
                <Checkbox checked={allOn} onChange={toggleAll} /> Select all
              </label>
            </CardHeader>
            <CardContent className="space-y-5">
              {permissionGroups.map((group) => {
                const groupOn = group.permissions.every((p) => selected.has(p.id))
                const some = group.permissions.some((p) => selected.has(p.id))
                return (
                  <div key={group.group} className="rounded-lg border border-border">
                    <div className="flex items-center justify-between border-b border-border bg-muted/40 px-4 py-2.5">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold">{group.group}</span>
                        {some && !groupOn && <Badge tone="warning">partial</Badge>}
                      </div>
                      <label className="flex items-center gap-2 text-xs text-muted-foreground">
                        <Checkbox checked={groupOn} onChange={(on) => toggleGroup(group, on)} /> All
                      </label>
                    </div>
                    <div className="grid gap-x-4 gap-y-3 p-4 sm:grid-cols-2">
                      {group.permissions.map((p) => (
                        <label key={p.id} className="flex items-center gap-2.5 text-sm">
                          <Checkbox checked={selected.has(p.id)} onChange={() => toggle(p.id)} />
                          <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{p.name}</code>
                        </label>
                      ))}
                    </div>
                  </div>
                )
              })}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <Field label="Role name" htmlFor="name" required>
                <Input id="name" defaultValue={role?.name} placeholder="e.g. Manager" />
              </Field>
              <div className="flex items-center justify-between rounded-lg border border-border p-3">
                <div>
                  <p className="text-sm font-medium">Active</p>
                  <p className="text-xs text-muted-foreground">Only active roles can be assigned.</p>
                </div>
                <Switch checked={status} onChange={setStatus} />
              </div>
            </CardContent>
            <CardFooter className="justify-end">
              <Link to="/roles"><Button variant="outline">Cancel</Button></Link>
              <Button onClick={() => navigate('/roles')}>{editing ? 'Save changes' : 'Create role'}</Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    </div>
  )
}
