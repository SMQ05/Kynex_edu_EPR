{{--
    Shared styling for the student and parent portals.

    WHY THIS FILE EXISTS: Filament's compiled stylesheet ships layout and
    typography utilities but almost none of the rest. Measured against
    public/css/filament/filament/app.css, 678 of the 699 class names used across
    this project's Filament views are absent — the whole colour scale, grid
    columns, ring, spacing and hover variants. Classes like
    "bg-amber-50 ring-1 text-gray-500" therefore render as nothing, which is not
    a visible error, just a page that quietly looks broken. That is exactly what
    the parent dashboard did before it was rewritten to use this sheet.

    The real fix is a Filament custom theme built with Vite, which regenerates
    that stylesheet from these views. It needs node_modules in the image, so it
    is a Dockerfile change and an image rebuild. Until then, everything these
    portals need is defined here once.

    The accent colour is a CSS variable, so each panel matches its own Filament
    palette without a second copy of this file. Pass accent = indigo for the
    parent portal; omit it for the student portal's teal.

    CAUTION: do not put a Blade comment terminator anywhere inside this comment,
    including in prose or examples. Blade comments do not nest, so the first one
    ends the comment and everything after becomes live code. Doing so once made
    this partial include itself and exhaust PHP's memory, taking every page that
    used it to a 500.
--}}
@php
    $accents = [
        // 'rgb' is the 600 shade as a bare triplet, so rules can build their
        // own alphas with rgb(var(--portal-accent-rgb) / .12).
        'teal'   => ['600' => '#0d9488', '400' => '#2dd4bf', '50' => '#f0fdfa', 'ring' => '#14b8a6', 'deep' => '#0f3d2e', 'rgb' => '13 148 136'],
        'indigo' => ['600' => '#4f46e5', '400' => '#a5b4fc', '50' => '#eef2ff', 'ring' => '#6366f1', 'deep' => '#312e81', 'rgb' => '79 70 229'],
    ];
    $a = $accents[$accent ?? 'teal'] ?? $accents['teal'];
