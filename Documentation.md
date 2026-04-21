# PADAK CRM — COMPLETE PROJECT DOCUMENTATION
### Version 1.0 | Internal Use | Last Updated: April 2026

---

## TABLE OF CONTENTS
1. Project Overview
2. Tech Stack & Requirements
3. File & Folder Structure
4. Database Schema (All 20 Tables)
5. Migration History
6. Core System Files
7. All Pages — Purpose, Access, Features
8. API & Helper Endpoints
9. Includes / Shared Files
10. Upload System
11. Role & Permission System
12. Notification System
13. Email System
14. Client Portal (Separate System)
15. CSS Variables & Design System
16. Global PHP Helper Functions
17. JavaScript Global Functions
18. Inter-File Relationships Map
19. Known Patterns & Conventions
20. Upgrade / Extension Guide

---

## 1. PROJECT OVERVIEW

**Padak CRM** is a self-hosted, internal Customer Relationship Management and Project Management platform built for startup teams. It replaces multiple tools (Asana + Slack + Google Drive + Invoicing + Expense tracking) with a single system.

- **Live URL (local):** `http://localhost/Project_Management/`
- **Database name:** `padak_crm` (local may be `projects_management`)
- **Timezone:** Asia/Colombo (set in config.php)
- **Session name:** `padak_crm` (CRM staff), `padak_portal` (clients)

---

## 2. TECH STACK & REQUIREMENTS

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ (uses match, typed params, arrow functions) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | Vanilla JavaScript (no jQuery), Custom CSS |
| Charts | Chart.js 4.4.1 (CDN) |
| Rich Text | TinyMCE (CDN, used in documents & emails) |
| Server | Apache with mod_rewrite |
| Email | Pure PHP SMTP via stream_socket_client (no PHPMailer) |
| File Upload | Native PHP $_FILES, stored in uploads/ |

**PHP Extensions needed:** mysqli, openssl, fileinfo

---

## 3. FILE & FOLDER STRUCTURE

```
/Project_Management/          ← CRM root (XAMPP: htdocs/Project_Management/)
│
├── config.php                ← DB connection, all global PHP functions
├── index.php                 ← Login page
├── logout.php                ← Destroys session, redirects to index.php
├── .htaccess                 ← Security headers, upload limits, hides errors
├── SETUP.txt                 ← Deployment instructions
├── fpdf.php                  ← PDF generation library
│
├── ── MAIN PAGES ──
├── dashboard.php             ← Overview stats, my tasks, projects, pipeline
├── mywork.php                ← Personal work dashboard
├── projects.php              ← Project CRUD + Kanban + detail view
├── tasks.php                 ← Task list + Kanban + edit modal + comments
├── contacts.php              ← CRM contacts CRUD
├── leads.php                 ← Sales pipeline (Kanban stages)
├── documents.php             ← File uploads + Rich document editor
├── invoices.php              ← Invoice creation, payment tracking
├── expenses.php              ← Monthly expense tracker + subscriptions
├── calendar.php              ← Events calendar with attendees
├── chat.php                  ← Channel-based team messaging
├── emails.php                ← Email Hub (compose/send/log/templates/SMTP)
├── email_template.php        ← Standalone email template generator
├── analytics.php             ← Reports & charts (admin/manager only)
├── activity.php              ← Activity log viewer (admin/manager only)
├── users.php                 ← Team/user management (admin/manager only)
├── portal_admin.php          ← Client portal management (admin/manager only)
├── search.php                ← Global search with web search integration
├── profile.php               ← User profile & password change
│
├── ── CLIENT PORTAL ──
├── client_portal.php         ← Standalone portal (separate session)
│
├── ── API ENDPOINTS ──
├── ajax_comments.php         ← GET/POST task comments (JSON)
├── attach_upload.php         ← File attachment upload/delete (JSON)
├── attach_list.php           ← Fetch attachments for any entity (JSON)
├── chat_api.php              ← All chat operations (JSON)
├── notif_api.php             ← Notification CRUD (JSON)
├── search_api.php            ← Global search queries (JSON)
├── email_track.php           ← Email open tracking pixel (1×1 GIF)
├── export_doc.php            ← Document export handler
│
├── ── CRON JOBS ──
├── cron_reminders.php        ← Task due reminders + invoice alerts (CLI)
│
├── ── MIGRATIONS ──
├── install.sql               ← Base schema (run first, always)
├── migration_v2.sql          ← Expenses, subscriptions, leads tables
├── migration_v8.sql          ← Email, notifications, invoices tables
├── migration_v9.sql          ← ALTER notifications (entity_type, entity_id)
├── migration_v10.sql         ← ALTER documents (task_id, lead_id)
│
├── includes/
│   ├── layout.php            ← Header, sidebar nav, CSS vars, global JS
│   ├── mailer.php            ← SMTP engine + email logging + notifications
│   └── attach_widget.php    ← Reusable file attachment widget
│
└── uploads/
    ├── documents/            ← All uploaded files (docs + attachments)
    ├── avatars/              ← User profile photos
    ├── chat/                 ← Chat file attachments
    ├── email_headers/        ← Email template header images
    └── index.php             ← Blank file (prevents directory listing)
```

---

## 4. DATABASE SCHEMA (ALL 20 TABLES)

### From install.sql (Base — run first)

**`users`**
- `id`, `name`, `email` (unique), `password` (bcrypt), `role` (admin/manager/member)
- `avatar`, `phone`, `department`, `status` (active/inactive), `last_login`, `created_at`
- ⚠ Password for all seeded users is `password` — change immediately

