# Footer CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make footer navigation sections, links, and social media icons fully configurable through the admin panel via two new database tables with backend CRUD and dynamic frontend rendering.

**Architecture:** Two new tables (`footer_section`, `footer_link`) with parent-child relationship. Backend CRUD controllers following existing Yii2+AdminLTE patterns. Frontend footer template queries these tables and renders `nav`-type sections as link columns and `social`-type sections as icon links, with fallback to current hardcoded content if no sections exist. Migration seeds default data and migrates social media URLs from `frontend_config`.

**Tech Stack:** Yii2 (PHP), MySQL/MariaDB, Kartik GridView, AdminLTE, Bootstrap Icons

---

## File Structure

### New Files
- `console/migrations/m260528_000001_create_table_footer_section.php` — Migration: create table + seed data
- `console/migrations/m260528_000002_create_table_footer_link.php` — Migration: create table + seed data
- `console/migrations/m260528_000003_insert_footer_menu.php` — Migration: insert admin menu items
- `common/models/FooterSection.php` — Shared ActiveRecord model with relation
- `common/models/FooterLink.php` — Shared ActiveRecord model
- `backend/models/FooterSectionSearch.php` — Search model for grid filtering
- `backend/models/FooterLinkSearch.php` — Search model for grid filtering
- `backend/controllers/FooterSectionController.php` — CRUD controller
- `backend/controllers/FooterLinkController.php` — CRUD controller
- `backend/views/footer-section/index.php` — Grid view
- `backend/views/footer-section/view.php` — Detail view
- `backend/views/footer-section/create.php` — Create form wrapper
- `backend/views/footer-section/update.php` — Update form wrapper
- `backend/views/footer-section/_form.php` — Shared form partial
- `backend/views/footer-link/index.php` — Grid view
- `backend/views/footer-link/view.php` — Detail view
- `backend/views/footer-link/create.php` — Create form wrapper
- `backend/views/footer-link/update.php` — Update form wrapper
- `backend/views/footer-link/_form.php` — Shared form partial

### Modified Files
- `frontend/views/layouts/footer.php` — Replace hardcoded sections/links with dynamic query

---

### Task 1: Database Migration — Create Tables and Seed Data

**Files:**
- Create: `console/migrations/m260528_000001_create_table_footer_section.php`
- Create: `console/migrations/m260528_000002_create_table_footer_link.php`

- [ ] **Step 1: Create the footer_section migration**

```php
<?php

namespace console\migrations;

use Yii;
use yii\db\Migration;

class m260528_000001_create_table_footer_section extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%footer_section}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'type' => $this->string(20)->notNull()->defaultValue('nav'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->insert('{{%footer_section}}', [
            'title' => 'LAYANAN',
            'type' => 'nav',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_section}}', [
            'title' => 'TENTANG',
            'type' => 'nav',
            'sort_order' => 2,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_section}}', [
            'title' => 'MEDIA SOSIAL',
            'type' => 'social',
            'sort_order' => 3,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%footer_section}}');
    }
}
```

- [ ] **Step 2: Create the footer_link migration**

This migration reads existing social media URLs from `frontend_config` (IDs 13, 14, 15) and migrates them into `footer_link` rows.

```php
<?php

namespace console\migrations;

use Yii;
use yii\db\Migration;

class m260528_000002_create_table_footer_link extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%footer_link}}', [
            'id' => $this->primaryKey(),
            'section_id' => $this->integer()->notNull(),
            'label' => $this->string(255)->notNull(),
            'url' => $this->string(500)->notNull()->defaultValue('#'),
            'icon_class' => $this->string(100),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'open_in_new_tab' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->createIndex('idx-footer_link-section_id', '{{%footer_link}}', 'section_id');

        // Seed LAYANAN links (section id = 1)
        $this->insert('{{%footer_link}}', [
            'section_id' => 1,
            'label' => 'Pengaduan',
            'url' => '#',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 1,
            'label' => 'Penilaian',
            'url' => '#',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Seed TENTANG links (section id = 2)
        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'Beranda',
            'url' => '/',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'FAQ',
            'url' => '#',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 2,
            'label' => 'Kontak Kami',
            'url' => '#',
            'sort_order' => 3,
            'status' => 1,
            'open_in_new_tab' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Seed MEDIA SOSIAL links (section id = 3)
        // Migrate from frontend_config IDs 13 (facebook), 14 (youtube), 15 (instagram)
        $fb = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 13')->queryScalar();
        $yt = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 14')->queryScalar();
        $ig = $this->db->createCommand('SELECT isi_konfig FROM {{%frontend_config}} WHERE id = 15')->queryScalar();

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Facebook',
            'url' => $fb ? strip_tags($fb) : '#',
            'icon_class' => 'bi bi-facebook',
            'sort_order' => 1,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Instagram',
            'url' => $ig ? strip_tags($ig) : '#',
            'icon_class' => 'bi bi-instagram',
            'sort_order' => 2,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'Twitter/X',
            'url' => '#',
            'icon_class' => 'bi bi-twitter-x',
            'sort_order' => 3,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->insert('{{%footer_link}}', [
            'section_id' => 3,
            'label' => 'YouTube',
            'url' => $yt ? strip_tags($yt) : '#',
            'icon_class' => 'bi bi-youtube',
            'sort_order' => 4,
            'status' => 1,
            'open_in_new_tab' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%footer_link}}');
    }
}
```

