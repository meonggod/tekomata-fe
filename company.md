# tekomata

<!-- Background context for refining tekomata's task drafts into [STORY] taskdocs.
     ClickUp target (configured in env.yaml): workspace 9018840713 · Kanban board · list 901818578961 -->

## What the company does
tekomata is a **WhatsApp-native AI assistant, sold as multi-tenant SaaS**, that answers a
business's questions about its own catalog. The product holds each tenant's products,
warehouses, per-warehouse stock, and price tiers, and lets the business **ask in plain
language over WhatsApp** — "do we have product X, where, how much stock, and at what price?" —
and get an instant answer.

**v1 is read-only knowledge.** The assistant looks things up and answers; it does **not** take
orders, reserve stock, or move money. Data is **owned by tekomata** (imported by the tenant —
tekomata is the source of truth, not an integration into the tenant's ERP).

**First client (the wedge):** a distributor with **multiple warehouses**, **stock tracked per
warehouse**, and **multiple price tiers** for different buyers. The owner can't keep this in his
head, so he WhatsApps the assistant himself to check product / stock / location / price. The
first user of the assistant is the **business owner querying his own data**, not his customers.

**Target market:** small/medium/large businesses with a sales / customer-service / support
function that already live on WhatsApp. Profit is mandatory — see the pricing model below.

## Pricing model (how tekomata makes money)
**Pay-per-use is the meter; the subscription tier is a discount lever.**
- Every answered query is a **billable usage event** — metered from day one, including trials.
- **No subscription** → highest per-query rate (fine for testing, zero commitment).
- **Higher subscription tier** (SMB → Mid → Large) → higher monthly fee but **cheaper per-query
  rate**, so heavy users are pushed toward a tier.
- Margin is protected because usage is always metered; tiers convert testers into MRR.

## v1 scope (the first shippable slice)
**Core loop + usage metering + subscription tiers.** Concretely:
- **Catalog import** — products, warehouses, per-warehouse stock, price tiers (tekomata owns the data).
- **Lookup core** — small pure query functions: stock by product/warehouse, price by product/tier,
  where stock lives.
- **WhatsApp inbound** — webhook → identify tenant by WhatsApp number → hand the message to the assistant.
- **Assistant** — parse the question → call the lookup functions → compose a plain answer → reply
  via the WhatsApp Business API.
- **Usage meter** — one billable event per answered query, charged at the tenant's tier rate.
- **Control panel (Laravel)** — owner registers / logs in, completes KYC/KYB onboarding, imports
  catalog, manages API keys, and sees usage & bill / manages subscription. The account stories
  (register, login, forgot-password, KYC/KYB onboarding, open-API keys) are the refined foundation
  for this panel; the landing page funnels signups into it.

## Accounts, tenancy & auth
The **company is the tenant**; **users ↔ companies is many-to-many** (a membership join with a
role). One user can own/belong to several companies, and a company can have several users.

- **Email-only auth, no OTP.** Register and login use email + password; account verification and
  password reset both go through **email links** (single-use, time-boxed) — no phone, no SMS/OTP
  cost. WhatsApp is reserved for the *product* assistant, not auth.
- **Registration is "approach A" (tidy pending-row).** `/auth/register` writes a short-lived
  `pending_registrations` row and emails a verification link; clicking it promotes the row to a
  real user and creates that user's **first company** + an **owner** membership. Spam/abuse is
  fought with rate-limiting + email throttling, not by withholding the DB write.
- **Register stays minimal** so users reach the dashboard fast; **KYC (the person) + KYB (the
  business)** are collected **after first login** in a gated onboarding step. KYC is per-user
  (once); KYB is per-company.
- **Tokens are scoped to one active company.** Login issues an access/refresh pair carrying the
  active company; a multi-company user can switch, which re-issues the token. The auth middleware
  exposes user id + active company id so every request is tenant-scoped.
- **Open API.** The Go API is the single surface, consumed both by the Laravel panel (user JWT)
  and by **machines/external integrations via per-company API keys** (hashed, revocable, scoped).
  Build endpoints API-first so they serve both callers, and attribute API-key calls to the company
  for metering/billing.

## Engineering shape
Built mostly by **Claude Code engineers**, so keep every unit **concise, small, and single-purpose**
— prefer tight pure functions (e.g. a lookup) and thin handlers over large multi-responsibility blocks.

## Stack
- **Backend: Go.** The `## Backend` section of every story describes Go work — HTTP API
  endpoints, handlers, services, migrations, the WhatsApp webhook, the lookup functions, and
  usage metering.
- **Frontend: Laravel (PHP).** The `## Frontend` section describes Laravel work — the web control
  panel: routes, controllers, Blade views, and calls to the Go API. Laravel is the web/UI layer,
  not a second backend. (The assistant itself is surfaced over **WhatsApp**, not the web UI.)

## How to write tasks (taskdoc style)
Every refined task is a `[STORY]` with `## Backend` (Go) and `## Frontend` (Laravel) sections,
each broken into `###` function/unit blocks tagged `_engineer_` or `_ai-agent_`, and closes with
a `## Acceptance criteria` checklist. A story with work on only one side keeps only that section.

**Keep refined docs light: goal + background per unit, plus a `## Acceptance criteria` checklist
that defines done.** The implementation "how" — steps, function signatures, tests — is the
**code repo's jurisdiction**, not the taskdoc's. A story states *what* each unit is for and the
context around it, then lists the observable conditions that mean it's done; the engineer or AI
agent works out the rest in the repo. (Full format spec lives in `companies/CLAUDE.md`.)

## How to think about priority
- 1 (urgent): the **core loop** — WhatsApp question in → catalog lookup → answer out. Without it
  there is no product.
- 2 (high):   foundational/init work the core loop depends on — catalog import, tenant identity,
  auth, core scaffolding (e.g. the existing landing-page and login stories).
- 3 (normal): usage metering, subscription tiers, and control-panel functionality around the loop.
- 4 (low):    nice-to-haves — richer natural-language phrasing, analytics, multi-language support.
