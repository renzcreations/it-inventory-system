
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

---

## 🛠️ Tech Stack

- **PHP**
- **JavaScript**
- **TailwindCSS**

### Components Used

- [Alpine.js](https://alpinejs.dev/)
- [SweetAlert2](https://sweetalert2.github.io/)
- [Chart.js](https://www.chartjs.org/)
- [Font Awesome](https://fontawesome.com/)
- [Brevo Email API](https://www.brevo.com/) (for email notifications)

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
