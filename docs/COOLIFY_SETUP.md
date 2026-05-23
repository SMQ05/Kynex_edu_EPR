# Hosting KynexEdu ERP on Coolify

This guide provides a comprehensive, step-by-step walkthrough on how to host the **KynexEdu ERP** system on your Coolify VPS. 

Because KynexEdu is a **multi-tenant application** (using a tenant-per-database architecture), there are specific configurations required for **wildcard subdomains**, **shared storage volumes**, and **environment key alignment** to ensure everything runs correctly.

---

## Prerequisites

Before starting the setup on Coolify, make sure you have completed the following:

1. **VPS with Coolify**: A VPS with Coolify installed and running.
2. **Repository Access**: Your KynexEdu repository pushed to a git provider (GitHub, GitLab, or a self-hosted instance like Gitea) that Coolify can access.
3. **Wildcard DNS Configuration**:
   To support dynamic subdomains (e.g., `demo.edu.kynexsolutions.com`, `school1.edu.kynexsolutions.com`), you **must** configure a wildcard DNS record pointing at your VPS IP address:
   * **Type**: `A`
   * **Name**: `*.edu` (or `*` if using the apex domain directly)
   * **Content/Value**: Your VPS IP address

---

## Deployment Strategy: Docker Compose (Recommended)

Coolify allows you to deploy a **Docker Compose** stack directly. This is the simplest and most robust way to host KynexEdu because it closely mirrors the production environment, keeps the database and queue workers inside a secure private network, and manages persistent volumes automatically.

### Step 1: Create a New Project & Environment
1. Log in to your Coolify dashboard.
2. Go to **Projects** → **+ Add New**.
3. Name your project (e.g., `KynexEdu ERP`) and create a `production` environment.

### Step 2: Add a Docker Compose Resource
1. Inside your new environment, click **+ New** → **Docker Compose**.
2. Select your Git Repository, Branch (`main`), and target VPS server.
3. In the **Docker Compose Raw Configuration** box, paste the contents of your `docker-compose.coolify.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: kynexedu-erp:coolify
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - kynexedu_storage:/var/www/html/storage
    environment:
      RUN_MIGRATIONS_AND_SEED: "true"
      # Injected runtime env variables will merge here

  queue:
    image: kynexedu-erp:coolify
    restart: unless-stopped
    command: ["/usr/local/bin/start-queue"]
    environment:
      RUN_MIGRATIONS_AND_SEED: "false"
    depends_on:
      db:
        condition: service_healthy
      app:
        condition: service_started
    volumes:
      - kynexedu_storage:/var/www/html/storage

  scheduler:
    image: kynexedu-erp:coolify
    restart: unless-stopped
    command: ["/usr/local/bin/start-scheduler"]
    environment:
      RUN_MIGRATIONS_AND_SEED: "false"
    depends_on:
      db:
        condition: service_healthy
      app:
        condition: service_started
    volumes:
      - kynexedu_storage:/var/www/html/storage

  db:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: kynexedu_central
      POSTGRES_USER: kynexedu
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U kynexedu -d kynexedu_central"]
      interval: 5s
      timeout: 5s
      retries: 20
    volumes:
      - kynexedu_db_data:/var/lib/postgresql/data

volumes:
  kynexedu_storage:
  kynexedu_db_data:
```

### Step 3: Configure Domains (Wildcard Routing)
To ensure the `app` service handles both the central portal and all dynamic tenant subdomains, configure the domain in Coolify:
1. In the **Service Settings** for the `app` container:
2. Set the **Domain** field to:
   ```
   https://edu.kynexsolutions.com, https://*.edu.kynexsolutions.com
   ```
   > [!NOTE]
   > Coolify uses Traefik as its reverse proxy. Specifying a comma-separated list with a wildcard subdomain tells Traefik to automatically provision SSL certificates for the apex domain and routing rules for any subdomain!

### Step 4: Configure Environment Variables
In the **Environment Variables** tab of your service, define the following variables:

