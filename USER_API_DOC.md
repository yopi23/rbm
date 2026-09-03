# Dokumentasi API Profil Pengguna, Karyawan & Penarikan Saldo (Withdrawal)

Dokumentasi ini menjelaskan API untuk mengambil profil pengguna (teknisi/kasir), mendapatkan daftar karyawan di bawah owner, melakukan penarikan saldo, serta memantau riwayat penarikan.

---

## 🔒 Persyaratan & Keamanan (Authentication)
Semua request harus menyertakan Header berikut:
- **Authorization**: `Bearer {token_sanctum}`
- **Accept**: `application/json`

---

## 📂 Daftar Endpoints

### 1. Mendapatkan Profil Pengguna (Get User Profile)
Mengambil informasi saldo utama, total penarikan bulanan, total komisi bulanan, serta **Saldo Tertahan (Pending Commission)** milik teknisi.

- **URL**: `GET /api/user-profile/{kode_user}`
- **Route Parameter**:
  - `kode_user` (integer/string): ID user (teknisi) yang ingin diambil datanya.
- **Response (200 OK)**:
  ```json
  {
    "kode_user": "3",
    "saldo": 450000,
    "saldo_tertahan": 150000,
    "total_penarikan": 200000,
    "total_penarikan_cash": 100000,
    "total_penarikan_transfer": 100000,
    "total_komisi": 800000
  }
  ```

#### Penjelasan Field Response:
| Field | Tipe | Keterangan |
|---|---|---|
| `kode_user` | string | ID unik user |
| `saldo` | numeric | **Saldo Utama (Bisa Ditarik)**. Dana dari servis yang sudah selesai dan diambil/dilunasi pelanggan. |
| `saldo_tertahan` | numeric | **Saldo Pending (Baru Ditambahkan)**. Komisi dari servis yang statusnya sudah `Selesai` namun belum diambil/dilunasi pelanggan. |
| `total_penarikan` | numeric | Total penarikan gaji yang disetujui dalam bulan ini. |
| `total_penarikan_cash` | numeric | Total penarikan tunai (cash) bulan ini. |
| `total_penarikan_transfer` | numeric | Total penarikan non-tunai (transfer bank) bulan ini. |
| `total_komisi` | numeric | Total komisi kotor yang didapatkan dalam bulan ini (termasuk yang tertahan). |

---

### 2. Daftar Karyawan (Get Employees list)
Mendapatkan daftar karyawan (jabatan selain admin/owner) yang berada di bawah upline/owner yang sedang login. Digunakan oleh Admin/Owner.

