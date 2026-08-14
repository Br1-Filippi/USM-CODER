#!/usr/bin/env bash
#
# setup.sh — instalación de USM-CODER + Judge0 en Ubuntu 22.04
#
# Uso:  sudo ./setup.sh
#
# Idempotente: se puede ejecutar varias veces. Si hace falta cambiar el
# kernel a cgroup v1 (requisito de Judge0), pide reiniciar y hay que
# volver a ejecutarlo después del reinicio.

set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

log()  { echo -e "\e[1;34m[setup]\e[0m $*"; }
err()  { echo -e "\e[1;31m[setup]\e[0m $*" >&2; }

if [ "$(id -u)" -ne 0 ]; then
    err "Este script necesita root. Ejecutar: sudo ./setup.sh"
    exit 1
fi

# El usuario real (el que hizo sudo) para no dejar archivos de root en el repo
REAL_USER="${SUDO_USER:-root}"

# ------------------------------------------------------------------
# 1. cgroup v1 (requisito de Judge0; Ubuntu 22.04 trae cgroup v2)
# ------------------------------------------------------------------
if [ "$(stat -fc %T /sys/fs/cgroup)" = "cgroup2fs" ]; then
    log "El sistema usa cgroup v2; Judge0 necesita cgroup v1."
    log "Configurando GRUB (systemd.unified_cgroup_hierarchy=0)..."
    mkdir -p /etc/default/grub.d
    cat > /etc/default/grub.d/99-judge0-cgroup-v1.cfg <<'EOF'
# Judge0 (isolate) requiere cgroup v1
GRUB_CMDLINE_LINUX_DEFAULT="$GRUB_CMDLINE_LINUX_DEFAULT systemd.unified_cgroup_hierarchy=0"
EOF
    update-grub
    echo
    err "==> HAY QUE REINICIAR para aplicar cgroup v1."
    err "==> Después del reinicio, ejecutar de nuevo: sudo ./setup.sh"
    exit 0
fi
log "cgroup v1 activo ✔"

# ------------------------------------------------------------------
# 2. Docker + Compose (repositorio oficial de Docker)
# ------------------------------------------------------------------
if ! command -v docker >/dev/null 2>&1; then
    log "Instalando Docker..."
    apt-get update
    apt-get install -y ca-certificates curl gnupg
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io \
        docker-buildx-plugin docker-compose-plugin
    systemctl enable --now docker
    [ "$REAL_USER" != "root" ] && usermod -aG docker "$REAL_USER" || true
fi
log "Docker instalado ✔ ($(docker --version))"

# ------------------------------------------------------------------
# 3. Secretos y archivos de configuración
# ------------------------------------------------------------------
gen_secret() { openssl rand -hex 24; }

# .env de Laravel
if [ ! -f .env ]; then
    log "Creando .env desde .env.example..."
    cp .env.example .env
    JUDGE_KEY="$(gen_secret)"
    sed -i \
        -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e "s|^JUDGE_API_URL=.*|JUDGE_API_URL=http://judge0-server:2358|" \
        -e "s|^JUDGE_API_KEY=.*|JUDGE_API_KEY=${JUDGE_KEY}|" \
        .env
    read -rp "URL pública de la app (ej. http://10.0.0.5 o http://examenes.usm.cl) [http://localhost]: " APP_URL
    APP_URL="${APP_URL:-http://localhost}"
    sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
    chown "$REAL_USER":"$REAL_USER" .env
else
    log ".env ya existe, no se toca."
    JUDGE_KEY="$(grep '^JUDGE_API_KEY=' .env | cut -d= -f2-)"
fi

# judge0.conf (mismo token que JUDGE_API_KEY)
if [ ! -f docker/judge0/judge0.conf ]; then
    log "Creando docker/judge0/judge0.conf con secretos generados..."
    sed \
        -e "s|^REDIS_PASSWORD=__GENERAR__|REDIS_PASSWORD=$(gen_secret)|" \
        -e "s|^POSTGRES_PASSWORD=__GENERAR__|POSTGRES_PASSWORD=$(gen_secret)|" \
        -e "s|^AUTHN_TOKEN=__GENERAR__|AUTHN_TOKEN=${JUDGE_KEY}|" \
        docker/judge0/judge0.conf.example > docker/judge0/judge0.conf
    chown "$REAL_USER":"$REAL_USER" docker/judge0/judge0.conf
    chmod 600 docker/judge0/judge0.conf
else
    log "docker/judge0/judge0.conf ya existe, no se toca."
fi

# BD SQLite en el host (bind mount)
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown "$REAL_USER":"$REAL_USER" database/database.sqlite
fi

# ------------------------------------------------------------------
# 4. Construir y levantar todo
# ------------------------------------------------------------------
log "Construyendo imágenes (la primera vez tarda varios minutos)..."
docker compose build

# APP_KEY (solo si falta)
if grep -q '^APP_KEY=$' .env; then
    log "Generando APP_KEY..."
    # Como root dentro del contenedor: .env es un bind mount del host
    # (dueño: $REAL_USER) y www-data no puede escribirlo.
    docker compose run --rm --no-deps app php artisan key:generate --force
fi

log "Levantando servicios..."
docker compose up -d

echo
log "Listo ✔  Servicios:"
docker compose ps
echo
log "La app queda en: $(grep '^APP_URL=' .env | cut -d= -f2-) (puerto 80)"
log "Prueba Judge0 desde el host:  curl http://127.0.0.1:2358/about"
log "Logs:  docker compose logs -f"
