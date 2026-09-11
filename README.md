# 📦 Deploy ke Railway - Aplikasi Tabung LPG

## ✅ Fitur JSON Database
- **Tanpa MySQL** - cuma pake file `data.json` sebagai database
- **Data terisolasi per user** - beda user = beda data
- **Backup & Restore** built-in di API

## 🚀 Cara Deploy ke Railway (3 Langkah!)

### 1️⃣ Push ke GitHub
```bash
cd c:\laragon\www\Tabung
git init
git add .
git commit -m "Initial commit - ready for Railway"
git branch -M main
git remote add origin <YOUR_GITHUB_REPO_URL>
git push -u origin main
```

### 2️⃣ Setup Railway
1. Buka https://railway.app
2. Login → **New Project** → **Deploy from GitHub repo**
3. Pilih repository kamu
4. Tunggu auto-deploy (~1 menit)

### 3️⃣ Done! 🔥
Railway otomatis detect PHP dan deploy dalam hitungan menit!

## ⚙️ Konfigurasi Railway
Di Railway dashboard, tambahkan environment variable:

| Variable | Value | Keterangan |
|----------|-------|------------|
| `PHP_VERSION` | `8.0` | Versi PHP |

Atau biarkan default, Railway auto-detect.

## 📁 Struktur File
```
📁 tabung/
 ├─ lpg.html          # Frontend aplikasi
 ├─ api.php           # Backend API (JSON storage)
 ├─ data.json         # "Database" JSON (auto-created)
 ├─ .gitignore        # Git ignore file
 └─ README.md         # Dokumentasi ini
```

## 🛡️ Keamanan
- Default password: `admin123` (ganti segera setelah login pertama!)
- Password di-hash dengan bcrypt
- Session-based authentication

## 💾 Backup Data
API menyediakan endpoint untuk backup:
```php
// GET /api.php?action=backup_data
// Returns semua data dalam format JSON
```

File `data.json` adalah satu-satunya tempat semua data tersimpan. Copy file ini untuk backup manual.

## 🔄 Restore dari Backup
Pasting ulang isi `data.json` yang sudah di-backup.

## 🔗 Endpoint API

| Action | Method | Auth | Fungsi |
|--------|--------|------|--------|
| `login` | POST | ❌ | Login user |
| `logout` | POST | ✅ | Logout |
| `me` | GET | ✅ | Info user current |
| `load` | GET | ✅ | Load data user |
| `save` | POST | ✅ | Save data user |
| `direct_load` | POST | ❌ | Load tanpa session |
| `direct_save` | POST | ❌ | Save tanpa session |
| `create_user` | POST | ✅ | Buat user baru |
| `get_users` | GET | ✅ | List semua user |
| `backup_data` | GET | ✅ | Export semua data |
| `restore_data` | POST | ✅ | Import backup |

## 🆘 Troubleshooting

### Error 404 saat akses API?
- Pastikan `.htaccess` atau config server mengarahkan ke root folder
- Check URL: `https://your-domain.railway.app/api.php?action=login`

### File data.json tidak persisten?
- Railway punya persistent storage by default
- Jika masih bermasalah, pakai **Persistent Volume** feature

### Port number berubah-ubah?
- Railway assign port via env variable `PORT`
- File API kita udah handle ini

---

**Happy coding! 🚀**
