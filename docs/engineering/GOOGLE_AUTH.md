# Google authentication setup

Google sign-in uses the OAuth authorization code flow and then requires a one-time email code for a first-time Google registration or for linking Google to an existing manual account. Subsequent sign-ins with the linked Google identity do not require an OTP.

## Google Cloud configuration

Create an OAuth 2.0 Web application client and configure:

- Authorized JavaScript origin: the deployed Cashlog origin, for example `https://ledger.example.com`.
- Authorized redirect URI: the exact value of `GOOGLE_REDIRECT_URI`, for example `https://ledger.example.com/auth/google/callback`.
- Scopes requested by Cashlog: `openid`, `email`, and `profile`.
- Branding homepage: `https://ledger.fdevsite.cloud/`.
- Branding privacy policy: `https://ledger.fdevsite.cloud/privacy-policy`.

Set the client credentials on the server, never in frontend build variables:

```dotenv
GOOGLE_CLIENT_ID=your-web-client-id
GOOGLE_CLIENT_SECRET=your-web-client-secret
GOOGLE_REDIRECT_URI=https://ledger.example.com/auth/google/callback
LEDGER_PUBLIC_URL=https://ledger.example.com
```

`LEDGER_PUBLIC_URL` supplies the absolute logo and Ledger link used in verification emails. The PNG logo must be publicly reachable at `/pwa-192x192.png` on that host.

## Email delivery

OTP delivery uses Laravel's configured mailer. Configure `MAIL_MAILER`, SMTP or provider credentials, and `MAIL_FROM_ADDRESS` in production. The repository's example environment uses the `log` mailer, which is for local development and writes message contents to the application log instead of delivering email.

## Account matching

Cashlog compares trimmed, lowercased email addresses. It does not normalize provider-specific aliases such as Gmail dots or `+tag` suffixes. An existing manual account is never linked automatically from a matching email: the user must sign in to that account, start linking from Settings, and verify the OTP sent to the matching address.

## Gmail transaction import

Gmail transaction import is a separate OAuth grant from Google sign-in. It requests `openid`, `email`, `profile`, and the restricted `https://www.googleapis.com/auth/gmail.readonly` scope. The Gmail mailbox can differ from the Ledger login email. The callback is `/auth/gmail/callback`; for production the expected URI is `https://ledger.fdevsite.cloud/auth/gmail/callback`.

Configure `GMAIL_CLIENT_ID` and `GMAIL_CLIENT_SECRET` for the Gmail OAuth client (they fall back to the existing Google client values if omitted), plus `GMAIL_REDIRECT_URI`. Register the exact callback URI in Google Cloud and enable Gmail API. `GMAIL_INTEGRATION_ENABLED` defaults to `false`; keep it off until OAuth verification and Google's required security assessment are complete. Gmail `gmail.readonly` is a restricted scope, and processing restricted data through the server requires the assessment. This assessment may introduce cost, so public activation is not part of the zero-cost implementation. The public privacy policy must describe Gmail data use and retention.

Set `GMAIL_BANK_SENDERS` to an explicit comma-separated mapping of verified sender domains to supported provider keys, for example `verified-domain.example:bca,another-verified-domain.example:mandiri`. Obtain sender domains from each bank's official documentation or verified notification samples; do not guess or accept user-controlled domains. The parser checks this exact domain allowlist before retrieving an email body. Supported provider keys are `bca`, `blu`, `mandiri`, and `seabank`.

By default, the Laravel scheduler queues connected Gmail accounts every five minutes; this is periodic sync, not instant delivery. Run the scheduler and a supervised Laravel queue worker using the database queue. Push updates are optional and require Cloud Pub/Sub, an HTTPS OIDC-authenticated subscription, and periodic watch renewal. Review current Google Cloud pricing before enabling any push infrastructure.

The application stores OAuth tokens encrypted and never stores full message bodies. It checks new inbox message headers first and fetches the body only for exact allowlisted sender domains. Parsed imports are always held for user review; no confidence score can post transactions automatically. Message IDs are unique per email connection to prevent duplicate imports. Wallets can have an optional provider label, but no account number or suffix is collected. Disconnecting a mailbox revokes its Google token and deletes its connection, mappings, and pending imports; already posted Ledger transactions remain.
