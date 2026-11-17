import { expect, test } from "@playwright/test";

async function signIn(page) {
    await page.goto("/login");
    await page.getByLabel("Alamat Email").fill("test@example.com");
    await page.locator('input[type="password"]').fill("password");
    const loginResponse = page.waitForResponse(
        (response) =>
            response.url().endsWith("/api/session/login") &&
            response.request().method() === "POST",
    );
    await page.getByRole("button", { name: "Masuk" }).click();
    const response = await loginResponse;
    if (!response.ok()) {
        throw new Error(
            `Session login failed (${response.status()}): ${await response.text()}`,
        );
    }
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(
        page.getByRole("heading", { name: "Uangmu, lebih jelas." }),
    ).toBeVisible();
}

test("login page renders at phone and desktop widths", async ({ page }) => {
    for (const width of [390, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto("/login");
        await expect(page.getByLabel("Alamat Email")).toBeVisible();
        await page.screenshot({ path: `docs/uiux-next/screenshots/login-${width}.png` });
    }
});

test("SPA login uses an HttpOnly session and survives a direct reload", async ({
    page,
    context,
}) => {
    await signIn(page);

    expect(await page.evaluate(() => localStorage.getItem("token"))).toBeNull();
    const sessionCookie = (await context.cookies()).find((cookie) =>
        cookie.name.endsWith("-session"),
    );
    expect(sessionCookie).toBeTruthy();
    expect(sessionCookie.httpOnly).toBe(true);

    await page.goto("/transactions");
    await expect(
        page.getByRole("heading", { name: "Transaksi" }),
    ).toBeVisible();
    await page.reload();
    await expect(page).toHaveURL(/\/transactions$/);
    await expect(
        page.getByRole("heading", { name: "Transaksi" }),
    ).toBeVisible();
});

test("an unauthenticated protected deep link redirects to login", async ({
    page,
}) => {
    await page.goto("/transactions");
    await expect(page).toHaveURL(/\/login\?redirect=/);
});

test("logout invalidates the browser session", async ({ page }) => {
    await signIn(page);
    await page.goto("/settings");
    await page.getByRole("button", { name: "Keluar dari Akun" }).click();
    await expect(page).toHaveURL(/\/login$/);
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/\/login\?redirect=/);
});
