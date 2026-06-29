import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "@/hooks/useAuth";
import { ROUTES } from "@/routes/path";

/**
 * Guards routes that require an authenticated session.
 * Unauthenticated visitors are bounced to the login page, remembering where
 * they were headed (via location state) so login can send them back.
 */
export function ProtectedRoute() {
    const { isAuthenticated } = useAuth();
    const location = useLocation();

    if (!isAuthenticated) {
        return (
            <Navigate
                to={ROUTES.auth.login}
                replace
                state={{ from: location }}
            />
        );
    }

    return <Outlet />;
}

/**
 * Guards guest-only routes (login, register, forgot/reset password).
 * Already-authenticated users are redirected away — back to wherever a
 * ProtectedRoute originally sent them, or the dashboard by default.
 */
export function GuestRoute() {
    const { isAuthenticated } = useAuth();
    const location = useLocation();

    if (isAuthenticated) {
        const redirectTo = location.state?.from?.pathname ?? ROUTES.dashboard;
        return <Navigate to={redirectTo} replace />;
    }

    return <Outlet />;
}
