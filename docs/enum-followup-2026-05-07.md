# Enum follow-up items — 2026-05-07

Deferred items identified during the enum-casting safety hotfix.

## Docket 1 — EasyPaisa / JazzCash as first-class FeePaymentMethod cases

**Status:** Deferred  
**Context:** During the 2026-05-07 enum-canonicalisation migration, `easypaisa` and `jazzcash` payment method values found in the `fee_payments` table were remapped to the generic `online` case. This mapping is correct for correctness (no crashes) but loses reconciliation specificity.

**Consideration:** Pakistan-specific mobile wallet payments (EasyPaisa, JazzCash) are a primary payment channel in this market. If fee reconciliation, payment reports, or banking exports ever need to distinguish them, the generic `online` bucket is insufficient.

**Proposed future action:** Add `case EasyPaisa = 'easypaisa'` and `case JazzCash = 'jazzcash'` to `App\Enums\FeePaymentMethod`, then write a reverse migration to restore the specific values from `online` records created after the original import window. Coordinate with the school admin to confirm which `online` records were originally EasyPaisa vs. JazzCash before running.

**Files to change when promoted:**
- `app/Enums/FeePaymentMethod.php`
- New tenant migration for the data update
- Fee-collection and fee-report UI (add display labels for the new cases)
