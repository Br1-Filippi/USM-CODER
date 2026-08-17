# Despliegue de USM-CODER con Docker (Ubuntu 22.04)

Todo el stack (app Laravel + Judge0) corre en un solo `docker-compose`.
La instalación en el servidor físico se reduce a clonar el repo y correr
`setup.sh`.

## Servicios

| Servicio        | Qué es                                              | Puerto |
|-----------------|-----------------------------------------------------|--------|
| `web`           | Nginx: estáticos + proxy a PHP-FPM                  | 80     |
| `app`           | Laravel (PHP 8.2-FPM), corre las migraciones        | —      |
| `queue`         | `php artisan queue:work`                            | —      |
| `judge0-server` | API de Judge0                                       | 2358 (solo localhost) |
| `judge0-workers`| Workers que ejecutan el código (privilegiados)      | —      |
| `judge0-db`     | PostgreSQL de Judge0                                | —      |
| `judge0-redis`  | Redis de Judge0                                     | —      |

La app le habla a Judge0 por la red interna de Docker
(`JUDGE_API_URL=http://judge0-server:2358`); nada de Judge0 queda
expuesto a la red externa. La BD de Laravel es SQLite y vive en el host
(`database/database.sqlite`, montada en el contenedor).

## Instalación en el servidor (o en la VM de prueba)

```bash
git clone <url-del-repo> USM-CODER
cd USM-CODER
sudo ./setup.sh
```

El script:

1. **Cambia el kernel a cgroup v1** si hace falta (Judge0 lo requiere;
   Ubuntu 22.04 trae cgroup v2). En ese caso pide **reiniciar** y hay que
   ejecutar `sudo ./setup.sh` de nuevo tras el reinicio. Esto pasa solo
   la primera vez.
2. Instala Docker + Compose desde el repositorio oficial.
3. Genera `.env` (pide la URL pública) y `docker/judge0/judge0.conf` con
   secretos aleatorios. El token de Judge0 (`AUTHN_TOKEN`) y
   `JUDGE_API_KEY` de Laravel quedan sincronizados automáticamente.
4. Construye las imágenes, genera `APP_KEY`, corre migraciones y levanta
   todo con `docker compose up -d`.

### Verificación

```bash
docker compose ps                      # todo "running"
curl http://127.0.0.1:2358/about       # Judge0 responde con su versión
```

Luego, desde un navegador:

1. Entrar a la app (puerto 80), crear/usar una pregunta **modo Judge0** y
   enviar código → debe calificar contra los unit tests.
2. Abrir una pregunta **modo interactivo (Pyodide)** y probar `input()` →
   verifica que `crossOriginIsolated` sea `true` en la consola del
   navegador.

## Probar primero en una VM (recomendado)

Antes de tocar el servidor físico, ensayar la instalación completa en
una VM Ubuntu Server 22.04 (4 vCPU, 6–8 GB RAM, 25 GB disco). El flujo
es idéntico: clonar → `sudo ./setup.sh` → reiniciar → `sudo ./setup.sh`
→ probar desde el navegador del host con la IP de la VM. Si funciona en
la VM, funciona igual en el fierro.

## Operación

```bash
docker compose logs -f app             # logs de Laravel
docker compose logs -f judge0-workers  # logs de ejecución de código
docker compose restart                 # reiniciar todo
docker compose down                    # detener (los datos persisten)
```

### Actualizar la app

```bash
git pull
docker compose build
docker compose up -d
```

(Las migraciones corren solas al arrancar el contenedor `app`.)

### Respaldos

- **BD de Laravel**: copiar `database/database.sqlite` (idealmente con
  `sqlite3 database/database.sqlite ".backup backup.sqlite"` para una
  copia consistente).
- **`.env` y `docker/judge0/judge0.conf`**: contienen los secretos; no
  están en git, respaldarlos aparte.
- La BD de Judge0 (volumen `judge0-db-data`) solo guarda historial de
  submissions de Judge0; no es crítica.

## Problemas conocidos

| Síntoma | Causa / solución |
|---|---|
| Submissions quedan en "Processing" para siempre | El host sigue en cgroup v2. Verificar con `stat -fc %T /sys/fs/cgroup` (debe decir `tmpfs`, no `cgroup2fs`). Reiniciar tras correr `setup.sh`. |
| El runner interactivo (Pyodide) no carga / `crossOriginIsolated` es `false` | Algo agregó cabeceras COOP/COEP fuera de Laravel. Las cabeceras deben venir **solo** del middleware `CrossOriginIsolation` (COEP `credentialless`). Si hay un proxy delante (Nginx externo, etc.), que **no** agregue `Cross-Origin-Embedder-Policy`. |
| `env()` devuelve null y falla la conexión a Judge0 | Alguien corrió `php artisan config:cache`. Ejecutar `docker compose exec app php artisan config:clear`. Nunca cachear la config en este proyecto. |
| Judge0 responde 401 | `JUDGE_API_KEY` (.env) ≠ `AUTHN_TOKEN` (judge0.conf). Igualarlos y `docker compose up -d`. |
