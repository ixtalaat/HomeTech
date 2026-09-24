# HomeTech Deployment Checklist (Ubuntu + Nginx + MySQL 8 + PHP 8.2)

## 0. Server prerequisites

- PHP 8.2 with `mbstring pdo_mysql bcmath intl gd zip curl openssl`
- Composer 2, Node 20, MySQL 8, Nginx, Supervisor, cron

## 1. Code and dependencies

```bash
git clone <repo> /var/www/hometech && cd /var/www/hometech
composer install --no-dev --prefer-dist --no-interaction
npm ci && npm run build
```

## 2. Environment (never commit these)

```bash
cp .env.example .env
php artisan key:generate
```

Fill in: `APP_URL`, `DB_*`, `MAIL_*` (smtp), `STRIPE_*`, `SUPPORT_EMAIL`,
WhatsApp/Twilio/Meta keys, `FIREBASE_VAPID_KEY` + web keys.

Copy (never commit) the Firebase service key and lock it down:

```bash
mkdir -p storage/app/firebase
scp firebase-credentials.json user@server:/var/www/hometech/storage/app/firebase/
chmod 600 storage/app/firebase/firebase-credentials.json
```

Seed accounts need passwords first: `ADMIN_PASSWORD`, `MANAGER_PASSWORD`
(and optional `TECHNICIAN_PASSWORD`, `BRANCH_MANAGER_PASSWORD`).

## 3. Database and caches

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R ug+rwx storage bootstrap/cache
```

## 4. Background processes (required — without these, notifications pile up silently)

Cron (ticks the scheduler every minute for nudges, monitors and pruning):

```cron
* * * * * cd /var/www/hometech && php artisan schedule:run >> /dev/null 2>&1
```

Supervisor (keeps queue workers alive; sample in
`deployment/supervisor-hometech-worker.conf`):

```bash
sudo cp deployment/supervisor-hometech-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status   # both workers must show RUNNING
```

## 5. Verify the deploy

```bash
php artisan schedule:list                        # nudges + monitors present
php artisan app:check-queue-backlog              # expect "Queue is healthy."
php artisan test --compact 2>&1 | tail -3        # full suite green (optional on server)
```

Open the site, approve a test request, confirm the technician push arrives.

## 6. On every later deploy

```bash
git pull
composer install --no-dev --prefer-dist --no-interaction
php artisan migrate --force
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart hometech-worker:*
```
