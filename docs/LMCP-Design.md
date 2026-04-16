2.1 Sitemap / Screen Map
A) Public / Pre-auth (Web + Mobile)
	•	Landing (About the Program)
	•	How it works (Members / Coops / Processors)
	•	Marketplace (Browse listings)
	•	Trust & Governance
	•	Cooperative endorsement model
	•	Audit & traceability explanation (non-technical)
	•	Contact / Support
	•	Login
	•	Language selector
Hierarchy logic: public screens focus on trust and clarity first, conversion second (gov/NGO grade). Marketplace browsing can exist pre-auth, but “transact” requires auth.
B) Authentication & Onboarding (Mobile-first)
	•	Role-aware Login
	•	Low-end phone compatible
	•	Onboarding chooser
	•	“Join as Member”
	•	“Coop Admin / Officer”
	•	“Processor”
	•	Coop selection / verification
	•	Identity & profile setup
	•	Device + offline readiness check
	•	Tutorial: “How offline sync works”
Decision node: You said onboarding is “all of the above.” In IA terms, this becomes 3 entry paths:
	•	Self-signup (Member/Processor)
	•	Admin-created user (Member)
	•	Field-agent assisted signup (Member)
We’ll map these in flows.
C) Mobile App — Member (Field Operations)
Primary: daily operations + marketplace actions
	•	Home (Today)
	•	Quick actions: Log milk, Request feed, Request service, View wallet
	•	Milk
	•	Log milk (offline-first)
	•	History & corrections
	•	Receipts/acknowledgements
	•	Feed
	•	Request feed
	•	Delivery/collection confirmation
	•	History
	•	Services
	•	Request service
	•	Appointments / visit log
	•	Marketplace
	•	Browse
	•	My requests/offers (if allowed for members)
	•	Order status (but finalization online-only)
	•	Wallet / Ledger
	•	Balance
	•	Transactions
	•	Payout requests
	•	Notifications / Inbox
	•	Approvals, payout status, listing updates
	•	Profile & Settings
	•	Language
	•	Accessibility (text size, contrast mode)
	•	Offline sync status
Hierarchy logic: Member app prioritizes “do the job fast” over navigation depth: Home is action-first; history is secondary.
D) Mobile App — Processor
Primary: procurement + quality + settlement
	•	Home
	•	New listings / offers
	•	Pending actions
	•	Marketplace Procurement
	•	Browse milk supply listings
	•	Place offers / accept offers
	•	Order tracking
	•	Vendor trust and fulfillment visibility
	•	Quality & Receiving
	•	Confirm receiving
	•	Quality notes (if in scope)
	•	Wallet / Settlement
	•	Escrow-like holds (if needed)
	•	Payout initiation to members/coops (still internal ledger)
	•	Disputes
	•	Raise dispute
	•	Track resolution
	•	Profile & Settings
	•	Notifications / Inbox
Locked decision: processors may browse and submit offers against cooperative-scoped listings, including listings originated by members, but settlement, disputes, approval visibility, and trust controls remain within cooperative governance. There is no direct processor-to-member settlement path outside cooperative scope.
E) Web Portal — Cooperative Admin (Ops + Governance + Heavy Data)
Primary: oversight + approvals + reporting
	•	Dashboard
	•	Production summary
	•	Exceptions (missing logs, anomalies)
	•	Pending approvals (payout + listing moderation)
	•	Members
	•	Member directory
	•	Profile audit trail
	•	Member status (active/suspended)
	•	Milk Operations
	•	Aggregated logs
	•	Approvals/verification (if needed later)
	•	Export
	•	Feed Operations
	•	Requests, distributions, reconciliation
	•	Services Management
	•	Service requests, assignments, completion
	•	Marketplace Admin
	•	Listings moderation queue
	•	Disputes queue
	•	Finance (All)
	•	Ledger
	•	Contributions/savings
	•	Withdrawals
	•	Loans
	•	Dividends
	•	Fees
	•	Payout approvals
	•	Audit trail
	•	Reports
	•	Operational reports
	•	Financial reports
	•	Export center
	•	Configuration
	•	Reference data (feeds, pricing rules if any, roles)
	•	Approval policies (maker-checker)
	•	Support / Case management
	•	Audit & Compliance
	•	Tamper-evident logs view (read-only)
	•	Access log
Hierarchy logic: Web is data-dense, so navigation is domain-based (Ops, Marketplace, Finance, Reports, Admin). This prevents one mega-dashboard becoming unreadable.
F) Cross-cutting screens (All roles)
	•	Search (global)
	•	Empty states
	•	Error states
	•	Offline/Sync center
	•	Permissions denied
	•	Suspended account / KYC needed (if applicable)
2.2 User Flow Mapping
I’ll map New user, Returning user, Admin, and edge cases.Format: Trigger → Steps → Success state → System feedback
A) New User Flows
A1) New Member — Self-signup (Mobile)
	•	Download/open app → Choose language
	•	“Join as Member” → Enter phone/name (low-end friendly)
	•	Select cooperative → Cooperative endorsement step
	•	Create PIN / magic link (if chosen)
	•	Minimal profile → “Ready to use offline” screen✅ Success: member lands on Home (Today) with “Log milk” CTASystem feedback: show offline/sync explanation + trust badge “Coop-endorsed”
Risk: endorsement step can block adoption if too strict (see UX risk section).
A2) New Member — Admin-created account (Field)
	•	Admin creates user in web portal
	•	Member receives invite/credentials (notification)
	•	Member logs in → forced PIN setup → profile confirmation✅ Success: member sees pre-linked coop identit
A3) Processor — Self-signup (Web or Mobile)
	•	Choose “Processor” → org details → verification path
	•	Role approval (optional) → access marketplace procurement✅ Success: processor sees “Browse supply” feed
Locked decision: processor onboarding requires verification and country or cooperative approval before settlement-capable access is granted. Pre-verification browsing may be allowed, but offer submission and financial actions remain blocked until approval.
B) Returning User Flows
B1) Member logs milk (Offline-first)
	•	Home → “Log milk”
	•	Enter quantity (and optional quality fields)
	•	Save offline (queued)
	•	Later: sync runs → server acknowledgement✅ Success: entry appears in history with status:
	•	Pending sync → Synced → Accepted/Rejected (if validation)
System feedback: clear status chips + “why” on rejection.

B2) Member requests payout (Requires approval)
	•	Wallet → Request payout
	•	Enter amount → choose destination (wallet/internal)
	•	Submit → status “Pending approval”
	•	Admin approves on web → member notified✅ Success: wallet shows transaction posted + receipt
