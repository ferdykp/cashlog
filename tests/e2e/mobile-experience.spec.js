import { expect, test } from "@playwright/test";

async function signIn(page) {
    await page.goto("/login");
    await page.getByLabel("Alamat Email").fill("test@example.com");
    await page.locator('input[type="password"]').fill("password");
    await page.getByRole("button", { name: "Masuk" }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

test("mobile navigation opens the quick composer and preselects an expense", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await signIn(page);
    const nav = page.getByRole("navigation", { name: "Navigasi utama" });
    await expect(nav.getByText("Home")).toBeVisible();
    await expect(nav.getByText("Aktivitas")).toBeVisible();
    await expect(nav.getByText("Rencana")).toBeVisible();
    await expect(nav.getByText("Lainnya")).toBeVisible();
    await nav.getByRole("button", { name: "Catat" }).click();
    await expect(page.locator("#quick-action-sheet")).toBeVisible();
    await page.locator(".toast-container").evaluate((element) => element.remove()).catch(() => {});
    await page.waitForTimeout(300);
    await page.screenshot({ path: "docs/uiux/screenshots/quick-actions-390.png" });
    await page.getByRole("button", { name: /Pengeluaran/ }).click();
    await expect(page).toHaveURL(/transactions\/create\?type=expense/);
    await expect(page.getByLabel("Jumlah transaksi")).toBeVisible();
    await expect(page.getByLabel("Jumlah transaksi")).toBeFocused();
});

test("authenticated routes fit the requested viewport widths", async ({ page }) => {
    test.setTimeout(90000);
    await signIn(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/dashboard");
    await page.locator(".cashlog-home").waitFor({ state: "visible" });
    await page.waitForTimeout(550);
    await page.locator(".toast-container").evaluate((element) => element.remove()).catch(() => {});
    await page.evaluate(async () => {
        for (let y = 0; y < document.documentElement.scrollHeight; y += innerHeight) {
            scrollTo(0, y);
            await new Promise((resolve) => setTimeout(resolve, 130));
        }
        scrollTo(0, 0);
    });
    await page.waitForTimeout(500);
    const routes = [
        "/dashboard", "/transactions", "/transactions/create", "/accounts",
        "/budget", "/goals", "/investasi", "/bills", "/report", "/scan",
        "/kolaborasi", "/settings", "/settings/whatsapp", "/categories",
    ];
    const widths = [320, 375, 390, 430, 768, 1024, 1280, 1440];
    const overflows = [];

    for (const width of widths) {
        await page.setViewportSize({ width, height: 900 });
        for (const route of routes) {
            await page.goto(route);
            await page.locator(".app-page").first().waitFor({ state: "visible", timeout: 5000 }).catch(() => {});
            if (width === 390 || width === 1440) {
                await page.waitForTimeout(800);
                await page.screenshot({ path: `docs/uiux-next/screenshots/${route.replaceAll("/", "_").replace(/^_/, "home")}-${width}.png` });
            }
            const metrics = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                page: document.documentElement.scrollWidth,
                offenders: [...document.querySelectorAll("body *")]
                    .map((element) => ({
                        tag: element.tagName,
                        className: typeof element.className === "string" ? element.className.slice(0, 100) : "",
                        text: element.innerText?.slice(0, 45),
                        right: Math.round(element.getBoundingClientRect().right),
                    }))
                    .filter((element) => element.right > document.documentElement.clientWidth + 2)
                    .slice(0, 5),
            }));
            if (metrics.page > metrics.viewport + 1) overflows.push({ width, route, ...metrics });
        }
    }

    expect(overflows, JSON.stringify(overflows)).toEqual([]);
});
