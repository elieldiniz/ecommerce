---
paths:
  - 'app/Actions/Refunds/**'
---

# Refunds

## CreateRefund requires a real User — there is no anonymous/system refund
`App\Actions\Refunds\CreateRefund::execute()` takes `User $user` (non-nullable) and `string $ipAddress` (non-nullable) — `refunds.user_id`, `audit_logs.user_id` and `audit_logs.ip_address` are all `NOT NULL` in the schema, unlike PLAN.md's earlier `?User $user`/`user: null` wording for automatic refunds (e.g. RF-33 duplicate-payment auto-refund in `ProcessSafe2PayWebhookJob`). For system-triggered refunds with no acting operator, resolve a dedicated "system" user (fixed email `sistema@digitallock.com.br`, `is_active = false`, `role_id` = admin, `firstOrCreate`'d on demand) and pass `'127.0.0.1'` as the ip address instead of trying to pass null. Do not add nullability to `refunds.user_id`/`audit_logs.user_id` — that's a schema change out of this feature's scope.