Critical: payout is the highest-trust flow; confirmation and audit trail must be obvious.
B3) Processor purchases milk supply (Online-only finalization)
	•	Marketplace → Select listing
	•	Offer / accept → agreement created
	•	Finalize (online required)
	•	Settlement posted to ledger✅ Success: order status “Finalized” + ledger receipt
C) Admin Flows (Web-heavy)
C1) Listing moderation (Approval)
	•	Marketplace Admin → “Moderation queue”
	•	Review listing → approve/reject
	•	If reject: must give reason✅ Success: listing state updated + processor/member notified
C1b) Vendor verification and trust review
	•	Marketplace Admin / Coop Governance → “Vendor queue”
	•	Review vendor profile, supporting evidence, and trust flags
	•	Approve / suspend / reject with reason✅ Success: vendor status and trust badges update across marketplace views
C2) Payout maker-checker approval
	•	Finance → “Pending payouts”
	•	Review request → approve/reject (with reason)
	•	Ledger posts transaction✅ Success: immutable record + notification delivered
C3) Reconciliation / reporting (High density)
	•	Reports → select report → filter by date/coop/member
	•	Export✅ Success: export ready + audit log entry created
D) Edge-case Flows (Must exist for trust)
	•	Offline conflict resolution
	•	Same record edited twice (member + admin) → conflict screen → server shows authoritative state, admin override requires reason, and approved or posted records cannot be silently overwritten
	•	Failed sync / partial sync
	•	Sync fails → retry queue → show what succeeded/failed
	•	Marketplace fulfillment exception
	•	Order accepted but pickup/delivery fails → order timeline shows blocked step → user can raise dispute from order context
	•	Role mismatch / permission denied
	•	User tries restricted action → explain why + next step
	•	Payout rejected
	•	Clear reason + guidance + re-submit path
	•	Listing rejected
	•	Clear reason + edit/resubmit
	•	Device lost
	•	Account recovery + revoke old device session (security)
	•	Low-end phone mode
	•	Lightweight UI + reduced imagery + local caching
2.3 UX Risk Areas (Friction & Mitigations)
1) Form fatigue (member onboarding + finance)
Risk: “Gov/NGO-grade” often becomes long forms → drop-off.Mitigation: progressive profiling: minimum to start, expand later.
2) Pricing/settlement confusion (marketplace + internal ledger)
Risk: Users may not understand wallet vs “real money” since Phase 1 is ledger-only.Mitigation: consistent language: “Wallet balance (internal)” + receipts + clear status labels.
2b) Order vs settlement confusion
Risk: Users may think “accepted” or “finalized” means goods are physically delivered.Mitigation: keep order status, fulfillment status, and settlement status visually distinct.
3) Overloaded dashboards (Admin data density = C)
Risk: one mega-dashboard becomes unusable.Mitigation: domain dashboards + exception-first panels + saved filters.
4) Mobile usability under intermittent network
Risk: users don’t trust whether action “worked.”Mitigation: explicit offline states, queued actions count, sync progress, and “receipt” screens.
5) Approval latency (payout/listing)
Risk: waiting without transparency reduces trust.Mitigation: status timeline + expected SLA text + notification + escalation path.
6) Low digital literacy
Risk: complex navigation and tables break usability.Mitigation: action-first home, icon + label combos, guided empty states, and “what to do next.”
7) Auditability without intimidation
Risk: surfacing audit trails can overwhelm non-technical users.Mitigation: “Receipts” for users, “Audit log” for admins; keep separate.
Phase 2 Component List (IA-level, not visual design)
These are the must-have reusable UX structures implied by the sitemap/flows:
	•	Global navigation (role-based)
	•	Quick-action panel (mobile home)
	•	Status chips (queued/synced/approved/rejected)
	•	Timeline/status tracker (payout/order/listing)
	•	Vendor trust badge row (verified / cooperative-endorsed / quality-assured)
	•	Dense table with filter + export (admin)
	•	Moderation review panel (approve/reject + reason)
	•	Maker-checker approval widget
	•	Offline banner + sync center
	•	Notification inbox
	•	Search (global)
	•	Error/empty state templates
Phase 3 — Design System Foundation (icon-first, low literacy, no mockups yet)
Design goal: reduce reading load, prevent mistakes, and make status/trust obvious across mobile (field/offline) and web (dense dashboards).
3.1 Typography System
Font strategy (low-literacy + low-end performance)
	•	Single-family UI font (avoid fancy pairings; reduces inconsistency and load).
	•	Choose a highly legible sans with excellent numeral clarity (for quantities/ledger).
Hierarchy scale (kept simple, consistent)
	•	H1 (page title): 24–28 (web), 20–22 (mobile)
	•	H2 (section): 18–20
	•	Body: 16 (default everywhere)
	•	Secondary text: 14
	•	Micro/help/meta: 12–13 (use sparingly; low literacy)
Readability principles (rules)
	•	Never rely on long paragraphs. Prefer bullets + short sentences.
	•	Numbers matter: quantity and currency always in a stronger weight.
	•	Avoid italics (harder to read).
	•	Use sentence case, not ALL CAPS.
Visual hierarchy breakdown:Title → 1-line summary → primary action → status block → details.This ensures users can act without reading everything.
3.2 Color Strategy (trustworthy + accessible)
Brand logic (gov/NGO-grade)
	•	Primary color: used for primary actions + key navigation highlights (not decoration).
	•	Neutrals: backgrounds and surfaces dominate (keeps it professional and readable).
	•	State colors: reserved strictly for statuses.
State colors (strict semantics)
	•	Success: Completed / Approved / Synced
	•	Warning: Pending / Needs attention / Offline queued
	•	Error: Rejected / Failed sync / Invalid
	•	Info: Guidance / Tips / System messages
Accessibility rules
	•	Text/background must meet WCAG AA contrast minimums.
	•	Status should never be color-only: always pair with icon + label (e.g., ✅ Approved, ⏳ Pending).
Icon-first principle: color supports recognition, icons carry meaning, labels confirm meaning.
3.3 Grid & Layout System
Mobile (primary)
	•	4pt spacing scale (4/8/12/16/24/32) for consistency.
	•	Single-column layout almost everywhere.
	•	Thumb-first actions: primary CTA within bottom half of screen.
	•	Bottom navigation for 4–5 core areas max (Home, Operations, Marketplace, Wallet, Inbox).
Tablet (secondary)
	•	Keep same structure as mobile, but allow:
	•	Split view for list/detail in admin-like tasks (e.g., moderation)
Web (dense dashboards)
	•	12-column grid with predictable breakpoints.
	•	Use cards as containers but avoid “card soup”:
	•	Summary row (KPIs)
	•	Exceptions panel
	•	Primary table area (filters pinned)
	•	Sticky filters + sticky table header for heavy reporting.
