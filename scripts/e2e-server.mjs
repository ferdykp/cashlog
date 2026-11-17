import { spawn, spawnSync } from "node:child_process";
import { randomUUID } from "node:crypto";
import { rm, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { tmpdir } from "node:os";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const appPort = Number(process.env.PLAYWRIGHT_APP_PORT || 8765);
const vitePort = Number(process.env.PLAYWRIGHT_VITE_PORT || 5178);
const databasePath = path.join(tmpdir(), `cashlog-e2e-${randomUUID()}.sqlite`);
const hotFile = path.join(tmpdir(), `cashlog-e2e-hot-${randomUUID()}`);
const databaseEnv = {
    ...process.env,
    APP_ENV: "local",
    APP_KEY: "base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=",
    APP_URL: `http://127.0.0.1:${appPort}`,
    DB_CONNECTION: "sqlite",
    DB_DATABASE: databasePath,
    DB_URL: "",
    CACHE_STORE: "array",
    SESSION_DRIVER: "database",
    SESSION_SECURE_COOKIE: "false",
    SESSION_SAME_SITE: "lax",
    QUEUE_CONNECTION: "sync",
    MAIL_MAILER: "array",
    LOG_CHANNEL: "stderr",
    SANCTUM_STATEFUL_DOMAINS: `localhost,127.0.0.1:${appPort}`,
    CASHLOG_E2E_HOT_FILE: hotFile,
};

await writeFile(databasePath, "");
const migrate = spawnSync(
    "php",
    ["artisan", "migrate:fresh", "--force", "--no-interaction"],
    { cwd: root, env: databaseEnv, stdio: "inherit" },
);

if (migrate.status !== 0) {
    await rm(databasePath, { force: true });
    process.exit(migrate.status || 1);
}

const seed = spawnSync(
    "php",
    ["artisan", "db:seed", "--class=E2ESeeder", "--force", "--no-interaction"],
    { cwd: root, env: databaseEnv, stdio: "inherit" },
);

if (seed.status !== 0) {
    await rm(databasePath, { force: true });
    process.exit(seed.status || 1);
}

const vite = spawn(
    "npm",
    [
        "run",
        "dev",
        "--",
        "--host",
        "127.0.0.1",
        "--port",
        String(vitePort),
        "--strictPort",
    ],
    {
        cwd: root,
        env: {
            ...databaseEnv,
            VITE_API_BASE_URL: "",
            VITE_PORT: String(vitePort),
        },
        stdio: "inherit",
    },
);
const laravel = spawn(
    "php",
    [
        "artisan",
        "serve",
        "--no-reload",
        "--host",
        "127.0.0.1",
        "--port",
        String(appPort),
    ],
    { cwd: root, env: databaseEnv, stdio: "inherit" },
);
let stopping = false;

async function stop(signal = "SIGTERM") {
    if (stopping) return;
    stopping = true;
    vite.kill(signal);
    laravel.kill(signal);
    await Promise.all([
        rm(databasePath, { force: true }),
        rm(hotFile, { force: true }),
    ]);
}

process.on("SIGINT", () => void stop("SIGINT"));
process.on("SIGTERM", () => void stop("SIGTERM"));

for (const child of [vite, laravel]) {
    child.on("exit", (code) => {
        if (!stopping && code !== 0) {
            console.error(`E2E server process exited with code ${code}`);
            void stop();
            process.exitCode = code || 1;
        }
    });
}

async function waitUntilReady(url) {
    const until = Date.now() + 60_000;
    while (Date.now() < until && !stopping) {
        try {
            const response = await fetch(url);
            if (response.ok) return;
        } catch {
            // The child server has not opened its port yet.
        }
        await new Promise((resolve) => setTimeout(resolve, 300));
    }
    throw new Error(`Timed out waiting for ${url}`);
}

try {
    await Promise.all([
        waitUntilReady(`http://127.0.0.1:${appPort}/up`),
        waitUntilReady(`http://127.0.0.1:${vitePort}/@vite/client`),
    ]);
    console.log(`Cashlog E2E app ready at http://127.0.0.1:${appPort}`);
} catch (error) {
    console.error(error);
    await stop();
    process.exit(1);
}
