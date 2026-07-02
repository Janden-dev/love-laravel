#!/bin/bash
set -e

# ===============================================
# love-laravel 一键部署脚本
# 用法: sudo bash deploy/deploy.sh [--url https://domain.com]
# ===============================================

# ---------- 配置 ----------
REPO_URL="https://github.com/Janden-dev/love-laravel.git"
DEPLOY_PATH="/home/ubuntu/larry"
DB_NAME="love_larry"
DB_USER="root"
DB_PASS='Larry@0103!'
MYSQL_ROOT_PASS='Larry@0103!'
UPLOADS_DIR="/var/www/love-laravel-uploads"
BACKUP_DIR="/home/ubuntu/backups"
ADMIN_USER="ubuntu"
NGINX_CONF_DIR="/etc/nginx/sites-available"
PHP_FPM_SOCK="/run/php/php8.3-fpm.sock"

# 参数
APP_URL="${2:-http://localhost}"

echo "================================================"
echo "  love-laravel 部署脚本"
echo "  目标: ${DEPLOY_PATH}"
echo "  数据库: ${DB_NAME}"
echo "  URL: ${APP_URL}"
echo "================================================"
echo ""

# ===========================================
# 1. 环境检查
# ===========================================
echo ">>> [1/11] 环境检查..."

check_cmd() {
    if ! command -v "$1" &>/dev/null; then
        echo "  !! $1 未安装"; return 1
    else
        echo "  OK $1 $(command -v "$1")"
    fi
}

check_cmd nginx
check_cmd php
check_cmd mysql
check_cmd composer
check_cmd node

PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "  PHP 版本: $PHP_VER"

# PHP 扩展检查
REQUIRED_EXT=("bcmath" "ctype" "curl" "fileinfo" "gd" "intl" "json" "mbstring"
              "openssl" "PDO" "pdo_mysql" "tokenizer" "xml" "zip")
MISSING_EXT=()
for ext in "${REQUIRED_EXT[@]}"; do
    php -m | grep -qi "$ext" || MISSING_EXT+=("$ext")
