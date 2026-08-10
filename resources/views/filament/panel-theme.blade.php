{{--
  Panel-wide visual system for the admin and SaaS panels.

  WHY THIS IS ONE FILE, NOT NINE DASHBOARDS: the school panel has a single
  Dashboard page whose contents are chosen by role — sixteen different widget
  sets for SCHOOL_ADMIN, INSTITUTE_HEAD, MULTI_INSTITUTE_HEAD, TEACHER,
  ACCOUNTANT, LIBRARIAN, REGISTRAR, HOSTEL_WARDEN, NURSE, COUNSELOR,
  TRANSPORT_MANAGER, CAFETERIA_MANAGER, RECEPTIONIST, ATTENDANCE_CLERK, PARENT
  and STUDENT. Styling Filament's stat, section and table primitives here
  brings every one of those dashboards onto the redesign at once, and keeps
  them there when new widgets are added.

  Scoped to .fi-main throughout so panel chrome — sidebar, topbar, modals,
  dropdowns — keeps Filament's own behaviour and nothing shifts under it.
--}}
@include('partials.fonts')
<style>
  :root {
    --kx-sans:'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    --kx-mono:'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    --kx-line:#e8edf5;
    --kx-ink:#0f172a;
    --kx-muted:#64748b;
    --kx-faint:#94a3b8;
    --kx-r:16px;
  }

  /* Type. Figures tabular so every stat column and money table lines up. */
  .fi-main, .fi-main input, .fi-main select, .fi-main textarea, .fi-main button {
    font-family: var(--kx-sans);
    font-variant-numeric: tabular-nums;
  }

  /* Cards: the redesign's 16px on a cooler hairline. */
  .fi-main .fi-section,
  .fi-main .fi-wi-stats-overview-stat {
    border-radius: var(--kx-r);
    border-color: var(--kx-line);
  }
  .dark .fi-main .fi-section,
  .dark .fi-main .fi-wi-stats-overview-stat { border-color: #2c353f; }

  /* ── Stat widgets ────────────────────────────────────────────────
     Every role's dashboard opens with a StatsOverview. The redesign's
     recipe: small heavy muted label, large tight mono figure, quiet hint. */
  .fi-main .fi-wi-stats-overview-stat-label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--kx-muted);
    letter-spacing: 0;
  }
  .fi-main .fi-wi-stats-overview-stat-value {
    font-family: var(--kx-mono);
    font-size: 1.85rem;
    font-weight: 600;
    letter-spacing: -.03em;
    line-height: 1.1;
    color: var(--kx-ink);
  }
  .dark .fi-main .fi-wi-stats-overview-stat-value { color: #f8fafc; }
  .fi-main .fi-wi-stats-overview-stat-description {
    font-size: .76rem;
    font-weight: 600;
    color: var(--kx-faint);
  }

  /* ── Tables ──────────────────────────────────────────────────────
     Table widgets carry most of the operational detail — fee collections,
     visitors, issued books, leave requests. */
  .fi-main .fi-ta-header-cell-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: var(--kx-muted);
  }
  .fi-main .fi-ta-text-item-label,
  .fi-main .fi-ta-record .fi-ta-cell { font-size: .86rem; }
  .fi-main .fi-ta-ctn { border-radius: var(--kx-r); border-color: var(--kx-line); }
  .dark .fi-main .fi-ta-ctn { border-color: #2c353f; }

  /* ── Headings ────────────────────────────────────────────────────
     The redesign runs section headings heavier and tighter than Filament. */
  .fi-main .fi-section-header-heading,
  .fi-main .fi-wi-stats-overview-stat-label,
  .fi-main .fi-header-heading {
    letter-spacing: -.015em;
  }
  .fi-main .fi-header-heading { font-weight: 800; }
  .fi-main .fi-section-header-heading { font-weight: 700; }

  /* Primary actions pick up the redesign's radius and weight. */
  .fi-main .fi-btn { border-radius: 11px; font-weight: 700; }
</style>
