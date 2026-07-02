# Laravel 项目 LNMP 一键自动化部署文档

> 项目: https://github.com/Janden-dev/love-laravel.git  
> 目标路径: `/home/ubuntu/larry`  
> 架构: **L**inux + **N**ginx + **M**ySQL 8.0 + **P**HP 8.3 (FPM)  
> 数据库: MySQL 8.0, 账号 `root`, 密码 `Larry@0103!`, 数据库名 `love_larry`

---

## 目录

1. [环境检查](#1-环境检查)
2. [克隆项目](#2-克隆项目)
3. [创建数据库](#3-创建数据库)
4. [配置 .env 文件](#4-配置-env-文件)
5. [安装 Composer 依赖](#5-安装-composer-依赖)
6. [生成 APP_KEY](#6-生成-app_key)
7. [数据库迁移](#7-数据库迁移)
8. [构建前端资源 (Vite)](#8-构建前端资源-vite)
9. [配置 Nginx](#9-配置-nginx)
10. [设置文件权限](#10-设置文件权限)
11. [Laravel 生产优化](#11-laravel-生产优化)
12. [验证](#12-验证)

---

## 1. 环境检查

前置要求（脚本自动检查，缺失则安装）：

### 1.1 软件清单

| 软件          | 版本要求    | 用途         |
| ------------- | ----------- | ------------ |
| Nginx         | ≥ 1.18      | Web 服务器   |
| PHP           | ≥ 8.3 (FPM) | 应用运行时   |
| PHP 扩展      | 见下方      | Laravel 所需 |
| MySQL         | 8.0+        | 数据库       |
| Composer      | ≥ 2.x       | PHP 依赖管理 |
| Node.js + npm | ≥ 18        | 前端构建     |

### 1.2 必需 PHP 扩展

```bash
# 安装命令
sudo apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql \
  php8.3-xml php8.3-mbstring php8.3-curl php8.3-bcmath \
  php8.3-zip php8.3-gd php8.3-intl
```

| 扩展            | 检查命令                             |
| --------------- | ------------------------------------ |
| bcmath          | `php -m \| grep bcmath`              |
| ctype           | `php -m \| grep ctype`               |
| curl            | `php -m \| grep curl`                |
| fileinfo        | `php -m \| grep fileinfo`            |
| gd              | `php -m \| grep gd`                  |
| intl            | `php -m \| grep intl`                |
| json            | `php -m \| grep json`                |
| mbstring        | `php -m \| grep mbstring`            |
| openssl         | `php -m \| grep openssl`             |
| PDO + pdo_mysql | `php -m \| grep -E 'PDO\|pdo_mysql'` |
| tokenizer       | `php -m \| grep tokenizer`           |
| xml / SimpleXML | `php -m \| grep -E 'xml\|SimpleXML'` |
| zip             | `php -m \| grep zip`                 |

### 1.3 检查命令参考

```bash
php -v                    # PHP 版本
php -m                    # 已安装扩展
nginx -v                  # Nginx 版本
mysql --version           # MySQL 版本
composer --version        # Composer 版本
php-fpm8.3 -v             # PHP-FPM 版本
systemctl status nginx    # Nginx 运行状态
systemctl status mysql    # MySQL 运行状态
systemctl status php8.3-fpm  # PHP-FPM 运行状态
```

---

## 2. 克隆项目

```bash
cd /home/ubuntu
# 先删除已存在的目录（如果重跑脚本）
rm -rf larry
# 克隆项目
git clone https://github.com/Janden-dev/love-laravel.git larry
cd larry
```

---

## 3. 创建数据库

### 3.1 设置 MySQL root 密码

> ⚠️ 跳过此步如果 root 密码已设置

```bash
# 如果 root 有密码但忘记了，用 debian-sys-maint 用户重置
# 先从 /etc/mysql/debian.cnf 获取凭据
DEBIAN_PASS=$(sudo cat /etc/mysql/debian.cnf | grep "^password" | head -1 | awk '{print $3}')

sudo mysql -u debian-sys-maint -p"$DEBIAN_PASS" \
  -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Larry@0103!'; FLUSH PRIVILEGES;"
```

### 3.2 创建数据库

```bash
mysql -u root -p'Larry@0103!' \
  -e "CREATE DATABASE IF NOT EXISTS love_larry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3.3 验证

```bash
mysql -u root -p'Larry@0103!' -e "SHOW DATABASES LIKE 'love_larry';"
```

---

## 4. 配置 .env 文件

### 4.1 复制环境文件

```bash
cp .env.example .env
```

### 4.2 写入配置内容

> **关键配置项：** 参考 [.env 模板](#env-模板)，特别注意：
> - `APP_ENV=production`
> - `APP_DEBUG=false`
> - `DB_CONNECTION=mysql`
> - `DB_DATABASE=love_larry`
> - `DB_PASSWORD="Larry@0103!"`（双引号包裹，因为密码含特殊字符）
> - `SESSION_DRIVER=database`

### 4.3 `.env` 模板

```env
APP_NAME="Love Laravel"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=love_larry
DB_USERNAME=root
DB_PASSWORD="Larry@0103!"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

---

## 5. 安装 Composer 依赖

```bash
cd /home/ubuntu/larry
composer install --no-dev --optimize-autoloader
```

---

## 6. 生成 APP_KEY

```bash
php artisan key:generate --force
```

> 验证：
> ```bash
> grep "^APP_KEY" .env
> # 应输出类似: APP_KEY=base64:xxxx...
> ```

---

## 7. 数据库迁移

```bash
php artisan migrate --force
```

> 预期输出（7 张表）：
> - `users`
> - `cache`
> - `cache_locks`
> - `jobs`
> - `job_batches`
> - `sessions`
> - `anniversaries`
> - `diaries`
> - `photos`

### 7.1 种子默认用户

项目自带 `UserSeeder`，会创建默认用户：

```bash
php artisan db:seed --class=UserSeeder --force
```

| 字段     | 值          |
| -------- | ----------- |
| username | `janden`    |
| password | `larry2026` |

> **注意：** UserSeeder 使用 `Hash::make('larry2026')`，User 模型的 `'password' => 'hashed'` cast 会自动处理哈希。直接用 Eloquent `updateOrCreate` 即可，不需要额外操作。

---

## 8. 构建前端资源 (Vite)

```bash
npm install
npm run build
```

> 构建后会在 `public/build/` 生成 `manifest.json`、CSS 和 JS 文件。缺少这一步会导致访问时 `Vite manifest not found` 错误。

---

## 9. 配置 Nginx

### 9.1 创建虚拟主机配置文件

路径: `/etc/nginx/sites-available/laravel`

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    root /home/ubuntu/larry/public;
    index index.php index.html index.htm;

    server_name _;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/laravel-access.log;
    error_log  /var/log/nginx/laravel-error.log;
}
```

### 9.2 启用站点

```bash
# 删除旧的默认站点
sudo unlink /etc/nginx/sites-enabled/default 2>/dev/null

# 启用 Laravel 站点
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重载 Nginx
sudo systemctl reload nginx
```

---

## 10. 设置文件权限

> **核心原则：** Nginx 和 PHP-FPM 都以 `www-data` 用户运行，所以 `storage/` 和 `bootstrap/cache/` 目录必须对 `www-data` 可写。

```bash
# www-data 需要能遍历 /home/ubuntu 目录
sudo chmod o+x /home/ubuntu

# 将 ubuntu 用户加入 www-data 组（可选，方便调试）
sudo usermod -a -G www-data ubuntu

# 设置 storage 和 cache 目录的权限
sudo chown -R www-data:www-data /home/ubuntu/larry/storage /home/ubuntu/larry/bootstrap/cache
sudo chmod -R 775 /home/ubuntu/larry/storage /home/ubuntu/larry/bootstrap/cache
```

> **注：** 如果后续用 `ubuntu` 用户运行 `artisan` 命令报权限错误，改为 `sg www-data -c 'php artisan xxx'` 执行。

---

## 11. Laravel 生产优化

> **⚠️ 重要顺序：** 必须先设置好 `.env` 的所有配置项，再执行缓存命令。  
> 因为 `config:cache` 会将 `.env` 的值烘焙到缓存文件中，缓存生成后 `.env` 不再被读取。  
> 如果缓存后发现 `.env` 配置错了，需先 `config:clear`，修好 `.env`，再重新缓存。

```bash
# 确保 www-data 可写 logs
sudo chmod 777 /home/ubuntu/larry/storage/logs
sudo touch /home/ubuntu/larry/storage/logs/laravel.log
sudo chmod 666 /home/ubuntu/larry/storage/logs/laravel.log

# 使用 sg 以 www-data 组身份执行（解决权限问题）
sg www-data -c '
  php artisan config:cache &&
  php artisan route:cache &&
  php artisan view:cache
'
```

---

## 12. 验证

### 12.1 HTTP 响应检查

```bash
# 检查首页是否返回 200
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/

# 检查完整登录流程
rm -f /tmp/cjar_test.txt

# 1. 获取 CSRF token
CSRF=$(curl -s -c /tmp/cjar_test.txt http://localhost/login | grep -oP 'name="_token".*?value="\K[^"]+')

# 2. 登录
curl -s -c /tmp/cjar_test.txt -b /tmp/cjar_test.txt -o /dev/null -w "Login: HTTP %{http_code}\n" \
  -X POST http://localhost/login \
  -d "username=janden&password=larry2026&_token=$CSRF"

# 3. 访问首页（验证 session 持久化）
curl -s -b /tmp/cjar_test.txt -o /dev/null -w "Home: HTTP %{http_code}\n" http://localhost/

# 4. 获取页面标题
curl -s -b /tmp/cjar_test.txt http://localhost/ | grep -oP '<title>[^<]+</title>'
```

### 12.2 数据库验证

```bash
mysql -u root -p'Larry@0103!' -e "USE love_larry; SELECT id, username FROM users;"
```

---

## ⚠️ 已知问题 & 修复记录

### 问题 1：`getAuthIdentifierName()` 导致登录后无法维持 session

**症状：** 登录 POST 返回 302（成功），但后续请求又被重定向到 `/login`。  
**根因：** `app/Models/User.php` 重写了 `getAuthIdentifierName()` 返回 `'username'`。

```php
// ❌ 问题代码
public function getAuthIdentifierName(): string
{
    return 'username';
}
```

当 Laravel 在后续请求中重新加载用户时，`EloquentUserProvider::retrieveById()` 会执行：

```php
// 因为 getAuthIdentifierName() 返回 'username'
// 实际查询的是：WHERE username = 1
// 而 username = 'janden'，所以查不到用户
$this->newModelQuery($model)->where($model->getAuthIdentifierName(), $identifier)
```

**修复方法：** 删除 `getAuthIdentifierName()` 重写，或增加 `getAuthIdentifier()` 返回正确的主键值。

```php
// ✅ 修复方案：删除 getAuthIdentifierName() 重写即可
//   Auth::attempt(['username' => 'janden', 'password' => '...']) 
//   会自动按 credential 中的 'username' 字段查找用户，
//   不需要额外重写 getAuthIdentifierName()
```

### 问题 2：`config:cache` 后 APP_KEY 为空 (NULL)

**症状：** `bootstrap/cache/config.php` 中 `'key' => NULL`。  
**根因：** 如果先执行了 `config:cache` 时 `.env` 还未配置正确（或未加载），缓存的 APP_KEY 就是 NULL。缓存生成后 `.env` 不再被加载，`env('APP_KEY')` 始终返回 NULL。  
**修复方法：** 先 `config:clear`，确认 `env('APP_KEY')` 正常，再重新 `config:cache`。

### 问题 3：Permission denied — www-data 无法访问项目目录

**症状：** Nginx 错误日志显示 `stat() "/home/ubuntu/larry/public/" failed (13: Permission denied)`。  
**根因：** `/home/ubuntu` 默认权限为 `750`，`www-data` 用户没有执行权限，无法遍历 `ubuntu` 主目录。  
**修复方法：** `sudo chmod o+x /home/ubuntu`。

### 问题 4：`Vite manifest not found`

**症状：** 访问页面报 500，日志显示 `Vite manifest not found at: public/build/manifest.json`。  
**修复方法：** 运行 `npm install && npm run build` 构建前端资源。

---

## 完整部署脚本参考

以下是一个可复用的完整部署脚本框架：

```bash
#!/bin/bash
set -e

# ====== 配置变量 ======
REPO_URL="https://github.com/Janden-dev/love-laravel.git"
DEPLOY_PATH="/home/ubuntu/larry"
DB_NAME="love_larry"
DB_USER="root"
DB_PASS="Larry@0103!"
MYSQL_ROOT_PASS="Larry@0103!"
ADMIN_USER="ubuntu"

# ====== 1. 环境检查 ======
echo ">>> Checking environment..."
command -v nginx || { echo "Nginx not found"; exit 1; }
command -v php || { echo "PHP not found"; exit 1; }
command -v mysql || { echo "MySQL not found"; exit 1; }
command -v composer || { echo "Composer not found"; exit 1; }

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "PHP version: $PHP_VER"

# 检查 PHP 扩展
REQUIRED_EXT=("bcmath" "ctype" "curl" "fileinfo" "gd" "intl" "json" "mbstring" 
              "openssl" "PDO" "pdo_mysql" "tokenizer" "xml" "zip")
for ext in "${REQUIRED_EXT[@]}"; do
    php -m | grep -qi "$ext" || { echo "Missing PHP extension: $ext"; exit 1; }
done
echo "All PHP extensions OK"

# ====== 2. 克隆项目 ======
echo ">>> Cloning repository..."
rm -rf "$DEPLOY_PATH"
git clone "$REPO_URL" "$DEPLOY_PATH"
cd "$DEPLOY_PATH"

# ====== 3. 设置 MySQL ======
echo ">>> Setting up MySQL..."
mysql -u root -p"$MYSQL_ROOT_PASS" \
  -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ====== 4. 配置 .env ======
echo ">>> Configuring .env..."
cp .env.example .env
# 写入配置（使用 sed 替换）

# ====== 5. 安装依赖 ======
echo ">>> Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo ">>> Generating APP_KEY..."
php artisan key:generate --force

# ====== 6. 数据库迁移 ======
echo ">>> Running migrations..."
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force

# ====== 7. 构建前端 ======
echo ">>> Building frontend assets..."
npm install
npm run build

# ====== 8. 配置 Nginx ======
echo ">>> Configuring Nginx..."
# 写入 Nginx 配置...

sudo nginx -t
sudo systemctl reload nginx

# ====== 9. 设置权限 ======
echo ">>> Setting permissions..."
sudo chmod o+x /home/ubuntu
sudo chown -R www-data:www-data "$DEPLOY_PATH/storage" "$DEPLOY_PATH/bootstrap/cache"
sudo chmod -R 775 "$DEPLOY_PATH/storage" "$DEPLOY_PATH/bootstrap/cache"

# ====== 10. 生产优化 ======
echo ">>> Optimizing Laravel..."
sg www-data -c "
  php artisan config:cache &&
  php artisan route:cache &&
  php artisan view:cache
"

echo ""
echo "========================================"
echo "  Deployment complete!"
echo "  URL: http://localhost"
echo "  Login: janden / larry2026"
echo "========================================"
```

---

## 附录：文件路径参考

| 文件/目录      | 路径                                            |
| -------------- | ----------------------------------------------- |
| 项目根目录     | `/home/ubuntu/larry`                            |
| Nginx 配置     | `/etc/nginx/sites-available/laravel`            |
| Nginx 启用     | `/etc/nginx/sites-enabled/laravel`              |
| PHP-FPM socket | `/run/php/php8.3-fpm.sock`                      |
| MySQL 配置     | `/etc/mysql/debian.cnf`                         |
| 用户模型       | `/home/ubuntu/larry/app/Models/User.php`        |
| .env           | `/home/ubuntu/larry/.env`                       |
| 缓存配置       | `/home/ubuntu/larry/bootstrap/cache/config.php` |
| 存储目录       | `/home/ubuntu/larry/storage/`                   |
| 日志文件       | `/home/ubuntu/larry/storage/logs/laravel.log`   |
| Nginx 错误日志 | `/var/log/nginx/laravel-error.log`              |
| Nginx 访问日志 | `/var/log/nginx/laravel-access.log`             |
| 上传持久目录   | `/var/www/love-laravel-uploads`                 |
| 备份目录       | `/home/ubuntu/backups/`                         |
| 备份脚本       | `/home/ubuntu/backups/backup.sh`                |

---

## 13. 数据库备份

### 13.1 自动备份

Cron 每天 03:07 执行备份，保留最近 30 天：

```bash
# 备份脚本路径
/home/ubuntu/backups/backup.sh

# Cron 配置
7 3 * * * /home/ubuntu/backups/backup.sh > /dev/null 2>&1
```

### 13.2 手动备份

```bash
# 立即执行一次备份
bash /home/ubuntu/backups/backup.sh

# 查看备份文件
ls -lh /home/ubuntu/backups/*.sql.gz

# 手动备份到指定文件
mysqldump -u root -p'Larry@0103!' love_larry > /tmp/love_larry_backup.sql
```

### 13.3 恢复备份

```bash
gunzip < /home/ubuntu/backups/love_larry_20260702_*.sql.gz | mysql -u root -p'Larry@0103!' love_larry
```

---

## 14. 持久化上传目录

上传的图片存储在项目目录外的独立路径，避免重新部署时丢失：

| 配置项 | 值 |
|--------|-----|
| 环境变量 | `PRIVATE_UPLOADS_ROOT=/var/www/love-laravel-uploads` |
| 物理路径 | `/var/www/love-laravel-uploads` |
| 所有者 | `www-data:www-data` |
| 权限 | `775` |
| 配置来源 | `config/filesystems.php` 中 `private_uploads` disk |

> **跨部署持久化原理：** 项目代码通过 `git clone` 更新，但上传目录指向 `/var/www/love-laravel-uploads`，不在项目目录内，所以重新部署不会影响已上传的文件。

### 不同环境适配

| 环境 | 方案 |
|------|------|
| **VPS/物理机** | 直接用 `/var/www/love-laravel-uploads`，部署脚本自动创建 |
| **Docker** | `docker run -v /host/persistent-dir:/var/www/love-laravel-uploads ...` |
| **共享主机** | 软链：`ln -sfn /home/user/persistent-uploads /path/to/project/storage/app/private/uploads` |

---

## 15. 一键部署脚本

项目自带 `deploy/` 目录，包含自动化部署工具：

| 文件 | 作用 |
|------|------|
| `deploy/deploy.sh` | 一键部署主脚本（11 个步骤全自动） |
| `deploy/.env.production` | 生产环境 .env 模板 |
| `deploy/nginx-laravel.conf` | Nginx 配置模板 |

### 用法

```bash
# 基本部署
sudo bash deploy/deploy.sh

# 指定域名
sudo bash deploy/deploy.sh --url https://love.yourdomain.com
```

### deploy.sh 自动完成的 11 步

1. 环境检查（软件 + PHP 扩展）
2. 从 GitHub 克隆/拉取最新代码
3. 创建 MySQL 数据库
4. 写入 `.env`（从模板替换 URL/密码）
5. `composer install` 安装 PHP 依赖
6. `key:generate` + `npm run build` 前端构建
7. `migrate --force` 运行数据库迁移
8. 创建持久化上传目录 `/var/www/love-laravel-uploads`
9. 写入 Nginx 配置并 reload
10. 设置 `storage/` + `bootstrap/cache/` 权限
11. `config:cache` + `route:cache` + `view:cache` 生产优化