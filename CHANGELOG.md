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