**`contacts`**
- `id`, `name`, `company`, `email`, `phone`, `address`, `notes`
- `type` (client/lead/partner/vendor), `status` (prospect/active/inactive)
- `assigned_to` → users.id, `created_by` → users.id

**`projects`**
- `id`, `title`, `description`, `status` (planning/active/on_hold/completed/cancelled)
- `priority` (low/medium/high/urgent), `start_date`, `due_date`, `budget`, `currency`
- **`progress` (INT 0–100)** — manually set by user via slider, NOT calculated from tasks
- `contact_id` → contacts.id, `created_by` → users.id
- ⚠ **Critical:** Dashboard uses `p.progress` directly. Do not replace with task ratio.

**`project_members`**
- Links users to projects: `project_id`, `user_id`, `role`, `joined_at`
- UNIQUE KEY on (project_id, user_id) — prevents duplicates

**`tasks`**
- `id`, `title`, `description`, `status` (todo/in_progress/review/done)
- `priority` (low/medium/high/urgent), `due_date`, `completed_at`
- `project_id` → projects.id, `assigned_to` → users.id, `created_by` → users.id

**`task_comments`**
- `id`, `task_id` → tasks.id (CASCADE DELETE), `user_id` → users.id
- `comment` (TEXT), `created_at`

**`documents`**
- `id`, `title`, `description`, `filename` (stored name), `original_name` (display name)
- `file_size`, `file_type`, `category`, `access` (all/admin/manager)
- `project_id` → projects.id, `contact_id` → contacts.id
- **`task_id`** (added v10) → links to tasks, **`lead_id`** (added v10) → links to leads
- `uploaded_by` → users.id
- ⚠ Files with `category='Attachment'` are inline attachments (from attach_widget)
- ⚠ Files with `category='General'` etc. are formal documents from documents.php

**`activity_log`**
- `id`, `user_id` → users.id, `action` (string), `entity_type`, `entity_id`, `details`
- Auto-populated by `logActivity()` helper in config.php

---

### From migration_v2.sql

**`expense_months`**
- `id`, `month_year` (VARCHAR '2026-01'), `month_label` ('January 2026')
- `revenue`, `notes`, `created_by` → users.id
- UNIQUE on month_year — one record per month

**`expense_entries`**
- `id`, `month_id` → expense_months.id (CASCADE DELETE)
- `category` (ENUM: Office & Rent, Software & Tools, Marketing, etc.)
- `own_spend`, `office_spend` (separate columns for personal vs company spend)
- `currency`, `purchase_date`, `expire_date`

**`subscriptions`**
- `id`, `invoice_number`, `paid_to`, `date_of_issue`, `date_of_end`
- `paid_amount`, `currency`, `payment_method`, `status` (active/expired/cancelled)
- `paid_by` → users.id, `created_by` → users.id

**`software_purchases`**
- `id`, `invoice_number`, `paid_to`, `date_purchase`, `date_expire`
- `usage_limit` (e.g. "Lifetime"), `paid_amount`, `currency`, `payment_method`

**`leads`**
- `id`, `name`, `company`, `email`, `phone`
- `source` (website/referral/social/cold_outreach/event/other)
- `service_interest`, `budget_est`, `budget_currency`
- `stage` (new/contacted/qualified/proposal/negotiation/won/lost)
- `priority`, `expected_close`, `last_contact`, `notes`, `loss_reason`
- `assigned_to` → users.id, `created_by` → users.id

**`lead_activities`**
- `id`, `lead_id` → leads.id (CASCADE), `user_id` → users.id
- `activity_type` (call/email/meeting/note/proposal/follow_up)
- `description`, `activity_date`

---

### From migration_v8.sql

**`email_settings`** (SMTP accounts)
- `id`, `name`, `from_name`, `from_email`, `host`, `port`, `encryption` (tls/ssl/none)
- `username`, `password`, `is_default`, `is_active`
- ⚠ Only one account should have `is_default=1`

**`email_log`**
- Full send log: `direction`, `subject`, `from_email`, `to_email` (JSON array), `cc_email`, `bcc_email`
- `body_html`, `body_text`, `status` (queued/sent/failed/draft), `error_msg`
- Links: `contact_id`, `lead_id`, `invoice_id`, `project_id`, `task_id`
- Tracking: `tracking_token`, `opened_at`, `opened_count`
- `sent_by` → users.id

**`email_templates`**
- `id`, `name`, `category`, `subject`, `body_html`
- `variables` (JSON array), `is_system` (1 = cannot delete), `created_by`
- Template variables: `{{name}}`, `{{company}}`, `{{project}}`, `{{invoice_no}}`, `{{amount}}`, `{{due_date}}`, `{{task}}`, `{{link}}`

**`notifications`**
- `id`, `user_id` → users.id (CASCADE), `type`, `title`, `body`, `link`
- `entity_type` (task/project/lead/document/message/invoice/comment) — added v9
- `entity_id` — added v9
- `is_read` (0/1), `email_sent` (0/1), `created_at`
- INDEX on (user_id, is_read) for fast unread count queries

**`invoices`** (from migration_v8.sql)
- `id`, `invoice_no` (auto-generated PAD-YYYY-NNN), `title`
- `contact_id` → contacts.id, `project_id` → projects.id
- `status` (draft/sent/partial/paid/overdue/cancelled)
- `currency`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `discount`, `total`
- `amount_paid`, `notes`, `terms`
- `is_recurring`, `recur_interval`, `recur_next`
- `created_by` → users.id

**`invoice_items`**
- `id`, `invoice_id` → invoices.id (CASCADE), `description`, `quantity`, `unit_price`, `amount`, `sort_order`

**`invoice_payments`**
- `id`, `invoice_id` → invoices.id, `amount`, `method`, `reference`, `paid_at`, `notes`
- `recorded_by` → users.id

