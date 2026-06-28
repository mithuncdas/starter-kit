import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { LocationCascader } from '@/components/common/LocationCascader'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card'
import { Field } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Select } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Icon } from '@/components/ui/icon'
import { Modal } from '@/components/ui/modal'
import { Link, navigate } from '@/router'
import {
  activeRoleOptions, adminUsers, userAddresses, addressLabels,
} from '@/lib/mock'
import {
  ArrowLeftIcon, EditIcon, DeleteIcon, AddIcon, PinIcon, EyeIcon, EyeOffIcon,
} from '@/lib/icons'

export default function AdminUserForm({ id }) {
  const editing = id != null
  const user = editing ? adminUsers.find((u) => String(u.id) === String(id)) : null

  const [status, setStatus] = useState(user ? user.status === 1 : true)
  const [showPw, setShowPw] = useState(false)
  const [addrOpen, setAddrOpen] = useState(false)
  const [isPrimary, setIsPrimary] = useState(false)
  const addresses = editing ? userAddresses : []

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex items-center gap-3">
        <Link to="/admin-users">
          <Button variant="outline" size="icon">
            <Icon icon={ArrowLeftIcon} className="size-4" />
          </Button>
        </Link>
        <PageHeader
          title={editing ? `Edit ${user?.name ?? 'admin'}` : 'New admin user'}
          description={editing ? 'Update account details, role and addresses.' : 'Create an admin account and assign a role.'}
        />
      </div>

      <Card>
        {/* Account details */}
        <CardHeader>
          <CardTitle>Account details</CardTitle>
          <CardDescription>Basic profile and login information.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field label="Full name" htmlFor="name" required>
            <Input id="name" defaultValue={user?.name} placeholder="Jane Manager" />
          </Field>
          <Field label="Email" htmlFor="email" required>
            <Input id="email" type="email" defaultValue={user?.email} placeholder="jane@example.com" />
          </Field>
          <Field
            label={editing ? 'New password' : 'Password'}
            htmlFor="password"
            required={!editing}
            hint={editing ? 'Leave blank to keep the current password.' : 'Min 8 characters.'}
          >
            <div className="relative">
              <Input id="password" type={showPw ? 'text' : 'password'} placeholder="••••••••" className="pr-9" />
              <button
                type="button"
                onClick={() => setShowPw((s) => !s)}
                className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground hover:text-foreground"
              >
                <Icon icon={showPw ? EyeOffIcon : EyeIcon} className="size-4" />
              </button>
            </div>
          </Field>
          <Field label="Confirm password" htmlFor="password_confirmation" required={!editing}>
            <Input id="password_confirmation" type={showPw ? 'text' : 'password'} placeholder="••••••••" />
          </Field>
        </CardContent>

        {/* Role & status */}
        <CardHeader className="border-t">
          <CardTitle>Role &amp; status</CardTitle>
          <CardDescription>Assign a role and control sign-in access.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Field label="Role" htmlFor="role" required hint="Only active roles are listed.">
            <Select id="role" defaultValue={user?.role_id ?? ''}>
              <option value="" disabled>Select a role…</option>
              {activeRoleOptions.map((r) => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </Select>
          </Field>
          <Field label="Status" required hint="Inactive admins can't sign in.">
            <Select value={status ? 1 : 0} onChange={(e) => setStatus(Number(e.target.value) === 1)}>
              <option value={1}>Active</option>
              <option value={0}>Inactive</option>
            </Select>
          </Field>
        </CardContent>

        <CardFooter className="justify-end border-t">
          <Link to="/admin-users"><Button variant="outline">Cancel</Button></Link>
          <Button onClick={() => navigate('/admin-users')}>
            {editing ? 'Save changes' : 'Create admin'}
          </Button>
        </CardFooter>
      </Card>

      {/* Addresses (edit only — nested resource) */}
      {editing && (
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <div>
              <CardTitle>Addresses</CardTitle>
              <CardDescription>Locations linked to this admin.</CardDescription>
            </div>
            <Button size="sm" variant="outline" onClick={() => setAddrOpen(true)}>
              <Icon icon={AddIcon} className="size-4" /> Add address
            </Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {addresses.map((a) => (
              <div key={a.id} className="flex items-start gap-3 rounded-lg border border-border p-3">
                <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <Icon icon={PinIcon} className="size-4" />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge tone="info">{a.label_name}</Badge>
                    {a.is_primary && <Badge tone="success">Primary</Badge>}
                  </div>
                  <p className="mt-1 text-sm font-medium">{a.address_line1}{a.address_line2 ? `, ${a.address_line2}` : ''}</p>
                  <p className="text-xs text-muted-foreground">
                    {a.hierarchy.map((h) => h.name).join(' › ')}
                  </p>
                </div>
                <div className="flex gap-1">
                  <Button variant="ghost" size="icon-sm"><Icon icon={EditIcon} className="size-4" /></Button>
                  <Button variant="ghost" size="icon-sm" className="hover:text-destructive">
                    <Icon icon={DeleteIcon} className="size-4" />
                  </Button>
                </div>
              </div>
            ))}
            {addresses.length === 0 && (
              <p className="py-6 text-center text-sm text-muted-foreground">No addresses yet.</p>
            )}
          </CardContent>
        </Card>
      )}

      {/* Add address modal */}
      <Modal
        open={addrOpen}
        onClose={() => setAddrOpen(false)}
        title="Add address"
        description="Pick a location and fill in the address details."
        className="max-w-lg"
        footer={
          <>
            <Button variant="outline" onClick={() => setAddrOpen(false)}>Cancel</Button>
            <Button onClick={() => setAddrOpen(false)}>Save address</Button>
          </>
        }
      >
        <div className="space-y-4">
          <LocationCascader defaultCountryId={1} />
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Label" required>
              <Select defaultValue={1}>
                {addressLabels.map((l) => (
                  <option key={l.value} value={l.value}>{l.label}</option>
                ))}
              </Select>
            </Field>
            <Field label="Address line 1">
              <Input placeholder="12 MG Road" />
            </Field>
            <Field label="Latitude">
              <Input type="number" step="any" placeholder="18.52" />
            </Field>
            <Field label="Longitude">
              <Input type="number" step="any" placeholder="73.85" />
            </Field>
          </div>
          <Field label="Notes">
            <Textarea placeholder="Optional notes…" />
          </Field>
          <label className="flex items-center gap-2 text-sm">
            <Checkbox checked={isPrimary} onChange={setIsPrimary} />
            Set as primary address
          </label>
        </div>
      </Modal>
    </div>
  )
}
