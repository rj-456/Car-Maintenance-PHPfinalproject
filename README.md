# 🏎️ Apex Auto Care - Service Appointment & Administration System

A premium, full-stack web application designed for booking car maintenance appointments and managing them via an intuitive administrative dashboard. 

This project utilizes a **modern decoupled (API-driven) architecture**, separating a fast, reactive frontend UI from a secure, data-rich backend engine.

---

## 📐 Architecture & Technology Stack

The application is structured into two main components:

### 1. The Frontend (Client-Facing Portal)
* **Technology:** React.js, Vite, Vanilla CSS
* **Design Philosophy:** Premium dark mode aesthetic, smooth CSS micro-animations, glassmorphic accents, and fully responsive grid systems.
* **Key Capabilities:**
  * **Interactive Intake Form:** Real-time user experience with adaptive fields (e.g., specifying custom models or services dynamically when selecting "Other").
  * **Double-Step Submission:** An interactive review screen allowing customers to verify details before finalize.
  * **Unified Media Uploader:** Supports attaching reference URLs or uploading physical photos/videos (converted to Base64 in-flight and saved directly on the backend).

### 2. The Backend (Control & Persistence Layer)
* **Technology:** Pure PHP, SQLite3 Database
* **Key Capabilities:**
  * **`api.php` (RESTful Endpoint):** Receives structured JSON input, decodes Base64 binary streams into local files inside `/uploads`, performs server-side schema validation, and inserts records securely.
  * **`admin.php` (Administrative Control Panel):** A classic server-side rendered (SSR) dashboard enabling full CRUD capabilities. It aggregates real-time business KPIs and filters massive datasets seamlessly.
  * **Secure SQLite Database:** Persistent lightweight database storage featuring self-healing auto-migrations.

---

## 📂 Project Directory Structure

```text
Car maintenance php/
├── backend/
│   ├── api.php             # JSON REST API endpoint (receives, validates, & saves data)
│   ├── admin.php           # PHP Server-Side Rendered Administrative Dashboard
│   ├── appointments.db     # SQLite3 database (auto-generated on launch)
│   └── uploads/            # Local folder storing uploaded customer images/videos
│
├── frontend/
│   ├── src/
│   │   ├── App.jsx         # Component bootstrap
│   │   ├── main.jsx        # React entry-point
│   │   ├── AppointmentForm.jsx    # Complete React booking form component
│   │   └── AppointmentForm.css    # Premium style system & transitions
│   ├── index.html          # HTML Shell
│   ├── package.json        # Frontend dependencies (React, Vite)
│   └── vite.config.js      # Build bundler configuration
│
└── README.md               # Project documentation
```

---

## 🛡️ Security & Validation Features

This project implements strict **Defense-in-Depth** security best practices:
1. **Client-Side Validation (UX layer):** Instant regex email checks and empty-field locks before a user submits.
2. **Server-Side Validation (Security layer):** Backend verification via standard email filters (`FILTER_VALIDATE_EMAIL`) and strict name regex checking (`/^[a-zA-Z-' ]*$/`) in `api.php`.
3. **Data Sanitization:** Stripping slashes, trimming margins, and applying `htmlspecialchars()` escapes to prevent Cross-Site Scripting (XSS).
4. **Prepared SQL Statements:** Database transactions use PDO-style named parameter bindings (`$stmt->bindValue()`) to completely immunize the database against SQL Injection attacks.

---

## 🚀 Setup & Installation Instructions

Follow these steps to run the complete environment locally:

### Prerequisites
* **Backend:** PHP 8.x installed (included with XAMPP, Laragon, or WampServer). Ensure SQLite extension is enabled in your `php.ini`.
* **Frontend:** Node.js (v16+) installed.

### Step 1: Run the Backend (PHP)
Place the `backend/` directory inside your local web server environment (such as XAMPP's `htdocs` folder) or launch PHP's built-in server inside the `backend` directory:
```bash
cd backend
php -S localhost:80
```
* The API will now listen for incoming bookings at `http://localhost/api.php`.
* You can access the live Control Panel dashboard at `http://localhost/admin.php`.

### Step 2: Run the Frontend (React/Vite)
Open your terminal, navigate to the `frontend/` directory, install dependencies, and start the development server:
```bash
cd frontend
npm install
npm run dev
```
* Open your browser to the URL output in your terminal (usually `http://localhost:5173`).
* Enjoy booking a service appointment!
