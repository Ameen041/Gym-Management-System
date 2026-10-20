#!/usr/bin/env bash
# Himma - first setup on a fresh Ubuntu server (24.04 or 22.04):
#
#   git clone https://github.com/Ameen041/Gym-Management-System.git /opt/himma
#   bash /opt/himma/deploy/setup.sh
#
# It installs Docker, opens the firewall for SSH and the web, asks for the domain, the
# admin account and (optionally) the email service, then builds and starts Himma.
# Running it again keeps the settings it already saved (deploy/.env) and just starts Himma.
# Without a terminal (automation), give the answers as variables:
#   DOMAIN=example.com ADMIN_EMAIL=me@example.com ADMIN_PASSWORD=... bash setup.sh
set -euo pipefail

DIR="$(cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")" && pwd)"
ENV_FILE="$DIR/.env"

say()  { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mXX  %s\033[0m\n' "$*" >&2; exit 1; }
compose() { docker compose --project-directory "$DIR" -f "$DIR/compose.yml" "$@"; }
has_tty() { { : </dev/tty; } 2>/dev/null; }

# Ask on the terminal unless the answer was given as a variable
ask() {
  local var="$1" prompt="$2" default="${3:-}" secret="${4:-}" reply=""
  if [ -n "${!var:-}" ]; then return; fi
  has_tty || die "No terminal to ask \"$prompt\": give $var=... before the command."
  if [ -n "$secret" ]; then
    read -r -s -p "$prompt: " reply </dev/tty; echo >/dev/tty
  else
    read -r -p "$prompt${default:+ [$default]}: " reply </dev/tty
  fi
  printf -v "$var" '%s' "${reply:-$default}"
}

[ "$(id -u)" -eq 0 ] || die "Run it as root: sudo bash $0"
# shellcheck source=/dev/null
. /etc/os-release
[ "${ID:-}" = ubuntu ] || warn "This was made for Ubuntu; this server runs ${PRETTY_NAME:-something else}."

# ---- 1) Docker -------------------------------------------------------------
if ! command -v docker >/dev/null || ! docker compose version >/dev/null 2>&1; then
  say "Installing Docker"
  apt-get update -q
  apt-get install -y -q ca-certificates curl
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -q
  apt-get install -y -q docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  systemctl enable --now docker
fi

# ---- 2) Firewall: only SSH and the web are open -----------------------------
if command -v ufw >/dev/null; then
  ssh_port="$( (ss -tlnpH 2>/dev/null || true) | awk '/"sshd"/ { n = split($4, a, ":"); print a[n]; exit }')"
  ufw allow "${ssh_port:-22}/tcp" >/dev/null
  ufw allow 80/tcp >/dev/null
  ufw allow 443 >/dev/null
  ufw --force enable >/dev/null
  say "Firewall on: SSH (port ${ssh_port:-22}), 80 and 443"
fi

# Small servers get some swap, so building the app doesn't run out of memory
if [ "$(awk '/MemTotal/ { print int($2 / 1024) }' /proc/meminfo)" -lt 3000 ] && [ -z "$(swapon --noheadings)" ]; then
  say "Adding 2 GB of swap"
  if fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile; then
    grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  else
    warn "Could not add swap; carrying on."
  fi
fi

# ---- 3) Settings (asked once, kept in deploy/.env) --------------------------
if [ ! -f "$ENV_FILE" ]; then
  say "A few questions (Enter keeps the value in brackets)"

  ask DOMAIN "Domain of the site, without www (e.g. himma.fit)"
  DOMAIN="${DOMAIN#http://}"; DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN#www.}"; DOMAIN="${DOMAIN%%/*}"
  DOMAIN="$(printf '%s' "$DOMAIN" | tr '[:upper:]' '[:lower:]')"
  [[ "$DOMAIN" =~ ^[a-z0-9.-]+\.[a-z]{2,}$|^localhost$ ]] || die "\"$DOMAIN\" doesn't look like a domain."

  ask ADMIN_EMAIL "Admin email (you log in with it; certificate notices go there too)"
  [[ "$ADMIN_EMAIL" == *@*.* ]] || die "\"$ADMIN_EMAIL\" doesn't look like an email."

  if [ -z "${ADMIN_PASSWORD:-}" ]; then
    while :; do
      ask ADMIN_PASSWORD "Admin password (10+ characters, hidden while you type)" "" secret
      confirm=""; ask confirm "The same password again" "" secret
      if [ "${#ADMIN_PASSWORD}" -lt 10 ]; then warn "At least 10 characters."; ADMIN_PASSWORD=""
      elif [ "$ADMIN_PASSWORD" != "$confirm" ]; then warn "The two passwords are different."; ADMIN_PASSWORD=""
      else break; fi
    done
  fi
  [ "${#ADMIN_PASSWORD}" -ge 10 ] || die "The admin password needs at least 10 characters."

  echo
  echo "Email (password reset links). Brevo's free plan works: Brevo -> SMTP & API -> SMTP."
  echo "Press Enter to skip; you can add it later in $ENV_FILE and run: himma restart"
  if has_tty; then ask MAIL_USERNAME "SMTP login"; fi
  MAIL_USERNAME="${MAIL_USERNAME:-}"
  if [ -n "$MAIL_USERNAME" ]; then
    ask MAIL_HOST "SMTP server" "smtp-relay.brevo.com"
    ask MAIL_PORT "SMTP port" "587"
    ask MAIL_PASSWORD "SMTP key / password (hidden)" "" secret
    ask MAIL_FROM_ADDRESS "Send emails from (an address you confirmed in Brevo)" "$ADMIN_EMAIL"
  fi

  for value in "$ADMIN_PASSWORD" "${MAIL_PASSWORD:-}" "${MAIL_USERNAME:-}"; do
    [[ "$value" != *"'"* ]] || die "Passwords and logins can't contain the ' character here; choose another."
  done

  umask 077
  cat > "$ENV_FILE" <<EOF
# Himma server settings, written by setup.sh. Keep this file private.
# After changing it, run: himma restart
DOMAIN=$DOMAIN
ADMIN_EMAIL='$ADMIN_EMAIL'
# used once, to create the admin account on the first start
ADMIN_PASSWORD='$ADMIN_PASSWORD'
APP_KEY=base64:$(openssl rand -base64 32)
DB_PASSWORD=$(openssl rand -hex 24)
DB_ROOT_PASSWORD=$(openssl rand -hex 24)

MAIL_MAILER=$([ -n "${MAIL_USERNAME:-}" ] && echo smtp || echo log)
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME='${MAIL_USERNAME:-}'
MAIL_PASSWORD='${MAIL_PASSWORD:-}'
MAIL_FROM_ADDRESS='${MAIL_FROM_ADDRESS:-$ADMIN_EMAIL}'
EOF
  umask 022
  say "Settings saved in $ENV_FILE"
else
  say "Using the settings in $ENV_FILE"
fi

DOMAIN="$(sed -n 's/^DOMAIN=//p' "$ENV_FILE")"
ADMIN_EMAIL="$(sed -n "s/^ADMIN_EMAIL='\{0,1\}\([^']*\)'\{0,1\}$/\1/p" "$ENV_FILE")"

