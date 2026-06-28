import { useMemo, useState } from 'react'
import { Field } from '@/components/ui/field'
import { Select } from '@/components/ui/select'
import { countries, countryStructures, countryTree } from '@/lib/mock'

/**
 * Cascading location picker driven by the mock country tree.
 * Mirrors the API flow: countries → top-level → children → … (per-country labels).
 */
export function LocationCascader({ defaultCountryId = 1, columns = 2 }) {
  const [countryId, setCountryId] = useState(defaultCountryId)
  // selected area id at each depth (1-indexed by depth)
  const [picks, setPicks] = useState({})

  const structure = countryStructures[countryId] || []
  const roots = countryTree[countryId] || []

  // Resolve the list of options available at each depth based on prior picks.
  const levels = useMemo(() => {
    const out = []
    let pool = roots
    for (let i = 0; i < structure.length; i++) {
      const depth = structure[i].depth
      out.push({ ...structure[i], options: pool })
      const chosen = pool.find((n) => n.id === picks[depth])
      pool = chosen ? chosen.children || [] : []
      if (!chosen) {
        // remaining levels have no options until this one is picked
        for (let j = i + 1; j < structure.length; j++) {
          out.push({ ...structure[j], options: [] })
        }
        break
      }
    }
    return out
  }, [countryId, picks, structure, roots])

  const choose = (depth, value) => {
    const id = value ? Number(value) : undefined
    setPicks((prev) => {
      const next = { ...prev, [depth]: id }
      // clear deeper picks
      Object.keys(next).forEach((d) => {
        if (Number(d) > depth) delete next[d]
      })
      return next
    })
  }

  return (
    <div className={columns === 2 ? 'grid gap-4 sm:grid-cols-2' : 'space-y-4'}>
      <Field label="Country" required>
        <Select
          value={countryId}
          onChange={(e) => {
            setCountryId(Number(e.target.value))
            setPicks({})
          }}
        >
          {countries.map((c) => (
            <option key={c.id} value={c.id}>{c.name}</option>
          ))}
        </Select>
      </Field>

      {levels.map((lvl) => (
        <Field key={lvl.depth} label={lvl.label}>
          <Select
            value={picks[lvl.depth] ?? ''}
            disabled={lvl.options.length === 0}
            onChange={(e) => choose(lvl.depth, e.target.value)}
          >
            <option value="">
              {lvl.options.length ? `Select ${lvl.label.toLowerCase()}…` : '—'}
            </option>
            {lvl.options.map((o) => (
              <option key={o.id} value={o.id}>{o.name}</option>
            ))}
          </Select>
        </Field>
      ))}
    </div>
  )
}