- [ ] **Step 3: Run migrations and verify tables**

```bash
php yii migrate/up
```

Expected: Both migrations apply successfully, showing "3 rows inserted" for `footer_section` and "9 rows inserted" for `footer_link`.

- [ ] **Step 4: Commit migrations**

```bash
git add console/migrations/m260528_000001_create_table_footer_section.php console/migrations/m260528_000002_create_table_footer_link.php && git commit -m "feat: add footer_section and footer_link tables with seed data"
```

---

### Task 2: Common Models — FooterSection and FooterLink

**Files:**
- Create: `common/models/FooterSection.php`
- Create: `common/models/FooterLink.php`

- [ ] **Step 1: Create FooterSection model**

This model uses `common/models/` namespace (shared between frontend and backend) with TimestampBehavior and a `getActiveLinks()` relation.

```php
<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class FooterSection extends ActiveRecord
{
    const TYPE_NAV = 'nav';
    const TYPE_SOCIAL = 'social';

    public static function tableName()
    {
        return '{{%footer_section}}';
    }

    public function rules()
    {
        return [
            [['title', 'type'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_NAV, self::TYPE_SOCIAL]],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Judul',
            'type' => 'Tipe',
            'sort_order' => 'Urutan',
            'status' => 'Status',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diubah Pada',
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getLinks()
    {
        return $this->hasMany(FooterLink::class, ['section_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getActiveLinks()
    {
        return $this->getLinks()->andOnCondition(['footer_link.status' => 1]);
    }

    public static function getActiveSections()
    {
        return self::find()
            ->where(['status' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->with('activeLinks')
            ->all();
    }
}
```

- [ ] **Step 2: Create FooterLink model**

```php
<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class FooterLink extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%footer_link}}';
    }

    public function rules()
    {
        return [
            [['section_id', 'label'], 'required'],
            [['section_id'], 'integer'],
            [['label'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 500],
            [['url'], 'default', 'value' => '#'],
            [['icon_class'], 'string', 'max' => 100],
            [['sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 1],
            [['open_in_new_tab'], 'integer'],
            [['open_in_new_tab'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'section_id' => 'Bagian',
            'label' => 'Label',
            'url' => 'URL',
            'icon_class' => 'Ikon',
            'sort_order' => 'Urutan',
            'status' => 'Status',
            'open_in_new_tab' => 'Buka di Tab Baru',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diubah Pada',
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getSection()
    {
        return $this->hasOne(FooterSection::class, ['id' => 'section_id']);
    }
}
```

- [ ] **Step 3: Commit models**

```bash
git add common/models/FooterSection.php common/models/FooterLink.php && git commit -m "feat: add FooterSection and FooterLink common models"
```

---

### Task 3: Backend Models — Search Classes

**Files:**
- Create: `backend/models/FooterSectionSearch.php`
- Create: `backend/models/FooterLinkSearch.php`

- [ ] **Step 1: Create FooterSectionSearch model**

Following the existing pattern (e.g., `FrontendConfigSearch`) — extends the base model, overrides `rules()` for searchable fields, uses `ActiveDataProvider` with default sort by `sort_order`.

```php
<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FooterSection;

class FooterSectionSearch extends FooterSection
{
    public function rules()
    {
        return [
            [['id', 'sort_order', 'status'], 'integer'],
            [['title', 'type'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = FooterSection::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['sort_order' => SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
```

- [ ] **Step 2: Create FooterLinkSearch model**

```php
<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FooterLink;

class FooterLinkSearch extends FooterLink
{
    public function rules()
    {
        return [
            [['id', 'section_id', 'sort_order', 'status', 'open_in_new_tab'], 'integer'],
            [['label', 'url', 'icon_class'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = FooterLink::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['section_id' => SORT_ASC, 'sort_order' => SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'section_id' => $this->section_id,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'open_in_new_tab' => $this->open_in_new_tab,
        ]);

        $query->andFilterWhere(['like', 'label', $this->label])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'icon_class', $this->icon_class]);

        return $dataProvider;
    }
}
```

- [ ] **Step 3: Commit search models**

```bash
git add backend/models/FooterSectionSearch.php backend/models/FooterLinkSearch.php && git commit -m "feat: add FooterSectionSearch and FooterLinkSearch backend models"
```

---

### Task 4: Backend Controllers — FooterSectionController and FooterLinkController

**Files:**
- Create: `backend/controllers/FooterSectionController.php`
- Create: `backend/controllers/FooterLinkController.php`

- [ ] **Step 1: Create FooterSectionController**

Following the existing pattern (e.g., `FrontendConfigController`). CRUD actions with flash messages in Indonesian. Delete uses try/catch for `IntegrityException` (cascading child links). After create/update, redirect to index (not view) for a better UX.

```php
<?php

namespace backend\controllers;

use Yii;
use common\models\FooterSection;
use backend\models\FooterSectionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class FooterSectionController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new FooterSectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new FooterSection();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil ditambahkan');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil diubah');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil dihapus');
        } catch (\yii\db\IntegrityException $e) {
            Yii::$app->session->setFlash('error', 'Bagian footer tidak dapat dihapus karena memiliki link terkait');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = FooterSection::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Halaman yang dicari tidak ada.');
    }
}
```

