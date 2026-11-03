#!/usr/bin/env bash
# Himma on shared hosting (Hostinger hPanel and similar), over SSH, in your home folder:
#
#   git clone https://github.com/Ameen041/Gym-Management-System.git ~/himma
#   bash ~/himma/deploy/shared/install.sh
#
# Before: in hPanel choose PHP 8.3+, create a MySQL database, and (optionally) an email address.
# It asks for those once, keeps them in ~/himma/.env, installs everything, points the domain's
# public_html at the app and adds the every-minute task. Running it again updates (himma update).
# Without a terminal, give the answers as variables (DOMAIN=... DB_DATABASE=... and so on).
set -euo pipefail
# shellcheck source=deploy/shared/lib.sh
. "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")/lib.sh"

ENV_FILE="$APP/.env"
# Hostinger's shell has no /dev/fd and maybe no /dev/tty: questions use the terminal itself then
tty_ok() { { : </dev/tty; } 2>/dev/null; }
has_tty() { tty_ok || [ -t 0 ]; }

# Ask on the terminal unless the answer was given as a variable
ask() {
  local var="$1" prompt="$2" default="${3:-}" secret="${4:-}" reply=""
  local opts=(-p "$prompt${default:+ [$default]}: ")
  if [ -n "${!var:-}" ]; then return; fi
  has_tty || die "No terminal to ask \"$prompt\": give $var=... before the command."
  [ -n "$secret" ] && opts=(-s -p "$prompt: ")
  if tty_ok; then read -r "${opts[@]}" reply </dev/tty; else read -r "${opts[@]}" reply; fi
  if [ -n "$secret" ]; then echo >&2; fi
  printf -v "$var" '%s' "${reply:-$default}"
}

# the PHP code reads the details from variables, so no password ends up in a command line
# shellcheck disable=SC2016
db_ok() {
  DBH="$DB_HOST" DBP="$DB_PORT" DBN="$DB_DATABASE" DBU="$DB_USERNAME" DBW="$DB_PASSWORD" "$PHP" -r '
    try { new PDO("mysql:host=" . getenv("DBH") . ";port=" . getenv("DBP") . ";dbname=" . getenv("DBN"), getenv("DBU"), getenv("DBW")); }
    catch (Throwable $e) { fwrite(STDERR, "   " . $e->getMessage() . PHP_EOL); exit(1); }'
}

say "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

