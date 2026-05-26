## [4.3.0](https://github.com/bphndigitalservice/ildis/compare/v4.2.1...v4.3.0) (2026-05-26)

### Features

* **recaptcha:** make backend login reCAPTCHA optional via env ([a11e91a](https://github.com/bphndigitalservice/ildis/commit/a11e91af78166c68155c795fd0aded6d34157fb5))

### Bug Fixes

* **docker:** restore dokumen_data_subyek view and align document validation ([be738a6](https://github.com/bphndigitalservice/ildis/commit/be738a6855366492510af77d598d9472a5e3aee7))
* **install:** docker install and migration reliability ([212cc61](https://github.com/bphndigitalservice/ildis/commit/212cc615d62f7d68fb059592d8d41b9154bd0dc4))
* **install:** docker install and migration reliability ([#40](https://github.com/bphndigitalservice/ildis/issues/40)) ([1d80712](https://github.com/bphndigitalservice/ildis/commit/1d80712bf56a04372276cf0a736c5e253b1e8fee))
* **install:** wire RECAPTCHA_ENABLED and patch GHCR image until rebuild ([b1c86f4](https://github.com/bphndigitalservice/ildis/commit/b1c86f4756f9ab15e4642c63acf6568ded6d1970))

## [4.2.1](https://github.com/bphndigitalservice/ildis/compare/v4.2.0...v4.2.1) (2026-05-26)

## [4.2.0](https://github.com/bphndigitalservice/ildis/compare/v4.1.4...v4.2.0) (2026-05-25)

### ⚠ BREAKING CHANGES

* require PHP >=8.3; composer.lock refreshes Symfony,
Codeception 5, and PHPUnit.

- fix(visitor-report): allow any authenticated backend user for
dashboard access

- fix(config): replace SwiftMailer with yii2-symfonymailer + MAILER_DSN
in templates

- chore(vagrant): provision PHP 8.3 FPM; nginx php8.3-fpm socket

- chore(docker): use php 8.3 base images

- chore(migrations): move visitor_* migrations from _next to
console/migrations

- test(codeception): Codeception 5 output paths; common unit suite
bootstrap

- docs: add VAGRANT-SETUP.md

- chore: ignore docs/local-private/ for local notes

- chore: simplify backend/frontend main-local, .htaccess, entry index
tweaks;
* require PHP >=8.3; composer.lock refreshes Symfony, Codeception 5, and PHPUnit.

- fix(visitor-report): allow any authenticated backend user for dashboard access

- fix(config): replace SwiftMailer with yii2-symfonymailer + MAILER_DSN in templates

- chore(vagrant): provision PHP 8.3 FPM; nginx php8.3-fpm socket

- chore(docker): use php 8.3 base images

- chore(migrations): move visitor_* migrations from _next to console/migrations

- test(codeception): Codeception 5 output paths; common unit suite bootstrap

- docs: add VAGRANT-SETUP.md

- chore: ignore docs/local-private/ for local notes

- chore: simplify backend/frontend main-local, .htaccess, entry index tweaks;

Co-authored-by: Cursor <cursoragent@cursor.com>

### Features

* add .env and docker-compose.yml generation to install.sh ([c6b7c94](https://github.com/bphndigitalservice/ildis/commit/c6b7c9427ad6b3925233b35ddc43125079107b26))
* add analyze-yii2-project skill for Yii2 codebase analysis ([ec6d7ad](https://github.com/bphndigitalservice/ildis/commit/ec6d7ad15f35d22e7aad2d8c0c61efa84cb59de3))
* add console/runtime and backups directories to Dockerfile permissions ([f214961](https://github.com/bphndigitalservice/ildis/commit/f21496158c95fa4c709c50a072ca804780745fad))
* add database migrations as first-class schema source ([4a5f39e](https://github.com/bphndigitalservice/ildis/commit/4a5f39e2d5f27de1f4383a581c06a609b364a0cb))
* add Docker CI/CD pipeline and cron container setup ([91c3f8d](https://github.com/bphndigitalservice/ildis/commit/91c3f8dc459c385e88dc0c04fb683100f80e47cc))
* add install, update, and main functions to install.sh ([fc1a7a4](https://github.com/bphndigitalservice/ildis/commit/fc1a7a4b3190d0171c8f97bba9ec74fdb40fc9ca))
* add install.sh with constants, helpers, and prerequisite checks ([1e88b85](https://github.com/bphndigitalservice/ildis/commit/1e88b85362df7a827950c6f26abd7a887307dfd3))
* add interactive wizard to install.sh ([e00f098](https://github.com/bphndigitalservice/ildis/commit/e00f098ca1645e5eecebfcdde996d84d4f5182bf))
* add Podman support to install.sh — auto-detects docker compose, podman compose, or podman-compose ([481b254](https://github.com/bphndigitalservice/ildis/commit/481b2549aea33ee658af2e4968a142de5730f070))
* add update.sh and console/runtime to init environment config ([ba2c0bc](https://github.com/bphndigitalservice/ildis/commit/ba2c0bcda105e14b27aa0d0d4e3afe57085790cf))
* add update.sh script for non-technical ILDIS updates ([b226be8](https://github.com/bphndigitalservice/ildis/commit/b226be884662e37cc92e3757b6839bd6658d753c))
* add VERSION file for update tracking ([8f3a247](https://github.com/bphndigitalservice/ildis/commit/8f3a247be776bf334fa15057e81d89db9173b985))
* configure Yii2 migration path and add baseline + update_log migrations ([ca39661](https://github.com/bphndigitalservice/ildis/commit/ca3966164f6b2561e34f618a5fb5d8fc26a1215e))
* translate install.sh, update.sh, and README to Bahasa Indonesia ([53e1daa](https://github.com/bphndigitalservice/ildis/commit/53e1daad3366f18fc4e7b11b686d5b09b424d439))
* use migrations for database setup, add auto-migration on container startup ([1a3ca7d](https://github.com/bphndigitalservice/ildis/commit/1a3ca7da8a4e5713239304ca9bcc339a6f97c7ac))
* **visitor-counter:** add dashboard views and Chart.js trend chart ([bad86b5](https://github.com/bphndigitalservice/ildis/commit/bad86b5449d763cada5972d377bf76321700a791))
* **visitor-counter:** add deduplication, trackVisit, realtime stat updates + tests ([8fdc12b](https://github.com/bphndigitalservice/ildis/commit/8fdc12b46e454925af7842158aeee3c9952f5f4e))
* **visitor-counter:** add menu migration for visitor report ([6f4df0f](https://github.com/bphndigitalservice/ildis/commit/6f4df0fa41f8068146675d184978d9bb7a6ba30b))
* **visitor-counter:** add nightly aggregation console command ([69ffa4e](https://github.com/bphndigitalservice/ildis/commit/69ffa4e530e6ce6fd978080b59e2c297c832de1a))
* **visitor-counter:** add visitor stats to frontend footer ([0ff4189](https://github.com/bphndigitalservice/ildis/commit/0ff4189694328fe34c973f5af94058d0a10d920e))
* **visitor-counter:** add visitor_log and visitor_stats migrations ([05bf15e](https://github.com/bphndigitalservice/ildis/commit/05bf15ea9cd07148cb5291dc162979faab60fcf0))
* **visitor-counter:** add VisitorCounter with fingerprint and cookie logic ([94b29d7](https://github.com/bphndigitalservice/ildis/commit/94b29d71bd31ee03d0c52d53932adc5d4256a55e))
* **visitor-counter:** add VisitorLog and VisitorStats AR models ([9e34965](https://github.com/bphndigitalservice/ildis/commit/9e349659767a44d82bf3d3169cfe3986bceb0232))
* **visitor-counter:** add VisitorReportController with dashboard and chart endpoint ([6909219](https://github.com/bphndigitalservice/ildis/commit/6909219e327b60b16f43c1234b9554cfb5d2fb20))
* **visitor-counter:** register VisitorCounter in frontend config ([e8d6675](https://github.com/bphndigitalservice/ildis/commit/e8d667571ec81277faadffd2374a7b8dc95d32e5))

### Bug Fixes

* add AUTO_INCREMENT to pcounter_users id column in UserCounter auto-install schema ([79c3b0e](https://github.com/bphndigitalservice/ildis/commit/79c3b0efcd51fac12ab3ee7b362f0682698d10b6))
* address code review findings ([698da5f](https://github.com/bphndigitalservice/ildis/commit/698da5fa6a6c22d0ef71021ca28dffb69de4377e))
* **backend,frontend:** peraturan jenis options, save penandatanganan, back-to-top ([4695b59](https://github.com/bphndigitalservice/ildis/commit/4695b59b18065f0a399ac57b18a7b42d43917ce8))
* multi-stage Dockerfile.cron to include icu-dev for intl extension build ([807758f](https://github.com/bphndigitalservice/ildis/commit/807758fcd9a0d23446027c3a30f50137ae1a6117))
* security hardening - fix 2 CRITICAL and 11 HIGH vulnerabilities ([fa1d94b](https://github.com/bphndigitalservice/ildis/commit/fa1d94bac260d9aac27b189346a5235e46425674))
* **seo:** critical SEO fixes - robots.txt, canonical tags, sitemap, structured data ([7f71ad9](https://github.com/bphndigitalservice/ildis/commit/7f71ad993869da434979a9c3e32cb149939f1995))
* visitor counter fixed ([d5b17e2](https://github.com/bphndigitalservice/ildis/commit/d5b17e2ae0bf950f8dee92ebc438d6c746853034))
* visitor counter fixed ([#37](https://github.com/bphndigitalservice/ildis/issues/37)) ([c6ad38c](https://github.com/bphndigitalservice/ildis/commit/c6ad38c693834a9c6dbe779b263ea3e688a3556f))

### Refactoring

* clean up footer layouts — add version from package.json, restyle user/role display, remove dead links ([021c54c](https://github.com/bphndigitalservice/ildis/commit/021c54c0e731a3c7d644425dbc4d281e09d9fde2))
* major clean code fixes across 24 files ([7764541](https://github.com/bphndigitalservice/ildis/commit/7764541d2f29080e982ec2c4dc50fce4452631b2))
* replace magic numbers with model constants, replace getTanggal with DateHelper, fix naming ([4bd81c1](https://github.com/bphndigitalservice/ildis/commit/4bd81c15fe97dfa4ef3b7740c5878b50f6dc95d0))
* update.sh becomes thin wrapper for install.sh --update ([f02960a](https://github.com/bphndigitalservice/ildis/commit/f02960a87b8b4a0037b7f589187541d301bb83bb))
* **visitor-counter:** redesign footer analytics strip with polished UI ([ed18aad](https://github.com/bphndigitalservice/ildis/commit/ed18aad54901098c5dfc7a88d7b9ae0bf4048a3c))

### Documentation

* add one-click install design ([9681ac7](https://github.com/bphndigitalservice/ildis/commit/9681ac7bdff2f9a5b536312274e61a889882846a))
* add one-click install implementation plan ([968611f](https://github.com/bphndigitalservice/ildis/commit/968611f9516f75a5a8db0fc523ef6668b45057a4))
* add quick install instructions to README ([8124afd](https://github.com/bphndigitalservice/ildis/commit/8124afdcebd76bbb44167e6866ffd2b5097bec64))
* add update mechanism implementation plan ([623019c](https://github.com/bphndigitalservice/ildis/commit/623019c4ed67a3842db95126b8ec924d6f55b721))
* **visitor-counter:** add implementation plan ([a8d8c03](https://github.com/bphndigitalservice/ildis/commit/a8d8c03ca110449ab6b59ec0ca07e47b54b9c02c))
* **visitor-counter:** production-grade visitor counter design document ([44933f3](https://github.com/bphndigitalservice/ildis/commit/44933f378cbb466f9f0b95d4b4362d64434351fd))

### Maintenance

* align dev stack with PHP 8.3 and Symfony Mailer ([b8029dc](https://github.com/bphndigitalservice/ildis/commit/b8029dc008ebc31dde04fb51d05e1da931329182))
* align dev stack with PHP 8.3 and Symfony Mailer ([#35](https://github.com/bphndigitalservice/ildis/issues/35)) ([136f95c](https://github.com/bphndigitalservice/ildis/commit/136f95c468cea4a8a45903cf05957fc1c1010fca))

## [4.1.4](https://github.com/bphndigitalservice/ildis/compare/v4.1.3...v4.1.4) (2026-05-13)

## [4.1.3](https://github.com/bphndigitalservice/ildis/compare/v4.1.2...v4.1.3) (2026-05-13)

### Bug Fixes

* **berita:** filter published news on OPAC list and block draft detail URLs ([ee3aba7](https://github.com/bphndigitalservice/ildis/commit/ee3aba7f2c3bd63a64ce8301568417bccc66deaa))
* **berita:** filter published news on OPAC list and block draft detail URLs ([#36](https://github.com/bphndigitalservice/ildis/issues/36)) ([61c7354](https://github.com/bphndigitalservice/ildis/commit/61c735484ad6d67eebb6b323061b79b7d77884c8))

## [4.1.2](https://github.com/bphndigitalservice/ildis/compare/v4.1.1...v4.1.2) (2026-05-12)

### Bug Fixes

* berita update status dropdown value and update portal text to JDIH ([7e0522d](https://github.com/bphndigitalservice/ildis/commit/7e0522d8738cc9119fdc090ceb09787dca35b173))

## [4.1.1](https://github.com/bphndigitalservice/ildis/compare/v4.1.0...v4.1.1) (2026-05-12)

### Bug Fixes

* use exact match for is_publish filter instead of LIKE ([88ed241](https://github.com/bphndigitalservice/ildis/commit/88ed241c661e78d96988528538b5b5bd65733d78))

## [4.1.0](https://github.com/bphndigitalservice/ildis/compare/v4.0.0...v4.1.0) (2026-03-15)

### Features

* add a comprehensive migration plan document and update Summernote's TypeScript configuration with `skipLibCheck` and `node_modules` exclusion. ([cfa8ed9](https://github.com/bphndigitalservice/ildis/commit/cfa8ed94a869e390e94c8632e2ee23f4ff847b48))
* **docker:** add MySQL service to docker-compose ([5c91078](https://github.com/bphndigitalservice/ildis/commit/5c910787a0897fdce31e6c6d952df86f64087d7b))
* **docker:** implement s6-overlay properly with nginx ([d79bdba](https://github.com/bphndigitalservice/ildis/commit/d79bdba22c17348132b61cb3c3ee2339a2f7a3ad))
* **docker:** separate nginx container from PHP-FPM ([2f6f572](https://github.com/bphndigitalservice/ildis/commit/2f6f57291407bb2b28af180914bd3fbe55aaa119))
* **docker:** use environment variables instead of .env file ([3f708f0](https://github.com/bphndigitalservice/ildis/commit/3f708f07928379c08d4f1139180335d976b8ad58))
* Implement SEO metatags and refine document detail page layouts and styling across various document types. ([aacf81e](https://github.com/bphndigitalservice/ildis/commit/aacf81e987340f7778f5c91efef0b3535910f22f))
* Introduce comprehensive migration plan, add cookie key generation script, and enhance Docker Nginx configuration loading. ([3f4d0f8](https://github.com/bphndigitalservice/ildis/commit/3f4d0f81e28cd18aeb3a5b183a8652282ff568dc))

### Bug Fixes

* **docker:** add --ignore-platform-reqs to composer install ([84e233f](https://github.com/bphndigitalservice/ildis/commit/84e233ffe15e5f2deff24b42e183c29f4a1ea0e3))
* **docker:** add MySQL user config with root password ([1607f78](https://github.com/bphndigitalservice/ildis/commit/1607f78da4eb856023eaa3d2eb49be4c185892ad))
* **docker:** create directories before setting permissions ([5f86932](https://github.com/bphndigitalservice/ildis/commit/5f86932941bef60b7b08a382de2e85a6bf138588))
* **docker:** create supervisor conf.d directory before config ([d2c8f61](https://github.com/bphndigitalservice/ildis/commit/d2c8f61c588e6c74cdfd8cf4159b6ceecc8f612c))
* **docker:** fix nginx and php-fpm s6 service scripts ([863aa80](https://github.com/bphndigitalservice/ildis/commit/863aa80b58497483cba48ade11da1f051f5c4b83))
* **docker:** nginx must run in foreground with daemon off ([854ff40](https://github.com/bphndigitalservice/ildis/commit/854ff4011a3a399246c30064829d48310778bd0f))
* **docker:** proper s6-overlay implementation with execlineb ([3fa17c3](https://github.com/bphndigitalservice/ildis/commit/3fa17c399edea80ee67e518dc0fb8eeccab60e44))
* **docker:** provide default MySQL password ([cf9bc27](https://github.com/bphndigitalservice/ildis/commit/cf9bc2796a77f1258411bb3cc4144a27be2207de))
* **docker:** remove invalid finish script creation ([3e9527a](https://github.com/bphndigitalservice/ildis/commit/3e9527acdf54a01a6f7c9efc1c6c4d8f14b9c089))
* **docker:** remove MYSQL_USER for root user ([7aa9c52](https://github.com/bphndigitalservice/ildis/commit/7aa9c5261498c255934f3560971fd66c0be16cf5))
* **docker:** run php init for Yii2 config bootstrap ([5e4ad9d](https://github.com/bphndigitalservice/ildis/commit/5e4ad9d12f8edfda9b2a7b08030b9ae00ad77a56))
* **docker:** use -dev packages for compiling PHP extensions ([ddcd50e](https://github.com/bphndigitalservice/ildis/commit/ddcd50ed3f40b8450fcdcf20fc632361cf1b97ed))
* **docker:** use bash script instead of s6-overlay ([b0d2847](https://github.com/bphndigitalservice/ildis/commit/b0d2847a211c1fb08eb9b0f4682aea811fdcbcd9))
* **docker:** use correct s6-overlay v3.2.2.0 with tar.xz files ([f7e5d0a](https://github.com/bphndigitalservice/ildis/commit/f7e5d0a7bb245aa0778ff07c3c7076dc0a32d796))
* **docker:** use s6-overlay instead of supervisord ([8ec69a3](https://github.com/bphndigitalservice/ildis/commit/8ec69a350bfb8173535c5f7eb0050860734b5938))
* **docker:** use simple startup script instead of s6-overlay ([ca995fe](https://github.com/bphndigitalservice/ildis/commit/ca995fe04d0dc49c4385793a31d79b12cd168f38))
* summer notes built ([b20ced4](https://github.com/bphndigitalservice/ildis/commit/b20ced494825f8f4d5b7ff53ec8842e52a4198a0))

### Refactoring

*  Docker infrastructure to s6-overlay v3 with external Nginx configuration, update composer dependencies, add calendar PHP extension, improve .env parsing, and include a cookie key generation script. ([d59b729](https://github.com/bphndigitalservice/ildis/commit/d59b7291a0955331be8c8a6220963971a074652c))

### Maintenance

* **config, layout:** enable debug mode in `.env.example` and fix footer HTML structure ([ec7c680](https://github.com/bphndigitalservice/ildis/commit/ec7c6807c18e9080bd9a2c8ca4c629c6bd4355bf))
* **husky:** remove pre-push hook ([6bc5bad](https://github.com/bphndigitalservice/ildis/commit/6bc5bada3c472a34dc0a3b3af238619d4549cd3a))
* release script fix ([c200119](https://github.com/bphndigitalservice/ildis/commit/c200119d4195e2bb24e1ce332e4eb064f9b5149d))
* semantic release fix ([62a5f42](https://github.com/bphndigitalservice/ildis/commit/62a5f4207d9a68cccd3d99c8774336fa59a0d922))
* semantic release fix ([#31](https://github.com/bphndigitalservice/ildis/issues/31)) ([1002a4e](https://github.com/bphndigitalservice/ildis/commit/1002a4ead8d07363259d186c897bda194fe29545))

# 4.0.0 (2025-08-29)


### Bug Fixes

* add calendar php extension ([a714c6a](https://github.com/bphndigitalservice/ildis/commit/a714c6aa18242b6fbe860a2fac5aaff2441ff42c))
* **config:** correct typo in `.releaserc.json` filename ([dc0a0ae](https://github.com/bphndigitalservice/ildis/commit/dc0a0aea67631c0caa761c89baf5cb77161ea561))
* disable innodb_strict_mode to allow import `DATABASE/ildis_v4.sql` into mariadb ([7672a66](https://github.com/bphndigitalservice/ildis/commit/7672a665d344d586df1556774129581696562c01))


### Features

* initial public release of ILDIS (Indonesian Law Documentation Information System) ([3e5eddf](https://github.com/bphndigitalservice/ildis/commit/3e5eddf617de1d9ae9cb5daf0591a09304f5c8a4))
* initialize project using Yii2 init script ([1311c66](https://github.com/bphndigitalservice/ildis/commit/1311c66347a424b4b01cfd746aabdeaa04a1b1ab))
* restore index.phps in environment folder ([ff1d0db](https://github.com/bphndigitalservice/ildis/commit/ff1d0dbe61b640a7ff864d0c0f3f1a1d832e8edf))
* set up local development using devcontainer & vscode ([d158a62](https://github.com/bphndigitalservice/ildis/commit/d158a6228b13d1b387270f4568d7f4fba292c46e))
