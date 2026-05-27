# Backend Laravel Jember Siaga

Folder ini berisi backend utama Laravel untuk aplikasi Jember Siaga.

Struktur penting:

- `app/`, `routes/`, `database/`, `config/`: aplikasi Laravel utama.
- `ml_service/`: service FastAPI terpisah untuk prediksi banjir berbasis model machine learning.
- `database/datasets/`: dataset pendukung yang dipakai proses pengembangan/training.

Untuk menjalankan Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Untuk menjalankan service ML, lihat `ml_service/README.md`.
