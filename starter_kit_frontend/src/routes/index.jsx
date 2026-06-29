import { cloneElement } from 'react';
import routes from '@/routes/route.config';
import { Route, Routes } from 'react-router';
import { ProtectedRoute, GuestRoute } from '@/routes/middleware';

function renderRoutes(routeList) {
  return routeList
    .filter((route) => route.visible !== false)
    .map((route, i) => {
      const key = route.path ?? `group-${i}`;

      // The layout/element route plus its child routes.
      const inner = (
        <Route path={route.path} element={route.element}>
          {route.children?.map((child, j) =>
            child.index ? (
              <Route key={`index-${j}`} index element={child.element} />
            ) : (
              <Route key={child.path} path={child.path} element={child.element} />
            )
          )}
        </Route>
      );

      // Wrap the group in the matching guard. The guard renders <Outlet />,
      // so the nested `inner` route only renders once the guard allows it.
      if (route.protected) {
        return (
          <Route key={key} element={<ProtectedRoute />}>
            {inner}
          </Route>
        );
      }

      if (route.guest) {
        return (
          <Route key={key} element={<GuestRoute />}>
            {inner}
          </Route>
        );
      }

      // Public route — no guard.
      return cloneElement(inner, { key });
    });
}

export default function AppRouter() {
  return (
    <Routes>
      {renderRoutes(routes)}
    </Routes>
  );
}