- [ ] **Step 2: Create FooterLinkController**

```php
<?php

namespace backend\controllers;

use Yii;
use common\models\FooterLink;
use common\models\FooterSection;
use backend\models\FooterLinkSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class FooterLinkController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new FooterLinkSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new FooterLink();
        $sections = FooterSection::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC])->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Link footer berhasil ditambahkan');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'sections' => $this->getSectionsList(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Link footer berhasil diubah');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'sections' => $this->getSectionsList(),
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Link footer berhasil dihapus');
        return $this->redirect(['index']);
    }

    private function getSectionsList()
    {
        return \yii\helpers\ArrayHelper::map(
            FooterSection::find()->orderBy(['sort_order' => SORT_ASC])->all(),
            'id',
            'title'
        );
    }

    protected function findModel($id)
    {
        if (($model = FooterLink::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Halaman yang dicari tidak ada.');
    }
}
```

- [ ] **Step 3: Commit controllers**

```bash
git add backend/controllers/FooterSectionController.php backend/controllers/FooterLinkController.php && git commit -m "feat: add FooterSection and FooterLink backend controllers"
```

---

### Task 5: Backend Views — FooterSection

**Files:**
- Create: `backend/views/footer-section/index.php`
- Create: `backend/views/footer-section/view.php`
- Create: `backend/views/footer-section/create.php`
- Create: `backend/views/footer-section/update.php`
- Create: `backend/views/footer-section/_form.php`

- [ ] **Step 1: Create footer-section/index.php**

Following the `frontend-config/index.php` and `tipe-dokumen/index.php` patterns: Kartik GridView with panel, Pjax, action buttons.

```php
<?php

use mdm\admin\components\Helper;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use common\models\FooterSection;

$this->title = 'Bagian Footer';
$this->params['breadcrumbs'][] = $this->title;

Pjax::begin(['enablePushState' => false]);
?>
<div class="box-body table-responsive no-padding">
    <?= GridView::widget([
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-th-list"></i> Bagian Footer</h3>',
        ],
        'toolbar' => [
            ['content' => Html::a('<i class="fa fa-plus-circle"></i> Tambah Bagian', ['create'], ['class' => 'btn btn-success'])],
            '{export}',
            '{toggleData}',
        ],
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => 'Ditampilkan {begin} - {end} dari {totalCount} Data',
        'layout' => "{items}\n{summary}\n{pager}",
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'width: 50px;', 'class' => 'text-center'],
                'header' => 'No',
                'headerOptions' => ['class' => 'text-center'],
            ],
            'title',
            [
                'attribute' => 'type',
                'value' => function ($model) {
                    return $model->type === FooterSection::TYPE_NAV ? 'Navigasi' : 'Media Sosial';
                },
                'filter' => [FooterSection::TYPE_NAV => 'Navigasi', FooterSection::TYPE_SOCIAL => 'Media Sosial'],
                'contentOptions' => ['style' => 'width: 150px;'],
            ],
            'sort_order',
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return $model->status ? 'Aktif' : 'Tidak Aktif';
                },
                'filter' => [1 => 'Aktif', 0 => 'Tidak Aktif'],
                'contentOptions' => ['style' => 'width: 120px;'],
            ],
            [
                'label' => 'Jumlah Link',
                'value' => function ($model) {
                    return count($model->links);
                },
                'contentOptions' => ['style' => 'width: 100px; text-align: center;'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 200px;', 'class' => 'text-center'],
                'contentOptions' => ['style' => 'width: 200px;', 'class' => 'text-center'],
                'header' => 'Aksi',
                'template' => '{view} {links} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-success"><b class="fa fa-search-plus"></b></span>', ['view', 'id' => $model->id], ['title' => 'Lihat']);
                    },
                    'links' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-info"><b class="fa fa-link"></b></span>', ['/footer-link/index', 'FooterLinkSearch[section_id]' => $model->id], ['title' => 'Kelola Link']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-warning"><b class="fa fa-pencil"></b></span>', ['update', 'id' => $model->id], ['title' => 'Ubah']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-danger"><b class="fa fa-trash"></b></span>', ['delete', 'id' => $model->id], [
                            'title' => 'Hapus',
                            'data' => ['confirm' => 'Yakin akan menghapus bagian ini?', 'method' => 'post'],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
<?php Pjax::end(); ?>
</div>
```

- [ ] **Step 2: Create footer-section/view.php**

```php
<?php

use yii\helpers\Html;
use common\models\FooterSection;

$this->title = 'Detail Bagian Footer: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->title;
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= Html::encode($model->title) ?></b>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th style="width: 200px;">Judul</th><td><?= Html::encode($model->title) ?></td></tr>
            <tr><th>Tipe</th><td><?= $model->type === FooterSection::TYPE_NAV ? 'Navigasi' : 'Media Sosial' ?></td></tr>
            <tr><th>Urutan</th><td><?= $model->sort_order ?></td></tr>
            <tr><th>Status</th><td><?= $model->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td></tr>
        </table>

        <h4 style="margin-top: 20px;">Link di Bagian Ini</h4>
        <?php if ($model->links): ?>
            <table class="table table-striped">
                <thead><tr><th>No</th><th>Label</th><th>URL</th><th>Ikon</th><th>Urutan</th><th>Status</th></tr></thead>
                <tbody>
                <?php $i = 1; foreach ($model->links as $link): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= Html::encode($link->label) ?></td>
                        <td><?= Html::encode($link->url) ?></td>
                        <td><?= $link->icon_class ? Html::encode($link->icon_class) : '-' ?></td>
                        <td><?= $link->sort_order ?></td>
                        <td><?= $link->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Belum ada link di bagian ini.</p>
        <?php endif; ?>
    </div>
</div>
```