done
if [ ${#MISSING_EXT[@]} -gt 0 ]; then
    echo "  缺少 PHP 扩展: ${MISSING_EXT[*]}"
    exit 1
fi
echo "  OK 全部 PHP 扩展就绪"

# 服务状态
for svc in nginx mysql php8.3-fpm; do
    if systemctl is-active --quiet "$svc"; then
        echo "  OK $svc 运行中"
    else
        echo "  ?? $svc 未运行，尝试启动..."
        sudo systemctl start "$svc"
    fi
done

echo ""

# ===========================================
# 2. 克隆项目
# ===========================================
echo ">>> [2/11] 克隆项目..."

if [ -d "${DEPLOY_PATH}/.git" ]; then
    echo "  项目已存在，拉取更新..."
    cd "${DEPLOY_PATH}"
    git pull origin main 2>&1 | tail -3
else
    rm -rf "${DEPLOY_PATH}"
    git clone "${REPO_URL}" "${DEPLOY_PATH}"
    cd "${DEPLOY_PATH}"
fi
echo "  OK 代码就绪（$(git log --oneline -1)）"
echo ""

# ===========================================
# 3. MySQL 设置
# ===========================================
echo ">>> [3/11] 配置 MySQL..."

if mysql -u root -p"${MYSQL_ROOT_PASS}" -e "SELECT 1" &>/dev/null; then
    echo "  OK MySQL root 密码正确"
else
    echo "  重置 MySQL root 密码..."
    DEBIAN_PASS=$(sudo cat /etc/mysql/debian.cnf | grep "^password" | head -1 | awk '{print $3}')
    sudo mysql -u debian-sys-maint -p"${DEBIAN_PASS}" \
      -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}'; FLUSH PRIVILEGES;"
    echo "  OK MySQL root 密码已设置"
fi

mysql -u root -p"${MYSQL_ROOT_PASS}" \
  -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
echo "  OK 数据库 ${DB_NAME} 就绪"
echo ""

# ===========================================
# 4. 配置 .env
# ===========================================
echo ">>> [4/11] 配置 .env..."

cp -f "${DEPLOY_PATH}/deploy/.env.production" "${DEPLOY_PATH}/.env"
sed -i "s|{{APP_URL}}|${APP_URL}|g" "${DEPLOY_PATH}/.env"
sed -i "s|{{DB_PASS}}|${DB_PASS}|g" "${DEPLOY_PATH}/.env"
echo "  OK .env 已配置"
echo ""

# ===========================================
# 5. Composer 依赖
# ===========================================
echo ">>> [5/11] 安装 Composer 依赖..."
cd "${DEPLOY_PATH}"
composer install --no-dev --optimize-autoloader 2>&1 | tail -3
echo "  OK Composer 就绪"
echo ""

# ===========================================
# 6. APP_KEY + 前端构建
# ===========================================
echo ">>> [6/11] 生成 APP_KEY..."
php artisan key:generate --force 2>&1 | tail -1

echo ">>> 构建前端..."
npm install --silent 2>&1 | tail -1
npm run build 2>&1 | tail -2
echo "  OK APP_KEY + 前端就绪"
echo ""

# ===========================================
# 7. 数据库迁移
# ===========================================
echo ">>> [7/11] 数据库迁移..."
php artisan migrate --force 2>&1 | tail -3
echo "  OK 迁移完成"
echo ""

# ===========================================
# 8. 持久化上传目录
# ===========================================
echo ">>> [8/11] 设置持久化上传目录..."
sudo mkdir -p "${UPLOADS_DIR}"
sudo chown www-data:www-data "${UPLOADS_DIR}"
sudo chmod 775 "${UPLOADS_DIR}"
echo "  OK 上传目录: ${UPLOADS_DIR}"
echo ""

# ===========================================
# 9. Nginx 配置
# ===========================================
echo ">>> [9/11] 配置 Nginx..."
sudo cp -f "${DEPLOY_PATH}/deploy/nginx-laravel.conf" "${NGINX_CONF_DIR}/laravel"
sudo unlink /etc/nginx/sites-enabled/default 2>/dev/null || true
sudo ln -sf "${NGINX_CONF_DIR}/laravel" /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
echo "  OK Nginx 就绪"
echo ""

# ===========================================
# 10. 权限
# ===========================================
echo ">>> [10/11] 设置文件权限..."
sudo chmod o+x /home/ubuntu
sudo chown -R www-data:www-data "${DEPLOY_PATH}/storage" "${DEPLOY_PATH}/bootstrap/cache"
sudo chmod -R 775 "${DEPLOY_PATH}/storage" "${DEPLOY_PATH}/bootstrap/cache"
sudo usermod -a -G www-data "${ADMIN_USER}" 2>/dev/null || true
echo "  OK 权限就绪"
echo ""

# ===========================================
# 11. 生产优化
# ===========================================
echo ">>> [11/11] 生产优化..."
sudo mkdir -p "${DEPLOY_PATH}/storage/logs"
sudo touch "${DEPLOY_PATH}/storage/logs/laravel.log"
sudo chmod 666 "${DEPLOY_PATH}/storage/logs/laravel.log"

sg www-data -c "
  cd ${DEPLOY_PATH}
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
" 2>&1 | tail -3
echo "  OK 生产优化完成"
echo ""

# ===========================================
# 完成
# ===========================================
echo "================================================"
echo "  部署完成！"
echo "  URL:        ${APP_URL}"
echo "  项目路径:   ${DEPLOY_PATH}"
echo "  上传目录:   ${UPLOADS_DIR}"
echo "  数据库:     ${DB_NAME}"
echo "  登录:       janden / 20030103"
echo "              larry  / 20030415"
echo "  备份目录:   ${BACKUP_DIR}"
echo "================================================"
