# DanversKara's Umbrel Community App Store

A community app store for [Umbrel](https://umbrel.com) with self-hosted apps including a **Bluesky Personal Data Server (PDS)**, **Windows 11 via Dockur**, and a wrapper for **Premium URL Shortener by GemPixel**.

> ⚠️ Still in early development — things may not work perfectly.

---

## 📦 Included Apps

| App | Status | Description |
|-----|--------|-------------|
| **Bluesky PDS** | ✅ Half Working | Self-hosted Bluesky Personal Data Server |
| **Windows 11 Dockur** | ✅ Working | Self-hosted Windows 11 via KVM/QEMU in Docker |
| **Premium URL Shortener** | ✅ Working | Personal wrapper — requires your own GemPixel license |
| **Wyoming Piper TTS** | 10200 | Local text-to-speech for Home Assistant (AI voice) |
| **Wyoming Faster Whisper STT** | 10300 | Local speech-to-text for Home Assistant (AI voice) |

---

## 🔧 Adding This App Store to Umbrel

1. In Umbrel, go to **App Store** → **Community App Stores**
2. Paste this repo URL and click **Add**
3. The apps will appear in your app store

---

## 🎙️ AI Voice Setup for ESP32-S3-Box-3

Install both **Wyoming Piper TTS** and **Wyoming Faster Whisper STT**, then in Home Assistant:

1. **Settings → Devices & Services → Add Integration → Wyoming Protocol**
   - Add Piper: your Umbrel IP, port `10200`
   - Add Whisper: your Umbrel IP, port `10300`
2. **Settings → Voice Assistants → Add Assistant**
   - Speech-to-text: Faster Whisper
   - Text-to-speech: Piper
3. Test with your ESP32-S3-Box-3 — fully local, no cloud required

---

## 🪟 Windows 11 (Dockur) Setup Guide

### Prerequisites

- A running Umbrel instance on **x86-64 hardware** (not Raspberry Pi or ARM)
- **KVM hardware virtualisation** must be available — see requirements below

---

### ⚠️ KVM Requirement

The Windows 11 Dockur app runs Windows inside a Docker container using QEMU/KVM. It **requires `/dev/kvm`** to be present on the host. Without it the app will fail at 1% during installation with the error:

```
error gathering device information while adding custom device "/dev/kvm": no such file or directory
```

**Check if KVM is available on your Umbrel:**
```bash
ls -la /dev/kvm
```

If the file exists, you're good. If not, follow the relevant fix below.

---

### Fix: Bare Metal (direct install on a PC)

1. Reboot and enter your **BIOS/UEFI** (usually `Delete`, `F2`, or `F12` at startup)
2. Find the virtualisation setting — it will be labelled one of:
   - **AMD SVM Mode** / **AMD-V** (AMD CPUs)
   - **Intel VT-x** / **Intel Virtualization Technology** (Intel CPUs)
3. Set it to **Enabled**
4. Save and exit (`F10`)
5. After reboot, run `ls -la /dev/kvm` to confirm it now exists

---

### Fix: Proxmox VM

If Umbrel is running as a Proxmox VM, nested virtualisation must be enabled on the VM:

1. **Shut down** the Umbrel VM
2. Go to the VM → **Hardware** → **Processors** → **Edit**
3. Change **Type** from `x86-64-v2-AES` (or similar) to **`host`**
4. In the **Extra CPU Flags** list, find **`nested-virt`** and click **`+`** to enable it
5. Click **OK**
6. Go to **Options** → **Boot Order** and make sure your **disk** is first (changing CPU settings can sometimes reset boot order)
7. Start the VM and SSH in, then verify:

```bash
ls -la /dev/kvm
# Should show: crw-rw---- 1 root kvm 10, 232 ...
```

Once `/dev/kvm` exists, install the app from the Umbrel App Store normally.

---

### Fix: Other VM platforms

| Platform | Fix |
|----------|-----|
| **VMware** | VM Settings → CPU → enable *"Virtualize Intel VT-x/EPT or AMD-V/RVI"* |
| **VirtualBox** | `VBoxManage modifyvm "YourVM" --nested-hw-virt on` |
| **Cloud VPS** | Most cloud providers do not support nested virtualisation — the app will not work |

---

### Accessing Windows 11

Once installed, open your browser and go to:
```
http://umbrel.local:8006
```
This opens the noVNC web interface where you can interact with Windows during and after installation. Windows installs automatically — the first boot may take 10–20 minutes.

You can also connect via **Remote Desktop (RDP)** on port `3389`.

Default credentials — **Username:** `Docker` / **Password:** `admin`

---

## 🦋 Bluesky PDS Setup Guide

### Prerequisites

- A running Umbrel instance
- A **real public domain name** (e.g. `bsky.yourdomain.com`) — `.local` hostnames will not work
- HTTPS access to that domain (Cloudflare Tunnel is recommended)

---

### Step 1 — Install the App

Install **Bluesky PDS** from this community app store in Umbrel.

---

### Step 2 — Configure Your Domain

Edit the app's `docker-compose.yml` and set your domain:

```yaml
- PDS_HOSTNAME=bsky.yourdomain.com
```

> The PDS will refuse to start if `PDS_HOSTNAME` ends in `.local` or is not a real domain.

---

### Step 3 — Generate a PLC Rotation Key

This key is **required** by recent versions of the atproto PDS. Run this on your Umbrel via SSH or the Terminal app:

```bash
openssl ecparam -name secp256k1 -genkey -noout -out /dev/stdout \
  | openssl ec -text -noout 2>/dev/null \
  | grep priv -A 3 | tail -3 \
  | tr -d ' \n:' | sed 's/^00//'
```

Copy the output and add it to `docker-compose.yml`:

```yaml
- PDS_PLC_ROTATION_KEY_K256_PRIVATE_KEY_HEX=your_key_here
```

> ⚠️ Keep this key safe and secret. If you lose it you lose control of your PDS identity.

---

### Step 4 — Set Secure Passwords

Change the default placeholder values in `docker-compose.yml`:

```yaml
- PDS_ADMIN_PASSWORD=your_strong_password
- PDS_JWT_SECRET=your_random_secret_string
```

---

### Step 5 — Expose Your PDS Publicly (Cloudflare Tunnel)

The PDS needs to be reachable over HTTPS for federation with the Bluesky network.

**Recommended: Cloudflare Tunnel** (free, no port forwarding needed)

1. In Umbrel go to **Settings → Advanced Settings → Cloudflare Tunnel**
2. Follow the setup wizard
3. Point a tunnel route at `localhost:2583` with the hostname `bsky.yourdomain.com`

---

### Step 6 — Create Your First Account

Once the PDS is running, generate an invite code:

```bash
curl -X POST http://YOUR-UMBREL-IP:2583/xrpc/com.atproto.server.createInviteCode \
  -H "Content-Type: application/json" \
  -H "Authorization: Basic $(echo -n 'admin:YOUR_ADMIN_PASSWORD' | base64)" \
  -d '{"useCount": 1}'
```

Then create your account using the returned code:

```bash
curl -X POST http://YOUR-UMBREL-IP:2583/xrpc/com.atproto.server.createAccount \
  -H "Content-Type: application/json" \
  -d '{
    "email": "you@example.com",
    "handle": "yourname.bsky.yourdomain.com",
    "password": "yourpassword",
    "inviteCode": "your-invite-code-here"
  }'
```

---

### Step 7 — Log In on Bluesky

1. Open [bsky.app](https://bsky.app) or the Bluesky mobile app
2. Tap **Sign in**
3. Tap **Hosting provider** and change it from `bsky.social` to `bsky.yourdomain.com`
4. Enter your handle (e.g. `yourname.bsky.yourdomain.com`) and password

---

## 🔗 Premium URL Shortener Setup Guide


Yes, this is absolutely possible. You have already done the "hard part" (figuring out the dependencies and the database). Converting this into an Umbrel Community App is essentially just a matter of wrapping your working blueprint into the specific folder structure that Umbrel requires.
Umbrel doesn't use a standard docker-compose.yml; it uses a specific metadata file (umbrel-app.yml) and a modified compose file so that the Umbrel OS can handle the networking (reverse proxy) and storage.
Here is the blueprint to convert your working setup into an Umbrel Community App.
1. The Umbrel App Structure
You need to organize your GitHub repository exactly like this:
code
Text
danverskara-url-shortener/
├── Dockerfile              <-- (The "Ultimate" Dockerfile we created)
├── docker-compose.yml      <-- (Modified for Umbrel)
├── umbrel-app.yml          <-- (The metadata file for the App Store)
└── icon.png                <-- (A 512x512 icon for the store)
2. The umbrel-app.yml (The Identity Card)
This is the file Umbrel uses to list your app in the store. Create this file in your repo:
code
Yaml
id: danverskara-url-shortener
name: Premium URL Shortener
description: A professional, high-performance URL shortening service.
category: Utilities
version: 1.0.0
# This is the icon that appears in the Umbrel dashboard
icon: icon.png
3. The docker-compose.yml (Modified for Umbrel)
Umbrel handles ports and networking automatically. You do not map ports like 8085:80. Instead, you leave the container on port 80, and Umbrel's proxy handles the rest.
Use this version for Umbrel:
code
Yaml
version: '3'
services:
  web:
    build: .
    container_name: urlshortener_web
    restart: unless-stopped
    # No ports section here! Umbrel manages this.
    volumes:
      - /data/storage/apps/danverskara-url-shortener/app:/var/www/html
    environment:
      - WEB_DOCUMENT_ROOT=/var/www/html/public
    depends_on:
      - db
    networks:
      - default

  db:
    image: mariadb:11
    container_name: urlshortener_db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: shortener
      MYSQL_USER: shortener
      MYSQL_PASSWORD: shortenerpass
    volumes:
      - /data/storage/apps/danverskara-url-shortener/db:/var/lib/mysql
    networks:
      - default

networks:
  default:
4. The Dockerfile
Use the exact same "Ultimate" Dockerfile we created in the previous step. Do not change it; it's already perfect.
5. How to handle your "Upload Files and SQL later"
Since you are putting this on GitHub for the App Store, you probably don't want to put your private config.php or your full SQL dump in a public repo.
Here is the workflow for the user (or you):
Install the app from the Umbrel Store.
The app will start, but it will be "broken" (White page/Error) because the files and DB are empty.
Use an SSH client or the Umbrel File Manager to go to:
/data/storage/apps/danverskara-url-shortener/app
Upload your PHP files and config.php there.
Import the SQL using the same command we used before:
code
Bash
docker exec -i urlshortener_db mariadb -u shortener -pshortenerpass shortener < /data/storage/apps/danverskara-url-shortener/app/your-db-dump.sql

🛠 Final Action Plan for your GitHub
Clean up: Go to your GitHub repo and delete all the old, non-working files.
Upload: Push the 4 files listed in the structure above (Dockerfile, docker-compose.yml, umbrel-app.yml, and icon.png).
The App Store: Since you are using the umbrel-community-app-store repository, Umbrel will see your umbrel-app.yml and the docker-compose.yml, and it will allow users to "Install" it.
Customization: Because we used /data/storage/apps/danverskara-url-shortener/ in the compose file, Umbrel will automatically create those folders on the user's hard drive, making it very easy for you to upload your files via SFTP later.

---

## ⚖️ Legal Notice

Premium URL Shortener is proprietary software © GemPixel. All Rights Reserved.
This Umbrel wrapper is NOT affiliated with or endorsed by GemPixel.
Each user must purchase their own license at https://gempixel.com/products/premium-url-shortener

---

## 📄 License

MIT

---

## 📄 HOW TO EXPOSE APPS TO THE WORLD

I hope this helps in a way that matches what you’re trying to achieve.
What I did to expose my apps to the internet in a more secure way was use Cloudflare Tunnels.
Setup Overview
I then installed Nginx Proxy Manager from the Umbrel App Store.
Cloudflare Tunnel Setup
Go to Cloudflare Dashboard → Networks / Connectors
Create a tunnel and install it using the provided installer/token - it will give you a installer *I installed the Cloudflare Tunnel directly on UmbrelOS (they do have a cloudflare tunnels app but i didn't test it yet but you should try that an update could break the install direct on the OS).
After setup, open your tunnel configuration.
Find Published Application Routes — this is where you define how each app is exposed.
Example: Nextcloud
Click Add Published Application Route
Enter your subdomain (example: cloud.example.com) or full domain
Leave path empty unless you specifically need it
Service settings:
Type: HTTP
URL: UmbrelOS IP:PORT
Example:
192.168.1.35:40080
Save the route.
Nginx Proxy Manager Setup
Now go to Nginx Proxy Manager:
Domain: same one you used in Cloudflare (example: cloud.example.com)
Scheme: http
Forward IP: UmbrelOS local IP (example: 192.168.1.35)
Forward Port: the app’s internal port
To find the correct port:
Go to the Umbrel dashboard
Open the app
Right-click → Troubleshoot
Look through logs for something like: http://0.0.0.0:6052
⚠️ Note: not all apps follow the same format.
IMPORTANT SECURITY WARNING
Do NOT expose every app.
Many apps are admin-only tools (for example, Zoraxy). If you expose those, anyone on the internet could gain admin access.
Only expose apps that are designed for external access, and be selective.
Optional: Securing Admin Apps
If you do need to expose apps with admin access:
In Nginx Proxy Manager:
Go to Access Lists
Create an authorization rule (username + strong password)
Save it
Then:
Go back to your Proxy Host
Assign that Access List to the domain
Make sure the username and password are strong and not easy to guess.
this is a lot more secure then exposing your routers port 80/443 and to protect your network.