**`invoice_counter`**
- `year`, `seq` — auto-increments per year for PAD-YYYY-NNN numbering

**Calendar tables** (created by calendar.php on first use)
- `calendar_events`: `id`, `title`, `description`, `event_type`, `start_datetime`, `end_datetime`, `all_day`, `location`, `color`, `project_id`, `task_id`, `contact_id`, `recur`, `status`, `created_by`
- `calendar_attendees`: `event_id`, `user_id`, `rsvp`

**Chat tables** (created by chat_api.php on first use)
- `chat_channels`: `id`, `type` (general/direct/project), `name`, `created_by`
- `chat_members`: `channel_id`, `user_id`
- `chat_messages`: `id`, `channel_id`, `user_id`, `parent_id`, `body`, `file_url`, `file_name`, `file_size`, `edited`, `deleted`
- `chat_reactions`: `message_id`, `user_id`, `emoji`

**Rich docs table** (used by documents.php)
- `rich_docs`: `id`, `title`, `content` (HTML from TinyMCE), `category`, `project_id`, `status` (draft/published), `created_by`

---

## 5. MIGRATION HISTORY

| File | When to run | What it adds |
|---|---|---|
| `install.sql` | Fresh install only | users, contacts, projects, project_members, tasks, task_comments, documents, activity_log + 3 seed users |
| `migration_v2.sql` | After install.sql | expense_months, expense_entries, subscriptions, software_purchases, leads, lead_activities |
| `migration_v8.sql` | After v2 | email_settings, email_log, email_templates, notifications, invoices, invoice_items, invoice_payments, invoice_counter + system email templates |
| `migration_v9.sql` | After v8 | ALTER notifications: adds entity_type, entity_id columns |
| `migration_v10.sql` | After v9 | ALTER documents: adds task_id, lead_id columns |

**⚠ Always run migrations in ORDER: install → v2 → v8 → v9 → v10**
**⚠ Calendar and chat tables are auto-created by PHP on first page visit — no migration needed**

---

## 6. CORE SYSTEM FILES

### config.php
Every PHP page includes this first. Defines:
- **Constants:** DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT, SITE_NAME, BASE_URL, CRM_VERSION
- **Upload constants:** UPLOAD_DIR, UPLOAD_DOC_DIR, UPLOAD_AVATAR_DIR, MAX_FILE_SIZE (20MB), ALLOWED_DOC_TYPES
- **`getCRMDB()`** — Singleton MySQLi connection using persistent `p:` prefix
- **`requireLogin()`** — Checks session, enforces 8-hour timeout
- **`requireRole(['admin','manager'])`** — Redirects to dashboard if role not in list
- **`currentUser()`** — Returns array: id, name, role, email, avatar
- **`isAdmin()`** — true only if role === 'admin'
- **`isManager()`** — true if role is 'admin' OR 'manager'
- **`logActivity(action, entity, entityId, details)`** — Writes to activity_log
- **`h(string)`** — htmlspecialchars wrapper (use on ALL user output)
- **`fDate(date, format)`** — Safe date formatter (returns '—' for null)
- **`statusColor(status)`** — Returns hex color for any status/priority string
- **`priorityIcon(priority)`** — Returns emoji for priority level
- **`formatSize(bytes)`** — Human-readable file size
- **`flash(msg, type)`** — Sets $_SESSION flash message shown on next page load

### includes/layout.php
Every CRM page calls `renderLayout('Page Title', 'active_page_key')` at start and `renderLayoutEnd()` at end.

- **`renderLayout(title, activePage)`** outputs: `<html>`, CSS variables, sidebar nav, header with search/bell/theme, `<main>` opening
- **`renderLayoutEnd()`** outputs: toast container, flash messages, all global JS (sidebar toggle, theme, modals, notifications, keyboard shortcuts)
- **Sidebar nav keys:** search, dashboard, mywork, projects, tasks, calendar, chat, emails, email_template, documents, contacts, invoices, leads, expenses, portal_admin, analytics, users, activity
- **Notification bell** polls `notif_api.php?action=count` every 30 seconds
- **Theme toggle** uses localStorage key `padak_theme` (dark/light)
- **`/` key shortcut** navigates to search.php
- **`Escape` key** closes notification panel

### includes/mailer.php
Included in emails.php and cron_reminders.php.

- **`crmSendEmail(opts, db)`** — Main send function. Loads SMTP config from DB, falls back to ENV vars, calls smtpSend() or phpMailFallback()
- **`smtpSend(smtp, to, cc, bcc, subject, html, text, msg_id)`** — Pure PHP SMTP using stream_socket_client. Supports SSL (port 465) and STARTTLS (port 587). Uses `mysqli_report(MYSQLI_REPORT_OFF)` to prevent exceptions
- **`sendAndLog(opts, db)`** — Wrapper: sends via crmSendEmail + logs to email_log. Always logs even if send fails
- **`logEmail(opts, status, error, msg_id, db)`** — Inserts into email_log table
- **`pushNotification(opts, db)`** — Creates in-app notification in notifications table AND sends email notification
- **`sendDueReminders(db)`** — Used by cron: finds tasks due today/tomorrow and overdue invoices, sends reminder emails
- **`renderEmailTemplate(html, vars)`** — Replaces `{{variable}}` placeholders in template HTML

### includes/attach_widget.php
- **`renderAttachWidget(entity, entity_id)`** — Outputs drag-and-drop file attachment UI
- **Supported entities:** 'task', 'project', 'contact', 'lead'
- CSS and JS output only once per page (static flag prevents duplication)
- Uses `attach_upload.php` for upload/delete and `attach_list.php` to fetch existing files

