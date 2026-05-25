# ILDIS Migration Plan

## Migration to Modern Stack

**Target Stack:**

- **Frontend**: Next.js 16 (App Router)
- **Backend API**: Hono
- **Database**: PostgreSQL
- **ORM**: Drizzle
- **Authentication**: Better Auth

---

## Table of Contents

1. [Database Schema Mapping](#1-database-schema-mapping)
2. [API Endpoint Design](#2-api-endpoint-design)
3. [Data Migration Script](#3-data-migration-script)
4. [File Storage Migration](#4-file-storage-migration)
5. [Authentication Migration](#5-authentication-migration)

---

## 1. Database Schema Mapping

### 1.1 Core Tables

#### 1.1.1 Users Table (Admin/Librarian)

**Old Table**: `user`

| Old Column | New Column | Type | Notes |
|------------|------------|------|-------|
| id | id | serial | Primary key |
| username | username | varchar(255) | Unique |
| auth_key | - | - | Not needed (JWT in new system) |
| password_hash | passwordHash | text | bcrypt hash |
| password_reset_token | - | - | Handled by Better Auth |
| email | email | varchar(255) | Unique |
| status | status | smallint | 10=active, 0=deleted |
| suspended_until | suspendedUntil | timestamp | Account suspension |
| created_at | createdAt | timestamp | |
| updated_at | updatedAt | timestamp | |
| picture | avatarUrl | varchar(255) | Profile image |
| updated_by | updatedBy | int | FK to user |

**New Drizzle Schema**:

```typescript
// db/schema/users.ts
export const users = pgTable('users', {
  id: serial('id').primaryKey(),
  username: varchar('username', { length: 255 }).notNull().unique(),
  email: varchar('email', { length: 255 }).notNull().unique(),
  passwordHash: text('password_hash').notNull(),
  status: smallint('status').default(10),
  suspendedUntil: timestamp('suspended_until'),
  avatarUrl: varchar('avatar_url', { length: 255 }).default('avatar.png'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
  updatedBy: integer('updated_by').references(() => users.id),
});

export type User = typeof users.$inferSelect;
export type NewUser = typeof users.$inferInsert;
```

---

#### 1.1.2 Members Table (Public Library Members)

**Old Table**: `member`

| Old Column | New Column | Type | Notes |
|------------|------------|------|-------|
| id | id | serial | Primary key |
| username | username | varchar(100) | Unique |
| password_hash | passwordHash | text | bcrypt hash |
| status | status | int | Member status |
| member_name | name | varchar(100) | Full name |
| gender | gender | varchar(100) | |
| birth_date | birthDate | date | |
| member_type_id | memberTypeId | int | FK to member_types |
| member_address | address | text | |
| member_email | email | varchar | Contact email |
| postal_code | postalCode | varchar(100) | |
| personal_id_number | nik | varchar(100) | ID number (KTP) |
| inst_name | institutionName | varchar(100) | For institutional members |
| member_image | avatarUrl | text | Profile photo |
| member_ktp | ktpImage | text | KTP scan |
| member_since_date | memberSinceDate | date | |
| register_date | registerDate | date | |
| expire_date | expireDate | date | |
| phone_number | phone | varchar(50) | |
| fax_number | fax | varchar(50) | |
| member_notes | notes | text | |
| created_at | createdAt | timestamp | |
| updated_at | updatedAt | timestamp | |
| created_by | createdBy | int | FK to users |
| updated_by | updatedBy | int | FK to users |
| auth_key | - | - | Not needed |
| password_reset_token | - | - | Handled by Better Auth |

**New Drizzle Schema**:

```typescript
// db/schema/members.ts
export const members = pgTable('members', {
  id: serial('id').primaryKey(),
  username: varchar('username', { length: 100 }).notNull().unique(),
  email: varchar('email').notNull(),
  passwordHash: text('password_hash'),
  status: integer('status').default(1),
  name: varchar('name', { length: 100 }).notNull(),
  gender: varchar('gender', { length: 100 }).notNull(),
  birthDate: date('birth_date'),
  memberTypeId: integer('member_type_id').references(() => memberTypes.id),
  address: text('address'),
  postalCode: varchar('postal_code', { length: 100 }),
  nik: varchar('nik', { length: 100 }),
  institutionName: varchar('institution_name', { length: 100 }),
  avatarUrl: text('avatar_url'),
  ktpImage: text('ktp_image'),
  memberSinceDate: date('member_since_date'),
  registerDate: date('register_date'),
  expireDate: date('expire_date'),
  phone: varchar('phone', { length: 50 }),
  fax: varchar('fax', { length: 50 }),
  notes: text('notes'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
  createdBy: integer('created_by').references(() => users.id),
  updatedBy: integer('updated_by').references(() => users.id),
});

export type Member = typeof members.$inferSelect;
export type NewMember = typeof members.$inferInsert;
```

---

#### 1.1.3 Documents Table (Main Legal Documents)

**Old Table**: `document`

| Old Column | New Column | Type | Notes |
|------------|------------|------|-------|
| id | id | serial | Primary key |
| tipe_dokumen | documentTypeId | int | FK to document_types |
| judul | title | text | Document title |
| teu | teu | text | Title entity |
| nomor_peraturan | regulationNumber | varchar | Regulation number |
| nomor_panggil | callNumber | varchar | |
| dokumen_type_id | - | - | Deprecated |
| bentuk_peraturan | regulationForm | text | |
| jenis_peraturan | regulationKind | varchar | e.g., "UNDANG-UNDANG" |
| singkatan_jenis | kindAbbrev | varchar | Abbreviation |
| cetakan | edition | varchar | |
| tempat_terbit | publicationPlace | text | |
| penerbit | publisherId | int | FK to publishers |
| tanggal_penetapan | enactmentDate | date | Date of enactment |
| deskripsi_fisik | physicalDesc | varchar | |
| sumber | source | text | |
| isbn | isbn | varchar | |
| bahasa | languageId | int | FK to languages |
| bidang_hukum | legalFieldId | int | FK to legal_fields |
| nomor_induk_buku | bookId | varchar | |
| singkatan_bentuk | formAbbrev | varchar | |
| tipe_koleksi_nomor_eksemplar | collectionNumber | varchar | |
| pola_nomor_eksemplar | exemplarPattern | varchar | |
| jumlah_eksemplar | exemplarCount | varchar | |
| kala_terbit | publicationFrequency | varchar | |
| tahun_terbit | publicationYear | varchar | |
| tanggal_dibacakan | readDate | date | |
| pernyataan_tanggung_jawab | responsibility | text | |
| edisi | edition | varchar | |
| gmd | gmd | varchar | |
| judul_seri | seriesTitle | varchar | |
| klasifikasi | classificationId | int | FK to classifications |
| info_detil_spesifik | specificInfo | varchar | |
| abstrak | abstract | text | |
| gambar_sampul | coverImage | varchar | |
| label | label | varchar | |
| sembunyikan_di_opac | hideInOpac | boolean | |
| promosikan_ke_beranda | promoteToHomepage | boolean | Default: true |
| status_terakhir | lastStatus | varchar | |
| status | status | varchar | "Berlaku" / "Tidak Berlaku" |
| integrasi | integration | varchar | |
| _created_by | createdBy | varchar | |
| _updated_by | updatedBy | varchar | |
| created_at | createdAt | timestamp | |
| updated_at | updatedAt | timestamp | |
| inisiatif | initiative | varchar | |
| pemrakarsa | initiatorId | int | FK to initiators |
| tanggal_pengundangan | gazetteDate | date | |
| daerah | regionId | int | FK to regions |
| penandatanganan | signatory | varchar | |
| lembaga_peradilan | court | varchar | |
| pemohon | petitioner | varchar | |
| termohon | respondent | varchar | |
| jenis_perkara | caseType | varchar | |
| sub_klasifikasi | subClassification | varchar | |
| amar_status | verdictStatus | varchar | |
| berkekuatan_hukum_tetap | finalJudgment | varchar | |
| urusan_pemerintahan | governmentAffairId | int | FK |
| catatan_status_peraturan | statusNotes | varchar | |
| hit_see | viewCount | int | |
| hit_download | downloadCount | int | |
| sumber_perolehan | acquisitionSource | varchar | |
| is_publish | isPublished | boolean | |
| subjek_data | dataSubject | varchar | |
| slug | slug | varchar | URL slug |

**New Drizzle Schema**:

```typescript
// db/schema/documents.ts
export const documents = pgTable('documents', {
  id: serial('id').primaryKey(),
  documentTypeId: integer('document_type_id').references(() => documentTypes.id),
  title: text('title').notNull(),
  teu: text('teu'),
  regulationNumber: varchar('regulation_number'),
  callNumber: varchar('call_number'),
  regulationForm: text('regulation_form'),
  regulationKind: varchar('regulation_kind'),
  kindAbbrev: varchar('kind_abbrev'),
  edition: varchar('edition'),
  publicationPlace: text('publication_place'),
  publisherId: integer('publisher_id').references(() => publishers.id),
  enactmentDate: date('enactment_date'),
  physicalDesc: varchar('physical_desc'),
  source: text('source'),
  isbn: varchar('isbn'),
  languageId: integer('language_id').references(() => languages.id),
  legalFieldId: integer('legal_field_id').references(() => legalFields.id),
  bookId: varchar('book_id'),
  formAbbrev: varchar('form_abbrev'),
  collectionNumber: varchar('collection_number'),
  exemplarPattern: varchar('exemplar_pattern'),
  exemplarCount: varchar('exemplar_count'),
  publicationFrequency: varchar('publication_frequency'),
  publicationYear: varchar('publication_year'),
  readDate: date('read_date'),
  responsibility: text('responsibility'),
  seriesTitle: varchar('series_title'),
  classificationId: integer('classification_id').references(() => classifications.id),
  specificInfo: varchar('specific_info'),
  abstract: text('abstract'),
  coverImage: varchar('cover_image'),
  label: varchar('label'),
  hideInOpac: boolean('hide_in_opac').default(false),
  promoteToHomepage: boolean('promote_to_homepage').default(true),
  lastStatus: varchar('last_status'),
  status: varchar('status').default('Berlaku'),
  integration: varchar('integration').default('1'),
  createdBy: varchar('created_by'),
  updatedBy: varchar('updated_by'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
  initiative: varchar('initiative'),
  initiatorId: integer('initiator_id').references(() => initiators.id),
  gazetteDate: date('gazette_date'),
  regionId: integer('region_id').references(() => regions.id),
  signatory: varchar('signatory'),
  court: varchar('court'),
  petitioner: varchar('petitioner'),
  respondent: varchar('respondent'),
  caseType: varchar('case_type'),
  subClassification: varchar('sub_classification'),
  verdictStatus: varchar('verdict_status'),
  finalJudgment: varchar('final_judgment'),
  governmentAffairId: integer('government_affair_id').references(() => governmentAffairs.id),
  statusNotes: varchar('status_notes'),
  viewCount: integer('view_count').default(0),
  downloadCount: integer('download_count').default(0),
  acquisitionSource: varchar('acquisition_source'),
  isPublished: boolean('is_published').default(false),
  dataSubject: varchar('data_subject'),
  slug: varchar('slug'),
});

export type Document = typeof documents.$inferSelect;
export type NewDocument = typeof documents.$inferInsert;
```

---

#### 1.1.4 News/Berita Table

**Old Table**: `berita`

| Old Column | New Column | Type | Notes |
|------------|------------|------|-------|
| id | id | serial | Primary key |
| tanggal | date | date | News date |
| judul | title | varchar | Title |
| isi | content | text | HTML content |
| image | imageUrl | text | Image filename |
| status | status | int | Published/draft |
| created_at | createdAt | timestamp | |
| created_by | createdBy | int | FK to users |
| updated_at | updatedAt | timestamp | |
| updated_by | updatedBy | int | FK to users |

**New Drizzle Schema**:

```typescript
// db/schema/berita.ts
export const news = pgTable('news', {
  id: serial('id').primaryKey(),
  date: date('date').notNull(),
  title: varchar('title').notNull(),
  content: text('content').notNull(),
  imageUrl: text('image_url'),
  status: integer('status').default(1),
  createdAt: timestamp('created_at').defaultNow(),
  createdBy: integer('created_by').references(() => users.id),
  updatedAt: timestamp('updated_at').defaultNow(),
  updatedBy: integer('updated_by').references(() => users.id),
});

export type News = typeof news.$inferSelect;
export type NewNews = typeof news.$inferInsert;
```

---

### 1.2 Reference Tables

#### 1.2.1 Document Types

```typescript
export const documentTypes = pgTable('document_types', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  abbreviation: varchar('abbreviation'),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.2 Languages (Bahasa)

```typescript
export const languages = pgTable('languages', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  status: varchar('status'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.3 Publishers (Penerbit)

```typescript
export const publishers = pgTable('publishers', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  status: varchar('status').notNull(),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.4 Legal Fields (Bidang Hukum)

```typescript
export const legalFields = pgTable('legal_fields', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.5 Classifications (Klasifikasi)

```typescript
export const classifications = pgTable('classifications', {
  id: serial('id').primaryKey(),
  code: varchar('code').notNull(),
  name: varchar('name').notNull(),
  parentId: integer('parent_id').references(() => classifications.id),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.6 Categories (Kategori)

```typescript
export const categories = pgTable('categories', {
  id: serial('id').primaryKey(),
  parentId: varchar('parent_id').notNull(),
  name: varchar('name').notNull(),
  status: varchar('status'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.7 Member Types

```typescript
export const memberTypes = pgTable('member_types', {
  id: serial('id').primaryKey(),
  memberType: varchar('member_type').notNull(),
  loanLimit: integer('loan_limit'),
  loanDuration: integer('loan_duration'),
  reservesLimit: integer('reserves_limit'),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.2.8 Geographic Tables

```typescript
export const provinces = pgTable('provinces', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});

export const regencies = pgTable('regencies', {
  id: serial('id').primaryKey(),
  provinceId: integer('province_id').references(() => provinces.id),
  name: varchar('name').notNull(),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});

export const districts = pgTable('districts', {
  id: serial('id').primaryKey(),
  regencyId: integer('regency_id').references(() => regencies.id),
  name: varchar('name').notNull(),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});

export const regions = pgTable('regions', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  level: varchar('level'), // province, regency, district
  parentId: integer('parent_id').references(() => regions.id),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

---

### 1.3 Relationship Tables

#### 1.3.1 Document Attachments (Data Lampiran)

```typescript
export const documentAttachments = pgTable('document_attachments', {
  id: serial('id').primaryKey(),
  documentId: integer('document_id').notNull().references(() => documents.id),
  title: varchar('title').notNull(),
  url: varchar('url'),
  description: varchar('description'),
  fulltext: text('fulltext'),
  accessLevel: varchar('access_level'),
  fileName: varchar('file_name'),
  restrictions: varchar('restrictions'),
  status: integer('status').default(1),
  integration: integer('integration').default(1),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
  sortOrder: integer('sort_order'),
});
```

#### 1.3.2 Document Authors (Data Pengarang)

```typescript
export const documentAuthors = pgTable('document_authors', {
  id: serial('id').primaryKey(),
  documentId: integer('document_id').notNull().references(() => documents.id),
  authorId: integer('author_id').references(() => authors.id),
  role: varchar('role'), // primary, secondary
  createdAt: timestamp('created_at').defaultNow(),
});

export const authors = pgTable('authors', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  authorTypeId: integer('author_type_id').references(() => authorTypes.id),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.3.3 Document Subjects (Data Subyek)

```typescript
export const documentSubjects = pgTable('document_subjects', {
  id: serial('id').primaryKey(),
  documentId: integer('document_id').notNull().references(() => documents.id),
  subjectId: integer('subject_id').references(() => subjects.id),
  createdAt: timestamp('created_at').defaultNow(),
});

export const subjects = pgTable('subjects', {
  id: serial('id').primaryKey(),
  name: varchar('name').notNull(),
  subjectTypeId: integer('subject_type_id').references(() => subjectTypes.id),
  status: varchar('status').default('1'),
  createdAt: timestamp('created_at').defaultNow(),
  updatedAt: timestamp('updated_at').defaultNow(),
});
```

#### 1.3.4 Related Documents (Peraturan Terkait)

```typescript
export const relatedDocuments = pgTable('related_documents', {
  id: serial('id').primaryKey(),
  documentId: integer('document_id').notNull().references(() => documents.id),
  relatedDocumentId: integer('related_document_id').notNull().references(() => documents.id),
  relationshipType: varchar('relationship_type'), // amends, repeals, etc.
  createdAt: timestamp('created_at').defaultNow(),
});
```

---

### 1.4 RBAC Tables (Replaced by Better Auth)

The old RBAC tables (`auth_item`, `auth_item_child`, `auth_assignment`, `auth_rule`) will be replaced by Better Auth's built-in session and account management. Admin roles will be stored in a simple role enum:

```typescript
export const userRoles = pgTable('user_roles', {
  id: serial('id').primaryKey(),
  userId: integer('user_id').notNull().references(() => users.id),
  role: varchar('role').notNull(), // 'admin', 'librarian', 'staff'
  createdAt: timestamp('created_at').defaultNow(),
});

export enum UserRole {
  ADMIN = 'admin',
  LIBRARIAN = 'librarian',
  STAFF = 'staff',
}
```

---

## 2. API Endpoint Design

### 2.1 Base URL Structure

```
API Base URL: /api/v1
```

### 2.2 Authentication Endpoints (Better Auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/sign-in` | Sign in with email/password |
| POST | `/api/auth/sign-up` | Register new member |
| POST | `/api/auth/sign-out` | Sign out |
| GET | `/api/auth/session` | Get current session |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password |

### 2.3 Document Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/documents` | List documents (paginated, filterable) | Public |
| GET | `/documents/:id` | Get document detail | Public |
| GET | `/documents/:id/download` | Download document/attachment | Public |
| POST | `/documents` | Create document | Admin |
| PUT | `/documents/:id` | Update document | Admin |
| DELETE | `/documents/:id` | Delete document | Admin |
| GET | `/documents/:id/related` | Get related documents | Public |

**Query Parameters for List**:

- `page`: Page number (default: 1)
- `limit`: Items per page (default: 20, max: 100)
- `documentType`: Filter by type (1=regulation, 2=monograph, 3=article, 4=decision)
- `year`: Filter by publication year
- `status`: Filter by status (Berlaku/Tidak Berlaku)
- `search`: Full-text search in title
- `sort`: Sort field (title, createdAt, enactmentDate)
- `order`: Sort order (asc/desc)

**Response Format**:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Undang-Undang Nomor 11 Tahun 2020",
      "slug": "undang-undang-nomor-11-tahun-2020",
      "regulationNumber": "11/2020",
      "enactmentDate": "2020-05-21",
      "status": "Berlaku",
      "viewCount": 1500,
      "downloadCount": 450
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "totalItems": 5000,
    "totalPages": 250
  }
}
```

### 2.4 News/Berita Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/news` | List news (paginated) | Public |
| GET | `/news/:id` | Get news detail | Public |
| POST | `/news` | Create news | Admin |
| PUT | `/news/:id` | Update news | Admin |
| DELETE | `/news/:id` | Delete news | Admin |

### 2.5 Member Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/members` | List members | Admin |
| GET | `/members/:id` | Get member detail | Admin/Member |
| POST | `/members` | Register new member | Public |
| PUT | `/members/:id` | Update member profile | Member |
| DELETE | `/members/:id` | Deactivate member | Admin |

### 2.6 Reference Data Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/reference/document-types` | Get document types |
| GET | `/reference/languages` | Get languages |
| GET | `/reference/publishers` | Get publishers |
| GET | `/reference/legal-fields` | Get legal fields |
| GET | `/reference/classifications` | Get classifications |
| GET | `/reference/regions` | Get regions (provinces, cities) |

### 2.7 Circulation Endpoints (Optional - if library feature needed)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/circulation/loans` | List loans | Librarian |
| POST | `/circulation/loans` | Create loan | Librarian |
| PUT | `/circulation/loans/:id/return` | Return item | Librarian |
| GET | `/circulation/reservations` | List reservations | Librarian |
| POST | `/circulation/reservations` | Create reservation | Member |

### 2.8 API Endpoint Mapping (Old Yii2 → New Hono)

#### Documents (Dokumen)

**Backend Admin:**

| Old Endpoint (Yii2) | Method | New Endpoint (Hono) | Method |
|---------------------|--------|---------------------|--------|
| `/dokumen/index` | GET | `/api/v1/documents` | GET |
| `/dokumen/view?id={id}` | GET | `/api/v1/documents/:id` | GET |
| `/dokumen/create` | POST | `/api/v1/documents` | POST |
| `/dokumen/update?id={id}` | PUT | `/api/v1/documents/:id` | PUT |
| `/dokumen/delete?id={id}` | DELETE | `/api/v1/documents/:id` | DELETE |

**Frontend Public:**

| Old Endpoint (Yii2) | New Endpoint (Hono) | Notes |
|---------------------|---------------------|-------|
| `/dokumen/index` | `GET /api/v1/documents` | |
| `/dokumen/peraturan` | `GET /api/v1/documents?documentType=1` | |
| `/dokumen/monografi` | `GET /api/v1/documents?documentType=2` | |
| `/dokumen/artikel` | `GET /api/v1/documents?documentType=3` | |
| `/dokumen/putusan` | `GET /api/v1/documents?documentType=4` | |
| `/dokumen/uu` | `GET /api/v1/documents?regulationKind=UNDANG-UNDANG` | |
| `/dokumen/buku` | `GET /api/v1/documents?regulationKind=BUKU HUKUM` | |
| `/dokumen/majalah` | `GET /api/v1/documents?regulationKind=MAJALAH HUKUM` | |
| `/dokumen/ma` | `GET /api/v1/documents?regulationKind=PUTUSAN MAHKAMAH AGUNG` | |
| `/dokumen/berlaku` | `GET /api/v1/documents?status=Berlaku&documentType=1` | |
| `/dokumen/tberlaku` | `GET /api/v1/documents?status=Tidak Berlaku` | |
| `/dokumen/view?id={id}` | `GET /api/v1/documents/:id` | |
| `/dokumen/download/{id}` | `GET /api/v1/documents/:id/download` | |

#### News (Berita)

| Old Endpoint (Yii2) | New Endpoint (Hono) | Method |
|---------------------|---------------------|--------|
| `/berita/index` | `/api/v1/news` | GET |
| `/berita/view?id={id}` | `/api/v1/news/:id` | GET |
| `/berita/create` | `/api/v1/news` | POST |
| `/berita/update?id={id}` | `/api/v1/news/:id` | PUT |
| `/berita/delete?id={id}` | `/api/v1/news/:id` | DELETE |

#### Members (Library Members)

| Old Endpoint (Yii2) | New Endpoint (Hono) | Method |
|---------------------|---------------------|--------|
| `/member/index` | `/api/v1/members` | GET |
| `/member/view?id={id}` | `/api/v1/members/:id` | GET |
| `/member/create` | `/api/v1/members` | POST |
| `/member/update?id={id}` | `/api/v1/members/:id` | PUT |
| `/member/delete?id={id}` | `/api/v1/members/:id` | DELETE |
| `/member/password?id={id}` | `POST /api/v1/members/:id/reset-password` | POST |

#### Admin Users

| Old Endpoint (Yii2) | New Endpoint (Hono) | Method |
|---------------------|---------------------|--------|
| `/user/index` | `/api/v1/admin/users` | GET |
| `/user/view?id={id}` | `/api/v1/admin/users/:id` | GET |
| `/user/create` | `/api/v1/admin/users` | POST |
| `/user/update?id={id}` | `/api/v1/admin/users/:id` | PUT |
| `/user/delete?id={id}` | `/api/v1/admin/users/:id` | DELETE |
| `/user/bulkdelete` | `POST /api/v1/admin/users/bulk-delete` | POST |

#### Reference Data

| Old Yii2 | New Hono Endpoint |
|----------|-------------------|
| `/bahasa/index` | `GET /api/v1/reference/languages` |
| `/penerbit/index` | `GET /api/v1/reference/publishers` |
| `/bidang-hukum/index` | `GET /api/v1/reference/legal-fields` |
| `/klasifikasi/index` | `GET /api/v1/reference/classifications` |
| `/kategori/index` | `GET /api/v1/reference/categories` |
| `/provinsi/index` | `GET /api/v1/reference/provinces` |
| `/kabupaten/index` | `GET /api/v1/reference/regencies` |
| `/kecamatan/index` | `GET /api/v1/reference/districts` |
| `/member-type/index` | `GET /api/v1/reference/member-types` |
| `/tipe-dokumen/index` | `GET /api/v1/reference/document-types` |
| `/pengarang/index` | `GET /api/v1/reference/authors` |
| `/urusan-pemerintahan/index` | `GET /api/v1/reference/government-affairs` |

#### Authentication (Better Auth)

| Old Yii2 | New Hono Endpoint | Method |
|----------|-------------------|--------|
| `/site/login` | `POST /api/v1/auth/sign-in` | POST |
| `/site/logout` | `POST /api/v1/auth/sign-out` | POST |
| `/site/signup` | `POST /api/v1/auth/sign-up` | POST |
| `/site/request-password-reset` | `POST /api/v1/auth/forgot-password` | POST |
| (profile page) | `GET /api/v1/auth/session` | GET |

#### Static Pages (Frontend)

| Old Yii2 | New Next.js Page |
|----------|-----------------|
| `/site/index` | `/` (homepage) |
| `/site/about` | `/about` |
| `/site/kontak` | `/contact` |
| `/site/sekilas-sejarah` | `/about/history` |
| `/site/visi` | `/about/vision` |
| `/site/misi` | `/about/mission` |
| `/site/sto` | `/about/organization` |
| `/site/pengelola` | `/about/management` |

#### Query Parameter Mapping

| Old Parameter | New Parameter |
|---------------|---------------|
| `DokumenSearch[tipe_dokumen]` | `documentType` |
| `DokumenSearch[tahun_terbit]` | `year` |
| `DokumenSearch[status]` | `status` |
| `DokumenSearch[judul]` | `search` |
| (pagination via Yii) | `page`, `limit` |

#### Authorization Matrix

| Endpoint | Public | Member | Librarian | Admin |
|----------|:------:|:------:|:---------:|:-----:|
| `GET /documents` | ✓ | ✓ | ✓ | ✓ |
| `POST /documents` | - | - | - | ✓ |
| `GET /news` | ✓ | ✓ | ✓ | ✓ |
| `POST /news` | - | - | - | ✓ |
| `GET /members` | - | - | ✓ | ✓ |
| `POST /members` | ✓ | - | - | ✓ |
| `GET /admin/users` | - | - | - | ✓ |
| `GET /reference/*` | ✓ | ✓ | ✓ | ✓ |

---

## 3. Data Migration Script

### 3.1 Migration Script Overview

The migration will be implemented as a Node.js script using Drizzle ORM to:

1. Connect to both source (MySQL) and target (PostgreSQL) databases
2. Transform and insert data
3. Handle foreign key relationships
4. Upload files to cloud storage

### 3.2 Project Structure for Migration

```
migrations/
├── package.json
├── drizzle.config.ts
├── src/
│   ├── db/
│   │   ├── source.ts      # MySQL connection (source)
│   │   ├── target.ts      # PostgreSQL connection (target)
│   │   └── schema.ts      # Drizzle schemas
│   ├── scripts/
│   │   ├── 01-migrate-users.ts
│   │   ├── 02-migrate-members.ts
│   │   ├── 03-migrate-documents.ts
│   │   ├── 04-migrate-news.ts
│   │   ├── 05-migrate-reference-tables.ts
│   │   ├── 06-migrate-document-relations.ts
│   │   └── 99-verify-migration.ts
│   └── utils/
│       ├── cloud-upload.ts
│       └── transform.ts
└── .env.example
```

### 3.3 Migration Scripts

#### 3.3.1 Configuration

```typescript
// drizzle.config.ts
import { defineConfig } from 'drizzle-kit';

export default defineConfig({
  schema: './src/db/schema.ts',
  out: './drizzle',
  dialect: 'postgresql',
  dbCredentials: {
    url: process.env.DATABASE_URL!,
  },
});
```

```typescript
// src/db/source.ts
import mysql from 'mysql2/promise';

export const sourceDb = await mysql.createPool({
  host: process.env.SOURCE_DB_HOST || 'localhost',
  port: parseInt(process.env.SOURCE_DB_PORT || '3306'),
  user: process.env.SOURCE_DB_USER,
  password: process.env.SOURCE_DB_PASSWORD,
  database: process.env.SOURCE_DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
});
```

```typescript
// src/db/target.ts
import { drizzle } from 'drizzle-orm/node-postgres';
import pg from 'pg';
const { Pool } = pg;

export const targetPool = new Pool({
  host: process.env.TARGET_DB_HOST || 'localhost',
  port: parseInt(process.env.TARGET_DB_PORT || '5432'),
  user: process.env.TARGET_DB_USER,
  password: process.env.TARGET_DB_PASSWORD,
  database: process.env.TARGET_DB_NAME,
  max: 10,
});

export const targetDb = drizzle(targetPool);
```

#### 3.3.2 Users Migration

```typescript
// src/scripts/01-migrate-users.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { users, userRoles } from '../db/schema';

export async function migrateUsers() {
  console.log('Migrating users...');
  
  const [rows] = await sourceDb.query('SELECT * FROM user');
  
  for (const row of rows) {
    const [existingUser] = await targetDb
      .select()
      .from(users)
      .where(users.email.equals(row.email));
    
    if (!existingUser) {
      await targetDb.insert(users).values({
        username: row.username,
        email: row.email,
        passwordHash: row.password_hash,
        status: row.status,
        suspendedUntil: row.suspended_until ? new Date(row.suspended_until) : null,
        avatarUrl: row.picture,
        createdAt: new Date(row.created_at * 1000),
        updatedAt: new Date(row.updated_at * 1000),
      });
      
      console.log(`Migrated user: ${row.email}`);
    }
  }
  
  console.log('Users migration complete');
}
```

#### 3.3.3 Members Migration

```typescript
// src/scripts/02-migrate-members.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { members, memberTypes } from '../db/schema';

export async function migrateMembers() {
  console.log('Migrating members...');
  
  const [rows] = await sourceDb.query('SELECT * FROM member');
  
  for (const row of rows) {
    await targetDb.insert(members).values({
      username: row.username,
      email: row.member_email,
      passwordHash: row.password_hash,
      status: row.status,
      name: row.member_name,
      gender: row.gender,
      birthDate: row.birth_date ? new Date(row.birth_date) : null,
      address: row.member_address,
      postalCode: row.postal_code,
      nik: row.personal_id_number,
      institutionName: row.inst_name,
      avatarUrl: row.member_image,
      ktpImage: row.member_ktp,
      memberSinceDate: row.member_since_date ? new Date(row.member_since_date) : null,
      registerDate: row.register_date ? new Date(row.register_date) : null,
      expireDate: row.expire_date ? new Date(row.expire_date) : null,
      phone: row.phone_number,
      fax: row.fax_number,
      notes: row.member_notes,
      createdAt: row.created_at ? new Date(row.created_at) : new Date(),
      updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
    }).onConflictDoNothing();
    
    console.log(`Migrated member: ${row.username}`);
  }
  
  console.log('Members migration complete');
}
```

#### 3.3.4 Documents Migration

```typescript
// src/scripts/03-migrate-documents.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { documents } from '../db/schema';

export async function migrateDocuments() {
  console.log('Migrating documents...');
  
  const [rows] = await sourceDb.query('SELECT * FROM document');
  
  for (const row of rows) {
    await targetDb.insert(documents).values({
      documentTypeId: row.tipe_dokumen,
      title: row.judul,
      teu: row.teu,
      regulationNumber: row.nomor_peraturan,
      callNumber: row.nomor_panggil,
      regulationForm: row.bentuk_peraturan,
      regulationKind: row.jenis_peraturan,
      kindAbbrev: row.singkat_jenis,
      edition: row.cetakan,
      publicationPlace: row.tempat_terbit,
      // publisherId: need to lookup
      enactmentDate: row.tanggal_penetapan ? new Date(row.tanggal_penetapan) : null,
      physicalDesc: row.deskripsi_fisik,
      source: row.sumber,
      isbn: row.isbn,
      // languageId: need to lookup by name
      // legalFieldId: need to lookup by name
      // bookId: row.nomor_induk_buku,
      formAbbrev: row.singkat_bentuk,
      collectionNumber: row.tipe_koleksi_nomor_eksemplar,
      exemplarPattern: row.pola_nomor_eksemplar,
      exemplarCount: row.jumlah_eksemplar,
      publicationFrequency: row.kala_terbit,
      publicationYear: row.tahun_terbit,
      readDate: row.tanggal_dibacakan ? new Date(row.tanggal_dibacakan) : null,
      responsibility: row.pernyataan_tanggung_jawab,
      // edition: row.edisi,
      // gmd: row.gmd,
      seriesTitle: row.judul_seri,
      // classificationId: need to lookup
      specificInfo: row.info_detil_spesifik,
      abstract: row.abstrak,
      coverImage: row.gambar_sampul,
      label: row.label,
      hideInOpac: row.sembunyikan_di_opac === 'Ya',
      promoteToHomepage: row.promosikan_ke_beranda === 'Ya',
      lastStatus: row.status_terakhir,
      status: row.status,
      integration: rowintegrasi,
      createdBy: row._created_by,
      updatedBy: row._updated_by,
      createdAt: row.created_at ? new Date(row.created_at) : new Date(),
      updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
      initiative: row.inisiatif,
      // initiatorId: need to lookup
      gazetteDate: row.tanggal_pengundangan ? new Date(row.tanggal_pengundangan) : null,
      // regionId: need to lookup
      signatory: row.penandatangani,
      court: row.lembaga_peradilan,
      petitioner: row.pemohon,
      respondent: row.termohon,
      caseType: row.jenis_perkara,
      subClassification: row.sub_klasifikasi,
      verdictStatus: row.amar_status,
      finalJudgment: row.berkekuatan_hukum_tetap,
      // governmentAffairId: need to lookup
      statusNotes: row.catatan_status_peraturan,
      viewCount: row.hit_see || 0,
      downloadCount: row.hit_download || 0,
      acquisitionSource: row.sumber_perolehan,
      isPublished: row.is_publish === 1,
      dataSubject: row.subjek_data,
      slug: row.slug,
    }).onConflictDoNothing();
    
    console.log(`Migrated document: ${row.judul?.substring(0, 50)}`);
  }
  
  console.log(`Documents migration complete: ${rows.length} records`);
}
```

#### 3.3.5 News Migration

```typescript
// src/scripts/04-migrate-news.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { news } from '../db/schema';

export async function migrateNews() {
  console.log('Migrating news...');
  
  const [rows] = await sourceDb.query('SELECT * FROM berita');
  
  for (const row of rows) {
    await targetDb.insert(news).values({
      date: new Date(row.tanggal),
      title: row.judul,
      content: row.isi,
      imageUrl: row.image,
      status: row.status,
      createdAt: row.created_at ? new Date(row.created_at) : new Date(),
      createdBy: row.created_by,
      updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
      updatedBy: row.updated_by,
    }).onConflictDoNothing();
    
    console.log(`Migrated news: ${row.judul}`);
  }
  
  console.log(`News migration complete: ${rows.length} records`);
}
```

#### 3.3.6 Reference Tables Migration

```typescript
// src/scripts/05-migrate-reference-tables.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { 
  languages, publishers, legalFields, classifications, 
  categories, memberTypes, documentTypes, regions 
} from '../db/schema';

export async function migrateReferenceTables() {
  console.log('Migrating reference tables...');
  
  // Languages
  const [languagesRows] = await sourceDb.query('SELECT * FROM bahasa');
  for (const row of languagesRows) {
    await targetDb.insert(languages).values({
      name: row.name,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${languagesRows.length} languages`);
  
  // Publishers
  const [publishersRows] = await sourceDb.query('SELECT * FROM penerbit');
  for (const row of publishersRows) {
    await targetDb.insert(publishers).values({
      name: row.name,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${publishersRows.length} publishers`);
  
  // Legal Fields
  const [legalFieldsRows] = await sourceDb.query('SELECT * FROM bidang_hukum');
  for (const row of legalFieldsRows) {
    await targetDb.insert(legalFields).values({
      name: row.nama_bidang_hukum,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${legalFieldsRows.length} legal fields`);
  
  // Classifications
  const [classificationsRows] = await sourceDb.query('SELECT * FROM klasifikasi');
  for (const row of classificationsRows) {
    await targetDb.insert(classifications).values({
      code: row.kode_klasifikasi,
      name: row.nama_klasifikasi,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${classificationsRows.length} classifications`);
  
  // Categories
  const [categoriesRows] = await sourceDb.query('SELECT * FROM kategori');
  for (const row of categoriesRows) {
    await targetDb.insert(categories).values({
      parentId: row.parent_id,
      name: row.nama_kategori,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${categoriesRows.length} categories`);
  
  // Member Types
  const [memberTypesRows] = await sourceDb.query('SELECT * FROM member_type');
  for (const row of memberTypesRows) {
    await targetDb.insert(memberTypes).values({
      memberType: row.member_type,
      loanLimit: row.loan_limit,
      loanDuration: row.loan_duration,
      reservesLimit: row.reserve_limit,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${memberTypesRows.length} member types`);
  
  // Document Types
  const [documentTypesRows] = await sourceDb.query('SELECT * FROM document_type');
  for (const row of documentTypesRows) {
    await targetDb.insert(documentTypes).values({
      name: row.document_type,
      abbreviation: row.singkat,
      status: row.status,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${documentTypesRows.length} document types`);
  
  // Regions (Provinsi, Kabupaten, Kecamatan -> provinces, regencies, districts)
  const [provincesRows] = await sourceDb.query('SELECT * FROM provinsi');
  for (const row of provincesRows) {
    await targetDb.insert(provinces).values({
      name: row.nama_provinsi,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${provincesRows.length} provinces`);
  
  const [kabupatenRows] = await sourceDb.query('SELECT * FROM kabupaten');
  for (const row of kabupatenRows) {
    await targetDb.insert(regencies).values({
      // provinceId: need to lookup by name match
      name: row.nama_kabupaten,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${kabupatenRows.length} regencies`);
  
  console.log('Reference tables migration complete');
}
```

#### 3.3.7 Document Relations Migration

```typescript
// src/scripts/06-migrate-document-relations.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { 
  documentAttachments, documentAuthors, documentSubjects, relatedDocuments 
} from '../db/schema';

export async function migrateDocumentRelations() {
  console.log('Migrating document relations...');
  
  // Document Attachments
  const [attachmentsRows] = await sourceDb.query('SELECT * FROM data_lampiran');
  for (const row of attachmentsRows) {
    await targetDb.insert(documentAttachments).values({
      documentId: row.id_dokumen,
      title: row.judul_lampiran,
      url: row.url_lampiran,
      description: row.deskripsi_lampiran,
      fulltext: row.fulltext,
      accessLevel: row.akses_lampiran,
      fileName: row.dokumen_lampiran,
      restrictions: row.pembatasan_lampiran,
      status: row.status,
      integration: rowintegrasi,
      sortOrder: row.urutan,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${attachmentsRows.length} document attachments`);
  
  // Document Authors
  const [authorsRows] = await sourceDb.query('SELECT * FROM data_pengarang');
  for (const row of authorsRows) {
    await targetDb.insert(documentAuthors).values({
      documentId: row.id_dokumen,
      // authorId: need to lookup
      role: row.tipe_pengarang,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${authorsRows.length} document authors`);
  
  // Document Subjects
  const [subjectsRows] = await sourceDb.query('SELECT * FROM dokumen_data_subyek');
  for (const row of subjectsRows) {
    await targetDb.insert(documentSubjects).values({
      documentId: row.id_dokumen,
      // subjectId: need to lookup
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${subjectsRows.length} document subjects`);
  
  // Related Documents
  const [relatedRows] = await sourceDb.query('SELECT * FROM peraturan_terkait');
  for (const row of relatedRows) {
    await targetDb.insert(relatedDocuments).values({
      documentId: row.id_dokumen,
      relatedDocumentId: row.id_dokumen_terkait,
      relationshipType: row.tipe_peraturan,
    }).onConflictDoNothing();
  }
  console.log(`Migrated ${relatedRows.length} related documents`);
  
  console.log('Document relations migration complete');
}
```

#### 3.3.8 Main Migration Runner

```typescript
// src/migrate.ts
import { migrateUsers } from './scripts/01-migrate-users';
import { migrateMembers } from './scripts/02-migrate-members';
import { migrateDocuments } from './scripts/03-migrate-documents';
import { migrateNews } from './scripts/04-migrate-news';
import { migrateReferenceTables } from './scripts/05-migrate-reference-tables';
import { migrateDocumentRelations } from './scripts/06-migrate-document-relations';
import { verifyMigration } from './scripts/99-verify-migration';

async function main() {
  console.log('=== Starting ILDIS Migration ===\n');
  
  try {
    // 1. First migrate reference tables (for FK lookups)
    await migrateReferenceTables();
    
    // 2. Migrate users
    await migrateUsers();
    
    // 3. Migrate members
    await migrateMembers();
    
    // 4. Migrate documents
    await migrateDocuments();
    
    // 5. Migrate news
    await migrateNews();
    
    // 6. Migrate document relations (needs documents first)
    await migrateDocumentRelations();
    
    // 7. Verify
    await verifyMigration();
    
    console.log('\n=== Migration Complete ===');
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
  
  process.exit(0);
}

main();
```

#### 3.3.9 Verification Script

```typescript
// src/scripts/99-verify-migration.ts
import { targetDb } from '../db/target';
import { users, members, documents, news, languages, publishers } from '../db/schema';
import { eq, sql } from 'drizzle-orm';

export async function verifyMigration() {
  console.log('\n=== Verifying Migration ===');
  
  const userCount = await targetDb.select({ count: sql<number>`count(*)` }).from(users);
  const memberCount = await targetDb.select({ count: sql<number>`count(*)` }).from(members);
  const documentCount = await targetDb.select({ count: sql<number>`count(*)` }).from(documents);
  const newsCount = await targetDb.select({ count: sql<number>`count(*)` }).from(news);
  const languageCount = await targetDb.select({ count: sql<number>`count(*)` }).from(languages);
  const publisherCount = await targetDb.select({ count: sql<number>`count(*)` }).from(publishers);
  
  console.log(`Users: ${userCount[0].count}`);
  console.log(`Members: ${memberCount[0].count}`);
  console.log(`Documents: ${documentCount[0].count}`);
  console.log(`News: ${newsCount[0].count}`);
  console.log(`Languages: ${languageCount[0].count}`);
  console.log(`Publishers: ${publisherCount[0].count}`);
  
  // Verify data integrity
  const documentsWithTitle = await targetDb
    .select({ count: sql<number>`count(*)` })
    .from(documents)
    .where(sql`${documents.title} IS NOT NULL`);
    
  console.log(`Documents with title: ${documentsWithTitle[0].count}`);
  
  if (documentsWithTitle[0].count === 0) {
    throw new Error('Documents migration verification failed - no documents have titles!');
  }
  
  console.log('\n=== Verification Complete ===');
}
```

---

## 4. File Storage Migration

### 4.1 Cloud Storage Configuration

```typescript
// src/utils/cloud-upload.ts
import { S3Client, PutObjectCommand, GetObjectCommand } from '@aws-sdk/client-s3';
import { getSignedUrl } from '@aws-sdk/s3-request-presigner';

const s3Client = new S3Client({
  region: process.env.AWS_REGION || 'us-east-1',
  credentials: {
    accessKeyId: process.env.AWS_ACCESS_KEY_ID!,
    secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY!,
  },
});

const BUCKET_NAME = process.env.AWS_S3_BUCKET || 'ildis-documents';

export async function uploadFileToCloud(
  localPath: string,
  key: string,
  contentType: string
): Promise<string> {
  const fs = await import('fs');
  const fileBuffer = fs.readFileSync(localPath);
  
  const command = new PutObjectCommand({
    Bucket: BUCKET_NAME,
    Key: key,
    Body: fileBuffer,
    ContentType: contentType,
  });
  
  await s3Client.send(command);
  
  // Return public URL or signed URL
  return `https://${BUCKET_NAME}.s3.${process.env.AWS_REGION}.amazonaws.com/${key}`;
}

export async function getDownloadUrl(key: string, expiresIn = 3600): Promise<string> {
  const command = new GetObjectCommand({
    Bucket: BUCKET_NAME,
    Key: key,
  });
  
  return getSignedUrl(s3Client, command, { expiresIn });
}

export function getPublicUrl(key: string): string {
  return `https://${BUCKET_NAME}.s3.${process.env.AWS_REGION}.amazonaws.com/${key}`;
}
```

### 4.2 File Migration Script

```typescript
// src/scripts/07-migrate-files.ts
import { sourceDb } from '../db/source';
import { uploadFileToCloud, getPublicUrl } from '../utils/cloud-upload';
import { documents, documentAttachments, news } from '../db/schema';
import { targetDb } from '../db/target';
import path from 'path';

const LOCAL_DOCS_PATH = process.env.LOCAL_DOCS_PATH || '/path/to/common/dokumen';

async function migrateDocumentFiles() {
  console.log('Migrating document files to cloud...');
  
  const [attachments] = await sourceDb.query('SELECT * FROM data_lampiran');
  
  for (const att of attachments) {
    if (att.dokumen_lampiran) {
      const localPath = path.join(LOCAL_DOCS_PATH, att.dokumen_lampiran);
      const cloudKey = `documents/attachments/${att.id}_${att.dokumen_lampiran}`;
      
      try {
        const publicUrl = await uploadFileToCloud(localPath, cloudKey, 'application/pdf');
        
        // Update the database with new URL
        await targetDb
          .update(documentAttachments)
          .set({ url: publicUrl })
          .where(documentAttachments.id.equals(att.id));
          
        console.log(`Uploaded: ${att.judul_lampiran}`);
      } catch (error) {
        console.error(`Failed to upload: ${att.dokumen_lampiran}`, error);
      }
    }
  }
}

async function migrateNewsImages() {
  console.log('Migrating news images to cloud...');
  
  const [newsItems] = await sourceDb.query("SELECT * FROM berita WHERE image IS NOT NULL");
  
  for (const item of newsItems) {
    if (item.image) {
      const localPath = path.join(LOCAL_DOCS_PATH, item.image);
      const cloudKey = `news/images/${item.id}_${item.image}`;
      
      try {
        const publicUrl = await uploadFileToCloud(localPath, cloudKey, 'image/*');
        
        await targetDb
          .update(news)
          .set({ imageUrl: publicUrl })
          .where(news.id.equals(item.id));
          
        console.log(`Uploaded news image: ${item.judul}`);
      } catch (error) {
        console.error(`Failed to upload: ${item.image}`, error);
      }
    }
  }
}

async function migrateCoverImages() {
  console.log('Migrating document cover images to cloud...');
  
  const [docs] = await sourceDb.query("SELECT * FROM document WHERE gambar_sampul IS NOT NULL");
  
  for (const doc of docs) {
    if (doc.gambar_sampul) {
      const localPath = path.join(LOCAL_DOCS_PATH, doc.gambar_sampul);
      const cloudKey = `documents/covers/${doc.id}_${doc.gambar_sampul}`;
      
      try {
        const publicUrl = await uploadFileToCloud(localPath, cloudKey, 'image/*');
        
        await targetDb
          .update(documents)
          .set({ coverImage: publicUrl })
          .where(documents.id.equals(doc.id));
          
        console.log(`Uploaded cover: ${doc.judul?.substring(0, 30)}`);
      } catch (error) {
        console.error(`Failed to upload: ${doc.gambar_sampul}`, error);
      }
    }
  }
}

export async function migrateAllFiles() {
  await migrateDocumentFiles();
  await migrateNewsImages();
  await migrateCoverImages();
  console.log('File migration complete');
}
```

---

## 5. Authentication Migration

### 5.1 Better Auth Setup

```typescript
// apps/api/src/lib/auth.ts
import { betterAuth } from 'better-auth';
import { drizzleAdapter } from 'better-auth/adapters/drizzle';
import { targetDb } from '../db/target';
import { 
  sessions, accounts, verifications 
} from 'better-auth/schema';

export const auth = betterAuth({
  database: drizzleAdapter(targetDb, {
    provider: 'pg',
    sessions: sessions,
    accounts: accounts,
    verifications: verifications,
  }),
  emailAndPassword: {
    enabled: true,
    requireEmailVerification: false,
  },
  session: {
    expiresIn: 60 * 60 * 24 * 7, // 7 days
    updateAge: 60 * 60 * 24, // 1 day
    cookieCache: {
      enabled: true,
      maxAge: 5 * 60, // 5 minutes
    },
  },
  advanced: {
    generateId: () => crypto.randomUUID(),
  },
});

export type Session = typeof auth.$Infer.Session.session;
export type User = typeof auth.$Infer.Session.user;
```

### 5.2 User Migration to Better Auth Accounts

```typescript
// src/scripts/08-migrate-auth.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { users, members } from '../db/schema';
import { accounts, sessions } from 'better-auth/schema';
import { hash } from 'bcryptjs';

export async function migrateAuthToBetterAuth() {
  console.log('Migrating authentication to Better Auth...');
  
  // Migrate admin users
  const [adminUsers] = await sourceDb.query('SELECT * FROM user WHERE status = 10');
  
  for (const user of adminUsers) {
    const hashedPassword = await hash(user.password_hash, 10);
    
    await targetDb.insert(accounts).values({
      userId: user.id.toString(),
      accountId: user.email,
      providerId: 'credential',
      password: hashedPassword,
      createdAt: new Date(user.created_at * 1000),
      updatedAt: new Date(user.updated_at * 1000),
    }).onConflictDoNothing();
    
    console.log(`Migrated auth for user: ${user.email}`);
  }
  
  // Migrate members
  const [membersList] = await sourceDb.query('SELECT * FROM member WHERE status = 1');
  
  for (const member of membersList) {
    if (member.password_hash) {
      const hashedPassword = await hash(member.password_hash, 10);
      
      await targetDb.insert(accounts).values({
        userId: member.id.toString(),
        accountId: member.username,
        providerId: 'credential',
        password: hashedPassword,
        createdAt: member.created_at ? new Date(member.created_at) : new Date(),
        updatedAt: member.updated_at ? new Date(member.updated_at) : new Date(),
      }).onConflictDoNothing();
      
      console.log(`Migrated auth for member: ${member.username}`);
    }
  }
  
  console.log('Authentication migration complete');
}
```

### 5.3 Role Migration

```typescript
// src/scripts/09-migrate-roles.ts
import { sourceDb } from '../db/source';
import { targetDb } from '../db/target';
import { users, userRoles } from '../db/schema';
import { eq } from 'drizzle-orm';

export async function migrateRoles() {
  console.log('Migrating roles...');
  
  // Get all auth assignments
  const [assignments] = await sourceDb.query('SELECT * FROM auth_assignment');
  
  for (const assignment of assignments) {
    // Map old roles to new roles
    let newRole = 'staff'; // default
    
    if (assignment.item_name === 'admin' || assignment.item_name === 'administrator') {
      newRole = 'admin';
    } else if (assignment.item_name === 'librarian' || assignment.item_name === 'pustakawan') {
      newRole = 'librarian';
    }
    
    // Find user by user_id (could be username or id)
    const [user] = await targetDb
      .select()
      .from(users)
      .where(eq(users.username, assignment.user_id));
    
    if (user) {
      await targetDb.insert(userRoles).values({
        userId: user.id,
        role: newRole,
      }).onConflictDoNothing();
      
      console.log(`Assigned role ${newRole} to ${user.username}`);
    }
  }
  
  console.log('Roles migration complete');
}
```

---

## 6. Migration Configuration

### 6.1 Environment Variables

```bash
# .env for migration script

# Source Database (MySQL)
SOURCE_DB_HOST=localhost
SOURCE_DB_PORT=3306
SOURCE_DB_USER=root
SOURCE_DB_PASSWORD=your_password
SOURCE_DB_NAME=ildis

# Target Database (PostgreSQL)
TARGET_DB_HOST=localhost
TARGET_DB_PORT=5432
TARGET_DB_USER=postgres
TARGET_DB_PASSWORD=your_password
TARGET_DB_NAME=ildis_new

# File Storage
LOCAL_DOCS_PATH=/path/to/common/dokumen
AWS_REGION=us-east-1
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_S3_BUCKET=ildis-documents
```

### 6.2 Package.json Scripts

```json
{
  "scripts": {
    "migrate:setup": "drizzle-kit push",
    "migrate:data": "tsx src/migrate.ts",
    "migrate:files": "tsx src/scripts/07-migrate-files.ts",
    "migrate:auth": "tsx src/scripts/08-migrate-auth.ts",
    "migrate:roles": "tsx src/scripts/09-migrate-roles.ts",
    "migrate:all": "npm run migrate:setup && npm run migrate:data && npm run migrate:files && npm run migrate:auth && npm run migrate:roles",
    "migrate:verify": "tsx src/scripts/99-verify-migration.ts"
  },
  "dependencies": {
    "drizzle-orm": "^0.29.0",
    "better-auth": "^1.0.0",
    "mysql2": "^3.6.0",
    "pg": "^8.11.0",
    "bcryptjs": "^2.4.3",
    "@aws-sdk/client-s3": "^3.400.0",
    "@aws-sdk/s3-request-presigner": "^3.400.0"
  },
  "devDependencies": {
    "drizzle-kit": "^0.20.0",
    "tsx": "^4.7.0"
  }
}
```

---

## 7. Migration Execution Order

1. **Pre-migration**:
   - Set up PostgreSQL database
   - Configure cloud storage (S3)
   - Export source database

2. **Schema Migration**:

   ```bash
   npm run migrate:setup
   ```

3. **Data Migration**:

   ```bash
   npm run migrate:data
   ```

4. **File Migration**:

   ```bash
   npm run migrate:files
   ```

5. **Authentication Migration**:

   ```bash
   npm run migrate:auth
   ```

6. **Role Migration**:

   ```bash
   npm run migrate:roles
   ```

7. **Verification**:

   ```bash
   npm run migrate:verify
   ```

---

## 8. Rollback Plan

In case of migration failure:

1. **Keep source database intact** - Do not drop or modify
2. **Backup PostgreSQL database** before each migration attempt
3. **Document last working state** - Save migration checkpoints

```bash
# Backup command
pg_dump -h localhost -U postgres ildis_new > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

## 9. Post-Migration Tasks

1. **Update DNS** - Point domain to new Next.js frontend
2. **Update API URLs** - Configure new Hono API base URL
3. **Verify all functionality** - Test core flows (login, search, download)
4. **Monitor logs** - Watch for errors in first 24 hours
5. **Clean up old files** - Remove local document storage after verification
