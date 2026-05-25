# Enhanced Visitor Counter — Design Document

**Date:** 2026-05-07
**Status:** Approved
**Approach:** Raw Event Log with Scheduled Aggregation (Approach B)

---

## 1. Overview

Build a production-grade visitor analytics system for the ILDIS (Indonesian Law Documentation Information System) Yii2 codebase. The system tracks site-wide and per-page/document visits, prevents refresh abuse via session-aware cookies, and aggregates daily, weekly, monthly, yearly, and all-time statistics. It coexists with the existing `UserCounter` component but operates as an independent enhanced analytics layer.

---

## 2. Goals & Success Criteria

- **Goal 1:** Accurately count unique visitors per day without inflating from repeated page refreshes.
- **Goal 2:** Track both site-wide totals and per-document visits.
- **Goal 3:** Provide fast dashboard statistics in the backend, refreshed via nightly aggregation.
- **Goal 4:** Maintain a GDPR-friendly tracking approach (hashed fingerprint, no raw PII).

**Success Criteria:**
- Dashboard displays correct daily/weekly/monthly/yearly unique visitor counts.
- Refreshing a page 20 times in 10 minutes counts as 1 unique visit.
- Aggregation command runs without error and completes under 60 seconds.
- 90-day raw log retention does not degrade database performance.

---

## 3. Architecture & Data Model

### Two-Table System

| Table | Purpose |
|-------|---------|
| `visitor_log` | Raw event stream. One row per tracked visit. |
| `visitor_stats` | Pre-aggregated counters for fast dashboard reads. |

### Schema

**`visitor_log`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `visitor_fingerprint` | VARCHAR(64) | MD5 of IP + User Agent hash, GDPR-friendly. |
| `document_id` | VARCHAR(100) NULL | For per-page tracking; NULL for site-wide. |
| `page_url` | VARCHAR(500) | Actual URL visited. |
| `visit_date` | DATE | Date of the visit, indexed for aggregation. |
| `visit_time` | DATETIME | Precise timestamp. |
| `is_unique` | TINYINT(1) | 1 if first visit from this fingerprint for this page today. |
| `created_at` | TIMESTAMP | Auto-set. |

**`visitor_stats`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `stat_type` | ENUM('daily','weekly','monthly','yearly','all_time') | |
| `stat_date` | DATE | Start date of the period; for all_time, use 1970-01-01. |
| `document_id` | VARCHAR(100) NULL | NULL for site-wide totals. |
| `total_visits` | INT UNSIGNED | Raw visit count. |
| `unique_visits` | INT UNSIGNED | Unique visitor count. |
| `updated_at` | TIMESTAMP | Auto-update. |

### Key Indexes

- `visitor_log`: composite on `visitor_fingerprint, visit_date`
- `visitor_log`: composite on `document_id, visit_date`
- `visitor_stats`: composite on `stat_type, stat_date, document_id`

### Component Structure

```
common/components/VisitorCounter.php          # Visit tracking engine
common/models/VisitorLog.php                  # ActiveRecord for visitor_log
common/models/VisitorStats.php                # ActiveRecord for visitor_stats
console/controllers/VisitorController.php     # Aggregation CLI command
backend/controllers/VisitorReportController.php  # Dashboard controller
backend/views/visitor-report/index.php        # Dashboard overview
backend/views/visitor-report/_chart.php     # Chart partial
```

---

## 4. Data Flow & Session Tracking

### Visit Tracking Flow

When a request is received, the `VisitorCounter` component executes the following steps:

1. **Generate Fingerprint:** Create an MD5 hash of `IP_ADDRESS|USER_AGENT`.
2. **Check Cookie:** Look for `__visitor_id` (UUIDv4). Reuse it if found.
3. **Deduplicate Window:** Query `visitor_log` for a row matching the same `visitor_fingerprint`/`visitor_id` + the same `document_id` (or NULL) within the last **30 minutes**.
4. **Insert Event:**
   - If no row found: insert with `is_unique = 1`.
   - If found: insert with `is_unique = 0`.
5. **Update Stats (Realtime):** Increment the appropriate `visitor_stats` rows for today (daily), this week (weekly), this month (monthly), this year (yearly), and all_time.

### Cookie Configuration

