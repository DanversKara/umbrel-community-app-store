# 🚀 Premium URL Shortener for Umbrel

[![Umbrel Compatible](https://img.shields.io/badge/Umbrel-Compatible-blue)](https://umbrel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-777bb4)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MariaDB%2011-0033cc)](https://mariadb.org)

A professional, self-hosted, high-performance URL shortening service designed specifically for the Umbrel ecosystem. Take full control of your links, track your analytics, and maintain your privacy without relying on third-party services.

---

## ✨ Features
- **Custom Aliases:** Create short, memorable links for your brand.
- **Detailed Analytics:** Track clicks and visitor data in real-time.
- **Self-Hosted:** Your data never leaves your Umbrel server.
- **High Performance:** Optimized for PHP 8.2 and MariaDB 11.
- **Privacy First:** No tracking scripts or third-party cookies.

## 📦 Installation

### 1. Install via Umbrel App Store
If you have added the DanversKara community store to your Umbrel:
1. Open your **Umbrel Dashboard**.
2. Navigate to the **App Store**.
3. Find **Premium URL Shortener** and click **Install**.

### 2. Post-Installation Setup (Required)
Because this is a premium application, the core source files and database are not bundled in the public store repository for security and licensing reasons. You must upload your files manually.

#### 📂 Uploading Application Files
Use SFTP or the Umbrel terminal to upload your application files to the following directory:
` /data/storage/apps/danverskara-url-shortener/app `

#### 🗄️ Importing the Database
Once your files are uploaded, import your database backup (`.sql` file) by running the following command from your Umbrel terminal:

```bash
docker exec -i urlshortener_db mariadb -u shortener -pshortenerpass shortener < /data/storage/apps/danverskara-url-shortener/app/your-db-dump.sql
```

⚠️ Legal Notice & Software Licensing
This repository is a Deployment Wrapper only.

This project provides the necessary infrastructure, environment configurations, and Docker orchestration required to run the Premium URL Shortener on Umbrel.

Please be advised of the following:

Source Code: This repository does not contain the proprietary source code, binaries, or database schemas of the Premium URL Shortener.

Licensing: To utilize this wrapper, you must possess a valid license and the official software package provided by GemPixel.

Acquisition: The software must be purchased directly from the official developer. You can acquire the software and review the licensing terms here:
🛒 Purchase Product: GemPixel Premium URL Shortener: [LINK](https://gempixel.com/products/premium-url-shortener)

📜 Licensing Terms: GemPixel Licenses: [LINK](https://gempixel.com/licenses)

This wrapper is provided "as-is" for the convenience of the community. The maintainer of this repository is not affiliated with GemPixel and does not distribute or sell their proprietary software.

---
