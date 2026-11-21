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
