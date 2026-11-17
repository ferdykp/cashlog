/**
 * Small page-lifetime cache for API responses.
 *
 * Values intentionally stay in JavaScript memory and are never written to
 * localStorage, sessionStorage, IndexedDB, or the Service Worker cache.
 */
export function createApiMemoryCache({
    ttlMs = 30 * 60 * 1000,
    maxEntries = 50,
    now = Date.now,
} = {}) {
    const entries = new Map();

    return {
        get(key) {
            const entry = entries.get(key);
            if (!entry) return null;
            if (now() - entry.savedAt > ttlMs) {
                entries.delete(key);
                return null;
            }
            return entry;
        },

        set(key, data, status = 200) {
            if (!data || typeof data !== "object" || maxEntries < 1) return [];
            const evicted = [];
            if (entries.has(key)) entries.delete(key);
            while (entries.size >= maxEntries) {
                const oldestKey = entries.keys().next().value;
                entries.delete(oldestKey);
                evicted.push(oldestKey);
            }
            entries.set(key, { savedAt: now(), data, status });
            return evicted;
        },

        clear() {
            entries.clear();
        },

        get size() {
            return entries.size;
        },
    };
}