---

## 7. ALL PAGES — PURPOSE, ACCESS, FEATURES

### index.php
- **Access:** Public (no login required)
- **Purpose:** Login form
- **Session keys set on success:** crm_user_id, crm_name, crm_role, crm_email, crm_avatar, crm_last_activity
- **On success:** Redirects to dashboard.php

### logout.php
- **Access:** Any logged-in user
- **Action:** Destroys session → redirect to index.php

### dashboard.php
- **Access:** All roles (requireLogin)
- **Role differences:** Managers see leads pipeline + expense banner. Members see quick links
- **Data shown:** Stats KPIs, My Tasks (assigned to $uid), Task status overview, overdue tasks, recent projects, lead pipeline (manager+), recent activity (manager+)
- **⚠ Progress:** Uses `p.progress` (stored INT) — NOT calculated from task counts

### mywork.php
- **Access:** All roles
- **Personal dashboard:** Tasks buckets (overdue/in_progress/review/todo), This Week calendar, My Recent Activity (5 entries), Quick Actions (create task / log expense), My Active Projects, My Leads, Financial Snapshot, Delegated Tasks, Unread Client Messages
- **AJAX:** Inline task status update (quick_status), inline title rename, quick task create, quick expense log

### projects.php
- **Access:** All roles (managers see more options)
- **Views:** List view + single project detail (?view=ID)
- **Features:** Create/edit/delete projects, assign members, progress slider (saves to projects.progress), task list per project, document list per project, file attachment widget, kanban task board
- **⚠ Progress:** Saved as INT in projects.progress column via POST action='progress'

### tasks.php
- **Access:** All roles
- **Views:** List/Kanban (?mode=kanban), new/edit modal (?new=1 or ?edit=ID)
- **Features:** CRUD tasks, assign to users, link to project, priority/status/due date, comments (via ajax_comments.php), file attachment widget (edit mode only)
- **AJAX:** quick_status action updates task status inline

### contacts.php
- **Access:** All roles
- **Features:** CRUD contacts, type (client/lead/partner/vendor), assign to user, notes
- **File attachment:** Widget appears in edit modal (?edit=ID)
- **Relationship:** contacts.contact_id referenced by projects, invoices, email_log, documents

### leads.php
- **Access:** All roles (but pipeline data most useful for managers)
- **Views:** Kanban pipeline + list view + single lead detail (?view=ID)
- **Features:** Stage drag-drop (kanban), lead activities log (calls/emails/meetings), file attachment widget in detail view
- **Stages:** new → contacted → qualified → proposal → negotiation → won/lost

### documents.php
- **Access:** All roles
- **Tabs:** Files (uploads), Editor (rich docs with TinyMCE)
- **File types allowed:** pdf, doc, docx, xls, xlsx, ppt, pptx, txt, png, jpg, jpeg, gif, zip, rar
- **Max size:** 20MB (set in .htaccess and config.php)
- **Download:** `?download=ID`, PDF inline view: `?view_pdf=ID`
- **Rich docs:** Create/edit HTML documents with TinyMCE, export to PDF, print
- **⚠ Attachment distinction:** Documents with category='Attachment' are created by attach_widget.php (inline). All others are formal documents.

### invoices.php
- **Access:** All roles
- **Features:** Create invoices with line items, tax/discount, multi-currency, record payments, mark as sent (fires email), recurring invoices (monthly/quarterly/yearly)
- **Auto-numbering:** PAD-YYYY-NNN format via invoice_counter table
- **Statuses:** draft → sent → partial/paid/overdue → cancelled
- **PDF generation:** Uses fpdf.php

### expenses.php
- **Access:** All roles
- **Tabs:** Monthly Expenses, Subscriptions, Software Purchases
- **Monthly expenses:** Grouped by month, own_spend vs office_spend columns, revenue input, balance calculation
- **Subscriptions:** Recurring services with active/expired/cancelled status
- **Software:** One-time tool purchases with expiry tracking

### calendar.php
- **Access:** All roles
- **Features:** Month/week/day views, create events with attendees, RSVP (yes/no/maybe), link events to projects/tasks/contacts, color coding, recurring events
- **Tables used:** calendar_events, calendar_attendees

### chat.php
- **Access:** All roles
- **Features:** Channel-based messaging, direct messages, file attachments (images show inline, others as download links), emoji reactions, message threading (replies), edit/delete messages
- **API:** All operations via chat_api.php (JSON)
- **Polling:** Client polls for new messages every 4s (dev) / 8-10s (recommended production)
- **File storage:** uploads/chat/ directory

### emails.php
- **Access:** All roles
- **Tabs:** Compose, Sent Log, Templates, Alerts (notifications), SMTP Settings
- **Compose:** TinyMCE editor, contact autocomplete, CC support, apply templates, link to contact/project/invoice/lead
- **Sent Log:** Shows all outbound emails, status (sent/failed/opened), retry failed emails, delete log entries
- **SMTP Settings:** Add/edit/delete SMTP accounts, test connection, Gmail quick-setup button
- **⚠ Gmail setup:** Use App Password (16 chars), NOT regular Gmail password

### email_template.php
- **Access:** All roles (standalone page)
- **Purpose:** Visual email template generator for HR communications (appointment letters, welcome emails etc.)
- **Features:** Employee photo layouts, Outlook/Gmail copy formatting