Hierarchy logic:Mobile = action-first. Web = overview + exceptions + drill-down.
3.4 Component Library (reusable, icon-first)
Below are the baseline components we’ll standardize before any page mockups.
A) Navigation
	•	Bottom Nav (mobile) with icon + short label (max 5)
	•	Side Nav (web) with grouped domains (Ops / Marketplace / Finance / Reports / Admin)
	•	Global Search (web) and lightweight search (mobile)
	•	Role badge (shows user context: Member / Processor / Coop Admin)
B) Buttons & Actions
	•	Primary button (one per screen ideally)
	•	Secondary button
	•	Tertiary/text button
	•	Icon button (only with tooltip/label for low literacy)
	•	Floating “Quick Action” button (optional; only if Home needs it)
C) Cards & Lists (mobile-first)
	•	Action Card (icon + title + 1-line description + CTA)
	•	Record List Item (leading status icon, title, key number, status chip)
	•	Detail Card (summary at top, timeline, receipt)
D) Status & Trust Components (critical)
	•	Status Chip: icon + label (Queued / Synced / Pending / Approved / Rejected)
	•	Progress/Timeline tracker (for payout/listing/order)
	•	Receipt block (transaction ID, timestamp, actor, status)
	•	Coop endorsement badge (visible on member/processor identity and key actions)
	•	Audit hint (for admins: “view audit trail” link)
E) Forms (low literacy optimized)
	•	Step form (1–3 questions per screen)
	•	Large input fields with icon cues
	•	Field helper pattern: short, example-based (“e.g., 12 liters”)
	•	Inline validation with icon + plain-language error
	•	Review screen before submit (especially payout / listing)
F) Tables (web-heavy)
	•	Dense table with:
	•	pinned key columns
	•	sticky header
	•	row actions grouped in a menu
	•	Filter bar with saved filters
	•	Export component (with permission gating)
G) System feedback
	•	Offline banner (“Offline — saving to device”) + queued count
	•	Sync center (queue, last sync time, retry)
	•	Toasts for non-critical
	•	Blocking alerts/modals for financial approvals + irreversible actions
	•	Empty states with icon + next best action
	•	Skeleton loaders (low-end friendly)
H) Notifications / Inbox
	•	Notification item: icon + status + short message + CTA
	•	Delivery/read state (important for trust)
	•	Category filters (Approvals / Payout / Marketplace / System)
Icon-first design rules (to bake into the system)
	•	Every primary action has icon + verb: “➕ Log Milk”, “💰 Request Payout”.
	•	Every status has icon + label, never color-only.
	•	Icons must be simple, consistent stroke/fill, culturally neutral.
	•	Do not overload with many icons on one screen; 1 icon per row is enough.
	•	Avoid abstract metaphors. Prefer concrete: milk can, wallet, bell, shield, check.
What’s locked in (design system constraints)
	•	Icon + label on navigation, actions, statuses, and key data rows.
	•	No color-only meaning (status = icon + label + optional color).
	•	Low-literacy defaults: short verbs, step forms, review-before-submit for irreversible actions.
	•	Mobile-first operations + web dense dashboards.
Phase 4 — Screen 1: Core Landing Page (Web)
Constraints applied: gov/NGO-grade, icon + short text everywhere, trust-first, conversion-aware, marketplace browse public, English + Hausa + French.
1) Structure and Information Hierarchy
Header (sticky)
	•	Left: Logo + Program/Platform name
	•	Center/Right:
	•	🏠 Home
	•	🛒 Marketplace (Browse)
	•	ℹ️ How it works
	•	🛡️ Trust & Audit
	•	📞 Support
	•	🔑 Login
	•	🌐 Language (EN / HA / FR) (top-level, always visible)
Why: Low literacy users need predictable, icon-labeled navigation. Language must be reachable without hunting.
Hero (above the fold)
Left block (primary message + CTA)
	•	Headline (1 line): “Better livestock records. Trusted cooperative payments.”
	•	Sub-line (1 line): “Log milk, manage feed, request services — even offline.”
	•	Primary CTA: ✅ Join as Member
	•	Secondary CTA: 🔑 Login
	•	Tertiary link: 🧾 “How endorsement works”
Right block (trust + status instead of imagery-heavy)A Trust Card stack (3 small cards):
	•	🏛️ Cooperative endorsed
	•	🔍 Audit-ready records
	•	📶 Works offline (sync later)
Why: For gov/NGO-grade trust, the right side should not be decorative. It must answer “is this real?” and “will it work here?” fast.
“Quick paths” (icon tiles)
3 large tiles (one row on desktop, stacked on mobile):
	•	🧑‍🌾 Members: Log milk, request payout
	•	🏢 Cooperatives: Approvals, reports, accountability
	•	🏭 Processors: Browse supply, place offers, track orders
Each tile: icon + 5–7 word label + 1-line explanation + link
Why: Role clarity reduces confusion and mis-onboarding.
Proof / Stats (trust anchor)
A Stats strip with 4 metrics (big number + label + icon):
	•	👥 Members onboarded
	•	🥛 Milk logs recorded
	•	💳 Payouts processed
	•	✅ Approvals completed
Rules:
	•	If stats are not available yet: show “Pilot starting” placeholders without faking numbers.
	•	Include “Last updated: date” for credibility.
Why: You chose stats as trust proof; numbers are scannable and low-literacy friendly.
“How it works” (3 steps)
Horizontal steps with icons:
	•	📝 Record (milk/feed/services)
	•	📩 Request (payouts/listings)
	•	✅ Approve & track (status timeline)
Each step has one short sentence.
Why: Flow-thinking > feature dumping.
Marketplace preview (public browsing)
	•	Section title: 🛒 “Marketplace (Browse)”
	•	Filters preview (read-only until marketplace page):
	•	Milk / Feed / Services
	•	Location (if applicable later)
	•	6 sample listing cards:
	•	Icon category + short title + key number (e.g., liters/price) + status
CTA: 🛒 Browse Marketplace
Why: You allow public browsing. This supports conversion by showing real utility before sign-up.
Trust & Governance (must-have section)
	•	🛡️ “Trust & Audit”Three cards:
	•	🔍 Audit trail (who did what, when)
	•	🧾 Receipts (clear confirmations for payouts/orders)
	•	🧑‍⚖️ Approvals (maker-checker for sensitive actions)
Why: Your trust pillars are cooperative endorsement + auditability; this section makes that explicit.
Support / Contact
	•	📞 Phone/WhatsApp (if allowed) + 📨 Support form link
	•	🕒 Support hours
	•	Short FAQ links (icon + text)