- [ ] **Step 3: Create footer-section/_form.php**

Shared form partial for create/update, following the `_form-create.php`/`_form-update.php` pattern with horizontal Bootstrap form and AdminLTE box styling.

```php
<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\FooterSection;

$form = ActiveForm::begin([
    'layout' => 'horizontal',
    'fieldConfig' => [
        'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
        'horizontalCssClasses' => [
            'label' => 'col-sm-2',
            'offset' => 'col-sm-offset-4',
            'wrapper' => 'col-sm-8',
        ],
    ],
]);
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= $model->isNewRecord ? 'Form Tambah Bagian Footer' : 'Form Ubah Bagian Footer' ?></b>
    </div>
    <div class="box-body">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'type')->dropDownList(
            [FooterSection::TYPE_NAV => 'Navigasi', FooterSection::TYPE_SOCIAL => 'Media Sosial'],
            ['prompt' => '-- Pilih Tipe --']
        ) ?>
        <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>
        <?= $form->field($model, 'status')->dropDownList(
            [1 => 'Aktif', 0 => 'Tidak Aktif'],
            ['prompt' => '-- Pilih Status --']
        ) ?>
    </div>
    <div class="box-footer">
        <?= Html::submitButton(
            '<i class="fa fa-save"></i> ' . ($model->isNewRecord ? 'Simpan' : 'Ubah'),
            ['class' => 'btn btn-success btn-flat']
        ) ?>
        <?= Html::a('<i class="fa fa-remove"></i> Batal', ['index'], ['class' => 'btn btn-danger btn-flat']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
```

- [ ] **Step 4: Create footer-section/create.php**

```php
<?php

use yii\helpers\Html;

$this->title = 'Tambah Bagian Footer';
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
```

- [ ] **Step 5: Create footer-section/update.php**

```php
<?php

use yii\helpers\Html;

$this->title = 'Ubah Bagian Footer: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Ubah';
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
```

- [ ] **Step 6: Commit footer-section views**

```bash
git add backend/views/footer-section/ && git commit -m "feat: add footer-section backend views"
```

---

### Task 6: Backend Views — FooterLink

**Files:**
- Create: `backend/views/footer-link/index.php`
- Create: `backend/views/footer-link/view.php`
- Create: `backend/views/footer-link/create.php`
- Create: `backend/views/footer-link/update.php`
- Create: `backend/views/footer-link/_form.php`

- [ ] **Step 1: Create footer-link/index.php**

```php
<?php

use mdm\admin\components\Helper;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use common\models\FooterSection;

$this->title = 'Link Footer';
$this->params['breadcrumbs'][] = $this->title;

Pjax::begin(['enablePushState' => false]);
?>
<div class="box-body table-responsive no-padding">
    <?= GridView::widget([
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-link"></i> Link Footer</h3>',
        ],
        'toolbar' => [
            ['content' => Html::a('<i class="fa fa-plus-circle"></i> Tambah Link', ['create'], ['class' => 'btn btn-success'])],
            '{export}',
            '{toggleData}',
        ],
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => 'Ditampilkan {begin} - {end} dari {totalCount} Data',
        'layout' => "{items}\n{summary}\n{pager}",
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'width: 50px;', 'class' => 'text-center'],
                'header' => 'No',
                'headerOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'section_id',
                'value' => function ($model) {
                    return $model->section ? $model->section->title : '-';
                },
                'filter' => \yii\helpers\ArrayHelper::map(FooterSection::find()->orderBy(['sort_order' => SORT_ASC])->all(), 'id', 'title'),
                'contentOptions' => ['style' => 'width: 200px;'],
            ],
            'label',
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->url), $model->url, ['target' => '_blank', 'class' => 'text-info']);
                },
            ],
            [
                'attribute' => 'icon_class',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->icon_class ? '<i class="' . Html::encode($model->icon_class) . '"></i> ' . Html::encode($model->icon_class) : '-';
                },
                'contentOptions' => ['style' => 'width: 200px;'],
            ],
            'sort_order',
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return $model->status ? 'Aktif' : 'Tidak Aktif';
                },
                'filter' => [1 => 'Aktif', 0 => 'Tidak Aktif'],
                'contentOptions' => ['style' => 'width: 120px;'],
            ],
            [
                'attribute' => 'open_in_new_tab',
                'value' => function ($model) {
                    return $model->open_in_new_tab ? 'Ya' : 'Tidak';
                },
                'filter' => [1 => 'Ya', 0 => 'Tidak'],
                'contentOptions' => ['style' => 'width: 120px;'],
                'label' => 'Tab Baru',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 150px;', 'class' => 'text-center'],
                'contentOptions' => ['style' => 'width: 150px;', 'class' => 'text-center'],
                'header' => 'Aksi',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-success"><b class="fa fa-search-plus"></b></span>', ['view', 'id' => $model->id], ['title' => 'Lihat']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-warning"><b class="fa fa-pencil"></b></span>', ['update', 'id' => $model->id], ['title' => 'Ubah']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-danger"><b class="fa fa-trash"></b></span>', ['delete', 'id' => $model->id], [
                            'title' => 'Hapus',
                            'data' => ['confirm' => 'Yakin akan menghapus link ini?', 'method' => 'post'],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
<?php Pjax::end(); ?>
</div>
```

