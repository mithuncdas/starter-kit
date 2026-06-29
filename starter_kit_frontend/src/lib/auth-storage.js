// Single source of truth for the persisted auth session.
// The shape mirrors the login response's `data` object: { user, tokens }.

const AUTH_KEY = "auth";

export const getStoredAuth = () => {
    try {
        const raw = localStorage.getItem(AUTH_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
};

export const setStoredAuth = (auth) => {
    if (auth && auth.tokens?.access_token) {
        localStorage.setItem(AUTH_KEY, JSON.stringify(auth));
    } else {
        localStorage.removeItem(AUTH_KEY);
    }
};

export const clearStoredAuth = () => {
    localStorage.removeItem(AUTH_KEY);
};

export const getAccessToken = () => getStoredAuth()?.tokens?.access_token ?? null;
export const getRefreshToken = () => getStoredAuth()?.tokens?.refresh_token ?? null;
