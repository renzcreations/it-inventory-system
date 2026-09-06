
# IT Inventory System

A lightweight IT inventory management system built with a **custom PHP framework**. Designed for easy deployment, maintenance, and scalability, this tool streamlines asset tracking and inventory control across your organization.

---

## 🚀 Getting Started

To set up and run the system locally:

1. **Install PHP dependencies**  
   ```bash
   composer install
   composer update
   ```

2. **Install JavaScript dependencies**  
   ```bash
   npm install
   npm update
   ```

3. **Start the development server**  
   ```bash
   npm run dev
   ```

4. **Create a production CSS build**
   ```bash
   npm run build
   ```

---

## 🛠️ Tech Stack

- **PHP**
- **JavaScript**
- **TailwindCSS**

## Architecture

The application uses a lightweight MVC structure:

- `controllers/` handles HTTP input, validation, redirects, sessions, and view selection.
- `models/` contains all SQL and transactional inventory operations.
- `views/` contains PHP templates and layouts.
- `system/core/` provides the router, controller, database, and base model classes.
- `system/Services/` contains external-service integrations such as email delivery.
- `router/routes.php` is the single route registry.

Controllers do not create PDO connections or contain executable SQL. Models use
prepared statements through `System\Core\Model` and own transaction boundaries for
multi-table operations.

For a fresh installation, import `database.sql`. To upgrade an existing installation
without deleting current records, select the existing database in phpMyAdmin and import
`database_saas_cms_migration.sql` exactly once. The migration is additive: it assigns
current records to workspace 1, adds tenant keys and tenant-first indexes, CMS catalogs,
Laravel/Yii-style RBAC tables, audit logs, report metadata, subscriptions, and security tables.

## Secure production configuration

The application does not use a public-root `.env` file. On shared hosting, copy
`config/secrets.example.php` to `it-inventory-secrets.php` **one directory above**
`public_html`, then enter the database, Brevo, cron, and future payment-provider secrets.
The loader checks that external file first. If your host supports server variables, set
`IT_INVENTORY_SECRETS` to its absolute path instead.

For the current local checkout, the former `.env` values were moved to the ignored and
Apache-denied `config/.secrets` compatibility file. Do not upload that local file. Upload
the external PHP secrets file instead. The root `.htaccess` also denies config, source,
SQL, logs, dependency manifests, directory listings, and dotfiles.

Deployment order:

1. Back up the current database.
2. Import `database_saas_cms_migration.sql` into the selected existing database.
3. Put `it-inventory-secrets.php` above `public_html` and update `APP_URL` with a trailing slash.
4. Upload the application including `vendor/` and the built `src/css/output.css` if Composer/Node are unavailable on the host.
5. Confirm HTTPS, log in as the existing administrator, and review **CMS settings** and role assignments.

## Browser HTTP Client

Axios is not used. Browser requests use `src/js/http.js`, a dependency-free wrapper
around the native Fetch API with same-origin enforcement, timeouts, JSON handling,
credentials, consistent errors, and CSRF-header support.

### Components Used

- [Alpine.js](https://alpinejs.dev/)
- [SweetAlert2](https://sweetalert2.github.io/)
- [Chart.js](https://www.chartjs.org/)
- [Brevo Email API](https://www.brevo.com/) (for email notifications)

## SaaS administration

- Every operational inventory query and staging workflow is scoped to the signed-in workspace.
- CMS settings manage branding, employee statuses, work arrangements, job titles,
  departments, part/accessory/computer categories, and lifecycle statuses.
- RBAC uses many-to-many user/role/permission mappings and route-level permission checks.
- Destructive catalog removal is implemented as deactivation, preserving historical records.
- The audit log records CMS, permission, role, and report events.
- The report center generates server-side PDF exports with remote loading and PHP execution disabled.

---

## 📊 Dashboard Features

- Summary of:
  - Part stocks, assigned units, and defective items
  - Accessory usage, stock quantities, and defective quantities
- Custody summary: signed vs. unsigned equipment forms

---

## 👤 Employee Management

- View and edit employee information
- Change status (e.g., from **Active** to **Resigned**)
- View and manage equipment custody agreements
- Register new employees manually
- Bulk upload or update via TSV file

---

## 🔩 Parts Management

- View part history
- Edit part information
- Add parts (supports multiple/bulk adding)
- Install/update parts for assigned computers

---

## 🎧 Accessories Management

- View accessory history
- View returned accessory history
- Add new accessories (supports multiple/bulk adding)

---

## 🖥️ Build & Deployment

- Real-time validation of available computer names
- Add available parts to build new computer configurations

---

## 💻 Computer Management

- View computer history and full specifications
- Remove/uninstall parts when necessary
- Mark computers as **returned**
- Assign computers to employees without one
- View returned/resigned employee computer history

---

## 📝 Registration

- **Admin registration only**
- Code or email-based verification for added security

---

## 🔔 Notifications

- Employees receive email alerts for:
  - Updates to their assigned computers
  - New computer assignments

---

## 📦 Backup System

- **Automatic Backup**: Every 24 hours via [cron-jobs.org](https://cron-jobs.org/)
- **Manual Backup**: Admins can trigger email backup from their profile page

---

## 👤 Profile Management

- Admins and users can update their personal and company information
- Change passwords securely
- Upload a digital signature:
  - Visible only in custody agreements **if the user is an administrator**
  - Otherwise, visible only on the user's profile page

---

## 📬 Contact

For issues or inquiries, please contact the system maintainer or submit a pull request if you're contributing.

---

> ⚠️ This project is under active development. Features and documentation are subject to change.