- [ ] **Step 2: Create footer-link/view.php**

```php
<?php

use yii\helpers\Html;
use common\models\FooterSection;

$this->title = 'Detail Link Footer: ' . $model->label;
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->label;
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= Html::encode($model->label) ?></b>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th style="width: 200px;">Bagian</th><td><?= $model->section ? Html::encode($model->section->title) : '-' ?></td></tr>
            <tr><th>Label</th><td><?= Html::encode($model->label) ?></td></tr>
            <tr><th>URL</th><td><?= Html::a(Html::encode($model->url), $model->url, ['target' => '_blank']) ?></td></tr>
            <tr><th>Ikon</th><td><?= $model->icon_class ? '<i class="' . Html::encode($model->icon_class) . '"></i> ' . Html::encode($model->icon_class) : '-' ?></td></tr>
            <tr><th>Urutan</th><td><?= $model->sort_order ?></td></tr>
            <tr><th>Status</th><td><?= $model->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td></tr>
            <tr><th>Buka di Tab Baru</th><td><?= $model->open_in_new_tab ? 'Ya' : 'Tidak' ?></td></tr>
        </table>
    </div>
</div>
```

- [ ] **Step 3: Create footer-link/_form.php**

```php
<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$form = ActiveForm::begin([
    'layout' => 'horizontal',
    'fieldConfig' => [
        'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
        'horizontalCssClasses' => [
            'label' => 'col-sm-2',
            'offset' => 'col-sm-offset-4',
            'wrapper' => 'col-sm-8',
        ],
    ],
]);
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= $model->isNewRecord ? 'Form Tambah Link Footer' : 'Form Ubah Link Footer' ?></b>
    </div>
    <div class="box-body">
        <?= $form->field($model, 'section_id')->dropDownList($sections, ['prompt' => '-- Pilih Bagian --']) ?>
        <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'icon_class')->textInput(['maxlength' => true])->hint('Contoh: bi bi-facebook, bi bi-instagram') ?>
        <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>
        <?= $form->field($model, 'status')->dropDownList([1 => 'Aktif', 0 => 'Tidak Aktif'], ['prompt' => '-- Pilih Status --']) ?>
        <?= $form->field($model, 'open_in_new_tab')->checkbox() ?>
    </div>
    <div class="box-footer">
        <?= Html::submitButton(
            '<i class="fa fa-save"></i> ' . ($model->isNewRecord ? 'Simpan' : 'Ubah'),
            ['class' => 'btn btn-success btn-flat']
        ) ?>
        <?= Html::a('<i class="fa fa-remove"></i> Batal', ['index'], ['class' => 'btn btn-danger btn-flat']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
```

- [ ] **Step 4: Create footer-link/create.php**

```php
<?php

use yii\helpers\Html;

$this->title = 'Tambah Link Footer';
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model, 'sections' => $sections]) ?>
</div>
```

- [ ] **Step 5: Create footer-link/update.php**

```php
<?php

use yii\helpers\Html;

$this->title = 'Ubah Link Footer: ' . $model->label;
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->label, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Ubah';
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model, 'sections' => $sections]) ?>
</div>
```

- [ ] **Step 6: Commit footer-link views**

```bash
git add backend/views/footer-link/ && git commit -m "feat: add footer-link backend views"
```

---

### Task 7: Admin Menu — Add Footer Menu Items

**Files:**
- Create: `console/migrations/m260528_000003_insert_footer_menu.php`

- [ ] **Step 1: Create the menu migration**

Following the pattern from `m260507_000003_insert_visitor_report_menu.php`. Adds a parent menu "Footer" and two child items "Bagian Footer" and "Link Footer" under the "Akses Kontrol" group (parent id=1) to keep admin configuration items together.

```php
<?php

namespace console\migrations;

use yii\db\Migration;

class m260528_000003_insert_footer_menu extends Migration
{
    public function safeUp()
    {
        $this->insert('{{%menu}}', [
            'name' => 'Footer',
            'parent' => 1,
            'route' => null,
            'order' => 0,
            'data' => json_encode(['icon' => 'fa fa-columns']),
        ]);

        $parentId = $this->db->getLastInsertID();

        $this->insert('{{%menu}}', [
            'name' => 'Bagian Footer',
            'parent' => $parentId,
            'route' => '/footer-section/index',
            'order' => 1,
            'data' => json_encode(['icon' => 'fa fa-th-list']),
        ]);

        $this->insert('{{%menu}}', [
            'name' => 'Link Footer',
            'parent' => $parentId,
            'route' => '/footer-link/index',
            'order' => 2,
            'data' => json_encode(['icon' => 'fa fa-link']),
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%menu}}', ['route' => '/footer-link/index']);
        $this->delete('{{%menu}}', ['route' => '/footer-section/index']);

        $parentId = $this->db->createCommand(
            'SELECT id FROM {{%menu}} WHERE name = \'Footer\' AND route IS NULL AND parent = 1'
        )->queryScalar();

        if ($parentId) {
            $this->delete('{{%menu}}', ['id' => $parentId]);
        }
    }
}
```

