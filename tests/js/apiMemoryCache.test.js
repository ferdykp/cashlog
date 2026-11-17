import test from "node:test";
import assert from "node:assert/strict";
import { createApiMemoryCache } from "../../resources/js/lib/apiMemoryCache.js";

test("API memory cache returns a live entry until its TTL expires", () => {
    let now = 100;
    const cache = createApiMemoryCache({ ttlMs: 50, now: () => now });
    const response = { items: [{ amount: "12.50" }] };

    cache.set("user-scope:transactions", response, 200);
    assert.deepEqual(cache.get("user-scope:transactions"), {
        savedAt: 100,
        data: response,
        status: 200,
    });

    now = 151;
    assert.equal(cache.get("user-scope:transactions"), null);
    assert.equal(cache.size, 0);
});

test("API memory cache can be cleared after logout or a mutation", () => {
    const cache = createApiMemoryCache();
    cache.set("user-one:accounts", { data: [] });
    cache.set("user-one:transactions", { data: [] });

    cache.clear();

    assert.equal(cache.size, 0);
    assert.equal(cache.get("user-one:accounts"), null);
});

test("API memory cache does not retain invalid response values", () => {
    const cache = createApiMemoryCache();
    cache.set("empty", null);
    cache.set("primitive", "sensitive string");

    assert.equal(cache.size, 0);
});

test("API memory cache evicts the oldest response at its entry limit", () => {
    const cache = createApiMemoryCache({ maxEntries: 2 });
    cache.set("first", { value: 1 });
    cache.set("second", { value: 2 });

    const evicted = cache.set("third", { value: 3 });

    assert.deepEqual(evicted, ["first"]);
    assert.equal(cache.get("first"), null);
    assert.equal(cache.get("second").data.value, 2);
    assert.equal(cache.get("third").data.value, 3);
    assert.equal(cache.size, 2);
});