Why: In low-connectivity, people trust systems with visible support.
Footer
	•	🌐 Language links repeated
	•	Legal: Terms, Privacy
	•	“Powered by / Partnered with” (if applicable)
2) Visual Hierarchy Breakdown (what users see first)
	•	Join as Member CTA (primary)
	•	Trust cards: “Coop endorsed / Audit-ready / Works offline”
	•	Role tiles (Member/Coop/Processor)
	•	Stats strip (proof)
	•	Marketplace preview (value)
	•	Trust & Governance details (reassurance)
This ordering supports: conversion → trust → comprehension → exploration.
3) Accessibility Considerations
	•	Language switcher is keyboard reachable and visible.
	•	Buttons have icon + text and minimum target size.
	•	Stats and statuses use icon + label (not color-only).
	•	Headline remains short; body text defaults to readable sizes.
	•	All cards and tiles have clear focus states.
4) Responsiveness Approach
	•	Desktop: two-column hero + 3 role tiles in a row + stats strip.
	•	Tablet: hero becomes stacked; tiles remain 2-per-row.
	•	Mobile: single column; CTA stays prominent; tiles become large tap cards; stats become 2x2 grid.
5) Component List Used (from Phase 3 library)
	•	Sticky header nav (icon + label)
	•	Primary/secondary buttons
	•	Role tiles (action cards)
	•	Trust cards
	•	Stats strip component
	•	Stepper (3-step)
	•	Listing preview cards
	•	FAQ links
	•	Footer links + language selector
Phase 4 — Screen 2: Primary Feature Page (Mobile)
Member Home: “Today” (Action-first, icon + short text everywhere)
Goal: let a low-literacy member complete the top 3 daily tasks in <30 seconds:
	•	🥛 Log milk
	•	💰 Request payout / check wallet
	•	📦 Request feed / 🧰 Request service…and always understand offline/sync status.
1) Page Structure
A) Top Bar (compact, always consistent)
	•	👤 Profile icon + short name
	•	🏛️ Coop badge (endorsed)
	•	🌐 Language (EN/HA/FR)
	•	🔔 Inbox (with count)
Why: identity + endorsement are trust anchors; inbox count reduces missed approvals/payout updates.
B) Offline & Status Banner (high priority)
A slim banner pinned under top bar:
	•	📶 Online / 📵 Offline
	•	⏳ Queued: 3 (if offline saves exist)
	•	🔄 “Sync now” button (only if online)
Why: In intermittent connectivity, the #1 anxiety is “did it save?” This removes ambiguity.
C) “Today” Quick Actions (primary zone)
3 large action cards (big icon left, verb label, 1-line helper):
	•	🥛 Log Milk“Record today’s quantity”
	•	💰 Wallet“Balance + payout status”
	•	📦 Request Feed“Submit a feed request”
Then a smaller 4th card (secondary):
	•	🧰 Request Service
Why: large targets, verb-first, minimal reading. We keep only 3 primary actions to avoid choice overload.
D) Today Summary (small, scannable)
A simple 3-item summary strip (icon + number + label):
	•	🥛 Logged today: 12 L
	•	⏳ Pending: 1
	•	✅ Approved: 2
Why: gives immediate feedback and motivation without forcing a dashboard.
E) My Activity (last 5)
A list of recent records with:
	•	Leading status icon + label chip
	•	Short title (“Milk log”, “Feed request”, “Payout request”)
	•	Key number (liters / amount)
	•	Time (“Today 10:40”)
Tap opens Receipt view (details + timeline).
Why: trust comes from “I can see my history” and clear statuses.
F) Help (low literacy support)
A single persistent help card at bottom:
	•	❓ Need help?“Call support / Ask your coop office”
Why: reduces abandonment and improves trust.
2) Visual Hierarchy Breakdown
	•	Offline banner (status confidence)
	•	“Log Milk” CTA (primary daily action)
	•	Wallet CTA (money = highest attention)
	•	Feed/service CTAs
	•	Today summary
	•	Activity list (receipts/history)
	•	Help
3) Accessibility + Low-literacy Rules Applied
	•	Every action = icon + short verb (no abstract labels)
	•	All statuses = icon + label (Queued / Pending / Approved / Rejected)
	•	Large tap targets, minimal text, no dense paragraphs
	•	Clear error prevention: review-before-submit for payouts
	•	Language switch accessible from top bar
4) Responsiveness
This is a mobile-first screen. On tablet, the same layout can become:
	•	Left: Quick actions + Today summary
	•	Right: Activity list
5) Component List Used
	•	Top bar (role-aware)
	•	Coop endorsement badge
	•	Offline/sync banner
	•	Action cards (primary/secondary)
	•	Summary strip
	•	Activity list item with status chip
	•	Receipt link pattern
	•	Help card
Phase 4 — Screen 3: Critical Conversion Flow (Mobile)
Onboarding (Member) — Option A approved: simple, stepwise, icon + short text
Goal: get a new member to first successful action (Log Milk) with minimal reading, minimal typing, and clear trust cues.
1) Flow Map (Member Onboarding)
Entry points (supported per your “all of the above”)
	•	Self-signup: user installs app → Join
	•	Admin-created: user receives invite → Activate
	•	Field-agent assisted: agent helps → user confirms
All three converge into the same Step 2 onward.
2) Screens in the Onboarding Flow (structure only)
Screen O1 — Welcome / Language
	•	🌐 Language picker: English / Hausa / French
	•	1-line promise: “Works offline. Saves on your phone.”
	•	CTA: ✅ Continue
Reasoning: language first prevents comprehension failure downstream.
Screen O2 — Choose Role (tight, no clutter)
Large tiles (icon + label + 1 line):
	•	🧑‍🌾 Member
	•	🏭 Processor (secondary)
	•	🏛️ Coop Admin (link only)
Member CTA continues.
Reasoning: prevents role confusion early; keeps path clean.
Screen O3 — Join Method (because you allow multiple onboarding modes)
Tiles:
	•	📱 Sign up myself
	•	🧾 I have an invite
	•	🤝 Agent helping me
Reasoning: reduces dead-ends (“I can’t proceed because I don’t have invite”).
Screen O4 — Phone + PIN (low-end friendly)
	•	📞 Phone number input (country preset)
	•	🔐 Create 4–6 digit PIN (with “show dots” only)
	•	CTA: ✅ Continue
	•	Small note: “PIN protects your wallet.”
Reasoning: lowest-friction identity; PIN works offline and on low-end devices.
Screen O5 — Cooperative Selection (trust gate)
	•	🔎 Search coop (big input)
	•	List of coops (icon + short name)
	•	Badge on verified coops: 🏛️ Verified
	•	CTA: ✅ Select