# ---- 1) Settings (asked once, kept in ~/himma/.env) ---------------------------
if [ ! -f "$ENV_FILE" ]; then
  say "A few questions (Enter keeps the value in brackets)"

  domains=()
  for d in "$HOME"/domains/*/; do
    if [ -d "$d" ]; then domains+=("$(basename "$d")"); fi
  done
  ask DOMAIN "Domain of the site, without www" "$([ "${#domains[@]}" -eq 1 ] && echo "${domains[0]}")"
  DOMAIN="${DOMAIN#http://}"; DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN#www.}"; DOMAIN="${DOMAIN%%/*}"
  DOMAIN="$(printf '%s' "$DOMAIN" | tr '[:upper:]' '[:lower:]')"
  [[ "$DOMAIN" =~ ^[a-z0-9.-]+\.[a-z]{2,}$ ]] || die "\"$DOMAIN\" doesn't look like a domain."

  echo
  echo "The database you created in hPanel -> Databases (names start with ${USER:-your user}_):"
  DB_HOST="${DB_HOST:-localhost}"
  DB_PORT="${DB_PORT:-3306}"
  for try in 1 2 3; do
    ask DB_DATABASE "Database name"
    ask DB_USERNAME "Database user"
    ask DB_PASSWORD "Database password (hidden)" "" secret
    if db_ok; then break; fi
    [ "$try" -eq 3 ] && die "Could not open the database with these details."
    warn "Could not open the database with these details; try again."
    DB_DATABASE=""; DB_USERNAME=""; DB_PASSWORD=""
  done

  echo
  ask ADMIN_EMAIL "Admin email (you log in with it)"
  [[ "$ADMIN_EMAIL" == *@*.* ]] || die "\"$ADMIN_EMAIL\" doesn't look like an email."
  if [ -z "${ADMIN_PASSWORD:-}" ]; then
    while :; do
      ask ADMIN_PASSWORD "Admin password (10+ characters, hidden)" "" secret
      confirm=""; ask confirm "The same password again" "" secret
      if [ "${#ADMIN_PASSWORD}" -lt 10 ]; then warn "At least 10 characters."; ADMIN_PASSWORD=""
      elif [ "$ADMIN_PASSWORD" != "$confirm" ]; then warn "The two passwords are different."; ADMIN_PASSWORD=""
      else break; fi
    done
  fi
  [ "${#ADMIN_PASSWORD}" -ge 10 ] || die "The admin password needs at least 10 characters."

  echo
  echo "Email for password reset links: an address you made in hPanel -> Emails (e.g. noreply@$DOMAIN)."
  echo "Press Enter to skip; you can add it later in $ENV_FILE."
  if has_tty; then ask MAIL_USERNAME "Email address"; fi
  MAIL_USERNAME="${MAIL_USERNAME:-}"
  if [ -n "$MAIL_USERNAME" ]; then
    ask MAIL_PASSWORD "Its password (hidden)" "" secret
  fi

  for value in "$ADMIN_PASSWORD" "$DB_PASSWORD" "${MAIL_PASSWORD:-}" "$DB_DATABASE" "$DB_USERNAME" "$MAIL_USERNAME"; do
    [[ "$value" != *"'"* ]] || die "Passwords and names can't contain the ' character here; choose another."
  done

  umask 077
  cat > "$ENV_FILE" <<EOF
# Himma settings, written by deploy/shared/install.sh. Keep this file private.
# After changing it, run: himma update
APP_NAME=Himma
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://$DOMAIN
# visitors reach the web server directly here, so no proxy is trusted
TRUSTED_PROXIES=

ADMIN_EMAIL='$ADMIN_EMAIL'
# used once, to create the admin account
ADMIN_PASSWORD='$ADMIN_PASSWORD'

DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE='$DB_DATABASE'
DB_USERNAME='$DB_USERNAME'
DB_PASSWORD='$DB_PASSWORD'

MAIL_MAILER=$([ -n "$MAIL_USERNAME" ] && echo smtp || echo log)
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME='$MAIL_USERNAME'
MAIL_PASSWORD='${MAIL_PASSWORD:-}'
MAIL_FROM_ADDRESS='${MAIL_USERNAME:-$ADMIN_EMAIL}'
MAIL_FROM_NAME=Himma

LOG_CHANNEL=stack
LOG_LEVEL=warning
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
EOF
  umask 022
  say "Settings saved in $ENV_FILE"
else
  say "Using the settings in $ENV_FILE"
fi
DOMAIN="$(sed -n 's|^APP_URL=https://||p' "$ENV_FILE")"

# ---- 2) PHP packages ----------------------------------------------------------
composer_phar() {
  local c
  c="$(command -v composer 2>/dev/null || true)"
  if [ -n "$c" ] && head -1 "$c" 2>/dev/null | grep -q php; then echo "$c"; return; fi
  mkdir -p "$TOOLS"
  if [ ! -s "$TOOLS/composer.phar" ]; then
    curl -fsSL -o "$TOOLS/composer.phar" https://getcomposer.org/download/latest-stable/composer.phar \
      || "$PHP" -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', '$TOOLS/composer.phar') || exit(1);" \
      || die "Could not download Composer."
  fi
  echo "$TOOLS/composer.phar"
}
cd "$APP"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# On an update, visitors see a short "back in a minute" page until it is done
was_up=1
[ -f storage/framework/down ] && was_up=0
if [ -f vendor/autoload.php ]; then artisan down --retry=30 >/dev/null 2>&1 || true; fi
trap 'if [ "$was_up" -eq 1 ]; then artisan up >/dev/null 2>&1 || true; fi' EXIT

say "Installing PHP packages (the first time takes a few minutes)"
# Composer starts its scripts with proc_open, which shared hosting turns off: run them here instead
"$PHP" -d memory_limit=-1 "$(composer_phar)" install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
artisan package:discover

# ---- 3) App: key, database, files, caches --------------------------------------
grep -q '^APP_KEY=base64:' "$ENV_FILE" || artisan key:generate --force

say "Database"
artisan migrate --force
artisan db:seed --force

# photos people upload are served from storage/app/public
[ -L public/storage ] || ln -s ../storage/app/public public/storage
artisan optimize:clear >/dev/null
artisan config:cache
artisan route:cache
artisan view:cache

# ---- 4) The domain shows the app ------------------------------------------------
web="$HOME/domains/$DOMAIN/public_html"
if [ "$(readlink -f "$web" 2>/dev/null)" != "$APP/public" ]; then
  if [ -e "$web" ] && [ ! -L "$web" ]; then
    keep="$web.before-himma"
    [ -e "$keep" ] && keep="$keep-$(date +%Y%m%d%H%M%S)"
    mv "$web" "$keep"
    say "The old public_html is kept as $keep"
  fi
  mkdir -p "$(dirname "$web")"
  ln -sfn "$APP/public" "$web"
fi
say "https://$DOMAIN shows $APP/public"

# ---- 5) The every-minute task (reminders, nightly backup) -------------------------
cron="* * * * * $PHP $APP/artisan schedule:run >/dev/null 2>&1"
cron_added=0
if command -v crontab >/dev/null 2>&1; then
  if { crontab -l 2>/dev/null | grep -vF "$APP/artisan schedule:run"; echo "$cron"; } | crontab - 2>/dev/null; then
    cron_added=1
  fi
fi

# ---- 6) The himma command --------------------------------------------------------
chmod +x "$APP/deploy/shared/himma"
grep -qF 'alias himma=' "$HOME/.bashrc" 2>/dev/null || echo "alias himma='bash $APP/deploy/shared/himma'" >> "$HOME/.bashrc"

say "Himma is installed"
cat <<EOF

  Site:   https://$DOMAIN
  Admin:  $(env_value ADMIN_EMAIL)

EOF
if [ "$cron_added" -eq 1 ]; then
  echo "  The every-minute task is set (crontab)."
else
  cat <<EOF
  One more step in hPanel: Advanced -> Cron Jobs -> Custom, every minute, with this command:

    $PHP $APP/artisan schedule:run

EOF
fi
cat <<EOF

  In hPanel -> Security -> SSL, make sure the certificate is on (http:// goes to https:// by itself;
  leave hPanel's "Force HTTPS" off, it can change files the updates bring).
  Then open Admin -> System: every line should be green.

  Everyday (open a new SSH session first, or run: source ~/.bashrc):
    himma update    newest version from GitHub (data is kept)
    himma backup    make a backup now and show how to download it
    himma logs      the latest errors, if any
    himma status    is the site answering, are the tasks running

EOF
