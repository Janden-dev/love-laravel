#!/usr/bin/env bash
#
# Love Laravel —— 一键自动化部署脚本 (LNMP)
# ---------------------------------------------------------------------------
# 用法:
#   sudo bash deploy/deploy.sh                 # 使用默认配置部署
#   sudo bash deploy/deploy.sh --url http://example.com
#   DB_PASS='xxx' sudo -E bash deploy/deploy.sh
#
# 可用参数 / 环境变量:
#   --url <URL>        应用访问地址 (APP_URL)，默认 http://localhost
#   --skip-nginx       跳过 Nginx 配置（仅部署应用）
#   --skip-build       跳过前端 npm 构建
#   --help             显示帮助
#
#   DB_NAME    数据库名   (默认 love_larry)
#   DB_USER    数据库用户 (默认 root)
#   DB_PASS    数据库密码 (默认 Larry@0103!)
#   WEB_USER   Web 运行用户/组 (默认 www-data)
#
# 特性: 幂等、可重复运行；自动定位项目根目录，不依赖固定路径。
# ---------------------------------------------------------------------------
set -euo pipefail

# ====== 路径自动定位 ======
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# ====== 默认配置（可被环境变量覆盖）======
DB_NAME="${DB_NAME:-love_larry}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-Larry@0103!}"
WEB_USER="${WEB_USER:-www-data}"
APP_URL="${APP_URL:-http://localhost}"
PHP_VER="${PHP_VER:-8.3}"
SKIP_NGINX=0
SKIP_BUILD=0

# ====== 参数解析 ======
while [[ $# -gt 0 ]]; do
  case "$1" in
    --url)         APP_URL="$2"; shift 2 ;;
    --skip-nginx)  SKIP_NGINX=1; shift ;;
    --skip-build)  SKIP_BUILD=1; shift ;;
    --help|-h)
      sed -n '2,25p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
      exit 0 ;;
    *) echo "未知参数: $1"; exit 1 ;;
  esac
done

# ====== 输出辅助 ======
c_reset='\033[0m'; c_blue='\033[1;34m'; c_green='\033[1;32m'; c_red='\033[1;31m'; c_yellow='\033[1;33m'
step() { echo -e "\n${c_blue}==> $*${c_reset}"; }
ok()   { echo -e "${c_green}  ✓ $*${c_reset}"; }
warn() { echo -e "${c_yellow}  ! $*${c_reset}"; }
die()  { echo -e "${c_red}  ✗ $*${c_reset}"; exit 1; }

# sudo 封装：以 root 运行时直接执行，否则加 sudo
SUDO=""
[[ "$(id -u)" -ne 0 ]] && SUDO="sudo"

PHP_FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"

echo -e "${c_blue}"
echo "╔════════════════════════════════════════╗"
echo "║   Love Laravel · 一键部署               ║"
echo "╚════════════════════════════════════════╝"
echo -e "${c_reset}"
echo "  项目路径 : $PROJECT_ROOT"
echo "  数据库   : $DB_NAME @ $DB_USER"
echo "  访问地址 : $APP_URL"
echo "  Web 用户 : $WEB_USER"

# ====== 1. 环境检查 ======
step "1/11 环境检查"
for cmd in nginx php mysql composer; do
  command -v "$cmd" >/dev/null 2>&1 || die "$cmd 未安装，请先安装依赖 (见 DEPLOY.md 1.2)"
done
[[ $SKIP_BUILD -eq 0 ]] && { command -v npm >/dev/null 2>&1 || die "npm 未安装（或使用 --skip-build）"; }

PHP_ACTUAL="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
ok "PHP $PHP_ACTUAL / $(nginx -v 2>&1) / composer $(composer --version 2>/dev/null | awk '{print $3}')"

REQUIRED_EXT=(bcmath ctype curl fileinfo gd intl json mbstring openssl PDO pdo_mysql tokenizer xml zip)
MISSING=()
for ext in "${REQUIRED_EXT[@]}"; do
  php -m | grep -qi "^${ext}$" || MISSING+=("$ext")