| Property | Value |
|----------|-------|
| Name | `__visitor_id` |
| Value | UUID v4 |
| Expiry | 180 days |
| HTTPOnly | Yes |
| Secure | Yes (if HTTPS) |
| SameSite | Lax |

### Deduplication Rationale

- Refreshing a page repeatedly increments `total_visits` but not `unique_visits`.
- The 30-minute window prevents penalizing legitimate multi-page browsing (e.g., reading a law document for 25 minutes).
- Per-page tracking allows a visitor to be unique on both the Home page and a Peraturan page in the same day.

---

## 5. Aggregation Pipeline

### Command: `php yii visitor/aggregate`

**Frequency:** Nightly (via cron or systemd timer)
**Scope:** Recomputes the last 7 days of `visitor_stats` from `visitor_log` to maintain data integrity.

**Steps:**
1. Acquire MySQL advisory lock (`GET_LOCK`) to prevent concurrent runs.
2. Delete existing `visitor_stats` rows for the last 7 days.
3. For each day in the window:
   - Compute `daily` aggregates (site-wide + per-document).
   - Compute `weekly` aggregates (rolling 7-day window).
   - Compute `monthly` aggregates for the current month.
   - Compute `yearly` aggregates for the current year.
   - Compute `all_time` incremental totals.
4. Insert recomputed stats in a single batch transaction.
5. Release the advisory lock.

### Data Retention

- `visitor_log`: Retain raw events for **90 days**, then purge via admin action or automated cron.
- `visitor_stats`: Retained **indefinitely** (small aggregated footprint).

---

## 6. Backend Dashboard & Reporting

### Controller Actions (`VisitorReportController`)

| Action | Description |
|--------|-------------|
| `actionIndex()` | Overview page with summary cards, comparison bars, top 10 pages. |
| `actionAjaxChart()` | Returns JSON for Chart.js line/bar charts across time periods. |
| `actionExport()` | Exports statistics to Excel/CSV via PhpSpreadsheet. |
| `actionPurge()` | Admin-only action to prune `visitor_log` records older than X months. |

### Dashboard Widgets

1. **Summary Cards:**
   - Daily Unique / Total
   - Weekly Unique / Total
   - Monthly Unique / Total
   - Yearly Unique / Total
   - All-Time Unique / Total

2. **Comparison Bars:**
   - Today vs Yesterday
   - This Week vs Last Week
   - This Month vs Last Month

3. **Top Pages:**
   - Table of most visited documents/pages with percentage share.

4. **Trend Chart:**
   - Line chart showing the last 30 days of unique visits.

---

## 7. Error Handling & Resilience

| Scenario | Mitigation |
|----------|------------|
| High-traffic insertion deadlock | Retry once; if fails, log to `Yii::error()` and continue. |
| Clock skew in distributed requests | Aggregation uses a 1-hour buffer to avoid missing late rows. |
| Concurrent aggregation runs | MySQL advisory lock (`GET_LOCK`) ensures single execution. |
| Database write failure on realtime stats | Log silently; nightly aggregation will recorrect. |

---

## 8. Security & Privacy

- No raw IP addresses or browser fingerprints stored.
- `visitor_fingerprint` is a one-way MD5 hash of `IP|UA` — not reversible.
- Cookie is `HttpOnly`, `Secure`, and `SameSite=Lax`.
- Only backend `admin` role can access `VisitorReportController`.

---

## 9. Testing Strategy

- **Unit Tests:** `common/tests/unit/components/VisitorCounterTest.php` — test deduplication logic, fingerprint generation, unique vs total increments.
- **Functional Tests:** `backend/tests/functional/VisitorReportCest.php` — test dashboard rendering, chart JSON output, access control.
- **Data Integrity Tests:** Run aggregation on a seeded `visitor_log` fixture and assert `visitor_stats` outputs are correct.

---

## 10. Migration Plan

1. Create `visitor_log` table migration.
2. Create `visitor_stats` table migration.
3. Register `VisitorCounter` as a Yii2 component in `frontend/config/main.php`.
4. Deploy console command and schedule nightly cron.
5. Build backend dashboard controller and views.
6. Add access control rules in `VisitorReportController`.

---

*Design approved by user on 2026-05-07.*
