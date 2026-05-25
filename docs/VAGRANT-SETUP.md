# Instalasi lingkungan pengembangan ILDIS dengan Vagrant

Panduan ini sebagai update dokumentasi instalasi lama dan diselaraskan dengan konfigurasi terkini di repositori: **PHP 8.3** di guest, **Composer** dengan `composer.lock` yang membutuhkan **PHP ≥ 8.3**, mailer **Symfony** (bukan SwiftMailer), serta dependensi dev **Codeception 5**.

## Prasyarat di komputer host (Windows / macOS / Linux)

- [VirtualBox](https://www.virtualbox.org/) dan [Vagrant](https://www.vagrantup.com/)
- Plugin Vagrant **hostmanager** (untuk entri `ildis-frontend.test` / `ildis-backend.test` di file hosts):

  ```bash
  vagrant plugin install vagrant-hostmanager
  ```

- Akses internet untuk mengunduh box dan paket Composer

## 1. Token GitHub (wajib untuk provisioning pertama)

Provisioning menjalankan `composer install` di dalam VM dan membutuhkan token untuk rate limit GitHub.

1. Buat **Personal Access Token** di GitHub: [https://github.com/settings/tokens](https://github.com/settings/tokens)  
   Untuk proyek ini biasanya cukup scope **`repo`** (atau setidaknya akses baca ke repositori yang diperlukan).
2. Salin token ke file konfigurasi lokal Vagrant.

File `vagrant/config/vagrant-local.yml` dibuat otomatis dari contoh jika belum ada. Edit dan isi `github_token`:

```yaml
# Your personal GitHub token
github_token: <paste-token-anda-di-sini>
# Read more: https://github.com/blog/1509-personal-api-tokens

timezone: Asia/Jakarta
box_check_update: false
machine_name: ildis4
ip: 192.168.83.137
cpus: 1
memory: 1024
```

> **Catatan keamanan:** Jangan commit `vagrant-local.yml` jika berisi token nyata; pastikan file ini di-ignore oleh Git jika perlu.

## 2. Menjalankan mesin virtual

Dari root repositori di host:

```bash
vagrant up
```

Setelah sukses, Anda akan melihat pesan mirip:

- Frontend: `http://ildis-frontend.test`
- Backend: `http://ildis-backend.test`

Masuk ke guest:

```bash
vagrant ssh
```

### Memperbarui VM yang sudah ada (tanpa vagrant destroy)

Gunakan jalur ini bila VM lama masih memakai PHP 7.4 / 8.1 / 8.2 setelah Anda `git pull` perubahan terbaru, atau bila Anda ingin mempertahankan disk VM dan data MySQL lokal.

**Mengapa tidak cukup `vagrant provision` saja?**  
`vagrant provision` menjalankan `always-as-root.sh` (restart PHP-FPM, Nginx, MySQL) dan **`once-as-root.sh` tidak dijalankan lagi** setelah VM pertama kali dibuat. Jadi **upgrade versi PHP** harus dilakukan manual di guest (atau destroy+up untuk provisioning dari nol).

#### Langkah 1 — Kode dan skrip terbaru di host

Di mesin host, dari root repositori:

```bash
git pull
```

Folder proyek disinkronkan ke `/app` di guest; pastikan VM menyala (`vagrant up`).

#### Langkah 2 — Pasang PHP 8.3 dan selaraskan dengan repo

Masuk guest (`vagrant ssh`), lalu jalankan perintah berikut sebagai **root** (misalnya `sudo -i`):

```bash
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y \
  php8.3 php8.3-cli php8.3-common php8.3-curl php8.3-mbstring \
  php8.3-intl php8.3-mysql php8.3-xml php8.3-fpm php8.3-gd php8.3-zip \
  php8.3-xdebug unzip
```

Konfigurasi pool FPM dan Xdebug mengikuti `vagrant/provision/once-as-root.sh`:

```bash
sed -i 's/user = www-data/user = vagrant/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/group = www-data/group = vagrant/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/owner = www-data/owner = vagrant/' /etc/php/8.3/fpm/pool.d/www.conf

cat << 'EOF' > /etc/php/8.3/mods-available/xdebug.ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9000
xdebug.discover_client_host=1
EOF

systemctl enable php8.3-fpm
systemctl restart php8.3-fpm
update-alternatives --set php /usr/bin/php8.3
```

**Nginx** harus memakai socket **`php8.3-fpm.sock`**. File di repo adalah `vagrant/nginx/app.conf` (biasanya sudah tertaut ke `/etc/nginx/sites-enabled/app.conf`). Setelah `git pull`, uji dan muat ulang:

```bash
nginx -t && systemctl reload nginx
```

**Opsional — hentikan FPM versi lama** agar tidak membingungkan:

```bash
for v in 7.4 8.0 8.1 8.2; do
  systemctl disable --now php${v}-fpm 2>/dev/null || true
done
```

#### Langkah 3 — Verifikasi

```bash
php -v
# Harus PHP 8.3.x

ls -la /var/run/php/php8.3-fpm.sock
```

#### Langkah 4 — Composer & migrasi

Sebagai user `vagrant`:

```bash
cd /app
composer install
php yii migrate   # jika ada migrasi baru
```

Dari **host**, Anda boleh menjalankan `vagrant provision` agar `always-as-root.sh` me-restart stack (pastikan di repo `vagrant/provision/always-as-root.sh` memakai `PHP_VERSION="8.3"`).

#### Kapan tetap memakai `vagrant destroy`?

- Lingkungan guest rusak parah, disk penuh, atau Anda ingin **fresh install** persis seperti mesin baru.
- Anda tidak ingin menjalankan langkah manual di atas.

---

### Memperbarui kode & dependensi saja (PHP di guest sudah 8.3)

Jika `php -v` sudah benar dan yang berubah hanya kode atau `composer.lock`:

1. **Host:** `git pull`
2. **Guest:**

   ```bash
   cd /app
   composer install
   ./init --env=Development --overwrite=n   # hanya jika ada perubahan environments/
   php yii migrate                          # jika perlu
   ```

Tidak perlu reinstall PHP atau `vagrant destroy`.

---

### Masalah umum: VirtualBox `VERR_ALREADY_EXISTS` / nama VM bentrok

Jika `vagrant up` gagal karena folder atau VM dengan nama yang sama masih tersisa:

1. Tutup VirtualBox Manager.
2. Hapus VM sisa lewat CLI (sesuaikan nama dari `VBoxManage list vms`):

   ```text
   "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" list vms
   "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" unregistervm "NAMA_VM" --delete
   ```

3. Bersihkan folder kosong di `VirtualBox VMs` jika masih ada konflik nama `ildis4`.
4. Jalankan `vagrant up` lagi.

### Penting: PHP 8.3 di dalam VM

Stack aplikasi dan `composer.lock` membutuhkan **PHP 8.3** (bukan 7.4 / 8.1).  
Provisioning **pertama kali** (`once-as-root.sh`) memasang **php8.3** dan Nginx memakai socket **`php8.3-fpm.sock`**.

Setelah masuk guest, verifikasi:

```bash
php -v
```

Keluaran harus menunjukkan **PHP 8.3.x**. Jika masih 7.4 / 8.1 / 8.2, ikuti bagian [Memperbarui VM yang sudah ada (tanpa vagrant destroy)](#memperbarui-vm-yang-sudah-ada-tanpa-vagrant-destroy) di atas. Alternatif paling “bersih” dari nol: `vagrant destroy` lalu `vagrant up` (data di dalam VM hilang kecuali Anda punya backup).

## 3. Dependensi PHP (Composer)

Di dalam guest, dari folder aplikasi:

```bash
cd /app
composer install
```

- Jangan mengandalkan `composer update` hanya untuk “memperbaiki” lock di tim; gunakan versi `composer.lock` dari repositori kecuali Anda sengaja meng-upgrade dependensi.
- Peringatan **Xdebug: Could not connect to debugging client** aman diabaikan jika IDE Anda tidak mendengarkan debug; itu bukan kegagalan Composer.

## 4. Inisialisasi environment Yii (`php init`)

Setelah `vendor/` terisi:

```bash
cd /app
./init --env=Development --overwrite=y
```

(Alternatif: `./init` interaktif dan pilih Development.)

## 5. File `.env`

Salin contoh dan sesuaikan:

```bash
cd /app
cp .env.example .env
```

Contoh isi yang relevan untuk Vagrant (aplikasi dan MySQL jalan di VM yang sama):

```env
# Environment configuration file for the application.
YII_ENV=dev
YII_DEBUG=true

# Database configuration (dari dalam VM, MySQL di localhost)
DB_HOST=127.0.0.1
DB_USER=root
DB_PASSWORD=
DB_DATABASE=ildis_v4
DB_DATABASE_PORT=3306

PUBLIC_DOMAIN=http://ildis-frontend.test

# Cookie validation keys — isi string acak yang cukup panjang
COOKIE_VALIDATION_KEY_BE=
COOKIE_VALIDATION_KEY_FE=

# reCAPTCHA (isi jika fitur dipakai)
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

# Email (Symfony Mailer). Tanpa ini, transport default aman untuk dev:
# MAILER_DSN=null://null
# Untuk SMTP nyata, contoh:
# MAILER_DSN=smtp://USER:PASS@smtp.contoh.com:587
```

Konfigurasi `common/config/main-local.php` membaca `MAILER_DSN` dari environment (via `.env` yang dimuat `common/config/env.php`).

## 6. Database

### Buat database

```bash
mysql -u root
```

Di prompt MySQL:

```sql
create database ildis_v4; //tekan enter
EXIT;
```

### Isi skema / data

- Jika tim menyediakan dump SQL (misalnya `DATABASE/ildis_v4.sql`), impor:

  ```bash
  mysql -u root ildis_v4 < /app/DATABASE/ildis_v4.sql
  ```

- jalankan migrasi Yii:

  ```bash
  cd /app
  php yii migrate
  ```

Sesuaikan nama database dengan nilai `DB_DATABASE` di `.env`.

## 7. Akses aplikasi

| Lingkungan | URL |
|------------|-----|
| Frontend | http://ildis-frontend.test |
| Backend (admin) | http://ildis-backend.test |

Pastikan plugin hostmanager sudah menulis entri hosts di mesin Anda, atau tambahkan manual mengarah ke IP VM (`ip` di `vagrant-local.yml`, misalnya `192.168.83.137`).

## 8. Akses MySQL dari host (misalnya DBeaver)

MySQL di VM di-bind ke `0.0.0.0` dan user `root` dari jarak jauh sesuai provisioning default Vagrant proyek ini.

- **Host:** IP privat VM (sama dengan `ip` di `vagrant-local.yml`)
- **Port:** `3306`
- **Database:** sesuai `.env` (misalnya `ildis_v4`)
- **User:** `root`
- **Password:** kosong (default provisioning; ubah di production)

Alternatif aman: **SSH tunnel** lewat `vagrant ssh-config`, lalu koneksi MySQL ke `127.0.0.1:3306` melalui tunnel.

## 9. Ringkasan perintah cepat (guest)

```bash
cd /app
composer install
./init --env=Development --overwrite=y
cp .env.example .env
# edit .env — DB, cookie keys, opsional MAILER_DSN & reCAPTCHA
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ildis_v4;"
php yii migrate   # atau impor dump SQL jika dipakai
```

## 10. Perbedaan utama dari dokumentasi lama

| Topik | Dulu | Sekarang |
|--------|------|----------|
| PHP di VM | 7.4 | **8.3** |
| `composer install` | Bisa di PHP lama | Wajib **PHP ≥ 8.3** sesuai `composer.json` / lock |
| Mailer | SwiftMailer (sering error class tidak ada) | **yii2-symfonymailer** + **`MAILER_DSN`** |
| Tes dev | Codeception 2 | **Codeception 5** + PHPUnit baru |
| Nginx → PHP-FPM | `php7.4-fpm.sock` | **`php8.3-fpm.sock`** |

Jika ada langkah yang tidak cocok dengan cabang atau dump database tim Anda, sesuaikan nama file SQL dan perintah migrasi dengan dokumentasi internal proyek.
