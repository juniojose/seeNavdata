# seeNavdata 🚀

A lightweight and modern diagnostic tool developed in **PHP 8.2**, designed to capture and display all publicly available information from a web connection, both from the server side (Server-Side) and the client side (Client-Side).

## 📋 About the Project

**seeNavdata** was created to help developers understand which data is accessible during an HTTP request. It is an essential tool for debugging headers, validating environment variables, and mapping browser capabilities, making it easier to build validation and security logic in other applications.

## ✨ Features

### 🖥️ Server-Side (PHP)
- **Connection Identification:** Real IP address, remote port, and protocol.
- **HTTP Request:** Method used (GET, POST, etc.) and raw User-Agent.
- **Headers:** Complete listing of all HTTP headers sent by the browser.
- **Environment Variables:** Formatted dump of the `$_SERVER` superglobal.
- **Email Reporting:** Functionality to send collected data directly to a configured email via SMTP (PHPMailer).
- **Automatic Geolocation:** Automatic IP lookup using the `ip-api.com` API to identify country, city, ISP, and more.

### 📱 Client-Side (JavaScript)
- **Hardware & Screen:** Total resolution, available area, color depth, and pixel ratio.
- **Location & Language:** System timezone and preferred languages.
- **Browser Capabilities:** Cookie status, platform, and browser engine.
- **Preferences:** System theme detection (Dark/Light Mode).
- **🕵️ Advanced Fingerprinting:**
    - **Canvas Hash:** Unique digital signature based on GPU rendering.
    - **WebGL Info:** Identification of the exact graphics card (GPU Vendor & Renderer).
    - **Hardware Specs:** CPU core count and touch point support.
- **📊 Analytics Dashboard:**
    - **SQLite Database:** Automatic persistence of all access logs.
    - **Advanced Bot Farm Detection:** Heuristic engine that analyzes multiple risk factors:
        - **Timezone Mismatch:** Brazilian IP with Asian system timezone.
        - **Automation Flags:** `navigator.webdriver` enabled.
        - **Power Anomaly:** Mobile devices with 100% battery and charging (Farm behavior).
        - **Fake Environment:** Inconsistencies between User-Agent (e.g., Android) and Platform (e.g., Win32).
        - **Software Rendering:** Usage of VM/Emulator graphics (SwiftShader, llvmpipe).
    - **Visual Interface:** Filter logs by date range, view suspicious activity metrics, and inspect full JSON payloads.

## 🚀 How to Run

### Prerequisites
- Web Server (Apache2 recommended).
- PHP 8.2 or higher + **php-sqlite3** extension.
- [Composer](https://getcomposer.org/) installed.

### Installation
1. Clone this repository to your server's root directory (e.g., `/var/www/html/`):
   ```bash
   git clone https://github.com/juniojose/seeNavdata.git
   ```
2. Install dependencies via Composer:
   ```bash
   composer install
   ```
3. Configure email credentials:
   - Copy the example file: `cp config.php.example config.php`
   - Edit `config.php` with your SMTP server settings.
4. Ensure write permissions for SQLite:
   ```bash
   # The web server needs write access to the directory to create/update the .db file
   chown www-data:www-data /var/www/html/seeNavdata
   chmod 775 /var/www/html/seeNavdata
   ```

5. Access via browser:
   - **Application:** `http://localhost/seeNavdata`
   - **Dashboard:** `http://localhost/seeNavdata/dashboard.php`

The page prints the IP of the request, so `.htaccess` sends `Cache-Control: no-store`. On talkei.me the files are served by Apache 2.4; an nginx cache in front of that Apache stores a response that does not say `no-store`. After publishing, drop the old copy with `curl -X PURGE https://talkei.me/seeNavdata/`.

## 🛠️ Technologies Used
- **PHP 8.2:** Server data processing.
- **SQLite3:** Serverless, self-contained database engine.
- **PHPMailer:** Library for sending emails via SMTP.
- **Bootstrap 5:** Responsive and modern interface.
- **JavaScript (Vanilla):** Browser metadata collection, Fingerprinting, and AJAX integration.
- **Composer:** Dependency management.

---
Developed for diagnostic and software development purposes.