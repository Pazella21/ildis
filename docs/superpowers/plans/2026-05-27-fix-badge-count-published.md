# Fix Homepage Badge Counts — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make homepage card badge counts only include published documents (`is_publish = 1`).

**Architecture:** Add `is_publish = 1` filter to `DocumentQuery::total()` and to the two status-count queries in the homepage view. Two small edits, no structural changes.

**Tech Stack:** Yii2 PHP (ActiveRecord, ActiveQuery)

---

### Task 1: Add `is_publish` filter to `DocumentQuery::total()`

**Files:**
- Modify: `frontend/models/DocumentQuery.php:35-38`

- [ ] **Step 1: Update the `total()` method to filter by `is_publish`**

Change `frontend/models/DocumentQuery.php` line 35-38 from:

```php
     public function total($id)
     {
        return $this->andWhere(['tipe_dokumen' => $id])->count();
     }     
```

to:

```php
     public function total($id)
     {
        return $this->andWhere(['tipe_dokumen' => $id, 'is_publish' => 1])->count();
     }
```

- [ ] **Step 2: Verify no syntax errors**

Run: `php -l frontend/models/DocumentQuery.php`
Expected: `No syntax errors detected in frontend/models/DocumentQuery.php`

- [ ] **Step 3: Commit**

```bash
git add frontend/models/DocumentQuery.php
git commit -m "fix: add is_publish filter to DocumentQuery::total()"
```

---

### Task 2: Add `is_publish` filter to status count queries in homepage view

**Files:**
- Modify: `frontend/views/site/index.php:23-24`

- [ ] **Step 1: Update the Berlaku and Tidak Berlaku count queries**

Change `frontend/views/site/index.php` lines 23-24 from:

```php
$totalBerlaku       = Dokumen::find()->where(['status' => 'Berlaku'])->count();
$totalTidakBerlaku  = Dokumen::find()->where(['status' => 'Tidak Berlaku'])->count();
```

to:

```php
$totalBerlaku       = Dokumen::find()->where(['status' => 'Berlaku', 'is_publish' => 1])->count();
$totalTidakBerlaku  = Dokumen::find()->where(['status' => 'Tidak Berlaku', 'is_publish' => 1])->count();
```

- [ ] **Step 2: Verify no syntax errors**

Run: `php -l frontend/views/site/index.php`
Expected: `No syntax errors detected in frontend/views/site/index.php`

- [ ] **Step 3: Commit**

```bash
git add frontend/views/site/index.php
git commit -m "fix: add is_publish filter to Berlaku/Tidak Berlaku badge counts"
```