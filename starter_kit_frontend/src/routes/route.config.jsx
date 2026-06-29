import { AuthLayout } from "@/components/layout/AuthLayout";
import { ROUTES } from "./path";
import Login from "@/pages/auth/Login";
import ForgotPassword from "@/pages/auth/ForgotPassword";
import ResetPassword from "@/pages/auth/ResetPassword";

// import Dashboard from "@/pages/dashboard/Dashboard";
// import { AppLayout } from "@/components/layout/AppLayout";
// import ProfileView from "@/pages/profile/ProfileView";
// import PasswordUpdate from "@/pages/profile/PasswordUpdate";
// import ProfileEdit from "@/pages/profile/ProfileEdit";
// import TodoCreate from "@/pages/todo/TodoCreate";
// import TodoList from "@/pages/todo/TodoList";
// import TodoEdit from "@/pages/todo/TodoEdit";
// import Unauthorized from "@/pages/errors/Unauthorized";
// import Forbidden from "@/pages/errors/Forbidden";
import NotFound from "@/pages/errors/NotFound";
// import ServerError from "@/pages/errors/ServerError";
// import ServiceUnavailable from "@/pages/errors/ServiceUnavailable";

const RouteConfig = [
    
    {
        path: '/',
        element: <AuthLayout />,
        protected: false,
        guest: true,
        children: [
            {index:true, element: <Login/>},
            { path: ROUTES.auth.login, element: <Login /> },
            { path: ROUTES.auth.forgotPassword, element: <ForgotPassword /> },
            { path: ROUTES.auth.resetPassword, element: <ResetPassword /> },
        ],
    },
    {
        // Catch-all 404 — rendered without any auth guard (see wrapElement).
        path: '*',
        element: <NotFound />,
    },
];

export default RouteConfig;