### analytics.php
- **Access:** admin + manager only (`requireRole(['admin','manager'])`)
- **Sections:** Overview KPIs, Revenue & Billing, Task Performance, Team Performance, Sales Pipeline, Expenses, Projects, Activity & Communications
- **Safe queries:** Uses `aq()`, `ar()`, `av()` helper functions — NEVER crash on missing tables
- **Charts:** Chart.js (CDN) — doughnuts, bar charts, line charts, heatmap
- **Filters:** Period (7/30/90/365/all days) + Project filter
- **⚠ Important:** `mysqli_report(MYSQLI_REPORT_OFF)` is set to prevent MariaDB exceptions from crashing the page

### activity.php
- **Access:** admin + manager only
- **Features:** Paginated log (50/page), single entry delete (🗑 button), bulk delete (checkboxes), clear all (TRUNCATE)
- **Delete access:** Only isManager() can see/use delete controls

### users.php
- **Access:** admin + manager only
- **Features:** Add/edit/deactivate team members, set role (admin/manager/member), department, avatar upload

### portal_admin.php
- **Access:** admin + manager only
- **Purpose:** Manage client portal accounts and messages
- **Features:** Create portal login credentials for contacts, view/reply to client messages, see portal activity

### search.php
- **Access:** All roles
- **Tabs:** Global search (tasks/projects/contacts/leads/docs), Web search (iframe engines)
- **Web engines:** Startpage (iframe), Wikipedia (iframe), Brave, DuckDuckGo, Bing (new tab)
- **API:** search_api.php handles DB queries
- **Keyboard:** `/` key anywhere in CRM opens search

### profile.php
- **Access:** All logged-in users (own profile only)
- **Features:** Update name/phone/department, change password, upload avatar

### client_portal.php
- **Access:** Clients only (separate `padak_portal` session — completely isolated from CRM)
- **Features:** Client sees their projects, invoices (no drafts), documents, messages
- **Session:** Uses session_name('padak_portal') — cannot access CRM pages
- **Login:** Separate credentials stored in client_portal table

---

## 8. API & HELPER ENDPOINTS

### ajax_comments.php
- **GET** `?task_id=N` → Returns JSON array of comments for a task
- **POST** `action=add&task_id=N&comment=...` → Inserts comment, returns `{ok:true,id:N}`
- **Used by:** tasks.php (modal comment section), mywork.php

### attach_upload.php
- **POST** `action=upload, entity=task|project|contact|lead, entity_id=N, file=...` → Uploads file, inserts documents row, returns JSON with file info
- **POST** `action=delete, id=N` → Deletes file and documents row (uploader or manager only)
- **Returns JSON always**

### attach_list.php
- **GET** `?entity=task|project|contact|lead&id=N` → Returns JSON array of attachments (category='Attachment' only)
- **Used by:** attach_widget.php (JS awLoad function)

### chat_api.php
- **Actions (POST):** send_message, edit_message, delete_message, react, get_channels, create_channel, get_members, load_messages, mark_read
- **File upload:** Handled inline in send_message action — stores to uploads/chat/

### notif_api.php
- **Actions (GET/POST):** count, list, mark_read, mark_all, delete, clear_read
- **`count`** → `{count: N}` (used by 30s poll in layout.php)
- **`list`** → `{ok:true, notifications:[...], unread:N, total:N}` with filter support (all/unread/task/project/lead/invoice/message)
- **Used by:** layout.php notification bell (polling + panel)

### search_api.php
- **GET** `?q=search_term&type=all|task|project|contact|lead|document` → Returns JSON search results
- **Searches:** tasks.title, projects.title, contacts.name/company, leads.name, documents.title/original_name

### email_track.php
- **GET** `?t=tracking_token` → Returns 1×1 transparent GIF, increments email_log.opened_count, sets opened_at
- **Embedded in all outgoing emails as invisible pixel**

### export_doc.php
- Handles document export (rich docs → PDF via fpdf.php)

### cron_reminders.php
- **CLI only** — not accessible via browser
- **Run:** `php /path/to/cron_reminders.php` daily via cron
- **Cron line:** `0 8 * * * php /path/to/Project_Management/cron_reminders.php`
- **Does:** Sends task due reminder emails + creates notifications for tasks due today/tomorrow and overdue invoices

---

## 9. INCLUDES / SHARED FILES

### includes/layout.php — renderLayout() outputs:
1. `<!DOCTYPE html>`, charset meta, viewport
2. CSS custom properties (`:root` variables for colors, fonts, spacing)
3. Dark/light theme styles
4. Sidebar HTML (nav links grouped: Main / Resources / Business / Admin)
5. Header HTML (hamburger, page title, date, search button, notification bell + panel, theme toggle)
6. Opens `<main id="content">`

### includes/layout.php — renderLayoutEnd() outputs:
1. Closes `</main></div>`
2. Toast container div
3. JavaScript: sidebar, theme toggle, openModal/closeModal, toast, dropdown, modal-close-on-outside-click
4. Keyboard shortcut: `/` → search.php, `Escape` → close notification panel
5. Flash message display
6. Notification polling JS (pollNotifCount every 30s, full loadNotifs when panel open)

### CSS Variables available globally (defined in layout.php):
```
--bg        Background (main)
--bg2       Card background
--bg3       Input/subtle background
--bg4       Hover/accent background
--border    Default border color
--border2   Hover border color
--text      Primary text
--text2     Secondary text
--text3     Muted text
--orange    Primary (#f97316)
--orange-bg Orange tint background
--red       Danger (#ef4444)
--green     Success (#10b981)
--blue      Info (#6366f1)
--yellow    Warning (#f59e0b)
--purple    (#8b5cf6)
--header-h  58px
--sidebar-w 240px
--radius    Border radius
--radius-sm Small border radius
--radius-lg Large border radius
--font      Body font family
--font-display  Heading font family
```

---

## 10. UPLOAD SYSTEM

