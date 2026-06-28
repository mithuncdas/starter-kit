import { createContext, useContext, useEffect, useState } from 'react'

const RouterContext = createContext({ path: '/login' })

function parseHash() {
  const raw = window.location.hash.replace(/^#/, '')
  const path = (raw.split('?')[0] || '/login') || '/login'
  return { path }
}

export function navigate(to) {
  window.location.hash = '#' + to
}

export function RouterProvider({ children }) {
  const [route, setRoute] = useState(parseHash())

  useEffect(() => {
    if (!window.location.hash) window.location.hash = '#/login'
    const onChange = () => {
      setRoute(parseHash())
      window.scrollTo({ top: 0 })
    }
    window.addEventListener('hashchange', onChange)
    return () => window.removeEventListener('hashchange', onChange)
  }, [])

  return <RouterContext.Provider value={route}>{children}</RouterContext.Provider>
}

export function useRoute() {
  return useContext(RouterContext)
}

/** Match a pattern like '/roles/:id/edit' against a path → params object or null. */
export function matchRoute(pattern, path) {
  const pp = pattern.split('/').filter(Boolean)
  const cp = path.split('/').filter(Boolean)
  if (pp.length !== cp.length) return null
  const params = {}
  for (let i = 0; i < pp.length; i++) {
    if (pp[i].startsWith(':')) params[pp[i].slice(1)] = decodeURIComponent(cp[i])
    else if (pp[i] !== cp[i]) return null
  }
  return params
}

export function Link({ to, className, children, onClick, ...props }) {
  return (
    <a
      href={'#' + to}
      className={className}
      onClick={onClick}
      {...props}
    >
      {children}
    </a>
  )
}