| Key | Value | Notes |
|:---|:---|:---|
| `APP_NAME` | `"KynexEdu ERP"` | Name of your application |
| `APP_ENV` | `production` | Production environment |
| `APP_DEBUG` | `false` | Turn off debugging in production |
| `APP_URL` | `https://edu.kynexsolutions.com` | Base URL of the portal |
| `APP_KEY` | `base64:YOUR_32_BYTE_SECURE_KEY` | **Crucial:** Generate a key locally (`php artisan key:generate --show`) and paste the SAME key here. Do not leave it blank. |
| `POSTGRES_PASSWORD` | `YOUR_SECURE_DB_PASSWORD` | Secret password for your DB container |
| `DB_PASSWORD` | `YOUR_SECURE_DB_PASSWORD` | Must match `POSTGRES_PASSWORD` |
| `TENANCY_DB_PASSWORD` | `YOUR_SECURE_DB_PASSWORD` | Must match `POSTGRES_PASSWORD` |
| `SAAS_ADMIN_EMAIL` | `admin@kynexedu.com` | Email for central portal administration |
| `SAAS_ADMIN_PASSWORD` | `YOUR_SUPERADMIN_PASSWORD` | Password for central portal administration |
| `MAIL_MAILER` | `resend` | Your mail client (e.g., `resend` or `smtp`) |
| `RESEND_API_KEY` | `your-resend-api-key` | Required if using Resend |
| `SESSION_SECURE_COOKIE` | `true` | Forces cookies over HTTPS only |

> [!IMPORTANT]
> The `APP_KEY` environment variable must be explicitly defined here. Since all three services (`app`, `queue`, `scheduler`) will read this environment variable, they will all share the exact same key, preventing any session or background queue decryption errors!

### Step 5: Deploy
1. Click **Deploy**.
2. Coolify will compile your Dockerfile, initialize the PostgreSQL database, run your migrations/seed database on first boot (`RUN_MIGRATIONS_AND_SEED: "true"`), and boot the queue and scheduler containers.
3. Once completed, your system is fully live, SSL certificates will be auto-generated by Traefik, and you can access your platform at `https://edu.kynexsolutions.com`.

---

## Alternative: Individual Services (Advanced)

If you prefer to keep your database managed separately under Coolify's native **Databases** resources, you can set it up as follows:

### Step 1: Create a PostgreSQL Database Resource
1. Coolify → **+ New** → **Database** → **PostgreSQL**.
2. Deploy the database.
3. Open the database settings and copy the **Internal Connection String**:
   `postgres://kynexedu:PASSWORD@kynexedu-db:5432/kynexedu_central`

### Step 2: Create individual Application Services
You will create three separate Coolify **Application** resources pointing to the same Git repository:

#### 1. Web Application (`kynexedu-app`)
* **Build Pack**: `Dockerfile`
* **Dockerfile Location**: `Dockerfile`
* **Domains**: `https://edu.kynexsolutions.com, https://*.edu.kynexsolutions.com`
* **Persistent Storage**: Mount a volume named `kynexedu_storage` to `/var/www/html/storage`.
* **Env Variables**:
  * Set `RUN_MIGRATIONS_AND_SEED=true`
  * Add the shared `APP_KEY`
  * Configure database credentials pointing to your PostgreSQL resource's host and port.

#### 2. Queue Worker (`kynexedu-queue`)
* **Build Pack**: `Dockerfile`
* **Dockerfile Location**: `Dockerfile`
* **Override Start Command**: Set this in Coolify to `["/usr/local/bin/start-queue"]` (or custom start command).
* **Persistent Storage**: Mount the **SAME** `kynexedu_storage` volume to `/var/www/html/storage`.
* **Env Variables**:
  * Set `RUN_MIGRATIONS_AND_SEED=false`
  * Add the same shared `APP_KEY` and DB credentials.

#### 3. Scheduler Worker (`kynexedu-scheduler`)
* **Build Pack**: `Dockerfile`
* **Dockerfile Location**: `Dockerfile`
* **Override Start Command**: Set this in Coolify to `["/usr/local/bin/start-scheduler"]`.
* **Persistent Storage**: Mount the **SAME** `kynexedu_storage` volume to `/var/www/html/storage`.
* **Env Variables**:
  * Set `RUN_MIGRATIONS_AND_SEED=false`
  * Add the same shared `APP_KEY` and DB credentials.

---

## Verifying the Coolify Setup

Once deployed, verify that the system is fully operational:

1. **Check System Health**: Access `https://edu.kynexsolutions.com/up`. You should receive a `200 OK` response (this is Laravel's native health-check route configured in `bootstrap/app.php`).
2. **Access SaaS Panel**: Visit `https://edu.kynexsolutions.com/saas/login` and log in with your configured `SAAS_ADMIN_EMAIL` and `SAAS_ADMIN_PASSWORD`.
3. **Test Subdomain Tenancy**: 
   - In the SaaS panel, create a new school tenant with slug `demo`.
   - Wait 10 seconds for the database migration to finish.
   - Navigate to `https://demo.edu.kynexsolutions.com/login`. It should resolve perfectly without any "School not found" or "DecryptException" errors!
