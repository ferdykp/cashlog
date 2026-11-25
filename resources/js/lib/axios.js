import axios from "axios";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "",
    timeout: 20000,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
    },
});

let csrfCookieRequest = null;

function ensureCsrfCookie() {
    if (!csrfCookieRequest) {
        csrfCookieRequest = api.get("/sanctum/csrf-cookie").catch((error) => {
            csrfCookieRequest = null;
            throw error;
        });
    }

    return csrfCookieRequest;
}

api.interceptors.request.use((config) => {
    const method = config.method?.toLowerCase();
    const needsCsrf = method && !["get", "head", "options"].includes(method);
    const isCsrfRequest = config.url?.includes("/sanctum/csrf-cookie");

    if (needsCsrf && !isCsrfRequest) {
        return ensureCsrfCookie().then(() => config);
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (
            error.response?.status === 419 &&
            error.config &&
            !error.config._csrfRetry
        ) {
            csrfCookieRequest = null;
            error.config._csrfRetry = true;

            return ensureCsrfCookie().then(() => api.request(error.config));
        }

        const publicAuthRequest = [
            "/api/login",
            "/api/session/login",
            "/api/register",
            "/api/forgot-password",
            "/api/reset-password",
        ].some((path) => error.config?.url?.includes(path));

        if (error.response?.status === 401 && !publicAuthRequest) {
            window.dispatchEvent(new Event("ledger:unauthorized"));
        } else if (
            error.config?.method === "get" &&
            !axios.isCancel(error)
        ) {
            window.dispatchEvent(
                new CustomEvent("ledger:request-error", {
                    detail: "Data belum dapat dimuat. Periksa koneksi lalu coba lagi.",
                }),
            );
        }

        return Promise.reject(error);
    },
);

export default api;