Reasoning: cooperative endorsement is your trust model; we surface it explicitly and simply.
Screen O6 — Endorsement / Confirmation (very short)
Card:
	•	🏛️ “Your cooperative endorses members.”
	•	“Your details will be visible to your coop officers.”Buttons:
	•	✅ Agree & Continue
	•	❓ “What does this mean?” (opens 3 bullet explainer)
Reasoning: informed consent without legal walls of text.
Screen O7 — Basic Profile (progressive profiling)
Only essentials (1 screen max):
	•	👤 Full name
	•	(Optional) Member ID / tag (if available)
	•	CTA: ✅ Save
Rule: anything else becomes “Later in Profile.”
Reasoning: long forms kill conversion; we keep it minimal.
Screen O8 — Offline Ready (confidence screen)
	•	✅ “Ready to use”
	•	📵 Offline note: “If you lose network, we still save.”
	•	🔄 Sync note: “We upload when you’re online.”CTA:
	•	🥛 Log Milk Now (primary)Secondary:
	•	🏠 Go to Home
Reasoning: immediately drives first value action and reduces fear of “will it work?”
3) Visual Hierarchy Breakdown (per screen)
Across all onboarding screens:
	•	Big icon + clear title
	•	1-line instruction
	•	Large choice tiles (when relevant)
	•	One primary CTA
	•	Small help link
This keeps cognitive load low and makes it “tap-tap-done.”
4) Error & Edge Handling (must be designed, not implied)
	•	❌ Invalid phone → “Check your number” (example format)
	•	🚫 Coop not found → “Ask your coop office” + Support CTA
	•	🔁 OTP/verification (only if you require it later) is optional; not assumed now
	•	📵 Offline during signup:
	•	Allow saving profile locally after PIN
	•	Coop selection may require online; if offline, show “Try when online” with retry
5) Components Used
	•	Language picker
	•	Role tiles
	•	Join method tiles
	•	Phone input + PIN component
	•	Coop search + verified badge
	•	Consent micro-card + explainer modal
	•	Minimal profile form
	•	Offline-ready confirmation card
	•	Inline validation + plain-language errors
Phase 4 — Screen 4: Coop Admin Web Dashboard (High-density, trust-first)
Dashboard goal
Help a coop admin answer, in <60 seconds:
	•	What needs my approval now? (payout + listing moderation)
	•	What is abnormal or missing? (exceptions)
	•	What is today’s operational picture? (summary)…and then drill down into tables without overload.
1) Layout Structure (Web)
A) Header (global)
	•	🏛️ Coop context switcher (if multiple)
	•	🔎 Global search
	•	🔔 Notifications (approvals, disputes)
	•	👤 Admin profile + role badge
	•	🌐 Language switch (EN/HA/FR)
Why: admins operate across many records; search and context are primary.
B) Left Navigation (domain grouped, icon + short text)
Ops
	•	🥛 Milk
	•	📦 Feed
	•	🧰 Services
Marketplace
	•	🛒 Listings
	•	⚠️ Disputes
Finance
	•	💰 Ledger
	•	🏦 Contributions/Savings
	•	↩️ Withdrawals
	•	📄 Loans
	•	🎁 Dividends
	•	🧾 Fees
	•	✅ Payout approvals
Reporting
	•	📊 Reports
	•	⬇️ Exports
Governance
	•	🛡️ Audit log
	•	⚙️ Configuration
	•	👥 Members
Why: prevents a single bloated dashboard. Icons help scanning.
2) Dashboard Content Regions (top → bottom)
Region 1 — “Action Queue” (most important, above the fold)
Two primary panels side-by-side:
Panel 1: ✅ Payout Approvals
	•	Count badge: “Pending (12)”
	•	5-row mini-table preview:
	•	Member | Amount | Requested | Status
	•	Primary CTA: ✅ “Review payouts”
Panel 2: 🛒 Listing Moderation
	•	Count badge: “Pending (4)”
	•	5-row preview:
	•	Listing | Category | Created | Status
	•	CTA: ✅ “Moderate listings”
Why: you explicitly said approvals are critical. Put them first, not buried.
Region 2 — “Exceptions” (trust + operational integrity)
A single wide panel: ⚠️ Needs AttentionException cards (icon + short label + count), clickable to filtered tables:
	•	🧾 “Unendorsed members active (X)”
	•	🥛 “Missing milk logs today (X)”
	•	📵 “Sync failures (X)”
	•	💰 “Failed payouts / reversals (X)”
	•	🛒 “Disputes awaiting response (X)”
Why: exception-first dashboards reduce cognitive load vs showing everything.
Region 3 — “Today Summary” (KPIs, not decorative)
A KPI strip (4–6 max):
	•	🥛 Total milk logged (today)
	•	👥 Active members (7 days)
	•	💰 Payouts approved (today)
	•	🛒 Marketplace orders finalized (today)
	•	📵 Offline queued items (today)
	•	✅ Approval turnaround time (avg)
Why: admin needs a pulse view but not at the cost of actionability.
Region 4 — “Work Tables” (high density, task mode)
Tabs (icon + text) switching the main table:
	•	✅ Approvals (default)
	•	👥 Members
	•	🥛 Milk
	•	💰 Finance
	•	🛒 Marketplace
Each tab loads a dense table with pinned filter bar.
Why: keeps dashboard from becoming multiple pages; still allows drill-down.
3) Table Strategy (for Data Density “C”)
Filter bar (sticky)
	•	Date range
	•	Member / Processor
	•	Status (Pending/Approved/Rejected)
	•	Category (Milk/Feed/Service/Listing/Payout)
	•	“Saved filters” dropdown
	•	Export button (permission-gated)
Table row structure
	•	Leading status chip (icon + label)
	•	Primary identifier (member/listing/txn)
	•	Key number (amount/liters)
	•	Time
	•	Assignee/actor
	•	Row actions in a single “⋯” menu
Row click opens a right-side drawer (recommended)
Drawer shows:
	•	Summary
	•	Timeline (who did what, when)
	•	Receipts/audit references
	•	Approve/Reject with reason (if applicable)
Why: drawers reduce page switching and support faster reviews.
4) Visual Hierarchy Breakdown
	•	Action Queue (payout + moderation)
	•	Exceptions panel (risk control)
	•	KPI strip (context)
	•	Main task table (execution)
	•	Drawer details (decision making)
5) Accessibility + Low-literacy Admin Considerations
	•	Still icon + short text (admins may read more, but scanning is faster)
	•	Keyboard navigable tables + drawer
	•	Status always label + icon
	•	“Reason required” for reject actions (prevents ambiguity and disputes)