### File Storage Paths
| Type | Directory | Constant |
|---|---|---|
| Documents & Attachments | `uploads/documents/` | `UPLOAD_DOC_DIR` |
| User Avatars | `uploads/avatars/` | `UPLOAD_AVATAR_DIR` |
| Chat files | `uploads/chat/` | (hardcoded in chat_api.php) |
| Email headers | `uploads/email_headers/` | (hardcoded in email_template.php) |

### File Naming
- Documents: `doc_[uniqid].[ext]` (from documents.php)
- Attachments: `att_[uniqid].[ext]` (from attach_upload.php)
- Chat files: `chat_[uniqid].[ext]` (from chat_api.php)
- Avatars: `av_[uniqid].[ext]` (from users.php)

### Allowed Types
Defined in `config.php`: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, png, jpg, jpeg, gif, zip, rar

### Max Size
20MB (enforced in PHP and .htaccess)

### Download/View
- Download: `documents.php?download=ID`
- PDF inline: `documents.php?view_pdf=ID`
- Chat files: served via URL in chat messages

---

## 11. ROLE & PERMISSION SYSTEM

| Feature | member | manager | admin |
|---|---|---|---|
| View dashboard, projects, tasks | ✅ | ✅ | ✅ |
| Create/edit tasks & projects | ✅ | ✅ | ✅ |
| View/send emails | ✅ | ✅ | ✅ |
| View analytics & reports | ❌ | ✅ | ✅ |
| View activity log | ❌ | ✅ | ✅ |
| Delete activity logs | ❌ | ✅ | ✅ |
| Manage users (team) | ❌ | ✅ | ✅ |
| SMTP settings (email) | ❌ | ❌ | ✅ |
| Delete documents | Own only | ✅ | ✅ |
| Manage client portal accounts | ❌ | ✅ | ✅ |
| View leads pipeline value | ✅ | ✅ | ✅ |
| Access expense banner on dashboard | ❌ | ✅ | ✅ |

**Role checks in code:**
- `requireLogin()` — any authenticated user
- `requireRole(['admin','manager'])` — manager or above
- `requireRole(['admin'])` — admin only (currently: SMTP delete)
- `isAdmin()` — inline check for specific UI elements
- `isManager()` — inline check for manager+ UI elements

---

## 12. NOTIFICATION SYSTEM

### Flow
1. An event happens (task assigned, invoice sent, task due)
2. `pushNotification(opts, $db)` called in PHP → inserts into `notifications` table
3. Optionally also sends email notification via `crmSendEmail()`
4. Every page polls `notif_api.php?action=count` every 30 seconds
5. Badge on bell icon updates with unread count
6. User clicks bell → panel loads `notif_api.php?action=list`
7. Click notification → marks read + navigates to entity URL

### Where notifications are triggered
- **Task assigned** → tasks.php (pushNotification with entity_type='task')
- **Task due** → cron_reminders.php (sendDueReminders)
- **Invoice sent** → invoices.php (when status changed to 'sent')

### Filter tabs in notification panel
All, Unread, Tasks, Projects, Leads, Invoices

### Notification types (stored in `type` column)
`task_assigned`, `task_due`, `invoice_sent`, `invoice_paid`, `lead_update`, `mention`, `comment_added`, `project_update`, `info`

---

## 13. EMAIL SYSTEM

### Architecture
- **No external library** — pure PHP SMTP via `stream_socket_client()`
- **Config stored in DB** — `email_settings` table (multiple accounts supported)
- **Default account** — `is_default=1` row used for all auto-sends
- **Fallback** — If no DB config, reads MAIL_* from environment variables

### Gmail Setup (required for sending)
1. Enable 2FA on Gmail account
2. Generate App Password (16 chars) at myaccount.google.com/apppasswords
3. In emails.php → SMTP Settings → ⚡ Quick Gmail Setup
4. Enter Gmail address as both From Email and Username
5. Enter App Password (NOT regular Gmail password)
6. Port 465 / SSL (recommended) or Port 587 / TLS

### Email sending flow
```
User clicks Send in emails.php
    → sendAndLog(opts, $db)
        → crmSendEmail(opts, $db)            [loads SMTP from DB]
            → smtpSend(smtp_row, ...)        [pure PHP SMTP]
        → logEmail(opts, status, ...)        [always logs, even if failed]
    → redirect to Sent Log
```

### Open tracking
- All outgoing emails have `<img src="email_track.php?t={token}">` injected
- When client opens email, browser loads the pixel
- `email_track.php` increments `opened_count` in email_log

### Retry failed emails
- In Sent Log, failed emails show a ↺ retry button
- Retry re-sends using same content + logs a new email_log entry

---

## 14. CLIENT PORTAL (SEPARATE SYSTEM)

### Key difference from CRM
The client portal uses a **completely different PHP session**:
- CRM session name: `padak_crm`
- Portal session name: `padak_portal`
- They cannot interfere — a client CANNOT access CRM pages

### What clients can do
- Login with their portal credentials (created by CRM admin in portal_admin.php)
- View their projects and project status
- View their invoices (draft invoices are hidden)
- Download their documents
- Send messages to the CRM team

### CRM admin management (portal_admin.php)
- Create portal accounts linked to a contact
- View inbox of all client messages
- Reply to client messages
- See which clients are active

---

## 15. CSS VARIABLES & DESIGN SYSTEM

### Spacing Grid
Uses 8px grid: 4px, 8px, 12px, 16px, 24px, 32px, 48px

### Color Palette
- Primary: `#f97316` (orange)
- Success: `#10b981` (green)
- Danger: `#ef4444` (red)
- Warning: `#f59e0b` (yellow)
- Info: `#6366f1` (indigo)
- Purple: `#8b5cf6`
- Teal: `#14b8a6`
- Muted: `#94a3b8`