# ---- 4) Does the domain point here? -----------------------------------------
if [ "$DOMAIN" != localhost ]; then
  server_ip="$(curl -fsS4 --max-time 8 https://api.ipify.org 2>/dev/null || true)"
  domain_ip="$(getent ahostsv4 "$DOMAIN" 2>/dev/null | awk 'NR == 1 { print $1 }')"
  if [ -n "$server_ip" ] && [ "$server_ip" != "$domain_ip" ]; then
    warn "$DOMAIN points to ${domain_ip:-nothing yet}, but this server is $server_ip."
    warn "Add an A record for $DOMAIN (and www) with $server_ip at your domain company."
    warn "HTTPS starts by itself a few minutes after the record is right (with Cloudflare's"
    warn "orange cloud this check can't see the server, which is fine)."
  fi
fi

# ---- 5) Build and start ------------------------------------------------------
ln -sf "$DIR/himma" /usr/local/bin/himma
chmod +x "$DIR/himma"

say "Building and starting Himma (the first time takes a few minutes)"
compose up -d --build

say "Waiting for Himma to answer"
for _ in $(seq 1 60); do
  status="$(docker inspect --format '{{.State.Health.Status}}' "$(compose ps -q app)" 2>/dev/null || true)"
  [ "$status" = healthy ] && break
  sleep 5
done
if [ "${status:-}" != healthy ]; then
  compose logs --tail=40 app
  die "Himma did not start. The lines above say why; send them to whoever helps you."
fi

say "Himma is running"
cat <<EOF

  Site:   https://$DOMAIN
  Admin:  $ADMIN_EMAIL (with the password you chose)

  Open Admin -> System: every line should be green.

  Everyday commands:
    himma update    get the newest version from GitHub and restart
    himma status    is everything running?
    himma logs      what the app is writing (Ctrl+C to stop)
    himma backup    make a backup now and copy it to /root/himma-backups
    himma restart   restart after changing $ENV_FILE

EOF
