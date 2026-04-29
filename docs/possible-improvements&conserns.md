# Possible improvements & concerns

## System Design:
## Why we chose MySQL JSON here

We chose **MySQL JSON columns** because the “Rule Object” is:
- **schema-flexible** (new condition types/operators are expected over time)
- **owned by a coupon** (fits well as embedded configuration rather than separate relational entities)
- primarily used for **runtime evaluation** (we don’t currently need heavy SQL-side querying on rule internals)

At the same time, the rest of the domain is naturally relational:
- coupons have standard fields (`code`, `is_active`, `valid_from/to`, timestamps)
- we want **transactions** and straightforward CRUD

This is a practical **hybrid**: relational columns for what we query often + JSON for the evolving rule/action payload.

### When we should reconsider

If we later need queries like:
- “show all coupons targeting location=US”
- “count coupons with spend window_days=30”
- “segment coupons by tier operator/value”

…then we can either:
- add **generated columns** extracted from JSON and index them, or
- move to a **normalized `coupon_conditions` table** for those specific fields, keeping JSON as the source of truth.

## Security note: `/validate-rule` endpoint

The route:

`Route::post('validate-rule', ValidateRuleController::class)->name('validate-rule');`

currently lives inside:

`Route::middleware(['auth', 'verified'])->group(function () { ... });`

This is intentional: even though the endpoint only returns a boolean, rule evaluation is still **CPU work** and can be abused if exposed publicly.

### If we ever need this endpoint without `auth`

We should add **DoS/abuse protection**:
- **Rate limiting / throttling** (per IP, and ideally per API key / client identifier)
- **Request timeouts** and safe guards (max conditions count, max string lengths, max array sizes)
- **Payload size limits** (e.g. web server limits + application validation)
- **Caching** (optional): cache `{user, rule} -> isValid` for short TTL to reduce repeated evaluation
- **Logging/monitoring** for spikes and suspicious patterns

## Business constraints we enforce in the Rule Builder

To avoid ambiguous or contradictory coupon rules, we added a small set of business constraints and enforce them in **two layers**:
- **Backend validation (source of truth)**: rejects invalid payloads with 422.
- **UI guardrails (better UX)**: disables impossible choices and auto-aligns fields so users don’t hit validation errors.

### Tier condition

- **At most one** `tier` condition per coupon rule.
- Rationale: having multiple tier conditions (e.g. `tier = gold` AND `tier = silver`) is either contradictory or confusing for non-technical users.

### Location conditions

- **At most two** `location` conditions per rule.
- If there are **two** location conditions, their operators must differ:
  - one must be `in`
  - the other must be `not_in`
- Rationale: two location conditions are only useful to express an allowlist + blocklist; two of the same operator is redundant or misleading.

### Spend conditions (`Window (days)`=>`window_days`, User `Window (days)`=>`user.spend_by_window_days`)

- Multiple `spend` conditions are allowed (e.g. to define a range with `gt` + `lte`).
- If there is more than one `spend` condition and **`Window (days)` is used**, then:
  - **all** spend conditions must include `Window (days)`
  - **all** spend conditions must use the **same** `Window (days)` value
- Rationale: a “range” only makes sense when every spend comparison refers to the same time window.

### Additional safety in evaluation (windowed spend)

When evaluating windowed spend rules, we treat missing windows in user payload defensively:
- If an exact `Window (days)` value is not present in `user.spend_by_window_days`, we only allow a smaller-window fallback for **lower-bound** operators (`gt`, `gte`).
- For `lt`, `lte`, and `eq`, we fail safe (return `false`) because smaller-window spend cannot prove an upper-bound constraint for a larger window.

## Next improvements: coupon-level validity checks

Right now, `POST /validate-rule` validates a **user payload** against a **rule object** only.

It does **not** currently take into account coupon-level fields such as:
- `coupons.is_active` (whether the coupon is enabled)
- `coupons.valid_from` / `coupons.valid_to` (whether “today” is inside the allowed date range)

In a real system, the final “coupon is applicable” decision should combine:
- coupon enabled (`is_active = true`)
- coupon within validity window (if `valid_from` / `valid_to` are set)
- rule conditions satisfied by the user (`rules.conditions`)