### Reusable CSS Classes (defined in layout.php)
- `.card`, `.card-header`, `.card-title` — standard content card
- `.stat-card`, `.stat-icon`, `.stat-val`, `.stat-lbl` — KPI metric cards
- `.stats-grid` — responsive grid for stat cards
- `.badge` — inline status pill
- `.btn`, `.btn-primary`, `.btn-ghost`, `.btn-danger`, `.btn-sm`, `.btn-icon`
- `.form-control`, `.form-group`, `.form-label`, `.form-row`
- `.table-wrap`, `table`, `.td-main` — responsive table
- `.modal-overlay`, `.modal`, `.modal-header`, `.modal-body`, `.modal-footer`
- `.progress-bar`, `.progress-fill` — progress bar
- `.empty-state`, `.empty-state .icon` — empty data placeholder
- `.dropdown`, `.dropdown-menu`, `.dropdown-item`
- `.avatar` — user avatar circle
- `.toast` — temporary notification

---

## 16. GLOBAL PHP HELPER FUNCTIONS (all in config.php)

| Function | Parameters | Returns | Purpose |
|---|---|---|---|
| `getCRMDB()` | none | mysqli | Singleton DB connection |
| `requireLogin()` | none | void | Redirects to login if no session |
| `requireRole(array)` | ['admin','manager'] | void | Redirects if role not in list |
| `currentUser()` | none | array | Returns id, name, role, email, avatar |
| `isAdmin()` | none | bool | True if role === 'admin' |
| `isManager()` | none | bool | True if role in admin/manager |
| `logActivity(action, entity, entityId, details)` | strings/ints | void | Writes activity_log row |
| `h(string)` | string | string | htmlspecialchars (use on all output) |
| `fDate(date, format)` | string, string | string | Safe date format, '—' for null |
| `formatSize(bytes)` | int | string | "1.5 MB" format |
| `statusColor(status)` | string | string | Returns hex color |
| `priorityIcon(priority)` | string | string | Returns emoji |
| `flash(msg, type)` | string, string | void | Sets session flash message |

---

## 17. JAVASCRIPT GLOBAL FUNCTIONS (all in layout.php renderLayoutEnd)

| Function | Purpose |
|---|---|
| `toggleSidebar()` | Open/close mobile sidebar |
| `closeSidebar()` | Close sidebar (overlay click) |
| `toggleTheme()` | Switch dark/light, saves to localStorage |
| `openModal(id)` | Add 'open' class to modal overlay |
| `closeModal(id)` | Remove 'open' class from modal overlay |
| `toast(msg, type)` | Show toast: type = 'success'/'error'/'info' |
| `toggleDropdown(id)` | Toggle dropdown menu open/closed |
| `toggleNotifPanel()` | Open/close notification panel |
| `setFilter(filter)` | Filter notifications by type |
| `loadNotifs(showLoader)` | Fetch + render notification list |
| `markAllRead()` | Mark all notifications read via API |
| `clearRead()` | Delete all read notifications |
| `updateBadge(count)` | Update bell badge number |
| `pollNotifCount()` | Fetch unread count, updates badge |
| `notifClick(el)` | Mark read + navigate to entity |
| `deleteNotif(btn, id)` | Remove single notification |

---

## 18. INTER-FILE RELATIONSHIPS MAP

```
config.php ──────────────────────────────────── required by EVERY file
includes/layout.php ─────────────────────────── required by all CRM pages
includes/mailer.php ─────────────────────────── required by emails.php, cron_reminders.php
includes/attach_widget.php ──────────────────── required by tasks.php, projects.php, contacts.php, leads.php

index.php ──────────────── POST → index.php → sets session → dashboard.php
logout.php ─────────────── destroys session → index.php
profile.php ────────────── reads/writes users table

dashboard.php ──────────── reads: projects(+p.progress), tasks, contacts, documents, rich_docs,
                                   leads, expense_entries, expense_months, activity_log
mywork.php ─────────────── reads: tasks, projects, project_members, calendar_events,
                                   calendar_attendees, activity_log, leads, expense_entries,
                                   expense_months, invoices, client_messages, notifications
                            writes: tasks (quick_status), expense_entries (quick_expense)

projects.php ───────────── reads/writes: projects, project_members, tasks, documents
                            calls: renderAttachWidget('project', id)
tasks.php ──────────────── reads/writes: tasks, task_comments
                            AJAX to: ajax_comments.php
                            calls: renderAttachWidget('task', id)
contacts.php ───────────── reads/writes: contacts
                            calls: renderAttachWidget('contact', id)
leads.php ──────────────── reads/writes: leads, lead_activities
                            calls: renderAttachWidget('lead', id)
documents.php ──────────── reads/writes: documents, rich_docs
                            files saved to: UPLOAD_DOC_DIR
invoices.php ───────────── reads/writes: invoices, invoice_items, invoice_payments, invoice_counter
                            calls: sendAndLog() from mailer.php (when status=sent)
expenses.php ───────────── reads/writes: expense_months, expense_entries, subscriptions, software_purchases
calendar.php ───────────── reads/writes: calendar_events, calendar_attendees
chat.php ───────────────── UI only, all operations via chat_api.php
chat_api.php ───────────── reads/writes: chat_channels, chat_members, chat_messages, chat_reactions
emails.php ─────────────── reads/writes: email_log, email_settings, email_templates, notifications
                            calls: sendAndLog(), pushNotification() from mailer.php
analytics.php ──────────── reads (never writes): all tables via aq()/ar()/av() safe helpers
activity.php ───────────── reads/writes: activity_log (delete)
users.php ──────────────── reads/writes: users table
portal_admin.php ───────── reads/writes: client_portal, client_messages tables
search.php ─────────────── UI only, queries via search_api.php
notif_api.php ──────────── reads/writes: notifications table
attach_upload.php ──────── reads/writes: documents table
                            files saved to: UPLOAD_DOC_DIR
attach_list.php ────────── reads: documents table (category='Attachment' only)
ajax_comments.php ──────── reads/writes: task_comments table
email_track.php ────────── updates: email_log.opened_count, opened_at
cron_reminders.php ─────── reads: tasks, invoices
                            calls: pushNotification(), sendAndLog() from mailer.php
```