- **URL**: `GET /api/karyawan`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Data karyawan ditemukan.",
    "data": [
      {
        "id": 3,
        "fullname": "Ahmad Teknisi",
        "saldo": 450000,
        "jabatan": "3",
        "id_upline": 1
      },
      {
        "id": 4,
        "fullname": "Siti Kasir",
        "saldo": 0,
        "jabatan": "2",
        "id_upline": 1
      }
    ]
  }
  ```

---

### 3. Pengajuan Penarikan Saldo oleh Karyawan (Employee Withdrawal Request)
Mengajukan penarikan dari saldo utama milik karyawan yang sedang login. 

- **URL**: `POST /api/penarikan`
- **Request Body (JSON)**:
  ```json
  {
    "jumlah_penarikan": 100000,
    "metode_penarikan": "transfer",
    "catatan_penarikan": "Tarik gaji mingguan"
  }
  ```

- **Parameters Detail**:
  | Field | Tipe | Wajib | Keterangan |
  |---|---|---|---|
  | `jumlah_penarikan` | numeric | ✅ | Nominal yang ingin ditarik. Harus lebih kecil atau sama dengan `saldo` utama. |
  | `metode_penarikan` | string | ❌ | Pilihan: `cash`, `transfer`, `split` (default: `cash`). |
  | `penarikan_cash` | numeric | ❌ | Nominal cash (jika memilih metode `split`). |
  | `penarikan_transfer` | numeric | ❌ | Nominal transfer (jika memilih metode `split`). |
  | `catatan_penarikan` | string | ❌ | Catatan/pesan penarikan. |

- **Response (201 Created)**:
  ```json
  {
    "status": "success",
    "data": {
      "id": 12,
      "kode_penarikan": "PEN2026052413",
      "kode_user": "3",
      "kode_owner": "1",
      "jumlah_penarikan": 100000,
      "jumlah_cash": 0,
      "jumlah_transfer": 100000,
      "metode_penarikan": "transfer",
      "catatan_penarikan": "Tarik gaji mingguan",
      "status_penarikan": "0",
      "dari_saldo": 450000,
      "tgl_penarikan": "2026-05-24 06:15:00",
      "updated_at": "2026-05-24T06:15:00.000000Z",
      "created_at": "2026-05-24T06:15:00.000000Z"
    }
  }
  ```

---

### 4. Penarikan Saldo Karyawan oleh Admin (Admin Withdraws for Employee)
Pencairan saldo karyawan secara langsung yang diinisiasi oleh Admin/Owner.

- **URL**: `POST /api/admin/penarikan-karyawan`
- **Request Body (JSON)**:
  ```json
  {
    "kode_user": 3,
    "jumlah_penarikan": 50000,
    "metode_penarikan": "cash",
    "id_kategorilaci": 2,
    "catatan_penarikan": "Bantuan kasbon darurat"
  }
  ```

- **Parameters Detail**:
  | Field | Tipe | Wajib | Keterangan |
  |---|---|---|---|
  | `kode_user` | integer | ✅ | ID User (karyawan) yang saldonya ingin ditarik. |
  | `jumlah_penarikan` | numeric | ✅ | Nominal pencairan. |
  | `metode_penarikan` | string | ❌ | `cash`, `transfer`, `split`. |
  | `id_kategorilaci` | integer | ❌ | ID Laci kas toko (Wajib jika ada pengeluaran tunai/`cash` agar kas laci terpotong). |
  | `catatan_penarikan` | string | ❌ | Catatan admin. |

- **Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 13,
      "kode_penarikan": "ADM2026052413",
      "kode_user": "3",
      "kode_owner": "1",
      "jumlah_penarikan": 50000,
      "jumlah_cash": 50000,
      "jumlah_transfer": 0,
      "metode_penarikan": "cash",
      "catatan_penarikan": "Bantuan kasbon darurat",
      "status_penarikan": "1",
      "dari_saldo": 350000,
      "admin_withdrawal": true,
      "admin_id": 1,
      "tgl_penarikan": "2026-05-24 06:20:00"
    }
  }
  ```

---

### 5. Riwayat Penarikan Mandiri Karyawan (Employee Withdrawal History)
Mengambil riwayat penarikan saldo milik karyawan yang sedang login saat ini.

- **URL**: `GET /api/employee-withdrawal-history`
- **Query Parameters**:
  - `month` (integer, opsional): Filter angka bulan (e.g. `5` untuk Mei).
  - `year` (integer, opsional): Filter tahun (e.g. `2026`).
  - `per_page` (integer, opsional): Limit paginasi (default: `15`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 12,
          "kode_penarikan": "PEN2026052413",
          "jumlah_penarikan": 100000,
          "catatan_penarikan": "Tarik gaji mingguan",
          "tgl_penarikan": "2026-05-24 06:15:00",
          "dari_saldo": 450000,
          "status_penarikan": "0",
          "name_laci": null,
          "catatan_admin": null,
          "assigned_by_name": null,
          "assigned_at": null,
          "created_at": "2026-05-24 06:15:00",
          "saldo_setelah": 350000
        }
      ],
      "first_page_url": "http://domain.com/api/employee-withdrawal-history?page=1",
      "from": 1,
      "last_page": 1,
      "last_page_url": "http://domain.com/api/employee-withdrawal-history?page=1",
      "next_page_url": null,
      "path": "http://domain.com/api/employee-withdrawal-history",
      "per_page": 15,
      "prev_page_url": null,
      "to": 1,
      "total": 1
    },
    "stats": {
      "total_amount_this_month": 100000,
      "total_count_this_month": 1,
      "current_balance": 350000
    }
  }
  ```

---

## 💡 Petunjuk Integrasi Aplikasi Mobile (Flutter/React Native)
1. **Dashboard Utama Teknisi**:
   * Panggil endpoint `GET /api/user-profile/{id_teknisi}` secara berkala untuk menampilkan panel saldo.
   * Tampilkan label **"Saldo Utama"** menggunakan field `saldo` dan label **"Saldo Tertahan"** menggunakan field `saldo_tertahan` yang berwarna oranye/kuning sebagai indikasi dana pending.
2. **Form Penarikan Dana**:
   * Batasi input nominal penarikan agar maksimal sebesar nilai `saldo` (bukan akumulasi dengan `saldo_tertahan`) agar pengajuan tidak ditolak oleh validasi server.
