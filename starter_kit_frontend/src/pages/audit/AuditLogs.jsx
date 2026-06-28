import { useState } from 'react'
import { PageHeader } from '@/components/common/PageHeader'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Icon } from '@/components/ui/icon'
import { Modal } from '@/components/ui/modal'
import { Table, THead, TBody, TR, TH, TD } from '@/components/ui/table'
import { Pagination } from '@/components/ui/pagination'
import { auditLogs } from '@/lib/mock'
import { SearchIcon, EyeIcon } from '@/lib/icons'

const ACTION_TONE = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  login: 'neutral',
  password_reset: 'warning',
}

function actionTone(action) {
  const verb = action.split('.')[1]
  return ACTION_TONE[verb] || 'primary'
}

const PER_PAGE_OPTIONS = [10, 15, 30, 50, 100]

export default function AuditLogs() {
  const [active, setActive] = useState(null)
  const [perPage, setPerPage] = useState(25)

  return (
    <div className="space-y-6">
      <PageHeader title="Audit Logs" description="A searchable trail of every sensitive action." />

      <Card>
        {/* Filters row — top */}
        <div className="flex flex-wrap items-center gap-2 border-b border-border p-4">
          <span className="mr-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Filters</span>
          <div className="w-40">
            <Select defaultValue="">
              <option value="">All subjects</option>
              <option value="User">User</option>
              <option value="Role">Role</option>
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
            <Input placeholder="Search action or correlation id…" className="pl-9" />
          </div>
        </div>

        <Table>
          <THead>
            <TR className="hover:bg-transparent">
              <TH>Action</TH>
              <TH>Actor</TH>
              <TH>Subject</TH>
              <TH>Tags</TH>
              <TH>When</TH>
              <TH className="text-right">Details</TH>
            </TR>
          </THead>
          <TBody>
            {auditLogs.map((log) => (
              <TR key={log.id}>
                <TD><Badge tone={actionTone(log.action)}>{log.action}</Badge></TD>
                <TD>
                  <p className="text-sm font-medium">{log.actor.name}</p>
                  <p className="text-xs text-muted-foreground">{log.actor.type} #{log.actor.id}</p>
                </TD>
                <TD>
                  <p className="text-sm font-medium">{log.subject.name}</p>
                  <p className="text-xs text-muted-foreground">{log.subject.type} #{log.subject.id}</p>
                </TD>
                <TD>
                  <div className="flex flex-wrap gap-1">
                    {log.tags.map((t) => (
                      <Badge key={t} tone="neutral">{t}</Badge>
                    ))}
                  </div>
                </TD>
                <TD className="whitespace-nowrap text-sm text-muted-foreground">{log.created_at}</TD>
                <TD className="text-right">
                  <Button variant="ghost" size="icon-sm" onClick={() => setActive(log)} aria-label="View">
                    <Icon icon={EyeIcon} className="size-4" />
                  </Button>
                </TD>
              </TR>
            ))}
          </TBody>
        </Table>

        <Pagination
          page={1}
          lastPage={Math.max(1, Math.ceil(auditLogs.length / perPage))}
          total={auditLogs.length}
          perPage={perPage}
        />
      </Card>

      <Modal
        open={!!active}
        onClose={() => setActive(null)}
        title="Audit entry"
        description={active?.id}
        className="max-w-lg"
        footer={<Button variant="outline" onClick={() => setActive(null)}>Close</Button>}
      >
        {active && (
          <dl className="space-y-3 text-sm">
            <Row label="Action"><Badge tone={actionTone(active.action)}>{active.action}</Badge></Row>
            <Row label="Actor">{active.actor.name} · {active.actor.type} #{active.actor.id}</Row>
            <Row label="Subject">{active.subject.name} · {active.subject.type} #{active.subject.id}</Row>
            <Row label="Correlation">
              <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{active.correlation_id}</code>
            </Row>
            <Row label="Tags">
              <div className="flex flex-wrap gap-1">
                {active.tags.map((t) => <Badge key={t} tone="neutral">{t}</Badge>)}
              </div>
            </Row>
            <Row label="When">{active.created_at}</Row>
          </dl>
        )}
      </Modal>
    </div>
  )
}

function Row({ label, children }) {
  return (
    <div className="flex items-start justify-between gap-4 border-b border-border pb-3 last:border-0 last:pb-0">
      <dt className="text-muted-foreground">{label}</dt>
      <dd className="text-right font-medium">{children}</dd>
    </div>
  )
}