done
[[ ${#MISSING[@]} -gt 0 ]] && die "缺少 PHP 扩展: ${MISSING[*]}"
ok "PHP 扩展齐全 (${#REQUIRED_EXT[@]} 项)"

# ====== 2. 创建数据库 ======
step "2/11 创建数据库"
if mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" >/dev/null 2>&1; then
  mysql -u "$DB_USER" -p"$DB_PASS" \
    -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ok "数据库 $DB_NAME 就绪"
else
  die "MySQL 连接失败，请检查 DB_USER / DB_PASS，或先设置 root 密码 (见 DEPLOY.md 3.1)"
fi

# ====== 3. 配置 .env ======
step "3/11 配置 .env"
if [[ ! -f .env ]]; then
  if [[ -f deploy/.env.production ]]; then
    cp deploy/.env.production .env
    # 注入数据库密码占位符
    sed -i "s|__DB_PASSWORD__|${DB_PASS}|g" .env
    ok "从 deploy/.env.production 生成 .env"
  else
    cp .env.example .env
    warn "使用 .env.example 生成 .env"
  fi
else
  warn ".env 已存在，保留现有配置"
fi
# 确保关键项正确（幂等）
set_env() { local k="$1" v="$2"; if grep -q "^${k}=" .env; then sed -i "s|^${k}=.*|${k}=${v}|" .env; else echo "${k}=${v}" >> .env; fi; }
set_env APP_ENV production
set_env APP_DEBUG false
set_env DB_CONNECTION mysql
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "\"$DB_PASS\""
set_env SESSION_DRIVER database
set_env APP_URL "$APP_URL"
ok ".env 配置完成"

# ====== 4. Composer 依赖 ======
step "4/11 安装 Composer 依赖"
composer install --no-dev --optimize-autoloader --no-interaction
ok "Composer 依赖安装完成"

# ====== 5. 生成 APP_KEY ======
step "5/11 生成 APP_KEY"
if grep -q "^APP_KEY=base64:" .env; then
  ok "APP_KEY 已存在，跳过"
else
  php artisan key:generate --force
  ok "APP_KEY 已生成"
fi

# ====== 6. 数据库迁移 + 种子 ======
step "6/11 数据库迁移"
php artisan migrate --force
ok "迁移完成"
php artisan db:seed --class=UserSeeder --force
ok "默认用户已创建 (janden / 20030103, larry / 20030415)"

# ====== 7. 构建前端 ======
step "7/11 构建前端资源"
if [[ $SKIP_BUILD -eq 1 ]]; then
  warn "已跳过前端构建 (--skip-build)"
else
  npm install --no-audit --no-fund
  npm run build
  [[ -f public/build/manifest.json ]] || die "构建失败: 未找到 public/build/manifest.json"
  ok "前端资源构建完成"
fi

# ====== 8. 配置 Nginx ======
step "8/11 配置 Nginx"
if [[ $SKIP_NGINX -eq 1 ]]; then
  warn "已跳过 Nginx 配置 (--skip-nginx)"
else
  TMP_CONF="$(mktemp)"
  sed -e "s|__PROJECT_ROOT__|${PROJECT_ROOT}/public|g" \
      -e "s|__PHP_FPM_SOCK__|${PHP_FPM_SOCK}|g" \
      deploy/nginx-laravel.conf > "$TMP_CONF"
  $SUDO cp "$TMP_CONF" /etc/nginx/sites-available/laravel
  rm -f "$TMP_CONF"
  $SUDO unlink /etc/nginx/sites-enabled/default 2>/dev/null || true
  $SUDO ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel
  $SUDO nginx -t
  $SUDO systemctl reload nginx
  ok "Nginx 已配置并重载"
fi

# ====== 9. 配置持久化上传目录 ======
step "9/11 配置持久化上传目录"
UPLOADS_DIR="/var/www/love-laravel-uploads"
$SUDO mkdir -p "$UPLOADS_DIR"
$SUDO chown "${WEB_USER}:${WEB_USER}" "$UPLOADS_DIR"
$SUDO chmod 775 "$UPLOADS_DIR"
ok "上传目录已就绪: $UPLOADS_DIR (不会被重新部署覆盖)"

# ====== 10. 设置文件权限 ======
step "10/11 设置文件权限"
# www-data 需要能遍历项目所在目录链
PARENT="$PROJECT_ROOT"
while [[ "$PARENT" != "/" && "$PARENT" != "$HOME" ]]; do
  $SUDO chmod o+x "$PARENT" 2>/dev/null || true
  PARENT="$(dirname "$PARENT")"
done
$SUDO chmod o+x "$HOME" 2>/dev/null || true
$SUDO chown -R "${WEB_USER}:${WEB_USER}" "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"
$SUDO chmod -R 775 "$PROJECT_ROOT/storage" "$PROJECT_ROOT/bootstrap/cache"
ok "storage / bootstrap/cache 权限已设置为 $WEB_USER"

# ====== 11. 生产优化（以 www-data 组身份执行）======
step "11/11 Laravel 生产优化"
$SUDO touch "$PROJECT_ROOT/storage/logs/laravel.log"
$SUDO chown "${WEB_USER}:${WEB_USER}" "$PROJECT_ROOT/storage/logs/laravel.log"
$SUDO chmod 664 "$PROJECT_ROOT/storage/logs/laravel.log"
# 先清缓存再重建，避免旧的 config:cache 残留（见 DEPLOY.md 问题 2）
$SUDO -u "$WEB_USER" bash -c "cd '$PROJECT_ROOT' && php artisan config:clear" || php artisan config:clear
if $SUDO -u "$WEB_USER" bash -c "cd '$PROJECT_ROOT' && php artisan config:cache && php artisan route:cache && php artisan view:cache" 2>/dev/null; then
  ok "配置/路由/视图缓存已生成"
else
  warn "以 $WEB_USER 身份缓存失败，改用当前用户执行"
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  ok "配置/路由/视图缓存已生成"
fi

# ====== 12. 验证 ======
step "12/11 验证部署"
if [[ $SKIP_NGINX -eq 0 ]]; then
  HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/" || echo 000)"
  if [[ "$HTTP_CODE" == "200" ]]; then
    ok "首页返回 HTTP 200"
  else
    warn "首页返回 HTTP $HTTP_CODE（请检查 Nginx / PHP-FPM 日志）"
  fi
fi
USER_COUNT="$(mysql -u "$DB_USER" -p"$DB_PASS" -N -e "USE $DB_NAME; SELECT COUNT(*) FROM users;" 2>/dev/null || echo '?')"
ok "数据库 users 表记录数: $USER_COUNT"

echo -e "${c_green}"
echo "════════════════════════════════════════"
echo "  🎉 部署完成！"
echo "  地址 : $APP_URL"
echo "  登录 : janden / larry2026"
echo "════════════════════════════════════════"
echo -e "${c_reset}"