---

## 19. KNOWN PATTERNS & CONVENTIONS

### POST Handler Pattern (every page with forms)
```php
ob_start();  // Buffer output FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        // ... handle
        flash('Created successfully.', 'success');
        ob_end_clean();
        header('Location: page.php');
        exit;
    }
}
ob_end_clean();  // Always clean buffer before HTML output
// ... HTML output starts here
renderLayout(...);
```

### bind_param Type String Convention
- `s` = string (VARCHAR, TEXT, ENUM, DATE stored as string)
- `i` = integer (INT, TINYINT)
- `d` = double (DECIMAL, FLOAT)
- ⚠ Always count characters in type string vs number of variables — mismatch causes fatal error

### Query Safety Convention
- Direct `$db->query()` is OK for tables guaranteed in install.sql
- For tables from later migrations (invoices, leads, expenses, email_log, etc.), always use `aq()`/`ar()`/`av()` helpers (defined in analytics.php) or wrap in try/catch
- Never chain `->fetch_assoc()` or `->fetch_all()` directly on a query that might return false

### Output Escaping
- **Always** use `h($variable)` when echoing user data to HTML
- Never echo raw database values directly

### CSRF
- Currently not implemented (planned for future)
- Forms use POST method which provides basic protection

### Navigation Active State
- Pass exact page key string to renderLayout() second parameter
- Must match the key used in layout.php nav link comparisons (e.g., 'dashboard', 'mywork', 'tasks')

### MariaDB Strict Mode
- `mysqli_report(MYSQLI_REPORT_OFF)` set in analytics.php prevents exceptions
- HAVING clauses must use full aggregate expressions, NOT aliases: `HAVING SUM(...)>0` not `HAVING alias>0`

---

## 20. UPGRADE / EXTENSION GUIDE

### Adding a New Page
1. Create `newpage.php` in CRM root
2. Start with:
   ```php
   <?php
   require_once 'config.php';
   require_once 'includes/layout.php';
   requireLogin(); // or requireRole(['admin','manager'])
   $db = getCRMDB();
   $user = currentUser();
   // POST handlers with ob_start() ...
   renderLayout('Page Title', 'nav_key');
   ?>
   <!-- HTML content -->
   <?php renderLayoutEnd(); ?>
   ```
3. Add nav link in `includes/layout.php` inside `renderLayout()` function

### Adding a New Database Table
1. Create `migration_vN.sql` with `CREATE TABLE IF NOT EXISTS`
2. Document it in the migration history table above
3. Use `IF NOT EXISTS` to make it safe to re-run

### Adding New Columns to Existing Tables
```sql
ALTER TABLE tablename ADD COLUMN IF NOT EXISTS col_name TYPE DEFAULT NULL;
```
Use `IF NOT EXISTS` (requires MySQL 8.0+ or MariaDB 10.3+)

### Adding File Attachments to a New Entity
1. Add `entity_id INT DEFAULT NULL` column to `documents` table (migration)
2. Add the column mapping to `attach_list.php` `$col_map` array
3. Add entity to `attach_upload.php` `$allowed_entities` array and FK mapping
4. Call `renderAttachWidget('entity_name', $id)` in the page PHP
5. Add `require_once 'includes/attach_widget.php'` to the page

### Adding a New Notification Type
1. Call `pushNotification()` in mailer.php format:
   ```php
   pushNotification([
       'user_id'     => $target_user_id,
       'type'        => 'your_type_name',
       'entity_type' => 'task|project|lead|invoice',
       'entity_id'   => $entity_id,
       'title'       => 'Notification title',
       'body'        => 'Short description',
       'link'        => 'relevant_page.php?id=N',
   ], $db);
   ```
2. Add icon mapping in `layout.php` NOTIF_ICONS JS object
3. Add color mapping in NOTIF_COLORS JS object

### Adding a New Email Template
1. Insert into `email_templates` table via phpMyAdmin
2. Set `is_system=1` to prevent deletion
3. Use `{{variable}}` placeholders (rendered by `renderEmailTemplate()`)

### Changing the Default Timezone
Edit `config.php`: `date_default_timezone_set('Asia/Colombo');`

### Deploying to Production (Hostinger)
1. Update `config.php`: DB credentials + `BASE_URL`
2. Upload all files to `public_html/crm/` (or subdirectory)
3. Run migrations in phpMyAdmin in order: install → v2 → v8 → v9 → v10
4. Set folder permissions: `uploads/` subdirectories to 755
5. Remove `ini_set('display_errors', 1)` from analytics.php
6. Set up cron: `0 8 * * * php /home/username/public_html/crm/cron_reminders.php`
7. Change all default user passwords immediately

### Default Login Credentials (change immediately!)
| Email | Password | Role |
|---|---|---|
| admin@thepadak.com | password | admin |
| thiki@thepadak.com | password | admin |
| vignesh@thepadak.com | password | manager |

---

*End of Padak CRM Documentation*
*Generated from full source code analysis — April 2026*
