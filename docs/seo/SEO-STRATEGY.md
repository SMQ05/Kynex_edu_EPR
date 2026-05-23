# KynexEdu — SEO Strategy & Roadmap

_Owner: Kynex Solutions · Primary site: https://edu.kynexsolutions.com (launching soon) · Company: https://kynexsolutions.com · Market: Pakistan-first · Last updated: 2026-05-22_

---

## 0. Honest expectations (read this first)

Ranking page-1 for the bare head term **"school management system"** globally is a 12–24 month effort — those SERPs are owned by high-authority sites (PowerSchool, Fedena, eSkooly, Classe365, Gradelink) with thousands of backlinks. A brand-new domain **cannot** shortcut that.

What **is** winnable in 3–9 months, and where we focus first:

1. **Brand terms** — "KynexEdu", "Kynex Solutions school software". (Win in weeks once indexed.)
2. **Geo-modified money terms** — "school management system **in Pakistan**", "school ERP **Pakistan**", "school management software **Lahore/Karachi/Islamabad**". (Lower competition, high commercial intent.)
3. **Feature long-tail** — "school **fee** management software", "online **admission** system for schools", "school software **with WhatsApp**", "**biometric** attendance software for schools", "**madrasa** management system". (Easiest wins, buyers with intent.)
4. **The head terms** ("school management system", "school ERP") come later, earned by authority built from 1–3 + backlinks + content depth.

> Strategy in one line: **win Pakistan + long-tail + feature/comparison content now, build authority, then climb to the head terms.**

---

## 0.1 Domain strategy — use the parent as the SEO hub (decided)

KynexEdu is a **product/subdomain of Kynex Solutions**: app at `edu.kynexsolutions.com`, parent company at `kynexsolutions.com`.

Google treats a subdomain as a largely **separate site**, so `edu.` does not inherit the parent's authority automatically. **But the parent is a static site that is already SEO-built** (`/home/kynex_solutions/Documents/kynexmain`, Hostinger): it already has `kynexsolutions.com/school-management-system`, `sitemap.xml` + `sitemap-images.xml`, a full `robots.txt` (AI crawlers allowed), `llms.txt`, a `blog/`, and its own `SEO-STRATEGY.md`. The hybrid architecture is therefore **already the right one and already in place** — we don't re-architect; we divide labour and stop the two sites competing.

