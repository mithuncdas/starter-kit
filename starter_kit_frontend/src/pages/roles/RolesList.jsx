import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { StatusBadge } from '@/components/common/StatusBadge'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Icon } from '@/components/ui/icon'
import { Modal } from '@/components/ui/modal'
import { Table, THead, TBody, TR, TH, TD } from '@/components/ui/table'
import { Pagination } from '@/components/ui/pagination'
import { Link, navigate } from '@/router'
import { roles } from '@/lib/mock'
import { AddIcon, SearchIcon, EditIcon, DeleteIcon, RolesIcon } from '@/lib/icons'

const PER_PAGE_OPTIONS = [10, 15, 30, 50, 100]

export default function RolesList() {
  const [toDelete, setToDelete] = useState(null)
  const [perPage, setPerPage] = useState(10)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Roles"
        description="Define roles and the permissions attached to them."
        actions={
          <Link to="/roles/new">
            <Button>
              <Icon icon={AddIcon} className="size-4" /> New role
            </Button>
          </Link>
        }
      />

      <Card>
        {/* Filters row — top */}
        <div className="flex flex-wrap items-center gap-2 border-b border-border p-4">
          <span className="mr-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Filters</span>
          <div className="w-32">
            <Select defaultValue="">
              <option value="">All status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </Select>
          </div>
        </div>

        {/* Toolbar — per-page (left) + search (right) */}
        <div className="flex flex-col gap-3 border-b border-border bg-muted/30 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground">Show</span>
            <div className="w-20">
              <Select value={perPage} onChange={(e) => setPerPage(Number(e.target.value))}>
                {PER_PAGE_OPTIONS.map((n) => (
                  <option key={n} value={n}>{n}</option>
                ))}
              </Select>
            </div>
            <span className="hidden text-sm text-muted-foreground sm:inline">entries</span>
          </div>

          <div className="relative w-full sm:w-72">
            <Icon icon={SearchIcon} className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input placeholder="Search roles…" className="pl-9" />
          </div>
        </div>

        <Table>
          <THead>
            <TR className="hover:bg-transparent">
              <TH>Role</TH>
              <TH>Permissions</TH>
              <TH>Admins</TH>
              <TH>Status</TH>
              <TH className="text-right">Actions</TH>
            </TR>
          </THead>
          <TBody>
            {roles.map((r) => (
              <TR key={r.id}>
                <TD>
                  <div className="flex items-center gap-3">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-violet-500/12 text-violet-600 dark:text-violet-400">
                      <Icon icon={RolesIcon} className="size-4" />
                    </span>
                    <span className="text-sm font-medium">{r.name}</span>
                  </div>
                </TD>
                <TD>
                  <Badge tone="primary">{r.permissions.length} permissions</Badge>
                </TD>
                <TD className="text-sm text-muted-foreground">{r.admins_count}</TD>
                <TD><StatusBadge status={r.status} /></TD>
                <TD>
                  <div className="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon-sm" onClick={() => navigate(`/roles/${r.id}/edit`)} aria-label="Edit">
                      <Icon icon={EditIcon} className="size-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      className="text-muted-foreground hover:text-destructive"
                      onClick={() => setToDelete(r)}
                      aria-label="Delete"
                    >
                      <Icon icon={DeleteIcon} className="size-4" />
                    </Button>
                  </div>
                </TD>
              </TR>
            ))}
          </TBody>
        </Table>

        <Pagination
          page={1}
          lastPage={Math.max(1, Math.ceil(roles.length / perPage))}
          total={roles.length}
          perPage={perPage}
        />
      </Card>

      <Modal
        open={!!toDelete}
        onClose={() => setToDelete(null)}
        title="Delete role?"
        description={
          toDelete?.admins_count
            ? `"${toDelete?.name}" is assigned to ${toDelete?.admins_count} admin(s). Reassign them before deleting.`
            : `Delete the "${toDelete?.name}" role? This cannot be undone.`
        }
        footer={
          <>
            <Button variant="outline" onClick={() => setToDelete(null)}>Cancel</Button>
            <Button variant="destructive" disabled={!!toDelete?.admins_count} onClick={() => setToDelete(null)}>
              Delete
            </Button>
          </>
        }
      />
    </div>
  )
}