- [ ] **Step 2: Run the migration and verify menu appears**

```bash
php yii migrate/up
```

Log into the admin panel and verify that the "Footer" menu group appears under "Akses Kontrol" in the sidebar with "Bagian Footer" and "Link Footer" sub-items.

- [ ] **Step 3: Add RBAC permissions for the new routes**

Log into the admin panel as superadmin, go to Routes, and ensure that `/footer-section/index`, `/footer-section/create`, `/footer-section/update`, `/footer-section/view`, `/footer-section/delete`, `/footer-link/index`, `/footer-link/create`, `/footer-link/update`, `/footer-link/view`, `/footer-link/delete` are available and assigned to the appropriate roles. The superadmin role with `/*` permission will have access automatically.

- [ ] **Step 4: Commit menu migration**

```bash
git add console/migrations/m260528_000003_insert_footer_menu.php && git commit -m "feat: add footer menu items to admin sidebar"
```

---

### Task 8: Frontend — Dynamic Footer Rendering

**Files:**
- Modify: `frontend/views/layouts/footer.php`

- [ ] **Step 1: Update footer.php to use dynamic sections and links**

Replace the hardcoded LAYANAN, TENTANG, and social media sections with a dynamic query. Keep the institution info block (frontend_config IDs 2, 4, 5, 6, 7) and analytics strip unchanged. Keep the fallback hardcoded content for when no sections exist.

The key changes:
1. Add `use common\models\FooterSection;` at the top
2. Remove the `FrontendConfig` queries for social media (IDs 13, 14, 15) and the `$fb`, `$yt`, `$ig` variables
3. Query `$sections = FooterSection::getActiveSections();`
4. Split sections by type: `$navSections` and `$socialSections`
5. Replace hardcoded LAYANAN/TENTANG columns with a loop over `$navSections`
6. Replace hardcoded social media links with a loop over `$socialSection->activeLinks`
7. If `$sections` is empty, render the current hardcoded fallback

Here is the complete updated file:

