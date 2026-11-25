# WhatsApp transaction processing

## Deployment

1. Deploy the backend and run `php artisan migrate` before accepting new webhooks. The migration adds `reply_text` and `replied_at` to the existing message inbox, without dropping financial data.
2. Set `EVOLUTION_WEBHOOK_SECRET` and configure the provider to send the identical value in `X-Ledger-Webhook-Secret`. An unset secret returns HTTP 503; a missing/wrong header returns HTTP 401.
3. Configure provider retry/backoff for non-2xx responses. Delivery failures return 502. Processing failures leave the inbox row unprocessed and retryable.
4. Keep provider message IDs stable across retries. Missing IDs are rejected. Outbound echoes, groups, non-message events, and opaque `@lid` identities are ignored; phone-based messages use `@s.whatsapp.net` or a numeric sender.
5. Pair through the existing OTP page. Legacy `/integrations/whatsapp/pairing` now returns 410, and `LINK` chat commands direct the user to OTP. Disconnect clears pending conversation and verification state.

## Consistency

- A unique inbox ID prevents the same webhook from becoming a second financial operation.
- The user and conversation rows are locked while processing a draft/confirmation. Saving the transaction, clearing the draft and storing the reply commit together.
- Replies are sent after financial processing commits. A delivery retry uses the stored reply instead of processing the transaction again.
- Replies are at-least-once: a process crash after the provider accepts a message but before `replied_at` commits can repeat the reply, but not the financial transaction.
- Two distinct confirmation messages are serialized by user/session locking; the second sees an idle session.
- Provider calls remain synchronous with bounded timeouts. Large deployments should dispatch delivery through a queue; they currently depend on provider retries, not an installed scheduler.

## Parsing and validation

Examples: `gaji 1.000.000 bca`, `gaji 1,5jt bca`, `makan 35k gopay`, `transfer 50rb bca ke gopay`. Transfer accounts follow text order with `ke`/`to` between them. The confirmation includes both source and destination. Ambiguous values require clarification. For income/expense messages, the enabled AI fallback can interpret an obvious typo in a description and correct account/category typos only to values in the user's list; canonical values are shown in the draft for confirmation. Transfers use fixed local rules, so AI cannot alter their direction. Invalid/timeout responses are ignored.

Optional transaction fields can be written as directives after the transaction: `catatan: ...`, `kategori: [nama persis]`, and `tanggal: ...`. Example: `makan 35rb gopay catatan: makan siang kategori: Makanan tanggal: 24-11-2026`. The category must match one of the user's expense/income categories; transfers do not use categories. Dates accept `hari ini`, `kemarin`, `DD-MM-YYYY`, or `YYYY-MM-DD`; omitted dates default to today. The `kategori`, `kategori pengeluaran`, and `kategori pemasukan` commands list available categories.

Financial information commands are `dompet`, `saldo [nama dompet]`, `ringkasan hari ini`, `ringkasan bulan ini`, and `riwayat` (also `riwayat transaksi`, `transaksi terakhir`, and `5 transaksi terakhir`). Every transaction draft must be confirmed with `1` or cancelled with `3`; to change a draft, cancel it and send the corrected message.

Web forms and WhatsApp share `TransactionRules`. Validate again at confirmation to catch changed/deleted categories, unavailable accounts, invalid amounts and dates. No external WhatsApp messages are sent by the automated tests.

## Verification

`php artisan test` includes parser, webhook authentication, echo/group filtering, processing retry, reply retry, duplicate confirmations, expired/cancelled drafts, disconnect, and AI failure cases. Tests use SQLite and mocked providers; production database concurrency and real Evolution delivery still need deployment smoke tests.