@endphp
@include('partials.fonts')
<style>
    :root {
        /* Shared with the public theme so a student moving from the marketing
           site into the portal sees one system, not two products. */
        --sp-sans:'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        --sp-mono:'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        --sp-line:#e8edf5;
        --sp-ink:#0f172a;
        --sp-muted:#64748b;
        --sp-faint:#94a3b8;
        --sp-r:16px;
        --portal-accent:       {{ $a['600'] }};
        --portal-accent-light: {{ $a['400'] }};
        --portal-accent-bg:    {{ $a['50'] }};
        --portal-accent-ring:  {{ $a['ring'] }};
        --portal-accent-deep:  {{ $a['deep'] }};
        --portal-accent-rgb:   {{ $a['rgb'] }};
    }
    /* Filament sets its own stack on body; scope ours to the page content so
       the portal matches the landing page without fighting the framework. */
    .fi-main, .fi-main input, .fi-main select, .fi-main textarea, .fi-main button {
        font-family: var(--sp-sans);
        font-variant-numeric: tabular-nums;
    }

    /* Filament's own card is 12px with its framework border; the redesign uses
       a 16px card on a cooler hairline. Scoped to .fi-main so panel chrome
       (sidebar, topbar, modals) is left alone. */
    .fi-main .fi-section {
        border-radius: var(--sp-r);
        border-color: var(--sp-line);
    }
    .dark .fi-main .fi-section { border-color: #2c353f; }

    /* ── Dashboard layout (redesign) ────────────────────────────── */
    .sp-top { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
    .sp-two-uneven { display: grid; gap: 1rem; grid-template-columns: 1fr; }
    @media (min-width: 1024px) { .sp-two-uneven { grid-template-columns: 1.15fr .85fr; } }

    /* ── "Right now" live card ──────────────────────────────────── */
    .sp-live {
        background: linear-gradient(140deg, var(--portal-accent-deep), var(--portal-accent));
        border-radius: var(--sp-r);
        padding: 1.4rem;
        display: flex;
        flex-direction: column;
    }
    .sp-live__eyebrow { font-size: .78rem; font-weight: 800; letter-spacing: .08em;
                        text-transform: uppercase; color: rgb(255 255 255 / .75); }
    .sp-live__title   { font-size: 1.5rem; font-weight: 800; color: #fff; letter-spacing: -.02em;
                        margin: .5rem 0 .25rem; line-height: 1.15; }
    .sp-live__meta    { font-size: .88rem; font-weight: 600; color: rgb(255 255 255 / .84);
                        margin-bottom: 1.1rem; }
    .sp-live__actions { display: flex; gap: .56rem; flex-wrap: wrap; margin-top: auto; }
    .sp-live__btn {
        display: inline-flex; align-items: center; min-height: 44px; padding: .7rem 1.1rem;
        border-radius: 11px; background: #fff; color: var(--portal-accent-deep);
        font-weight: 700; font-size: .87rem; text-decoration: none;
        transition: transform .15s ease, background .15s ease;
    }
    .sp-live__btn:hover { transform: translateY(-1px); }
    .sp-live__btn--ghost { background: rgb(255 255 255 / .16); color: #fff;
                           border: 1px solid rgb(255 255 255 / .3); }
    .sp-live__btn--ghost:hover { background: rgb(255 255 255 / .26); }

    /* ── "Due this week" rows ───────────────────────────────────── */
    .sp-due { display: flex; gap: .75rem; align-items: center; padding: .6rem 0;
              border-bottom: 1px solid #f1f5f9; }
    .sp-due:last-child { border-bottom: 0; }
    .sp-due__ic { width: 36px; height: 36px; border-radius: 10px; display: grid;
                  place-items: center; flex: none; }
    .sp-due__ic--late { background: #fee2e2; color: #dc2626; }
    .sp-due__ic--soon { background: #fef3c7; color: #d97706; }
    .sp-due__ic--calm { background: #e0f2fe; color: #0369a1; }
    .sp-due__body { min-width: 0; flex: 1; }
    .sp-due__title { font-size: .87rem; font-weight: 700; color: var(--sp-ink);
                     overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sp-due__when { font-size: .78rem; font-weight: 700; }
    .sp-due__when--late { color: #dc2626; }
    .sp-due__when--soon { color: #d97706; }
    .sp-due__when--calm { color: var(--sp-faint); }
    .sp-due__go {
        flex: none; font-size: .78rem; font-weight: 800; text-decoration: none;
        padding: .5rem .8rem; border-radius: 9px; background: #eff6ff; color: #2563eb;
    }
    .sp-due__go--solid { background: #2563eb; color: #fff; }
    .sp-due__go:hover { filter: brightness(.95); }

    /* ── Meters (attendance, per-subject) ───────────────────────── */
    .sp-meter { height: 7px; border-radius: 99px; background: #eef2f7; overflow: hidden;
                margin: .55rem 0 .4rem; }
    .sp-meter__fill { display: block; height: 100%; border-radius: 99px; }
    .sp-meter__fill--good { background: #22c55e; }
    .sp-meter__fill--warn { background: #f59e0b; }
    .sp-meter__fill--bad  { background: #ef4444; }

    .sp-stat__of { font-size: .95rem; font-weight: 600; color: var(--sp-faint); }
    .sp-stat__value--sm { font-size: 1.2rem; }
    .sp-delta { display: inline-block; margin-top: .45rem; font-size: .78rem; font-weight: 800; }
    .sp-delta--down { color: #dc2626; }
    .sp-delta--up   { color: #059669; }

    /* ── Subject performance rows ───────────────────────────────── */
    .sp-perf + .sp-perf { margin-top: .7rem; }
    .sp-perf__head { display: flex; justify-content: space-between; gap: .75rem;
                     font-size: .85rem; font-weight: 700; color: var(--sp-ink); }
    .sp-perf__pct { font-family: var(--sp-mono); font-weight: 600; }

    /* ── Study coach ────────────────────────────────────────────── */
    .sp-coach {
        background: linear-gradient(160deg, #0b1120, #1e1b4b);
        border-radius: var(--sp-r);
        padding: 1.25rem;
        color: #cbd5e1;
        display: flex;
        flex-direction: column;
    }
    .sp-coach__head { display: flex; align-items: center; gap: .56rem; margin-bottom: .85rem; }
    .sp-coach__mark { width: 26px; height: 26px; border-radius: 8px; flex: none;
                      background: linear-gradient(135deg, #4f46e5, #06b6d4); }
    .sp-coach__title { color: #fff; font-weight: 800; font-size: .95rem; }
    .sp-coach__body { font-size: .88rem; line-height: 1.6; margin: 0 0 .9rem; }
    .sp-coach__body strong { color: #fff; font-weight: 700; }
    .sp-coach__cta {
        display: flex; align-items: center; justify-content: center; min-height: 46px;
        border-radius: 11px; background: linear-gradient(135deg, #4f46e5, #0ea5e9);
        color: #fff; font-weight: 700; font-size: .9rem; text-decoration: none;
        margin-top: auto; transition: filter .15s ease;
    }
    .sp-coach__cta:hover { filter: brightness(1.08); color: #fff; }

    .dark .sp-meter { background: #1f2937; }
    .dark .sp-due { border-bottom-color: #1f2937; }
    .dark .sp-due__title { color: #f3f4f6; }

    /* ── Welcome banner (from the redesign) ─────────────────────── */
    .sp-hero {
        background: linear-gradient(140deg, #1e1b4b, var(--portal-accent-deep) 55%, var(--portal-accent));
        border-radius: var(--sp-r);
        padding: 1.5rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
        align-items: center;
    }
    .sp-hero__eyebrow { font-size: .78rem; font-weight: 800; letter-spacing: .08em;
                        text-transform: uppercase; color: rgb(255 255 255 / .72); }
    .sp-hero__title   { font-size: 1.6rem; font-weight: 800; color: #fff;
                        letter-spacing: -.02em; margin: .5rem 0 .35rem; line-height: 1.15; }
    .sp-hero__sub     { font-size: .9rem; color: rgb(255 255 255 / .8); margin: 0; }
    .sp-hero__side    { display: flex; gap: .625rem; flex-wrap: wrap; }
    @media (min-width: 700px) { .sp-hero__side { justify-self: end; } }
    .sp-hero__pill    { display: inline-flex; flex-direction: column; gap: .1rem;
                        background: rgb(255 255 255 / .13); border: 1px solid rgb(255 255 255 / .22);
                        border-radius: 12px; padding: .6rem .85rem; min-width: 6.5rem; }
    .sp-hero__pillv   { font-family: var(--sp-mono); font-size: 1.15rem; font-weight: 600; color: #fff; }
    .sp-hero__pilll   { font-size: .68rem; font-weight: 700; letter-spacing: .06em;
                        text-transform: uppercase; color: rgb(255 255 255 / .68); }

    /* ── Layout ─────────────────────────────────────────────────── */
    .sp-grid-stats { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
    .sp-grid-two   { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
    .sp-grid-cards { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

    /* ── Stat tiles ─────────────────────────────────────────────── */
    .sp-stat        { text-align: left; }
    .sp-stat__value { font-family: var(--sp-mono); font-size: 1.85rem; font-weight: 600;
                      line-height: 1.1; letter-spacing: -.03em; color: var(--sp-ink); }
    .sp-stat__label { margin-top: .38rem; font-size: .78rem; font-weight: 700; color: var(--sp-muted); }
    .sp-stat__hint  { display: block; margin-top: .3rem; font-size: .76rem;
                      font-weight: 600; color: var(--sp-faint); }
    .dark .sp-stat__value { color: #f8fafc; }
    .dark .sp-stat__label { color: #94a3b8; }
    .dark .sp-stat__hint  { color: #64748b; }

    /* ── Semantic colours ───────────────────────────────────────── */
    .sp-teal { color: var(--portal-accent); }  .dark .sp-teal { color: var(--portal-accent-light); }
    .sp-good { color: #16a34a; }  .dark .sp-good { color: #4ade80; }
    .sp-warn { color: #d97706; }  .dark .sp-warn { color: #fbbf24; }
    .sp-bad  { color: #dc2626; }  .dark .sp-bad  { color: #f87171; }
    .sp-ink  { color: #111827; }  .dark .sp-ink  { color: #f9fafb; }
    .sp-mute { color: #6b7280; }  .dark .sp-mute { color: #9ca3af; }

    /* ── List rows ──────────────────────────────────────────────── */
    .sp-row      { display: flex; align-items: flex-start; justify-content: space-between;
                   gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #f1f5f9; }
    .sp-row:last-child { border-bottom: 0; }
    .dark .sp-row      { border-bottom-color: #1f2937; }
    .sp-row__title { font-weight: 650; color: var(--sp-ink); }
    .dark .sp-row__title { color: #f9fafb; }
    .sp-row__meta  { font-size: .76rem; color: var(--sp-muted); }
    .dark .sp-row__meta { color: #9ca3af; }
    .sp-empty { padding: 1.5rem 0; text-align: center; font-size: .875rem; color: #6b7280; }

    /* ── Badges ─────────────────────────────────────────────────── */
    .sp-badge       { flex-shrink: 0; border-radius: 9999px; padding: .125rem .5rem;
                      font-size: .74rem; font-weight: 700; white-space: nowrap; }
    .sp-badge--due  { background: #f3f4f6; color: #374151; }
    .sp-badge--late { background: #fee2e2; color: #b91c1c; }
    .sp-badge--exam { background: #ccfbf1; color: #0f766e; }
    .sp-badge--ok   { background: #dcfce7; color: #15803d; }
    .sp-badge--wait { background: #fef3c7; color: #b45309; }
    .sp-badge--ai   { background: #ede9fe; color: #6d28d9; }
    .dark .sp-badge--due  { background: #1f2937; color: #d1d5db; }
    .dark .sp-badge--late { background: rgba(127,29,29,.4);  color: #fca5a5; }
    .dark .sp-badge--exam { background: rgba(19,78,74,.5);   color: #5eead4; }
    .dark .sp-badge--ok   { background: rgba(22,101,52,.4);  color: #86efac; }
    .dark .sp-badge--wait { background: rgba(146,64,14,.4);  color: #fcd34d; }
    .dark .sp-badge--ai   { background: rgba(76,29,149,.4);  color: #c4b5fd; }

    /* ── Tables ─────────────────────────────────────────────────── */
    .sp-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .sp-table th { text-align: left; font-weight: 700; font-size: .72rem;
                   text-transform: uppercase; letter-spacing: .03em;
                   color: var(--sp-muted); padding: .5rem .75rem;
                   border-bottom: 1px solid var(--sp-line); }
    .sp-table td { padding: .625rem .75rem; border-bottom: 1px solid #f1f5f9; color: #334155;
                   font-variant-numeric: tabular-nums; }
    .sp-table tr:last-child td { border-bottom: 0; }
    .sp-table td.sp-num, .sp-table th.sp-num { text-align: right; font-variant-numeric: tabular-nums; }
    .dark .sp-table th { color: #9ca3af; border-bottom-color: #374151; }
    .dark .sp-table td { color: #d1d5db; border-bottom-color: #1f2937; }
    .sp-table__scroll { overflow-x: auto; }

    /* ── Progress bar (attendance, fee paid ratio) ──────────────── */
    .sp-bar      { height: .5rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
    .dark .sp-bar { background: #374151; }
    .sp-bar__fill { height: 100%; border-radius: 9999px; background: var(--portal-accent); }

    /* ── Selectable list (lecture library) ──────────────────────── */
    /* Every class this list needs is defined here: items-center, gap-*,
       text-left, rounded-lg, px-*, space-y-*, overflow-y-auto and the
       max-h-[...] arbitrary value are ALL absent from Filament's compiled
       stylesheet, which is why the markup rendered centred and unspaced. */
    .sp-scroll { max-height: 32rem; overflow-y: auto; }
    .sp-list        { display: flex; flex-direction: column; gap: .25rem; }
    .sp-list__item  { display: block; width: 100%; text-align: left;
                      padding: .5rem .75rem; border-radius: .5rem;
                      border: 1px solid transparent; background: transparent;
                      cursor: pointer; transition: background .12s ease; }
    .sp-list__item:hover { background: #f9fafb; }
    .dark .sp-list__item:hover { background: #1f2937; }
    .sp-list__item--on   { background: var(--portal-accent-bg); border-color: var(--portal-accent-ring); }
    .dark .sp-list__item--on { background: rgba(19,78,74,.35); border-color: #14b8a6; }
    .sp-list__title { font-size: .875rem; font-weight: 500; color: #111827;
                      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dark .sp-list__title { color: #f9fafb; }
    .sp-list__meta  { display: flex; align-items: center; gap: .375rem;
                      margin-top: .125rem; font-size: .75rem; color: #6b7280; }
    .dark .sp-list__meta { color: #9ca3af; }

    /* ── Notes + chat ───────────────────────────────────────────── */
    .sp-notes  { white-space: pre-line; font-size: .875rem; line-height: 1.6;
                 color: #374151; }
    .dark .sp-notes { color: #d1d5db; }
    .sp-notes__h { font-size: .875rem; font-weight: 600; margin: 1rem 0 .375rem;
                   color: #111827; }
    .dark .sp-notes__h { color: #f9fafb; }

    .sp-chat        { display: flex; flex-direction: column; gap: .75rem;
                      max-height: 24rem; overflow-y: auto; margin-bottom: 1rem; }
    .sp-chat__row   { display: flex; }
    .sp-chat__row--me  { justify-content: flex-end; }
    .sp-chat__bubble   { max-width: 85%; padding: .5rem .75rem; font-size: .875rem;
                         border-radius: 1rem; white-space: pre-line; line-height: 1.5; }
    .sp-chat__bubble--me { background: var(--portal-accent); color: #fff; border-bottom-right-radius: .25rem; }
    .sp-chat__bubble--ai { background: #f3f4f6; color: #1f2937; border-bottom-left-radius: .25rem; }
    .dark .sp-chat__bubble--ai { background: #1f2937; color: #e5e7eb; }

    .sp-ask       { display: flex; gap: .5rem; align-items: flex-end; }
    .sp-ask__box  { flex: 1 1 auto; min-width: 0; width: 100%;
                    padding: .5rem .625rem; font-size: .875rem; line-height: 1.4;
                    border: 1px solid #d1d5db; border-radius: .5rem;
                    background: #fff; color: #111827; resize: vertical; }
    .dark .sp-ask__box { border-color: #374151; background: #111827; color: #f9fafb; }
    .sp-ask__box:focus { outline: 2px solid var(--portal-accent-ring); outline-offset: -1px; }

    .sp-note-box { padding: .75rem; border-radius: .5rem; font-size: .875rem;
                   background: #fffbeb; color: #92400e; }
    .dark .sp-note-box { background: rgba(120,53,15,.35); color: #fde68a; }

    /* ── Video frame ────────────────────────────────────────────── */
    .sp-video { position: relative; width: 100%; aspect-ratio: 16 / 9;
                border-radius: .5rem; overflow: hidden; background: #000; }
    .sp-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }

    /* Local click-to-play poster: no YouTube request until pressed. */
    .sp-video__poster {
        position: absolute; inset: 0; width: 100%; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .5rem; border: 0; cursor: pointer; color: #fff;
        background: linear-gradient(135deg, var(--portal-accent-deep) 0%, var(--portal-accent) 100%);
        transition: filter .15s ease;
    }
    .sp-video__poster:hover { filter: brightness(1.08); }
    .sp-video__play  { font-size: 2.25rem; line-height: 1; }
    .sp-video__label { font-size: .9375rem; font-weight: 600; }
    .sp-video__hint  { font-size: .75rem; opacity: .8; }

    /* ── ID card ────────────────────────────────────────────────── */
    .sp-card {
        max-width: 26rem; border-radius: 1rem; overflow: hidden;
        background: linear-gradient(135deg, var(--portal-accent-deep) 0%, var(--portal-accent) 100%);
        color: #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,.3);
    }
    .sp-card__head { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.2); }
    .sp-card__body { display: flex; gap: 1.25rem; padding: 1.25rem; align-items: flex-start; }
    .sp-card__photo { width: 5.5rem; height: 6.5rem; border-radius: .5rem; flex-shrink: 0;
                      background: rgba(255,255,255,.15); display: flex;
                      align-items: center; justify-content: center;
                      font-size: 2rem; font-weight: 700; }
    .sp-card__field { margin-bottom: .5rem; }
    .sp-card__key   { font-size: .625rem; text-transform: uppercase; letter-spacing: .06em;
                      opacity: .75; }
    .sp-card__val   { font-size: .875rem; font-weight: 600; }
    .sp-card__foot  { display: flex; gap: 1rem; align-items: center;
                      padding: 1rem 1.25rem; background: rgba(0,0,0,.18); }
    .sp-card__qr    { width: 5rem; height: 5rem; border-radius: .375rem;
                      background: #fff; padding: .25rem; flex-shrink: 0; }
    .sp-card__qr img { width: 100%; height: 100%; display: block; }
/* ── Revision cards ─────────────────────────────────────────── */
.sp-cards{display:flex;flex-direction:column;align-items:center;gap:.85rem}
.sp-card{position:relative;width:100%;min-height:11rem;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:.6rem;padding:1.75rem 1.5rem;text-align:center;
  border:1px solid rgb(var(--portal-accent-rgb)/.28);border-radius:1rem;cursor:pointer;
  background:linear-gradient(160deg,rgb(var(--portal-accent-rgb)/.07),rgb(var(--portal-accent-rgb)/.015));
  transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.sp-card:hover{transform:translateY(-2px);box-shadow:0 12px 28px -14px rgb(var(--portal-accent-rgb)/.5);
  border-color:rgb(var(--portal-accent-rgb)/.5)}
.sp-card--flipped{background:linear-gradient(160deg,rgb(var(--portal-accent-rgb)/.16),rgb(var(--portal-accent-rgb)/.04));
  border-color:rgb(var(--portal-accent-rgb)/.55)}
.sp-card__side{font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;
  color:rgb(var(--portal-accent-rgb))}
.sp-card__text{font-size:1.02rem;line-height:1.55;font-weight:600;color:#1f2937;max-width:44rem}
.sp-card--flipped .sp-card__text{font-weight:500}
.sp-card__hint{font-size:.7rem;color:#9ca3af}
.sp-cards__bar{display:flex;align-items:center;gap:.9rem}
.sp-cards__nav{width:2.1rem;height:2.1rem;border-radius:9999px;border:1px solid var(--sp-line);background:#fff;
  color:#6b7280;font-size:.95rem;line-height:1;cursor:pointer;transition:all .15s ease}
.sp-cards__nav:hover{border-color:rgb(var(--portal-accent-rgb)/.5);color:rgb(var(--portal-accent-rgb))}
.sp-cards__dots{display:flex;gap:.3rem}
.sp-cards__dot{width:.45rem;height:.45rem;border-radius:9999px;background:#e5e7eb;transition:all .15s ease}
.sp-cards__dot--seen{background:rgb(var(--portal-accent-rgb)/.4)}
.sp-cards__dot--on{background:rgb(var(--portal-accent-rgb));transform:scale(1.35)}
.sp-cards__count{font-size:.72rem;color:#9ca3af}

/* ── Practice quiz ──────────────────────────────────────────── */
.sp-quiz{display:flex;flex-direction:column;gap:.9rem}
.sp-quiz__q{padding:.95rem 1.05rem;border:1px solid var(--sp-line);border-radius:12px;background:#fff;
  transition:border-color .15s ease}
.sp-quiz__q--right{border-color:rgb(16 185 129/.45);background:rgb(16 185 129/.04)}
.sp-quiz__q--wrong{border-color:rgb(244 63 94/.4);background:rgb(244 63 94/.03)}
.sp-quiz__text{display:flex;gap:.6rem;font-size:.88rem;font-weight:600;color:#1f2937;line-height:1.55}
.sp-quiz__n{flex:none;width:1.4rem;height:1.4rem;display:inline-flex;align-items:center;justify-content:center;
  border-radius:9999px;background:rgb(var(--portal-accent-rgb)/.12);color:rgb(var(--portal-accent-rgb));
  font-size:.7rem;font-weight:700}
.sp-quiz__opts{display:flex;flex-direction:column;gap:.35rem;margin:.7rem 0 0 2rem}
.sp-opt{display:flex;align-items:center;gap:.55rem;padding:.55rem .75rem;border:1px solid var(--sp-line);
  border-radius:.55rem;font-size:.84rem;color:#374151;cursor:pointer;transition:all .13s ease}
.sp-opt:hover{border-color:rgb(var(--portal-accent-rgb)/.45);background:rgb(var(--portal-accent-rgb)/.04)}
.sp-opt input{accent-color:rgb(var(--portal-accent-rgb));flex:none}
.sp-opt span{flex:1}
.sp-opt--picked{border-color:rgb(var(--portal-accent-rgb)/.6);background:rgb(var(--portal-accent-rgb)/.07)}
.sp-opt--answer{border-color:rgb(16 185 129/.6);background:rgb(16 185 129/.09);color:#065f46;font-weight:600}
.sp-opt--miss{border-color:rgb(244 63 94/.55);background:rgb(244 63 94/.07);color:#9f1239}
.sp-opt__mark{width:1rem;height:1rem;flex:none}
.sp-opt--answer .sp-opt__mark{color:#059669}
.sp-opt--miss .sp-opt__mark{color:#e11d48}
.sp-quiz__why{margin:.7rem 0 0 2rem;padding:.55rem .75rem;border-radius:.5rem;background:#f9fafb;
  font-size:.8rem;line-height:1.6;color:#4b5563}
.sp-quiz__why strong{color:#1f2937}
.sp-quiz__actions{display:flex;align-items:center;gap:.85rem;margin-top:1.1rem;flex-wrap:wrap}
.sp-quiz__progress{font-size:.78rem;color:#9ca3af}
.sp-quiz__result{display:flex;align-items:center;gap:1rem;padding:.95rem 1.1rem;margin-bottom:1.1rem;
  border-radius:.85rem;border:1px solid}
.sp-quiz__result--ace{border-color:rgb(16 185 129/.4);background:rgb(16 185 129/.07)}
.sp-quiz__result--ok{border-color:rgb(var(--portal-accent-rgb)/.35);background:rgb(var(--portal-accent-rgb)/.06)}
.sp-quiz__result--low{border-color:rgb(245 158 11/.4);background:rgb(245 158 11/.07)}
.sp-quiz__score{font-family:var(--sp-mono);font-size:1.6rem;font-weight:600;color:var(--sp-ink);line-height:1;letter-spacing:-.02em}
.sp-quiz__score span{font-size:.95rem;font-weight:500;color:#9ca3af}
.sp-quiz__verdict{font-size:.86rem;font-weight:600;color:#1f2937}
.sp-quiz__best{font-size:.75rem;color:#6b7280;margin-top:.15rem}

.dark .sp-card__text{color:#f3f4f6}
.dark .sp-cards__nav{background:#1f2937;border-color:#374151;color:#9ca3af}
.dark .sp-cards__dot{background:#374151}
.dark .sp-quiz__q{background:#1f2937;border-color:#374151}
.dark .sp-quiz__text,.dark .sp-quiz__score,.dark .sp-quiz__verdict,.dark .sp-quiz__why strong{color:#f3f4f6}
.dark .sp-opt{border-color:#374151;color:#d1d5db}
.dark .sp-opt--answer{color:#6ee7b7}
.dark .sp-opt--miss{color:#fda4af}
.dark .sp-quiz__why{background:#111827;color:#9ca3af}
/* ── Parent: course progress and assessment timing ──────────── */
.pp-courses{display:grid;gap:.7rem;grid-template-columns:repeat(auto-fit,minmax(230px,1fr))}
.pp-course{padding:.7rem .8rem;border:1px solid var(--sp-line);border-radius:12px;background:#fff}
.pp-course__head{display:flex;align-items:baseline;justify-content:space-between;gap:.5rem}
.pp-course__name{font-size:.84rem;font-weight:700;color:var(--sp-ink)}
.pp-course__pct{font-size:.78rem;font-weight:700;color:rgb(var(--portal-accent-rgb))}
.pp-bar{height:.4rem;margin:.5rem 0 .45rem;border-radius:9999px;background:#eef2f7;overflow:hidden}
.pp-bar span{display:block;height:100%;border-radius:9999px;background:rgb(var(--portal-accent-rgb))}
.pp-course__meta{font-size:.71rem;color:#9ca3af}
.pp-course__now{margin-top:.3rem;font-size:.73rem;color:#4b5563}
.pp-course__now::before{content:'';display:inline-block;width:.4rem;height:.4rem;margin-right:.35rem;
  border-radius:9999px;background:rgb(var(--portal-accent-rgb));vertical-align:middle}
.pp-when{display:flex;flex-direction:column;align-items:flex-end;gap:.15rem;flex:none}
.pp-countdown{font-size:.66rem;color:#9ca3af}
.sp-badge--live{background:rgb(16 185 129/.13);color:#047857;font-weight:700}
.dark .pp-course{background:#1f2937;border-color:#374151}
.dark .pp-course__name{color:#f3f4f6}
.dark .pp-bar{background:#374151}
.dark .pp-course__now{color:#d1d5db}
/* ── Student: course map ────────────────────────────────────── */
.sp-focus{display:grid;gap:.9rem;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.sp-focus__item{display:flex;align-items:center;gap:.8rem}
.sp-focus__ring{--v:50;position:relative;width:3.1rem;height:3.1rem;flex:none;border-radius:9999px;
  background:conic-gradient(rgb(var(--portal-accent-rgb)) calc(var(--v)*1%), #eef2f7 0);
  display:grid;place-items:center}
.sp-focus__ring::after{content:'';position:absolute;inset:.32rem;border-radius:9999px;background:#fff}
.sp-focus__ring span{position:relative;z-index:1;font-size:.78rem;font-weight:700;color:#1f2937}
.sp-focus__ring small{font-size:.55rem;font-weight:600}
.sp-focus__subject{font-size:.86rem;font-weight:600;color:#1f2937}
.sp-focus__meta{font-size:.72rem;color:#9ca3af}

.sp-course__top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
.sp-course__subject{font-size:1rem;font-weight:700;color:#1f2937}
.sp-course__title{font-size:.79rem;color:#6b7280;margin-top:.1rem}
.sp-course__teacher{font-size:.72rem;color:#9ca3af;margin-top:.15rem}
.sp-course__right{display:flex;align-items:center;gap:1rem;flex:none}
.sp-course__standing{text-align:right}
.sp-course__standingv{display:block;font-family:var(--sp-mono);font-size:.98rem;font-weight:600;color:var(--sp-ink)}
.sp-course__standingl{display:block;font-size:.63rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em}
.sp-course__pct{font-family:var(--sp-mono);font-size:1.35rem;font-weight:600;color:rgb(var(--portal-accent-rgb));letter-spacing:-.02em}
.sp-course__meta{margin-top:.35rem;font-size:.76rem;color:#6b7280}
.sp-course__toggle{margin-top:.7rem;font-size:.78rem;font-weight:600;color:rgb(var(--portal-accent-rgb));
  background:none;border:0;padding:0;cursor:pointer}
.sp-course__toggle:hover{text-decoration:underline}

.sp-units{list-style:none;margin:1rem 0 0;padding:0;position:relative}
.sp-units::before{content:'';position:absolute;left:.42rem;top:.55rem;bottom:.55rem;width:1px;background:#e5e7eb}
.sp-unit{position:relative;display:flex;gap:.85rem;padding:.55rem 0}
.sp-unit__mark{position:relative;z-index:1;flex:none;width:.85rem;height:.85rem;margin-top:.28rem;
  border-radius:9999px;background:#fff;border:2px solid #d1d5db}
.sp-unit--completed .sp-unit__mark{background:rgb(var(--portal-accent-rgb));border-color:rgb(var(--portal-accent-rgb))}
.sp-unit--in_progress .sp-unit__mark{border-color:rgb(var(--portal-accent-rgb));
  box-shadow:0 0 0 3px rgb(var(--portal-accent-rgb)/.18)}
.sp-unit__body{min-width:0;flex:1}
.sp-unit__head{display:flex;align-items:baseline;justify-content:space-between;gap:.6rem}
.sp-unit__title{font-size:.85rem;font-weight:600;color:#1f2937}
.sp-unit--planned .sp-unit__title{font-weight:500;color:#6b7280}
.sp-unit__tag{flex:none;font-size:.65rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em}
.sp-unit--in_progress .sp-unit__tag{color:rgb(var(--portal-accent-rgb))}
.sp-unit__desc{margin-top:.2rem;font-size:.76rem;line-height:1.55;color:#6b7280}
.sp-unit__mat{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-top:.45rem;padding:.4rem .55rem;
  border:1px solid rgb(var(--portal-accent-rgb)/.25);border-radius:.5rem;text-decoration:none;
  background:rgb(var(--portal-accent-rgb)/.04);transition:all .14s ease}
.sp-unit__mat:hover{border-color:rgb(var(--portal-accent-rgb)/.55);background:rgb(var(--portal-accent-rgb)/.09)}
.sp-unit__mat svg{color:rgb(var(--portal-accent-rgb));flex:none}
.sp-unit__matname{font-size:.79rem;font-weight:600;color:#1f2937}
.sp-chip{font-size:.65rem;padding:.1rem .4rem;border-radius:9999px;background:rgb(var(--portal-accent-rgb)/.12);
  color:rgb(var(--portal-accent-rgb));font-weight:600}
.sp-chip--best{background:rgb(16 185 129/.14);color:#047857}

.dark .sp-focus__ring::after{background:#1f2937}
.dark .sp-focus__ring span,.dark .sp-focus__subject,.dark .sp-course__subject,
.dark .sp-course__standingv,.dark .sp-unit__title,.dark .sp-unit__matname{color:#f3f4f6}
.dark .sp-units::before{background:#374151}
.dark .sp-unit__mark{background:#1f2937;border-color:#4b5563}
</style>
