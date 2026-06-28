import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { StatusBadge } from '@/components/common/StatusBadge'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Avatar } from '@/components/ui/avatar'
import { Icon } from '@/components/ui/icon'
import { Modal } from '@/components/ui/modal'
import { Table, THead, TBody, TR, TH, TD } from '@/components/ui/table'
import { Pagination } from '@/components/ui/pagination'
import { Link, navigate } from '@/router'
import { adminUsers, roles } from '@/lib/mock'
import { AddIcon, SearchIcon, EditIcon, DeleteIcon } from '@/lib/icons'

const PER_PAGE_OPTIONS = [10, 15, 30, 50, 100]

export default function AdminUsersList() {
  const [toDelete, setToDelete] = useState(null)
  const [perPage, setPerPage] = useState(15)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Admin Users"
        description="Manage admin accounts and their assigned roles."
        actions={
          <Link to="/admin-users/new">
            <Button>
              <Icon icon={AddIcon} className="size-4" /> New admin
            </Button>
          </Link>
        }
      />

      <Card>
        {/* Filters row — top */}
        <div className="flex flex-wrap items-center gap-2 border-b border-border p-4">
          <span className="mr-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Filters</span>
          <div className="w-36">
            <Select defaultValue="">
              <option value="">All roles</option>
              {roles.map((r) => (
                <option key={r.id} value={r.id}>{r.name}</option>
              ))}
            </Select>
          </div>
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
            <Input placeholder="Search by name or email…" className="pl-9" />
          </div>
        </div>

        {/* Table */}
        <Table>
          <THead>
            <TR className="hover:bg-transparent">
              <TH>Admin</TH>
              <TH>Role</TH>
              <TH>Status</TH>
              <TH>Created</TH>
              <TH className="text-right">Actions</TH>
            </TR>
          </THead>
          <TBody>
            {adminUsers.map((u) => (
              <TR key={u.id}>
                <TD>
                  <div className="flex items-center gap-3">
                    <Avatar name={u.name} size="sm" />
                    <div>
                      <p className="text-sm font-medium">{u.name}</p>
                      <p className="text-xs text-muted-foreground">{u.email}</p>
                    </div>
                  </div>
                </TD>
                <TD><Badge tone="violet">{u.roles[0]?.name}</Badge></TD>
                <TD><StatusBadge status={u.status} /></TD>
                <TD className="text-sm text-muted-foreground">{u.created_at}</TD>
                <TD>
                  <div className="flex items-center justify-end gap-1">
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      onClick={() => navigate(`/admin-users/${u.id}/edit`)}
                      aria-label="Edit"
                    >
                      <Icon icon={EditIcon} className="size-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      className="text-muted-foreground hover:text-destructive"
                      onClick={() => setToDelete(u)}
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
          lastPage={Math.max(1, Math.ceil(adminUsers.length / perPage))}
          total={adminUsers.length}
          perPage={perPage}
        />
      </Card>

      <Modal
        open={!!toDelete}
        onClose={() => setToDelete(null)}
        title="Delete admin user?"
        description={`This will permanently delete ${toDelete?.name} and revoke all their sessions. This action cannot be undone.`}
        footer={
          <>
            <Button variant="outline" onClick={() => setToDelete(null)}>Cancel</Button>
            <Button variant="destructive" onClick={() => setToDelete(null)}>Delete</Button>
          </>
        }
      />
    </div>
  )
}
