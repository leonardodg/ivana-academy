# Ivana Academy — Moodle Platform

Docker multi-stage, multi-environment setup for Moodle 4.5.

---

## Stack

| Component | Version |
|-----------|---------|
| Moodle    | 4.5.1+  |
| PHP       | 8.2     |
| MariaDB   | 11.4    |
| Apache    | 2.4     |

---

## File Structure

```
ivana-academy/
└── .devcontainer/
    ├── build/
    │   └── moodle.Dockerfile     # Multi-stage: base → production | development
    ├── compose/
    │   ├── base.yml              # Shared resources (volumes, secrets, networks)
    │   ├── db.yml                # MariaDB service
    │   ├── dev.yml               # Development override (hot-reload, xdebug)
    │   ├── prod.yml              # Production override
    │   ├── build-dev.yml         # Build local target: development (optional)
    │   └── build-prod.yml        # Build local target: production (optional)
    ├── env/
    │   ├── defaults.env          # Shared non-sensitive defaults
    │   ├── db.env                # Database vars
    │   ├── development.env       # Development-specific vars
    │   └── production.env        # Production-specific vars
    ├── secrets/
    │   ├── db_password           # Moodle DB password
    │   ├── db_user               # Moodle DB user
    │   └── db_root_password      # MariaDB root password
    ├── certs/
    │   ├── develop-local.crt     # Self-signed cert (dev only)
    │   └── develop-local.key
    ├── php/
    │   ├── opcache.ini
    │   ├── opcache-dev.ini
    │   └── uploads.ini
    └── readme.md
```

---

## Initial Setup

### 1. Copy env files

```bash
cp .devcontainer/env/defaults.example    .devcontainer/env/defaults.env
cp .devcontainer/env/development.example .devcontainer/env/development.env
cp .devcontainer/env/production.example  .devcontainer/env/production.env
cp ".devcontainer/env/db .example"       .devcontainer/env/db.env
```

### 2. Create secrets

```bash
# Database credentials
echo "moodle_user" > .devcontainer/secrets/db_user
echo "strong_pass" > .devcontainer/secrets/db_password
echo "root_pass"   > .devcontainer/secrets/db_root_password
```

Or generate random:

```bash
head /dev/urandom | tr -dc 'A-Za-z0-9@#$' | head -c 24 > .devcontainer/secrets/db_user
head /dev/urandom | tr -dc 'A-Za-z0-9@#$' | head -c 24 > .devcontainer/secrets/db_password
head /dev/urandom | tr -dc 'A-Za-z0-9@#$' | head -c 24 > .devcontainer/secrets/db_root_password
```

### 3. Create SSL certs (dev only)

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout .devcontainer/certs/develop-local.key \
    -out    .devcontainer/certs/develop-local.crt \
    -subj   "/CN=develop.ivana.academy"
```

---

## Docker Compose

### Development

```bash
# Up
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    up -d

# Down
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    down

# Logs
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    logs -f moodle
```

### Development with local build

```bash
# Build + up
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    -f .devcontainer/compose/build-dev.yml \
    up --build -d

# Down
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    -f .devcontainer/compose/build-dev.yml \
    down
```

### Production

```bash
# Up
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/prod.yml \
    up -d

# Down
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/prod.yml \
    down
```

### Via environment variable (avoids typing every time)

```bash
# Set once per session
export COMPOSE_FILE=.devcontainer/compose/base.yml:.devcontainer/compose/db.yml:.devcontainer/compose/dev.yml

docker compose up -d
docker compose logs -f moodle
docker compose down
```

Permanent (add to `~/.bashrc` or `~/.zshrc`):

```bash
echo 'export COMPOSE_FILE=.devcontainer/compose/base.yml:.devcontainer/compose/db.yml:.devcontainer/compose/dev.yml' >> ~/.bashrc
source ~/.bashrc
```

---

## Docker Build (standalone)

```bash
# Development
docker buildx build --load \
    --target development \
    -f .devcontainer/build/moodle.Dockerfile \
    -t leodg/moodle-academy:development .

# Production
docker buildx build --load \
    --target production \
    --no-cache \
    -f .devcontainer/build/moodle.Dockerfile \
    -t leodg/moodle-academy:production .
```

### Test builds

```bash
# Test dev
docker run -d --rm --name moodle-test leodg/moodle-academy:development

# Test prod (with secrets as bind mounts)
docker run -d --rm \
    -e ENVIRONMENT=production \
    -v $(pwd)/.devcontainer/certs/develop-local.key:/run/secrets/ssl_key:ro \
    -v $(pwd)/.devcontainer/certs/develop-local.crt:/run/secrets/ssl_cert:ro \
    --name moodle-test-prod \
    leodg/moodle-academy:production

# Check PHP extensions
docker run --rm leodg/moodle-academy:development php -m | grep -E 'gd|intl|zip|xdebug'
```

### Push to Docker Hub

```bash
docker push leodg/moodle-academy:development
docker push leodg/moodle-academy:production
docker push leodg/moodle-academy:latest
```

---

## Security Reports

```bash
docker scout cves leodg/moodle-academy:production \
    --only-severity high,critical \
    --format markdown \
    --output relatorio-prod.json

docker scout cves leodg/moodle-academy:development \
    --only-severity high,critical \
    --format markdown \
    --output relatorio-dev.json
```

---

## Image Layers

```bash
docker image history --no-trunc \
    --format "{{.Size}} - {{truncate .CreatedBy 100}}" \
    leodg/moodle-academy:development > layers-dev

docker image history --no-trunc \
    --format "{{.Size}} - {{truncate .CreatedBy 100}}" \
    leodg/moodle-academy:production > layers-prod
```

---

## Validate Compose Merge

```bash
# Check merged config
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    config

# Confirm only ONE moodle service
docker compose \
    -f .devcontainer/compose/base.yml \
    -f .devcontainer/compose/db.yml \
    -f .devcontainer/compose/dev.yml \
    config | grep -E "^  moodle:" | wc -l
# Expected: 1
```

---

## Secrets Reference

### In Dockerfile (build-time)

```dockerfile
RUN --mount=type=secret,id=db_password \
    DB_PASS=$(cat /run/secrets/db_password) && \
    php setup.php --dbpass="$DB_PASS"
```

### In docker run (bind mount)

```bash
docker run \
    -v $(pwd)/secrets/db_password:/run/secrets/db_password:ro \
    -v $(pwd)/secrets/db_user:/run/secrets/db_user:ro \
    leodg/moodle-academy:production
```

### In GitHub Actions

```yaml
- name: Build Docker image
  run: |
    echo "${{ secrets.MOODLE_DBPASS }}" | docker build \
      --secret id=db_password,src=/dev/stdin \
      -t leodg/moodle-academy:latest .
```