6) Responsiveness Approach
	•	Desktop: 2-panel approvals + wide exceptions + full table
	•	Tablet: approvals stack vertically; table remains with horizontal scroll + pinned columns
	•	Mobile web (fallback): show “Approvals first” list mode; tables become stacked cards
7) Component List Used
	•	Sidebar nav (icon + text)
	•	KPI strip
	•	Action queue panels (mini tables)
	•	Exception cards
	•	Dense table + sticky filter bar
	•	Status chips
	•	Detail drawer with timeline + approve/reject module
	•	Export module
	•	Notifications panel
Pause (Stepwise)
If you approve this dashboard structure, next is Screen 5: Error & Empty States.
Reply: “Dashboard approved — proceed to Error/Empty states.”
approved
Phase 4 — Screen 5: Error & Empty States (system-wide)
Icon-first + short text, built to reduce anxiety (offline, approvals, money).
1) Empty States (when there is “nothing”)
A) Member Home — No activity yet
	•	🥛 No records yet
	•	“Start with today’s milk.”
	•	CTA: ✅ Log Milk
	•	Secondary: 📦 Request Feed
Why: always give the “next best action,” not a dead end.
B) Marketplace — No listings found
	•	🛒 No listings
	•	“Try another category.”
	•	Filters hint: “Milk / Feed / Services”
	•	CTA: 🔄 Clear filters
	•	Secondary: ➕ Create listing (only if role allows)
C) Wallet — No transactions
	•	💰 No wallet activity
	•	“Your receipts will appear here.”
	•	CTA: 🧾 View how payouts work
	•	Secondary: 💳 Request payout (only if eligible)
D) Admin approvals — No pending approvals
	•	✅ All done
	•	“No pending approvals.”
	•	CTA: 📊 View reports
	•	Secondary: 🛡️ Audit log
Why: reduce uncertainty (“Is it broken?”) and move to productive next step.
2) Error States (when something goes wrong)
A) Offline Save (success but offline)
	•	📵 Saved offline
	•	“We will sync when online.”
	•	Show: ⏳ Queued count
	•	CTA: 🏠 Back to Home
Why: treat offline as a normal mode, not an error.
B) Sync Failed (action didn’t reach server)
	•	⚠️ Sync failed
	•	“Your data is safe on this phone.”
	•	Show: “Last try: time”
	•	CTA: 🔄 Try again
	•	Secondary: 📞 Get help
	•	Advanced (small): “Details” (for support)
Why: preserves trust; avoids panic.
C) Validation Error (user input issue)
	•	❌ Fix this
	•	One-line plain language: “Amount is too high.”
	•	Inline highlight field + example: “e.g., 5,000”
	•	CTA: ✅ Continue
Rule: never show technical codes.
D) Permission denied (role restriction)
	•	🔒 Not allowed
	•	“Only Coop Admin can approve payouts.”
	•	CTA: 👤 Contact coop office
	•	Secondary: 🏠 Home
Why: prevents confusion and reduces repeated attempts.
E) Approval Rejected (payout or listing)
	•	🚫 Rejected
	•	Show required: “Reason: …”
	•	CTA: ✏️ Edit & resubmit
	•	Secondary: 📞 Get help
Why: rejection without reason destroys trust. Reason is mandatory.
F) Marketplace finalization blocked (online-only)
	•	🌐 Go online to finish
	•	“Finalizing needs internet.”
	•	CTA: 🔄 Try again
	•	Secondary: 🧾 “Save as draft” (if applicable)
Why: aligns with your constraint: finalization online-only.
G) Something went wrong (generic)
	•	⚠️ Try again
	•	“We couldn’t complete that.”
	•	CTA: 🔄 Retry
	•	Secondary: 📞 Support
	•	Small: “Error ID: 8F2…” (copy button)
Why: gives support something actionable without exposing internals.
3) Trust + Accessibility Patterns (applies to all states)
	•	Icon + short title + 1-line explanation + 1 primary CTA
	•	Never rely on color to communicate status
	•	Focus states visible, keyboard reachable actions (web)
	•	Minimum readable text size; no walls of text
	•	Include time stamps for financial/system actions where relevant
4) Reusable “State Templates” (for consistency)
We standardize 6 templates:
	•	Empty → Next Action
	•	Offline Saved
	•	Sync Failed (Safe Data)
	•	Input Fix
	•	Rejected with Reason
	•	Blocked by Network / Permission
These become part of the component library so every module behaves the same.
Phase 5 — Responsive Strategy (Mobile ops + Web dashboards + Low-end devices)
Your product has two different “responsive realities”:
	•	Mobile app: primary for member operations, intermittent network, low literacy, low-end phones.
	•	Web portal: primary for admin dashboards, heavy tables, approvals, reporting.
So responsiveness is not just “shrink the UI” — it’s preserve task success as layout compresses.
5.1 Layout Compression Approach
A) Mobile app (baseline)
	•	Single column, action-first.
	•	Keep one primary action per screen where possible.
	•	Use progressive disclosure: show summary → tap for details (receipt/timeline).
B) Tablet
	•	Allow list + detail split where it reduces taps:
	•	Approvals moderation
	•	Activity history + receipt
	•	Keep icon + label; don’t introduce dense tables.
C) Web (desktop → tablet → mobile web fallback)
Desktop
	•	2-panel approvals + wide exceptions + full table + drawer
Tablet
	•	Stack panels vertically
	•	Keep table but:
	•	Reduce columns by priority
	•	Keep pinned identifier + status
	•	Move secondary columns into the row drawer
Mobile web fallback (admin)
	•	Do not attempt full table UI.
	•	Switch to card list mode:
	•	Each “row” becomes a card with 3 key fields + status + action
	•	Filters become a collapsible sheet
Rule: If the screen is approval-heavy, optimize for “approve/reject with reason” even on mobile web.
5.2 Navigation Adaptation
Mobile app
	•	Bottom nav (max 5) stays stable:
	•	🏠 Home
	•	🧾 Records (milk/feed/services grouped)
	•	🛒 Market
	•	💰 Wallet
	•	🔔 Inbox
	•	If more areas exist, use More (⋯) as the 5th slot, not an extra row.
Web portal
	•	Persistent left sidebar on desktop.
	•	On tablet:
	•	Sidebar collapses to icon rail
	•	Labels appear on hover/focus or expanded state
	•	On small screens:
	•	Sidebar becomes hamburger drawer (still icon + short text)
Reasoning: admins rely on navigation memory; keep locations consistent across breakpoints.
5.3 Form Usability Adjustments (low literacy + small screens)
Principles
	•	One decision per screen (especially onboarding, payout, listing).
	•	Replace long forms with step forms + progress indicator (Step 1 of 3).
	•	Use examples and units inside fields (e.g., “Liters”, “₦ amount”).
