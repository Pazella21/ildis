# ILDIS Update Mechanism — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a self-contained `update.sh` script and Yii migration infrastructure that allows non-technical server admins to update ILDIS with a single command.

**Architecture:** A bash script (`update.sh`) that orchestrates Docker Compose-based updates — version checking via GitHub Releases API, automated DB backup, image pull, container restart, Yii migration execution, and health verification. A `VERSION` file tracks the installed version. Two Yii migrations establish the baseline and create the `update_log` table.

**Tech Stack:** Bash 4+, Docker Compose, Yii2 Console Migrations, GitHub Releases API, MySQL mysqldump

---

### Task 1: Create the VERSION file

**Files:**
- Create: `VERSION`

**Step 1: Create VERSION file**

The file should contain just the current version number: `4.1.1`

**Step 2: Commit**

```bash
git add VERSION
git commit -m "feat: add VERSION file for update tracking"
```

---

### Task 2: Create the console/migrations directory and configure Yii2 migrate controller

**Files:**
- Create: `console/migrations/.gitkeep`
- Modify: `console/config/main.php`

**Step 1: Create the migrations directory**

```bash
mkdir -p console/migrations
touch console/migrations/.gitkeep
```

**Step 2: Add migrate controller to console config**

In `console/config/main.php`, add a `migrate` entry to `controllerMap` that configures `yii\console\controllers\MigrateController` with `migrationPath` pointing to `@console/migrations`. Keep the existing `fixture` and `migration` (bizley) entries.

Add after the existing `migration` entry:

```php
'migrate' => [
    'class' => 'yii\console\controllers\MigrateController',
    'migrationPath' => '@console/migrations',
],
```

**Step 3: Commit**

```bash
git add console/migrations/.gitkeep console/config/main.php
git commit -m "feat: configure Yii2 migration path in console config"
```

---

### Task 3: Create the baseline migration

**Files:**
- Create: `console/migrations/m240101_000000_baseline_v411.php`

**Step 1: Create baseline migration**

This is a no-op migration that establishes the v4.1.1 baseline for existing installations.

```php
<?php

use yii\db\Migration;

class m240101_000000_baseline_v411 extends Migration
{
    public function safeUp()
    {
        echo "    > Baseline migration for ILDIS v4.1.1 — marked as applied.\n";
        return true;
    }

    public function safeDown()
    {
        echo "    > Cannot revert baseline migration.\n";
        return false;
    }
}
```

**Step 2: Commit**

```bash
git add console/migrations/m240101_000000_baseline_v411.php
git commit -m "feat: add baseline migration for v4.1.1"
```

---

### Task 4: Create the update_log migration

**Files:**
- Create: `console/migrations/m240601_000000_add_update_log_table.php`

**Step 1: Create update_log table migration**

```php
<?php

use yii\db\Migration;

class m240601_000000_add_update_log_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%update_log}}', [
            'id' => $this->primaryKey(),
            'version_from' => $this->string(20)->notNull(),
            'version_to' => $this->string(20)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'backup_file' => $this->string(255)->null(),
            'started_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'completed_at' => $this->timestamp()->null(),
            'notes' => $this->text()->null(),
        ]);

        $this->createIndex('idx-update_log-status', '{{%update_log}}', 'status');
        $this->createIndex('idx-update_log-version_to', '{{%update_log}}', 'version_to');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-update_log-version_to', '{{%update_log}}');
        $this->dropIndex('idx-update_log-status', '{{%update_log}}');
        $this->dropTable('{{%update_log}}');
    }
}
```

**Step 2: Commit**

```bash
git add console/migrations/m240601_000000_add_update_log_table.php
git commit -m "feat: add update_log table migration for tracking updates"
```

---

### Task 5: Create the update.sh script

**Files:**
- Create: `update.sh`

**Step 1: Create update.sh**

The script is already written and saved at `update.sh`. It includes:
- Pre-flight checks (docker, compose, .env, disk space, containers running)
- Version comparison via GitHub Releases API
- Database backup via mysqldump with gzip
- Docker compose pull and up -d
- MySQL readiness check
- Yii migration execution
- HTTP health check
- VERSION file update on success
- Rollback instructions on failure
- CLI flags: --yes, --version, --check, --rollback, --help

Make it executable: `chmod +x update.sh`

**Step 2: Update .gitignore and .dockerignore**

Add backup patterns to `.gitignore` (ignore `*.sql.gz` and `*.sql` in `backups/`).
Add `backups/` to `.dockerignore`.

**Step 3: Commit**

```bash
git add update.sh .gitignore .dockerignore backups/.gitkeep
git commit -m "feat: add update.sh script for non-technical ILDIS updates"
```

---

### Task 6: Update README with update documentation

**Files:**
- Modify: `README.md`

**Step 1: Add update documentation to README**

Add an "Updating" section to the README after the "Pengembangan" section, documenting:
- How to run `./update.sh` for updates
- Available flags (--check, --yes, --version, --rollback, --help)
- Where backups are stored
- How to rollback manually

**Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add update instructions to README"
```

---

### Task 7: Verify and clean up

**Step 1: Review all changed files**

```bash
git diff --stat
```

Verify:
- `VERSION` exists with content "4.1.1"
- `update.sh` exists and is executable
- `console/migrations/` has both migrations and `.gitkeep`
- `backups/.gitkeep` exists
- `console/config/main.php` has the migrate controller map entry
- `.gitignore` includes backup patterns
- `.dockerignore` includes `backups/`

**Step 2: Bash syntax check on update.sh**

```bash
bash -n update.sh
```

Expected: No output (no syntax errors)

**Step 3: Final commit (if any fixes needed)**