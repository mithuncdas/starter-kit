export const ROUTES = {
    auth: {
        login: '/login',
        forgotPassword: '/forgot-password',
        resetPassword: '/reset-password',
    },
    dashboard: '/dashboard',
    profile: {
        index: '/profile',
        updateProfile: '/profile/edit',
        updatePassword: '/profile/change-password'
    },
    todos: {
        index: '/todos',
        create: '/todos/create',
        edit: '/todos/:id/edit',
        delete: '/todos/:id/delete'
    },
    errors: {
        unauthorized: '/401',
        forbidden: '/403',
        notFound: '/404',
        serverError: '/500',
        serviceUnavailable: '/503',
    }
}