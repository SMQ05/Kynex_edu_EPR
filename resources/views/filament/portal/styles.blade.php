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
        'teal'   => ['600' => '#0d9488', '400' => '#2dd4bf', '50' => '#f0fdfa', 'ring' => '#14b8a6', 'deep' => '#0f3d2e'],
        'indigo' => ['600' => '#4f46e5', '400' => '#a5b4fc', '50' => '#eef2ff', 'ring' => '#6366f1', 'deep' => '#312e81'],
    ];
    $a = $accents[$accent ?? 'teal'] ?? $accents['teal'];
@endphp
<style>
    :root {
        --portal-accent:       {{ $a['600'] }};
        --portal-accent-light: {{ $a['400'] }};
        --portal-accent-bg:    {{ $a['50'] }};
        --portal-accent-ring:  {{ $a['ring'] }};
        --portal-accent-deep:  {{ $a['deep'] }};
    }
    /* ── Layout ─────────────────────────────────────────────────── */
    .sp-grid-stats { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
    .sp-grid-two   { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
    .sp-grid-cards { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

    /* ── Stat tiles ─────────────────────────────────────────────── */
    .sp-stat        { text-align: center; }
    .sp-stat__value { font-size: 1.875rem; font-weight: 700; line-height: 1.25; }
    .sp-stat__label { margin-top: .25rem; font-size: .875rem; color: #6b7280; }
    .sp-stat__hint  { display: block; font-size: .75rem; color: #9ca3af; }
    .dark .sp-stat__label { color: #9ca3af; }
    .dark .sp-stat__hint  { color: #6b7280; }

    /* ── Semantic colours ───────────────────────────────────────── */
    .sp-teal { color: var(--portal-accent); }  .dark .sp-teal { color: var(--portal-accent-light); }
    .sp-good { color: #16a34a; }  .dark .sp-good { color: #4ade80; }
    .sp-warn { color: #d97706; }  .dark .sp-warn { color: #fbbf24; }
    .sp-bad  { color: #dc2626; }  .dark .sp-bad  { color: #f87171; }
    .sp-ink  { color: #111827; }  .dark .sp-ink  { color: #f9fafb; }
    .sp-mute { color: #6b7280; }  .dark .sp-mute { color: #9ca3af; }

    /* ── List rows ──────────────────────────────────────────────── */
    .sp-row      { display: flex; align-items: flex-start; justify-content: space-between;
                   gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #f3f4f6; }
    .sp-row:last-child { border-bottom: 0; }
    .dark .sp-row      { border-bottom-color: #1f2937; }
    .sp-row__title { font-weight: 500; color: #111827; }
    .dark .sp-row__title { color: #f9fafb; }
    .sp-row__meta  { font-size: .75rem; color: #6b7280; }
    .dark .sp-row__meta { color: #9ca3af; }
    .sp-empty { padding: 1.5rem 0; text-align: center; font-size: .875rem; color: #6b7280; }

    /* ── Badges ─────────────────────────────────────────────────── */
    .sp-badge       { flex-shrink: 0; border-radius: 9999px; padding: .125rem .5rem;
                      font-size: .75rem; font-weight: 500; white-space: nowrap; }
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
    .sp-table th { text-align: left; font-weight: 600; font-size: .75rem;
                   text-transform: uppercase; letter-spacing: .03em;
                   color: #6b7280; padding: .5rem .75rem;
                   border-bottom: 1px solid #e5e7eb; }
    .sp-table td { padding: .625rem .75rem; border-bottom: 1px solid #f3f4f6; color: #374151; }
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
</style>