### Role split (single source of truth per query)
| Surface | Owns | Optimize for |
|---|---|---|
| `kynexsolutions.com/school-management-system` (+ future `/features`, `/pricing`, `/blog`, comparison pages) | **SEO ranking** — generic & geo terms ("school management system in Pakistan", "school ERP", feature/long-tail) | Inherits parent authority; targets the keywords; CTAs → `edu.` |
| `edu.kynexsolutions.com/` (this app's landing) | **Brand + product + conversion** | "KynexEdu", brand/app intent, register/login. Title now leads with the **brand**, not the generic keyword |

### ⚠ Keyword cannibalization — fixed
The `edu.` landing was briefly titled "School Management System & ERP in Pakistan | KynexEdu", competing with the parent page for the same term. **Fixed:** the `edu.` home now leads with the brand ("KynexEdu — School Management System & ERP Software"). Rule going forward: **only the parent page targets the generic "school management system [Pakistan]" query**; `edu.` and any new product page lead with the brand or a *different* long-tail to avoid two same-brand pages fighting one term.

### Make the parent pass authority to the product (do now)
1. **Parent → product links** already exist (`index.html` and the SMS page link to `edu.`). Keep them **dofollow** with descriptive anchors ("KynexEdu school management system", not "click here"); add a clear nav "Product → KynexEdu" if not present.
2. **Product → parent**: edu footer links to `kynexsolutions.com` ✅. Keep brand/NAP identical on both.
3. **Google Search Console:** add `kynexsolutions.com` as a **Domain property** — it covers the root *and* `edu.` subdomain together. Submit both sitemaps (parent's + edu's).
4. **Align the two strategy docs:** this file (edu/app) and `kynexmain/SEO-STRATEGY.md` (parent) — parent leads SEO content; this one covers the app/brand surfaces + the in-app/tenant SEO (CMS schema, etc.).
5. **One brand story:** every tenant school's "Powered by KynexEdu" dofollow link → `edu.` is a compounding backlink asset.

---

## 1. Keyword strategy

> ⚠️ Volumes below are **planning estimates** (no live keyword API was available). Validate with Google Keyword Planner / DataForSEO / Ahrefs before committing. "Difficulty" is relative (Low/Med/High).

### Tier 1 — Brand (priority: capture immediately)
| Keyword | Intent | Difficulty |
|---|---|---|
| kynexedu | Navigational | Low |
| kynex solutions school software | Navigational | Low |
| kynexedu login / pricing / register | Navigational | Low |

### Tier 2 — Geo money terms (priority: primary commercial targets)
| Keyword | Intent | Difficulty | Target page |
|---|---|---|---|
| school management system in pakistan | Commercial | Med | Home / `/school-management-system-pakistan` |
| school management software pakistan | Commercial | Med | Home / dedicated |
| school erp pakistan | Commercial | Med | `/school-erp` |
| best school management software in pakistan | Commercial | Med | `/best-school-management-software-pakistan` (listicle/landing) |
| cloud based school management system | Commercial | Med | Home |
| school management software in lahore / karachi / islamabad | Local commercial | Low–Med | City landing pages |

### Tier 3 — Feature long-tail (priority: content engine, fastest wins)
| Keyword | Target page |
|---|---|
| school fee management software | `/features/fee-management` |
| online admission system for schools | `/features/online-admissions` |
| school management software with whatsapp | `/features/whatsapp-notifications` |
| biometric attendance system for schools | `/features/biometric-attendance` |
| student attendance management system | `/features/attendance` |
| online exam / entry test software for schools | `/features/online-entry-test` |
| school management system with parent portal | `/features/parent-portal` |
| madrasa management system / software | `/solutions/madrasa` |
| multi campus school management system | `/solutions/multi-campus` |
| school management system with mobile app | `/features/mobile-apps` |

### Tier 4 — Comparison / alternative (highest conversion: 4–7%)
| Keyword | Target page |
|---|---|
| eskooly alternative | `/eskooly-alternative` |
| fedena alternative | `/fedena-alternative` |
| kynexedu vs eskooly / vs fedena | `/compare/kynexedu-vs-eskooly` etc. |
| best school management software 2026 | `/best-school-management-software` |

### Tier 5 — Informational (top-of-funnel blog, builds topical authority + AI citations)
"what is a school management system", "how to choose a school ERP", "benefits of school management software", "how to digitize school admissions", "school fee collection best practices Pakistan".

---

## 2. Site architecture (SaaS template)

Build these as **real, indexable pages** (anchors on one page don't rank individually). Priority order:

```
/                                   Home (✅ live — optimized)
/features                           Overview hub
  /features/online-admissions       ← Tier 3
  /features/online-entry-test
  /features/fee-management
  /features/attendance (+biometric)
  /features/exams-results
  /features/parent-portal
  /features/whatsapp-notifications
  /features/mobile-apps
/solutions
  /solutions/madrasa                ← segment
  /solutions/multi-campus
  /solutions/private-schools
/pricing                            High-intent; add real plans + FAQ + Offer schema
/school-management-system-pakistan  ← Tier 2 money page
/school-erp                         ← head-term hub (build last, link-rich)
/compare/kynexedu-vs-{competitor}   ← Tier 4
/{competitor}-alternative           ← Tier 4
/blog                               ← Tier 5 engine
/about  /contact                    Trust + E-E-A-T + LocalBusiness schema
```

Internal linking: every feature/blog page links up to its hub and across to `/pricing` and the relevant Tier-2 money page. Hubs link down to children. This passes authority to money pages.

---

## 3. On-page SEO — already implemented ✅

Done in this codebase (verify after deploy):

- **Landing `<head>`** — keyword title (`School Management System & ERP Software in Pakistan | KynexEdu`), meta description, `canonical`, robots directives, hreflang (en-pk + x-default), full **Open Graph** + **Twitter** cards. → `resources/views/portal/landing.blade.php`
- **JSON-LD** — `Organization`, `WebSite`, `SoftwareApplication` (with `offers`, `featureList`) + a `FAQPage`. No fake ratings (Google penalizes those).
- **Visible FAQ section** — 7 keyword-targeted Q&As (matches the FAQ schema; great for featured snippets + AI Overviews).
- **CMS layout** — canonical, OG/Twitter, and `EducationalOrganization` schema for every tenant school site. → `resources/views/cms/layout.blade.php`
- **robots.txt** — allows search + AI crawlers, blocks dashboards, references the sitemap. → `public/robots.txt`
- **sitemap.xml** — homepage now; **extend it with every new page**. → `public/sitemap.xml`
- **llms.txt** — structured product summary for AI assistants (GEO). → `public/llms.txt`
- **OG image** — editable source `public/images/og-kynexedu.svg`. ⚠️ **Launch task:** generate the PNG: `node scripts/generate-og-image.mjs` (Chromium is already in the prod image), outputting `public/images/og-kynexedu.png`.

### On-page rules for every NEW page
- One `<h1>` containing the target keyword; logical `<h2>/<h3>`.
- Title ≤ 60 chars, description ≤ 155, both with the target keyword + a benefit.
- Self-referencing canonical; descriptive `alt` on every image; WebP/AVIF; lazy-load below the fold.
- 800–1,500 words on money/feature pages; lead with a direct answer (AI Overview bait); include an FAQ block + FAQPage schema.
- Add `BreadcrumbList` schema on deep pages.

---

## 4. Technical foundation

- **Core Web Vitals targets:** LCP < 2.5s, INP < 200ms, CLS < 0.1. The new pages are light, but **Tailwind/Alpine via CDN** on the CMS adds blocking JS — for production, compile Tailwind to a static CSS file and self-host Alpine (defer). Use the bunny.net font with `preconnect` (already done) or self-host.
- **HTTPS + HSTS** everywhere (cert provisioning already in progress per repo).
- **Mobile-first** — done in the redesign.
- **Per-tenant SEO (Phase 2):** make `robots.txt` and `sitemap.xml` host-aware so each tenant/custom domain emits its own sitemap (currently static for the marketing domain).
- **404/500** branded pages (exist), correct status codes.

---

## 5. Off-page & launch roadmap

### Phase 0 — Launch day (do immediately when edu goes live)
1. **Generate the OG PNG** (`node scripts/generate-og-image.mjs`).
2. **Google Search Console** — verify both `edu.kynexsolutions.com` and `kynexsolutions.com`; submit `sitemap.xml`; request indexing of the home page.
3. **Bing Webmaster Tools** — verify + submit sitemap (also feeds ChatGPT/Copilot).
4. **Google Analytics 4** (or Plausible) installed; mark "Register" as a conversion.
5. **Google Business Profile** for Kynex Solutions (address/phone/category "Software company") — unlocks local "school software near me / in Lahore" visibility.

### Phase 1 — Foundation (weeks 1–4)
- Build `/pricing`, `/features` hub + top 3 feature pages, `/about`, `/contact` (with LocalBusiness + EducationalOrganization schema, real NAP).
- Consistent **NAP** (Name, Address, Phone) across site + GBP + directories.
- First citations: Pakistani business directories, software listing sites (Capterra, G2, GetApp, SourceForge, Crunchbase, Software Suggest, Trustpilot profile).

### Phase 2 — Expansion (weeks 5–12)
- Remaining feature/solution pages + the `/school-management-system-pakistan` money page.
- Launch `/blog` with 6–8 Tier-5 posts (1–2/week).
- 2–3 comparison/alternative pages (Tier 4 — highest converting).
- Start link building: guest posts on Pakistani EdTech/education blogs, school associations, partnerships with schools (a "Powered by KynexEdu" backlink from every tenant CMS site is a real, compounding asset — ensure it's a `dofollow` link to edu.kynexsolutions.com).

### Phase 3 — Scale (months 4–6)
- City landing pages (Lahore/Karachi/Islamabad/Faisalabad).
- Customer case studies (E-E-A-T: real schools, names, results, photos).
- Outreach for backlinks: EdTech roundups, press in Pakistani tech media (TechJuice, ProPakistani), education forums.
- Reviews drive: ask onboarded schools for Google + Capterra/G2 reviews (review signals + rich snippets).

### Phase 4 — Authority (months 7–12)
- Thought-leadership content, original data (e.g., "State of school digitization in Pakistan" survey → linkable asset).
- Now build/strengthen the `/school-erp` head-term hub and push for the competitive head terms.

---

## 6. GEO — AI search (ChatGPT, Perplexity, Google AI Overviews, Copilot)

- `llms.txt` ✅ shipped; `FAQPage` + `SoftwareApplication` schema ✅ help AI cite us.
- Write content in **answer-first** style (definition/short answer in the first 1–2 sentences, then detail) — this is what AI engines extract.
- Get listed on **Capterra/G2/SourceForge** and in "best school management software Pakistan" listicles — AI engines lean heavily on these for SaaS recommendations.
- Keep Bing index healthy (ChatGPT search + Copilot use Bing).

---

## 7. KPI targets

| Metric | Launch | 3 months | 6 months | 12 months |
|---|---|---|---|---|
| Indexed pages | 1 | 15–25 | 40–60 | 80+ |
| Ranking keywords (top 100) | 0 | 40–80 | 150–300 | 500+ |
| Brand terms (top 3) | — | ✅ | ✅ | ✅ |
| Tier-2/3 keywords in top 10 | 0 | 3–8 | 15–30 | 40+ |
| Monthly organic visits | 0 | 150–400 | 800–2k | 3k–8k |
| Referring domains | 0 | 10–20 | 30–60 | 80–150 |
| Core Web Vitals (mobile) | pass | pass | pass | pass |

_Targets assume consistent execution (≈1–2 content pieces/week + steady link building). Pakistan-market SaaS; adjust after the first 90 days of real GSC data._

---

## 8. Quick launch checklist

- [ ] Deploy the redesigned/optimized landing to edu.kynexsolutions.com
- [ ] `node scripts/generate-og-image.mjs` → commit `og-kynexedu.png`
- [ ] Set `APP_URL=https://edu.kynexsolutions.com` (so `asset()`/`url()` emit correct absolute URLs)
- [ ] Verify GSC + Bing, submit sitemap, request home indexing
- [ ] Install GA4 + mark Register as conversion
- [ ] Create/verify Google Business Profile (Kynex Solutions)
- [ ] Validate: [Rich Results Test](https://search.google.com/test/rich-results), [Schema validator](https://validator.schema.org), [OG debugger](https://www.opengraph.xyz)
- [ ] Run PageSpeed Insights on the live home page; fix any CWV reds
- [ ] List on Capterra / G2 / GetApp / SourceForge / Crunchbase
- [ ] Build the Phase-1 pages (pricing, features, about/contact)
