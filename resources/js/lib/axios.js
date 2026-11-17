// resources/js/lib/axios.js
import axios from "axios";
import { reactive } from "vue";
import { createApiMemoryCache } from "@/lib/apiMemoryCache";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "",
    timeout: 20000,
    withCredentials: false,
    headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
    },
});

const CACHE_TTL_MS = 30 * 60 * 1000;
const CACHE_NAMESPACE = "cashlog:api-memory:v1:";
const inFlightRefreshes = new Map();
const inFlightMisses = new Map();
const reactiveCache = new Map();
const apiResponseCache = createApiMemoryCache({ ttlMs: CACHE_TTL_MS });
let cacheEpoch = 0;
let pendingRefreshes = 0;
let hasStaleData = false;
let failedRefreshes = 0;
let refreshFailed = false;
const getRequest = api.get.bind(api);

function cacheStatus() {
    return {
        pending: pendingRefreshes,
        hasStale: hasStaleData,
        offline: refreshFailed && pendingRefreshes === 0,
    };
}

function emitCacheStatus() {
    if (typeof window !== "undefined") {
        window.dispatchEvent(
            new CustomEvent("ledger:api-cache-status", {
                detail: cacheStatus(),
            }),
        );
    }
}

function stableValue(value) {
    if (Array.isArray(value)) return value.map(stableValue);
    if (!value || typeof value !== "object") return value;
    if (value instanceof URLSearchParams) {
        return [...value.entries()].sort(([a], [b]) => a.localeCompare(b));
    }
    return Object.fromEntries(
        Object.keys(value)
            .sort()
            .map((key) => [key, stableValue(value[key])]),
    );
}

async function digest(value) {
    if (globalThis.crypto?.subtle) {
        const bytes = new TextEncoder().encode(value);
        const hash = await globalThis.crypto.subtle.digest("SHA-256", bytes);
        return [...new Uint8Array(hash)]
            .map((part) => part.toString(16).padStart(2, "0"))
            .join("");
    }
    let hash = 14695981039346656037n;
    for (const char of value) {
        hash ^= BigInt(char.codePointAt(0));
        hash = BigInt.asUintN(64, hash * 1099511628211n);
    }
    return hash.toString(16);
}

async function storageKey(token, url, config) {
    const [scope, request] = await Promise.all([
        digest(token),
        digest(
            JSON.stringify([
                url,
                stableValue(config?.params ?? null),
                config?.baseURL ?? "",
            ]),
        ),
    ]);
    return `${CACHE_NAMESPACE}${scope}:${request}`;
}

function patchReactive(target, source) {
    if (Array.isArray(target) && Array.isArray(source)) {
        target.splice(0, target.length, ...source);
        return;
    }
    if (
        target &&
        source &&
        typeof target === "object" &&
        typeof source === "object" &&
        !Array.isArray(target) &&
        !Array.isArray(source)
    ) {
        for (const key of Object.keys(target)) {
            if (!(key in source)) delete target[key];
        }
        for (const [key, value] of Object.entries(source)) {
            if (
                target[key] &&
                value &&
                typeof target[key] === "object" &&
                typeof value === "object" &&
                Array.isArray(target[key]) === Array.isArray(value)
            ) {
                patchReactive(target[key], value);
            } else {
                target[key] = value;
            }
        }
    }
}

function clearApiCache() {
    cacheEpoch++;
    reactiveCache.clear();
    apiResponseCache.clear();
    hasStaleData = false;
    failedRefreshes = 0;
    refreshFailed = false;
    emitCacheStatus();
}

function cachedResponse(data, config, status = 200) {
    return {
        data,
        status,
        statusText: "OK",
        headers: {},
        config,
        request: undefined,
    };
}

async function getWithSessionCache(url, config = {}) {
    const token =
        typeof localStorage !== "undefined"
            ? localStorage.getItem("token")
            : "";
    const canCache =
        token &&
        typeof window !== "undefined" &&
        url.startsWith("/api/") &&
        !config.responseType;
    if (!canCache) return getRequest(url, config);

    const key = await storageKey(token, url, config);
    if (token !== localStorage.getItem("token")) return getRequest(url, config);
    const cached = apiResponseCache.get(key);
    if (!cached) {
        reactiveCache.delete(key);
        if (!inFlightMisses.has(key)) {
            const epochAtStart = cacheEpoch;
            const request = getRequest(url, config)
                .then((response) => {
                    if (
                        epochAtStart === cacheEpoch &&
                        token === localStorage.getItem("token")
                    ) {
                        const evicted = apiResponseCache.set(
                            key,
                            response.data,
                            response.status,
                        );
                        for (const evictedKey of evicted) {
                            reactiveCache.delete(evictedKey);
                        }
                    }
                    return response;
                })
                .finally(() => inFlightMisses.delete(key));
            inFlightMisses.set(key, request);
        }
        return inFlightMisses.get(key);
    }

    let reactiveData = reactiveCache.get(key);
    if (!reactiveData) {
        reactiveData = reactive(cached.data);
        reactiveCache.set(key, reactiveData);
    }
    const requestConfig = { ...config };
    const epochAtStart = cacheEpoch;
    hasStaleData = true;
    emitCacheStatus();

    if (!inFlightRefreshes.has(key)) {
        if (pendingRefreshes === 0) failedRefreshes = 0;
        pendingRefreshes++;
        emitCacheStatus();
        const refresh = getRequest(url, requestConfig)
            .then((response) => {
                if (
                    epochAtStart === cacheEpoch &&
                    token === localStorage.getItem("token")
                ) {
                    patchReactive(reactiveData, response.data);
                    const evicted = apiResponseCache.set(
                        key,
                        response.data,
                        response.status,
                    );
                    for (const evictedKey of evicted) {
                        reactiveCache.delete(evictedKey);
                    }
                }
            })
            .catch((error) => {
                if (error?.code !== "ERR_CANCELED") failedRefreshes++;
            })
            .finally(() => {
                inFlightRefreshes.delete(key);
                pendingRefreshes = Math.max(0, pendingRefreshes - 1);
                if (pendingRefreshes === 0) {
                    refreshFailed = failedRefreshes > 0;
                    if (!refreshFailed) hasStaleData = false;
                }
                emitCacheStatus();
            });
        inFlightRefreshes.set(key, refresh);
    }

    return cachedResponse(reactiveData, config, cached.status || 200);
}

api.get = getWithSessionCache;
api.interceptors.request.use((config) => {
    if (config.method && config.method.toLowerCase() !== "get") clearApiCache();
    return config;
});

if (typeof window !== "undefined") {
    window.addEventListener("ledger:session-cleared", clearApiCache);
    window.addEventListener("ledger:data-changed", clearApiCache);
}

export function getApiCacheStatus() {
    return cacheStatus();
}

// Otomatis menempelkan Bearer Token dari localStorage
api.interceptors.request.use((config) => {
    const token = localStorage.getItem("token");
    config.ledgerToken = token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Tangani token expired / unauthorized
api.interceptors.response.use(
    (response) => {
        if (response.config.ledgerToken !== localStorage.getItem("token")) {
            return Promise.reject(new axios.CanceledError("Session changed"));
        }
        return response;
    },
    (error) => {
        const activeSession =
            error.config?.ledgerToken === localStorage.getItem("token");
        if (
            error.response?.status === 401 &&
            activeSession &&
            error.config?.ledgerToken
        ) {
            window.dispatchEvent(new Event("ledger:unauthorized"));
        } else if (
            error.config?.method === "get" &&
            activeSession &&
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
