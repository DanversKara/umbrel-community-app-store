# 🚀 Premium URL Shortener for Umbrel

[![Umbrel Compatible](https://img.shields.io/badge/Umbrel-Compatible-blue)](https://umbrel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-777bb4)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MariaDB%2011-0033cc)](https://mariadb.org)

A professional, self-hosted, high-performance URL shortening service designed specifically for the Umbrel ecosystem. Take full control of your links, track your analytics, and maintain your privacy without relying on third-party services.

---
# HOW TO SETUP

  - SSH into Umbrel:
    cd ~/umbrel/app-data/danverskara-url-shortener/app/

 Transfer Files from computer with files:
  - UmbrelOS ssh umbrel@192.168.1.35
    -- sudo chmod 777 ~/umbrel/app-data/danverskara-url-shortener/app/
  - Other Computer where main.zip is located
    -- scp main.zip umbrel@192.168.1.35:~/umbrel/app-data/danverskara-url-shortener/app/
  
  - Install unzip
    -- sudo apt update
    -- sudo apt install unzip -y
  - UmbrelOS ssh umbrel@192.168.1.35
    -- cd ~/umbrel/app-data/danverskara-url-shortener/app/
    -- sudo unzip main.zip

  - Get DB Password
 sudo docker inspect danverskara-url-shortener_db_1 | grep -i password

  - must fix DB Password
 sudo nano ~/umbrel/app-data/danverskara-url-shortener/app/config.php

  - Must get new DB Password
 sudo docker exec -i danverskara-url-shortener_db_1 \
  mariadb -u shortener -p"PASSWORD" shortener \
  < ~/umbrel/app-data/danverskara-url-shortener/app/urlshortener-db-20260414.sql

 - Must Fix
sudo chown -R www-data:www-data ~/umbrel/app-data/danverskara-url-shortener/app/
sudo chmod -R 755 ~/umbrel/app-data/danverskara-url-shortener/app/
sudo chmod -R 777 ~/umbrel/app-data/danverskara-url-shortener/app/storage
sudo mkdir -p ~/umbrel/app-data/danverskara-url-shortener/app/storage/cache
sudo chmod -R 777 ~/umbrel/app-data/danverskara-url-shortener/app/storage/cache

 - Now Setup NPM and Cloudflare Tunnels
 - Cloudflare http://192.168.1.35:40080
 - NPM http 192.168.1.35 (get port right app click troubleshoot) port
 - Now Right Click Restart App or Reboot server.
 - Website should be live.

---

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
