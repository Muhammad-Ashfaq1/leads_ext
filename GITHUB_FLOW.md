# GitHub Flow & Server Setup — leads.obtainsolutions.com (Hostinger)

Reference guide for syncing the Hostinger production server directly with GitHub, running Git commands safely, and operating **Antigravity** / AI agents on the server.

---

## 1. Environment & Repository Details

- **Application**: Leads Engine (VektorLeads)
- **Production URL**: `https://leads.obtainsolutions.com`
- **GitHub Remote**: `git@github.com:Muhammad-Ashfaq1/leads_ext.git` (or HTTPS: `https://github.com/Muhammad-Ashfaq1/leads_ext.git`)
- **Default Branch**: `main`
- **Hostinger SSH Host / IP**: `145.79.26.191`
- **Hostinger Inbound SSH Port**: `65002` (for logging in from your local machine)
- **Hostinger User**: `u407529782`
- **Server Project Path**: `/home/u407529782/domains/obtainsolutions.com/public_html/leads`

---

## 2. Initial One-Time Git Setup on Hostinger

Log into Hostinger via SSH:
```bash
ssh -p 65002 u407529782@145.79.26.191
```

### A. Configure Git Identity
Run the following on the server to ensure all commits match your GitHub profile:
```bash
git config --global user.name "Muhammad-Ashfaq1"
git config --global user.email "mitf19e032@gmail.com"
git config --global init.defaultBranch main
```

### B. Generate SSH Key for GitHub
Create an SSH key pair on the Hostinger server:
```bash
ssh-keygen -t ed25519 -C "mitf19e032@gmail.com"
# Press ENTER to accept default path (~/.ssh/id_ed25519)
# Press ENTER twice for no passphrase
```

Print your public key:
```bash
cat ~/.ssh/id_ed25519.pub
```

Copy the entire output and add it to GitHub:
- **Option 1 (Repository Deploy Key - Recommended for Server)**:
  - Go to `https://github.com/Muhammad-Ashfaq1/leads_ext/settings/keys`
  - Click **Add deploy key**
  - Title: `Hostinger Server (leads.obtainsolutions.com)`
  - Key: paste `~/.ssh/id_ed25519.pub`
  - **Check "Allow write access"** (required if you want to push from server)
  - Click **Add key**
- **Option 2 (Personal GitHub Account SSH Key)**:
  - Go to `https://github.com/settings/keys`
  - Click **New SSH key** and paste the key.

### C. Configure Outbound SSH Port (Bypass Port 22 Firewalls)
Hosting providers (including Hostinger and CloudLinux environments) frequently block outbound connections on TCP port 22. To ensure git connects reliably without timeouts, route GitHub SSH through **port 443** via `ssh.github.com`:

Create or edit `~/.ssh/config`:
```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
cat << 'EOF' > ~/.ssh/config
Host github.com
    HostName ssh.github.com
    Port 443
    User git
    IdentityFile ~/.ssh/id_ed25519
    IdentitiesOnly yes
    StrictHostKeyChecking accept-new
EOF
chmod 600 ~/.ssh/config
```

### D. Test GitHub SSH Authentication
```bash
ssh -T git@github.com
```
**Expected output:**
> `Hi Muhammad-Ashfaq1/leads_ext! You've successfully authenticated, but GitHub does not provide shell access.`

---

## 3. Connecting the Server Directory to Git

Navigate to the project root:
```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads
```

### If `.git` does not exist on the server yet:
If the folder was originally uploaded via CI/CD rsync without the `.git` directory:
```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads
git init
git remote add origin git@github.com:Muhammad-Ashfaq1/leads_ext.git
git fetch origin main

# Align the local folder to origin/main without deleting your server .env:
git reset --mixed origin/main
git branch --set-upstream-to=origin/main main
```

Verify status:
```bash
git status
```

---

## 4. Pull Workflow

### Standard Pull (Preserves local modifications / fast-forward):
```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads
git pull origin main
```