Mobile optimizations
	•	Numeric keyboards for quantities/money.
	•	Large buttons with icon + verb.
	•	Review screen before submit for:
	•	💰 payouts
	•	🛒 finalize order
	•	any “irreversible” action
Error handling in forms
	•	Inline errors next to field, plain language, icon.
	•	Don’t clear inputs on error.
	•	If offline blocks a required online step (e.g., finalize), show a single clear blocker state.
5.4 Table Responsiveness (Admin, data density C)
Column priority model (must be defined once, reused)
For each table define:
	•	Tier 1 (always visible): Status, Primary ID (member/txn/listing), Key number (amount/liters), Time
	•	Tier 2 (hide on smaller): Actor/assignee, location, method, category
	•	Tier 3 (drawer only): audit metadata, references, long notes
Interaction model
	•	Desktop: table + sticky filters + right drawer
	•	Tablet: table with fewer columns + drawer
	•	Mobile: card list + bottom sheet filters + detail screen/drawer
Touch-friendly controls
	•	Row actions consolidated into one menu (⋯)
	•	Bulk actions only on desktop/tablet (avoid on mobile)
5.5 Touch Targets & Density Controls
Because you need both low literacy and high density:
	•	Mobile: minimum tap target ~44px, generous spacing.
	•	Web: provide a density toggle for tables:
	•	Comfortable (default)
	•	Compact (for power users)
Why: admins may want compact; members need comfort.
5.6 Performance-Sensitive Responsiveness (low-end phones)
	•	Avoid heavy imagery on mobile.
	•	Use skeleton loaders (simple shapes) instead of spinners.
	•	Defer non-critical components (e.g., charts) behind “View details.”
	•	Keep icon set lightweight and consistent (single style family).
5.7 Responsive Checklist (what we’ll validate per page)
	•	Does the primary CTA remain visible without scrolling?
	•	Do statuses remain readable (icon + label)?
	•	Are offline/sync indicators still present and not hidden?
	•	Can approvals be completed on tablet without horizontal scrolling?
	•	Do tables degrade into card lists on small screens?
	•	Are language switch and support reachable everywhere?
Phase 6 — Accessibility & Trust Review (explicit evaluation)
Scope: validate the system decisions we’ve made (icon+text, offline-first, approvals, wallet/ledger, multi-language) against accessibility and trustworthiness requirements.
6.1 Color Contrast and Non-Color Communication
Requirements
	•	All text/background combinations meet WCAG AA contrast minimums.
	•	Status meaning is never color-only:
	•	Must include icon + label (✅ Approved, ⏳ Pending, ❌ Failed).
Risk areas
	•	Status chips often fail contrast when tinted.
	•	Disabled states can become unreadable on low-end screens.
Controls
	•	Provide a high-contrast mode toggle in Settings.
	•	Use neutral chip backgrounds with strong text/icon, reserve color as an accent only.
6.2 Font Size Minimums and Readability
Requirements
	•	Default body text remains comfortably readable on low-end phones.
	•	Avoid dense paragraphs; keep instructions to 1 line when possible.
Controls
	•	Text size setting: Normal / Large / Extra Large
	•	Never hide critical meaning in microcopy (12–13px is “support only”).
6.3 Focus States and Keyboard Navigation (Web Portal)
Requirements
	•	Every interactive element is reachable by keyboard:
	•	Sidebar items, filters, table rows, row action menus, drawer actions.
	•	Visible focus ring (not subtle).
High-risk components
	•	Dense tables + row menus (⋯)
	•	Drawer panels (must trap focus properly and return focus on close)
	•	Filter chips + saved filters dropdown
Controls
	•	Define a standard focus style for:
	•	Buttons, links, inputs, table rows, menus
	•	“Skip to content” link for admin portal.
6.4 Screen Reader Friendliness (structure, not visuals)
Requirements
	•	Correct semantic hierarchy:
	•	One H1 per page
	•	Clear section headings (H2/H3)
	•	Icon buttons must have accessible names (“Log Milk”, “Open inbox”).
	•	Status chips must be read meaningfully:
	•	“Status: Pending approval”
High-risk components
	•	Icon-only controls (we avoided this by icon + text everywhere)
	•	Data tables (must be real tables with headers, or card lists on mobile)
Controls
	•	For tables: ensure column headers are announced properly.
	•	For drawers/modals: proper ARIA roles and labels.
6.5 Error Messaging Clarity (low literacy + trust)
Requirements
	•	Errors must be:
	•	Plain language
	•	Actionable
	•	Local to the field (inline) when possible
	•	Never show technical codes to users (except an optional “Error ID” for support).
High-risk flows
	•	Wallet payout request
	•	Marketplace finalization (online-only)
	•	Offline sync conflicts
Controls
	•	Standard error templates:
	•	❌ “Fix this” + single sentence + example
	•	Rejection must include reason required and next step CTA.
6.6 Trust Signals Review (your stated trust model)
You explicitly rely on:
	•	Cooperative endorsement
	•	Auditability
Trust signals we must surface consistently
	•	🏛️ Endorsement badge (Member/Coop/Processor context)
	•	🧾 Receipts for any money or finalization event
	•	✅/⏳ Status timeline for approvals and payouts
	•	🛡️ Audit log access for admins (read-only, tamper-evident perception)
	•	📵 Offline saved confirmation (data safety messaging)
Risk areas
	•	If users can onboard without coop (your Option B), trust could weaken unless:
	•	Restricted actions are clearly gated (payout/finalize)
	•	“Endorsement needed” is communicated early and politely
Controls
	•	Add a consistent gated-state screen:
	•	🏛️ “Coop endorsement needed”
	•	CTA: “Select cooperative” / “Contact coop office”
6.7 Localization & Language UX (EN/HA/FR)
Requirements
	•	Language switch accessible on:
	•	Landing
	•	App top bar
	•	Login/onboarding
	•	Avoid long text strings that break layout (French can expand).
Controls
	•	Use short labels; design buttons to accommodate 30–40% text expansion.
	•	Prefer icons to reduce translation burden, but never replace labels.
6.8 Offline/Sync Trust Review (intermittent connectivity)
Requirements
	•	Users always know:
	•	What is saved locally
	•	What is synced
	•	What is pending approval
	•	Never silently fail.
Controls
	•	Persistent sync banner + sync center
	•	Every record has a status chip
	•	Sync failures explicitly state: “Your data is safe on this phone.”
Final Output of Phase 6: Acceptance Checklist (what “passes”)
To approve final UX structure before mockups, we should be able to say “yes” to:
	•	All statuses have icon + label (no color-only)
	•	All financial actions produce a receipt view
	•	Approvals have a timeline + reason-required rejection
	•	Admin tables are keyboard navigable + drawer focus-managed
	•	Language switch is reachable everywhere critical
	•	Offline mode always confirms “saved” and exposes queued count
	•	Coop endorsement gating is clear under Option B onboarding
