# shellcheck shell=bash disable=SC2034
# Shared by install.sh and himma (shared hosting). Sourced, not run.

APP="$(cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")/../.." && pwd)"
TOOLS="$HOME/.himma"

say()  { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mXX  %s\033[0m\n' "$*" >&2; exit 1; }

# The site uses the PHP version chosen in hPanel, but the terminal's "php" can be older:
# take the first PHP 8.3+ (Hostinger keeps each version under /opt/alt).
find_php() {
  local p
  for p in "${PHP:-}" php /opt/alt/php85/usr/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php \
           /usr/bin/php8.5 /usr/bin/php8.4 /usr/bin/php8.3; do
    if [ -z "$p" ] || ! command -v "$p" >/dev/null 2>&1; then continue; fi
    if "$p" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' 2>/dev/null; then
      command -v "$p"
      return 0
    fi
  done
  return 1
}

PHP="$(find_php)" || die "PHP 8.3 or newer was not found. In hPanel: Advanced -> PHP Configuration -> 8.3, then run this again."

artisan() { "$PHP" "$APP/artisan" "$@"; }
env_value() { sed -n "s/^$1='\{0,1\}\([^']*\)'\{0,1\}$/\1/p" "$APP/.env" | head -1; }