### Hard Sync (Recommended when server has merge conflicts or dirty tree):
Wipe all untracked changes and align the server **100% identically to `origin/main`**.
> Note: `.env` and files in `storage/` are in `.gitignore`, so `git clean -fd` will NOT delete them.
```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads
git fetch origin main
git clean -fd
git reset --hard origin/main
```

### Post-Pull Laravel Maintenance Routine
Whenever you pull updates that touch dependencies, migrations, or views, run:
```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads

# 1. Update composer dependencies if composer.json changed
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Run pending database migrations
php artisan migrate --force

# 3. Clear and rebuild production caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Ensure storage symlink exists
php artisan storage:link

# 5. Maintain folder permissions
chmod -R 775 storage bootstrap/cache
```

---

## 5. Push Workflow (Server to GitHub)

When making modifications on the server (manually or via Antigravity):

```bash
cd /home/u407529782/domains/obtainsolutions.com/public_html/leads

# 1. Review changed files
git status
git diff

# 2. Stage changes
git add app/ resources/ views/ # or specific files

# 3. Commit
git commit -m "feat(module): describe changes made on server"

# 4. Push to GitHub
git push origin main
```

### Dry-Run Write Verification
Test that your SSH key has write access to GitHub without modifying branches:
```bash
git push --dry-run origin "HEAD:refs/heads/_pushtest_$(date +%s)"
```

---

## 6. Operating Antigravity on Hostinger

When installing or running Antigravity on the Hostinger server:

1. **User Execution**:
   - Always run Antigravity under user `u407529782` (your standard SSH login user).
   - This prevents permission skew (files created as `root` cannot be updated or served by LiteSpeed/PHP).

2. **Git Hygiene with Antigravity**:
   - Antigravity working files and local transcripts (`.agents/`, `.gemini/`, logs) are already protected by `.gitignore`.
   - Never stage `.env` or credentials generated during AI sessions:
     ```bash
     git status --ignored
     ```
   - Before completing an Antigravity task, run `php artisan test` or verify the web UI to ensure no syntax errors were introduced.

3. **Storage & Permission Checks**:
   - After an Antigravity run edits or creates files:
     ```bash
     chmod -R 775 storage bootstrap/cache
     ```

---

## 7. Protected Files (Never Commit to Git)

The following items are defined in [.gitignore](file:///.gitignore) and must never be committed:

- `.env`, `.env.*` (Contains database passwords, Google API keys, SMTP credentials)
- `/vendor`, `/node_modules`
- `storage/logs/*.log`, `storage/framework/*`
- `storage/app/public/*` (User uploads, tenant files, customer attachments)
- `.phpunit.result.cache`, `.phpunit.cache`
- `/extractor/.venv/`, `/extractor/__pycache__/`
- `auth.json`, `Homestead.*`

---

## 8. Troubleshooting

| Symptom | Probable Cause | Solution |
|---|---|---|
| `Permission denied (publickey)` | SSH key missing or not added to GitHub | Run `cat ~/.ssh/id_ed25519.pub` and add it to GitHub Deploy Keys (with Write permission). |
| `ssh: connect to host github.com port 22: Connection timed out` | Outbound port 22 is firewalled by Hostinger | Create `~/.ssh/config` using `HostName ssh.github.com` and `Port 443` (see Section 2C). |
| `error: Your local changes to the following files would be overwritten by merge` | Local server edits conflict with remote branch | Run `git stash` or execute the Hard Sync commands (`git clean -fd && git reset --hard origin/main`). |
| `fatal: not a git repository` | Project was rsynced without `.git` | Run `git init`, add remote, and fetch (see Section 3). |
| HTTP 500 or blank white page after pull | Cache mismatch or unmigrated DB changes | Run `php artisan optimize:clear && php artisan config:cache && php artisan migrate --force`. |
| Images / avatars broken (`/storage/...`) | Symlink missing or broken | Run `rm -f public/storage && php artisan storage:link`. |
| Permission denied writing to `storage/logs` | Permission regression | Run `chmod -R 775 storage bootstrap/cache`. |