UI Specification Pack (components + states + content rules)
Scope: shared system for Mobile (Member/Processor) + Web (Coop Admin) with icon + short text everywhere, offline-first, approvals, and ledger receipts.
1) Global Rules (apply everywhere)
1.1 Icon + Text rule
	•	Every navigational item, primary action, and status must show:
	•	Icon + short label (1–3 words).
	•	Icon-only is allowed only for decorative (never interactive) or paired with tooltip + ARIA label on web.
1.2 Content rules (low literacy)
	•	Use verbs first: “Log milk”, “Request payout”, “Approve payout”.
	•	One instruction line per section max.
	•	Avoid jargon (“ledger” can be shown as “Wallet records” for members; “Ledger” for admins).
	•	Numbers are always formatted consistently (units, currency symbol).
1.3 Status meaning rule
	•	No color-only meaning. Status must be:
	•	Icon + label + (optional) timestamp.
1.4 Offline rule
	•	Offline is treated as a normal state, not an error.
	•	Any offline-save shows an explicit confirmation and queued count.
2) Navigation Components
2.1 Mobile Bottom Navigation
Slots (max 5):
	•	🏠 Home
	•	🧾 Records
	•	🛒 Market
	•	💰 Wallet
	•	🔔 Inbox
States:
	•	Default / Active / Disabled (rare) / Badge count
Content rules:
	•	Label ≤ 7 characters where possible; avoid multi-word labels in nav.
2.2 Web Sidebar Navigation
Groups:
	•	Ops, Marketplace, Finance, Reporting, Governance
States:
	•	Default / Active / Collapsed icon-rail / Hover / Focus / Disabled
Content rules:
	•	Keep labels short; if needed use tooltips in collapsed mode.
3) Core Action Components
3.1 Primary Button
Structure: icon + labelStates: default / hover (web) / pressed / loading / disabled / focusRules:
	•	Only one primary per screen where possible.
	•	Loading state must keep layout stable (no button width jump).
3.2 Action Card (Mobile Quick Actions)
Structure: leading icon, title, 1-line helper, optional chevronStates: default / pressed / disabledRules: helper text optional; title must be a verb phrase.
4) Status + Trust Components (critical)
4.1 Status Chip
Structure: status icon + labelStandard statuses (minimum set):
	•	⏳ Pending
	•	✅ Approved
	•	🚫 Rejected
	•	📵 Saved offline
	•	🔄 Syncing
	•	❌ Sync failed
Rules:
	•	Always place status chips in consistent locations:
	•	Lists: right side or under title
	•	Detail: near top under title
4.2 Timeline / Tracker
Used for: payouts, listing moderation, disputes, marketplace ordersStructure: vertical steps with icon + label + timestamp + actor (admin)States: complete / current / blocked / failedRules: show the “next step” explicitly.
4.3 Receipt Block
Used for: any finalized transaction or approval outcomeFields (members):
	•	Receipt ID, Date/time, Amount/Quantity, Status, “Who approved” (optional)Fields (admins):
	•	All above + audit reference + actor chainRules: receipt must be reachable from history lists.
4.4 Coop Endorsement Badge
States:
	•	🏛️ Verified (endorsed)
	•	⚠️ Not endorsed (limited)
	•	⏳ Pending endorsementRules: show near profile + on gated actions.
5) Offline + Sync Components
5.1 Offline Banner (Mobile)
Content:
	•	📵 Offline / 📶 Online
	•	⏳ Queued: N
	•	🔄 Sync now (only when online)
States: online / offline / syncing / sync failedRules: always visible on Home and on any “create record” screen.
5.2 Sync Center
Shows queue items, last sync time, retry all, item-level retryRules: “Your data is safe on this phone” line appears on failure states.
6) Forms (low literacy optimized)
6.1 Step Form Pattern
Structure: title + 1-line instruction + 1–3 fields + primary CTAStates: default / validation error / blocked (offline requirement) / loading
Rules:
	•	Inline errors, plain language.
	•	Use examples (“e.g., 12 liters”).
	•	For money: show currency symbol and thousand separators.
6.2 Review Before Submit (critical)
Used for: payout request, finalize marketplace order, delete/irreversibleStructure: summary list + edit links + confirm CTARules: confirm CTA has a stronger “final” label: ✅ Confirm payout
7) Lists & Tables
7.1 Mobile Activity List Item
Structure: status icon, title, key number, timeStates: default / pressed / unread (in inbox)Rules: tap opens receipt/detail with timeline.
7.2 Admin Dense Table (Web)
Required features:
	•	Sticky filter bar
	•	Sticky header
	•	Column priority tiers
	•	Row action menu (⋯)
	•	Row click opens right drawer
States: loading (skeleton) / empty / error / filtered-emptyRules: “Reject” requires reason; approvals use drawer confirmation.
7.3 Drawer (Web)
Structure: summary + timeline + actions + audit linkRules: focus trap, ESC closes, returns focus to originating row.
8) Alerts, Modals, and Notifications
8.1 Alert banners
Types: info / success / warning / errorRules: include icon + short text + optional CTA.
8.2 Modals (use sparingly)
Use only for:
	•	irreversible confirmations
	•	reason-required rejection
	•	critical blocking issuesRules: no long text; provide next step.
8.3 Inbox Notification Item
Structure: icon + label + 1-line message + CTARules: actionable; avoid “FYI only” spam.
9) Empty & Error State Templates
Empty state template
	•	Icon + “Nothing here” label + 1-line explanation + primary CTA
Error state template
	•	Icon + “Try again” label + 1-line explanation + Retry CTA + Support CTA
Rejection template
	•	🚫 Rejected + Reason + “Edit & resubmit” CTA
Gated state template (Option B onboarding)
	•	🏛️ Endorsement needed
	•	“Select your cooperative to request payout.”
	•	CTA: ✅ Select cooperative
10) Microcopy Dictionary (standard phrases)
Use consistent phrasing to build trust:
	•	“Saved offline”
	•	“Syncing…”
	•	“Sync failed — data is safe”
	•	“Pending approval”
	•	“Approved”
	•	“Rejected — reason shown”
	•	“Go online to finish”
11) Do/Don’t Summary (enforcement)
Do
	•	show receipts for money/final actions
	•	show status everywhere
	•	keep labels short + verb-first
	•	keep offline visible and reassuring
Don’t
	•	hide important states in toasts only
	•	use color without label
	•	overload dashboards with cards
	•	require long onboarding forms