```php
<?php

use yii\helpers\Html;
use backend\models\FrontendConfig;
use common\models\FooterSection;
use common\models\VisitorStats;

$today = date('Y-m-d');
$thisWeek = date('Y-m-d', strtotime('monday this week'));
$thisMonth = date('Y-m-01');
$thisYear = date('Y-01-01');

$todayStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_DAILY, 'stat_date' => $today, 'document_id' => null])->one();
$weekStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_WEEKLY, 'stat_date' => $thisWeek, 'document_id' => null])->one();
$monthStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_MONTHLY, 'stat_date' => $thisMonth, 'document_id' => null])->one();
$yearStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_YEARLY, 'stat_date' => $thisYear, 'document_id' => null])->one();

$todayVisits = $todayStat ? (int)$todayStat->unique_visits : 0;
$weekVisits = $weekStat ? (int)$weekStat->unique_visits : 0;
$monthVisits = $monthStat ? (int)$monthStat->unique_visits : 0;
$yearVisits = $yearStat ? (int)$yearStat->unique_visits : 0;

$logo = FrontendConfig::findOne(3);
$instansi = FrontendConfig::findOne(2);
$deskripsi = FrontendConfig::findOne(4);
$alamat = FrontendConfig::findOne(5);
$nomor = FrontendConfig::findOne(6);
$email = FrontendConfig::findOne(7);

$cleanInstansi = $instansi ? trim(strip_tags($instansi->isi_konfig)) : 'Badan Pembinaan Hukum Nasional - Kementerian Hukum R.I';
$cleanAlamat = $alamat ? trim(strip_tags($alamat->isi_konfig)) : 'Jl. Mayjend Sutoyo, Cililitan, Jakarta Timur';
$cleanNomor = $nomor ? trim(strip_tags($nomor->isi_konfig)) : 'Telp +62-21 8091909 (hunting) Faks +62-21 8011753';
$cleanEmail = $email ? trim(strip_tags($email->isi_konfig)) : 'humas@bphn.go.id · bphn.humaskerjasamantu@gmail.com';

$sections = FooterSection::getActiveSections();
$navSections = [];
$socialSections = [];
foreach ($sections as $section) {
    if ($section->type === FooterSection::TYPE_NAV) {
        $navSections[] = $section;
    } elseif ($section->type === FooterSection::TYPE_SOCIAL) {
        $socialSections[] = $section;
    }
}

$hasDynamicContent = !empty($navSections) || !empty($socialSections);
?>

<!-- ======= Footer ======= -->
<footer class="footer bphn-footer" style="background-color: #1a2752;" role="contentinfo">
  <div class="container py-5 mt-3 mb-1">
    <div class="row pt-2 pb-4">
      <!-- Info Address -->
      <div class="col-lg-5 col-md-12 mb-4 mb-lg-0 pe-lg-5">
        <h6 class="fw-bold mb-4" style="color: #ffffff; letter-spacing: 0.5px;">
          JARINGAN DOKUMENTASI DAN INFORMASI <span style="color: #ffc107;">HUKUM NASIONAL</span>
        </h6>
        <p class="mb-4" style="line-height: 1.6;">
          <?= Html::encode($cleanInstansi) ?>
        </p>
        <p class="mb-3" style="line-height: 1.6;">
          <?= Html::encode($cleanAlamat) ?>
        </p>
        <p class="mb-3" style="line-height: 1.6;">
          <?= Html::encode(str_replace('Faks', ' Faks', $cleanNomor)) ?>
        </p>
        <p class="mb-0" style="line-height: 1.6;">
          Email <?= Html::encode($cleanEmail) ?>
        </p>
      </div>

<?php if ($hasDynamicContent): ?>
    <?php foreach ($navSections as $section): ?>
      <?php if (!empty($section->activeLinks)): ?>
      <div class="col-lg-3 col-md-6 mb-4 mb-md-0 ps-lg-5">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;"><?= Html::encode($section->title) ?></h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <?php foreach ($section->activeLinks as $link): ?>
            <li><?= Html::a(Html::encode($link->label), Html::encode($link->url), array_filter([
                'class' => 'footer-link',
                'target' => $link->open_in_new_tab ? '_blank' : null,
                'rel' => $link->open_in_new_tab ? 'noopener noreferrer' : null,
            ])) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php
    $socialSection = !empty($socialSections) ? $socialSections[0] : null;
    $socialLinks = $socialSection ? $socialSection->activeLinks : [];
    ?>
<?php else: ?>
      <!-- Fallback: hardcoded content -->
      <div class="col-lg-3 col-md-6 mb-4 mb-md-0 ps-lg-5">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">LAYANAN</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="#" class="footer-link">Pengaduan</a></li>
          <li><a href="#" class="footer-link">Penilaian</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">TENTANG</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="/" class="footer-link">Beranda</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="#" class="footer-link">Kontak Kami</a></li>
        </ul>
      </div>
    <?php
    $socialLinks = [];
    ?>
<?php endif; ?>

    </div>

    <!-- Divider -->
    <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 0 0 25px 0;">

    <!-- Analytics Strip -->
    <div class="footer-analytics">
      <div class="container">
        <div class="analytics-strip">
          <div class="analytics-item">
            <i class="bi bi-calendar-day"></i>
            <span class="analytics-label">Hari Ini</span>
            <span class="analytics-value"><?= $todayVisits ?></span>
          </div>
          <div class="analytics-divider"></div>
          <div class="analytics-item">
            <i class="bi bi-calendar-week"></i>
            <span class="analytics-label">Minggu Ini</span>
            <span class="analytics-value"><?= $weekVisits ?></span>
          </div>
          <div class="analytics-divider"></div>
          <div class="analytics-item">
            <i class="bi bi-calendar-month"></i>
            <span class="analytics-label">Bulan Ini</span>
            <span class="analytics-value"><?= $monthVisits ?></span>
          </div>
          <div class="analytics-divider"></div>
          <div class="analytics-item">
            <i class="bi bi-calendar-event"></i>
            <span class="analytics-label">Tahun Ini</span>
            <span class="analytics-value"><?= $yearVisits ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center" style="font-size: 0.75rem;">
      <div class="d-flex flex-wrap justify-content-center justify-content-lg-start align-items-center gap-3 mb-3 mb-lg-0" style="color: #64748b;">
        <span class="text-white">&copy; <?= date('Y') ?> <?= Html::encode($cleanInstansi) ?> powered by <a href="https://ildis.bphn.go.id" target="_blank" style="color: #ffc107;">ILDIS</a></span>
      </div>

      <div class="d-flex align-items-center gap-4">
        <div class="d-flex align-items-center gap-2 text-white" style="cursor: pointer; font-size: 0.85rem;">
          <i class="bi bi-globe"></i>
          <span>Indonesia</span>
        </div>
        
        <div class="d-flex border-start ps-4 align-items-center gap-4" style="border-color: rgba(255, 255, 255, 0.1) !important; font-size: 1.15rem;">
<?php if (!empty($socialLinks)): ?>
    <?php foreach ($socialLinks as $link): ?>
          <?php
          $linkOptions = ['class' => 'footer-social'];
          if ($link->open_in_new_tab) {
              $linkOptions['target'] = '_blank';
              $linkOptions['rel'] = 'noopener noreferrer';
          }
          ?>
          <?php if ($link->icon_class === 'bi bi-twitter-x'): ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
            </svg>
          </a>
          <?php elseif ($link->icon_class): ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>><i class="<?= Html::encode($link->icon_class) ?>"></i></a>
          <?php else: ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>><i class="bi bi-link-45deg"></i></a>
          <?php endif; ?>
    <?php endforeach; ?>
<?php elseif ($hasDynamicContent): ?>
<?php else: ?>
          <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
          <a href="#" class="footer-social">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
            </svg>
          </a>
          <a href="#" class="footer-social"><i class="bi bi-youtube"></i></a>
<?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <style>
    .bphn-footer {
      color: #a5b4cc !important;
      font-size: 0.85rem;
    }
    .bphn-footer p, .bphn-footer span, .bphn-footer li {
      color: #a5b4cc !important;
    }
    .bphn-footer .text-white, .bphn-footer .text-white span {
      color: #ffffff !important;
    }
    .footer-link {
      color: #a5b4cc !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-link:hover {
      color: #ffffff !important;
    }
    .footer-link-muted {
      color: #728aad !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-link-muted:hover {
      color: #ffffff !important;
    }
    .footer-social {
      color: #a5b4cc !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-social:hover {
      color: #ffffff !important;
    }
    .footer-social svg {
      vertical-align: middle;
      transform: translateY(-2px);
    }

    /* Analytics Strip */
    .footer-analytics {
      background: rgba(255, 255, 255, 0.03);
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      padding: 16px 0;
      margin-bottom: 20px;
    }

    .analytics-strip {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0;
      flex-wrap: wrap;
    }

    .analytics-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      padding: 0 24px;
      min-width: 100px;
    }

    .analytics-item i {
      font-size: 1.1rem;
      color: #ffc107;
      opacity: 0.8;
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .analytics-item:hover i {
      opacity: 1;
      transform: translateY(-2px);
    }

    .analytics-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #728aad;
      font-weight: 500;
    }

    .analytics-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1;
      font-variant-numeric: tabular-nums;
    }

    .analytics-divider {
      width: 1px;
      height: 36px;
      background: rgba(255, 255, 255, 0.1);
      margin: 0 12px;
    }

    @media (max-width: 768px) {
      .analytics-strip {
        flex-direction: column;
        gap: 16px;
      }
      
      .analytics-item {
        padding: 0 16px;
        min-width: auto;
      }
      
      .analytics-divider {
        width: 40px;
        height: 1px;
        margin: 8px 0;
      }
    }
  </style>
</footer>
```

