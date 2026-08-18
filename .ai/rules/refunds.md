---
paths:
  - 'app/Actions/Refunds/**'
---

# Refunds

## CreateRefund requires a real User — there is no anonymous/system refund
`App\Actions\Refunds\CreateRefund::execute()` takes `User $user` (non-nullable) and `string $ipAddress` (non-nullable) — `refunds.user_id`, `audit_logs.user_id` and `audit_logs.ip_address` are all `NOT NULL` in the schema, unlike PLAN.md's earlier `?User $user`/`user: null` wording for automatic refunds (e.g. RF-33 duplicate-payment auto-refund in `ProcessSafe2PayWebhookJob`). For system-triggered refunds with no acting operator, resolve a dedicated "system" user (fixed email `sistema@digitallock.com.br`, `is_active = false`, `role_id` = admin, `firstOrCreate`'d on demand) and pass `'127.0.0.1'` as the ip address instead of trying to pass null. Do not add nullability to `refunds.user_id`/`audit_logs.user_id` — that's a schema change out of this feature's scope.

## RequestPixRefund/RequestCardRefund take ipAddress — PLAN.md omits it
`RequestPixRefund::execute()` and `RequestCardRefund::execute()` both take `string $ipAddress` as a 4th parameter (`Payment $payment, RefundReason $reason, User $user, string $ipAddress`), unlike PLAN.md's T23/T24 wording which lists only 3 params. This mirrors the same PLAN.md staleness already documented for `CreateRefund` (non-nullable `user_id`/`ip_address` in schema) — since these actions call `CreateRefund::execute()` internally and it requires a non-nullable `$ipAddress`, the caller must supply one. Sync failures against Safe2Pay (CT-02/CT-03: HTTP failed response or `Illuminate\Http\Client\ConnectionException`) are swallowed and recorded as an extra `audit_logs` row (`action = 'pix_refund_sync_call_failed'`/`'card_refund_sync_call_failed'`) — the action still returns the already-created `Refund`, it never rethrows. Precondition rejection (`payment->status->slug !== 'authorized'`) throws `App\Exceptions\Refunds\RefundNotAllowedException` before any `refunds` row is created. No `RequestBoletoRefund` exists or should be created (Q-05).