- [ ] **Step 2: Verify the footer renders correctly on the frontend**

Start the dev server and check the footer renders correctly with the seeded data. The visual output should be identical to the current footer.

```bash
php yii serve --port=8080
```

Open `http://localhost:8080` in a browser and verify:
- LAYANAN section shows Pengaduan and Penilaian links
- TENTANG section shows Beranda, FAQ, Kontak Kami links
- Social media icons appear at bottom right
- Institution info, analytics, copyright unchanged
- All links use the same CSS classes as before

- [ ] **Step 3: Test the admin CRUD**

Log into the admin panel, navigate to the new Footer menu:
- Create a new section (type: nav) and verify it appears on the frontend
- Add a link to the new section and verify it appears
- Edit a section title and verify the change
- Toggle a section to inactive and verify it disappears from the frontend
- Delete a link and verify it disappears

- [ ] **Step 4: Test the fallback behavior**

Temporarily set all sections to inactive (or truncate the footer_section table) and verify the hardcoded footer still renders correctly.

- [ ] **Step 5: Commit the footer update**

```bash
git add frontend/views/layouts/footer.php && git commit -m "feat: dynamic footer rendering from footer_section and footer_link tables"
```

---

### Task 9: Cache Invalidation (Optional Enhancement)

**Files:**
- Modify: `common/models/FooterSection.php`
- Modify: `common/models/FooterLink.php`

- [ ] **Step 1: Add caching to getActiveSections()**

Update `FooterSection::getActiveSections()` to use Yii2's query caching with a 1-hour TTL:

```php
public static function getActiveSections()
{
    $db = self::getDb();
    $key = 'footer_sections_active';
    $duration = 3600;

    $sections = $db->cache(function ($db) {
        return self::find()
            ->where(['status' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->with('activeLinks')
            ->all();
    }, $duration, new \yii\caching\DbDependency([
        'sql' => 'SELECT MAX(updated_at) FROM footer_section UNION ALL SELECT MAX(updated_at) FROM footer_link',
    ]));

    return $sections;
}
```

- [ ] **Step 2: Add cache invalidation on save/delete in the models**

Add `afterSave()` and `afterDelete()` to both `FooterSection` and `FooterLink` models to invalidate the cache:

In `FooterSection`:
```php
public function afterSave($insert, $changedAttributes)
{
    parent::afterSave($insert, $changedAttributes);
    Yii::$app->cache->delete('footer_sections_active');
}

public function afterDelete()
{
    parent::afterDelete();
    Yii::$app->cache->delete('footer_sections_active');
}
```

In `FooterLink`:
```php
public function afterSave($insert, $changedAttributes)
{
    parent::afterSave($insert, $changedAttributes);
    Yii::$app->cache->delete('footer_sections_active');
}

public function afterDelete()
{
    parent::afterDelete();
    Yii::$app->cache->delete('footer_sections_active');
}
```

- [ ] **Step 3: Verify caching works**

Create/update a section in admin and verify the frontend reflects the change immediately (due to cache invalidation). Then verify that subsequent page loads use the cached version.

- [ ] **Step 4: Commit caching**

```bash
git add common/models/FooterSection.php common/models/FooterLink.php && git commit -m "feat: add query caching with invalidation for footer sections"
```

**Note:** This task depends on Yii2 cache being configured in the application. If cache is not configured, skip this task — the footer will work without caching, just with a DB query on every page load.