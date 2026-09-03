# Dokumentasi API Manajemen Produk (Sparepart, Foto & Varian)

Dokumentasi ini menjelaskan API untuk mengelola data produk (sparepart), varian produk, atribut, dan foto pendukung produk (maksimal 5 foto per produk) yang disimpan dalam kolom `foto_sparepart` berformat JSON.

---

## 🔒 Persyaratan & Keamanan (Authentication)
Semua request harus menyertakan Header berikut:
- **Authorization**: `Bearer {token_sanctum}`
- **Accept**: `application/json`

**Pencegahan Transaksi**:
1. Pengguna harus memiliki langganan aktif (`subscribed.api` middleware).
2. Shift kasir harus dalam keadaan terbuka (`Shift::getActiveShift()` check). Jika shift belum dibuka, API akan mengembalikan status `403 Forbidden`.

---

## 📂 Daftar Endpoints

### 1. Mendapatkan Daftar Produk (List Products)
Mengambil daftar produk milik owner beserta varian dan atributnya. Mendukung pencarian, filter, dan paginasi.

- **URL**: `GET /api/products`
- **Query Parameters**:
  - `q` (string, opsional): Pencarian nama produk atau kode produk.
  - `category_id` (integer, opsional): Filter ID kategori.
  - `supplier_id` (integer, opsional): Filter ID supplier.
  - `is_active` (boolean, opsional): Filter status aktif produk (`true`/`false`). Jika tidak dikirim, hanya produk aktif yang ditampilkan (default behavior).
  - `category_is_active` (boolean, opsional): Filter status aktif kategori (`true`/`false`).
  - `limit` (integer, opsional): Jumlah data per halaman (default: `15`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "kode_sparepart": "SP202605201200001",
        "nama_sparepart": "Oli Shell Helix HX7",
        "desc_sparepart": "Oli mesin bensin semi sintetik",
        "stok_sparepart": 20,
        "harga_beli": 75000,
        "harga_jual": 95000,
        "harga_ecer": 90000,
        "harga_pasang": 10000,
        "is_active": true,
        "main_photo": "20260520_shell1.jpg",
        "main_photo_url": "http://domain.com/public/uploads/20260520_shell1.jpg",
        "photos": [
          {
            "id": 0,
            "photo_path": "20260520_shell1.jpg",
            "url": "http://domain.com/public/uploads/20260520_shell1.jpg"
          },
          {
            "id": 1,
            "photo_path": "20260520_shell2.jpg",
            "url": "http://domain.com/public/uploads/20260520_shell2.jpg"
          }
        ],
        "variants": [
          {
            "variant_id": 10,
            "sku": "SP202605201200001",
            "display_name": "Oli Shell Helix HX7 - 1 Liter",
            "stock": 15,
            "prices": {
              "purchase": 75000,
              "wholesale": 90000,
              "retail": 95000,
              "internal": 95000
            },
            "attributes": [
              {
                "attribute_id": 1,
                "name": "Ukuran",
                "value": "1 Liter"
              }
            ]
          },
          {
            "variant_id": 11,
            "sku": "SP202605201200001-4L",
            "display_name": "Oli Shell Helix HX7 - 4 Liter",
            "stock": 5,
            "prices": {
              "purchase": 280000,
              "wholesale": 330000,
              "retail": 350000,
              "internal": 350000
            },
            "attributes": [
              {
                "attribute_id": 1,
                "name": "Ukuran",
                "value": "4 Liter"
              }
            ]
          }
        ],
        "kategori": {
          "id": 2,
          "nama_kategori": "Oli",
          "is_active": true
        },
        "supplier": {
          "id": 1,
          "nama_supplier": "PT. Shell Indonesia"
        },
        "created_at": "2026-05-20T19:00:00.000000Z",
        "updated_at": "2026-05-20T19:00:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "count": 1,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 1,
      "has_more_pages": false
    }
  }
  ```

---

### 2. Mendapatkan Detail Produk (Get Single Product)
Mengambil informasi lengkap untuk satu produk tertentu beserta semua variannya.

- **URL**: `GET /api/products/{id}`
- **Response (200 OK)**:
  *(Struktur data response sama dengan satu object di dalam `data` pada endpoint List Products)*
- **Response (404 Not Found)**:
  ```json
  {
    "success": false,
    "message": "Produk tidak ditemukan."
  }
  ```

---

### 3. Menambah Produk Baru (Create Product)
Menambahkan produk baru ke sistem, sekaligus secara otomatis mendaftarkan varian utamanya di `product_variants`. Mendukung upload sampai dengan 5 foto.

- **URL**: `POST /api/products`
- **Content-Type**: `multipart/form-data`
- **Request Body**:

  | Field | Tipe | Wajib | Keterangan |
  |---|---|---|---|
  | `nama_sparepart` | string | ✅ | Nama produk |
  | `kode_kategori` | integer | ✅ | ID kategori |
  | `desc_sparepart` | string | ❌ | Deskripsi produk |
  | `stok_sparepart` | integer | ✅ | Jumlah stok awal (min: 0) |
  | `harga_beli` | numeric | ✅ | Harga beli (min: 0) |
  | `harga_jual` | numeric | ✅ | Harga jual (min: 0) |
  | `harga_ecer` | numeric | ❌ | Harga ecer/grosir |
  | `harga_pasang` | numeric | ✅ | Harga jasa pasang (min: 0) |
  | `kode_spl` | integer | ❌ | ID supplier |
  | `is_active` | boolean | ❌ | Status aktif (default: `1`) |
  | `foto_sparepart` | file | ❌ | Foto utama produk (maks 2MB) |
  | `photos[]` | file[] | ❌ | List foto pendukung (maks 5, maks 2MB/file) |

- **Response (201 Created)**:
  ```json
  {
    "success": true,
    "message": "Produk berhasil ditambahkan.",
    "data": { "...objek produk lengkap dengan variants..." }
  }
  ```

---

### 4. Memperbarui Produk (Update Product)
Memperbarui detail metadata produk, harga, stok, dan memodifikasi foto (menghapus foto lama berdasarkan index dan/atau mengunggah foto baru).

> **Catatan**: Karena PHP native tidak mem-parsing `multipart/form-data` pada request bertipe `PUT`/`PATCH`, kirimkan request menggunakan metode **`POST`** dengan menyertakan field data **`_method`** bernilai **`PUT`**.

- **URL**: `POST /api/products/{id}`
- **Content-Type**: `multipart/form-data`
- **Request Body**:

  | Field | Tipe | Wajib | Keterangan |
  |---|---|---|---|
  | `_method` | string | ✅ | Isi dengan `PUT` |
  | `nama_sparepart` | string | ❌ | Nama produk baru |
  | `kode_kategori` | integer | ❌ | ID kategori baru |
  | `desc_sparepart` | string | ❌ | Deskripsi baru |
  | `stok_sparepart` | integer | ❌ | Jumlah stok baru |
  | `harga_beli` | numeric | ❌ | Harga beli baru |
  | `harga_jual` | numeric | ❌ | Harga jual baru |
  | `harga_ecer` | numeric | ❌ | Harga ecer baru |
  | `harga_pasang` | numeric | ❌ | Harga jasa pasang baru |
  | `kode_spl` | integer | ❌ | ID supplier baru |
  | `is_active` | boolean | ❌ | Status aktif |
  | `delete_photo_ids[]` | int[] | ❌ | Index foto yang dihapus (dimulai dari `0`) |
  | `foto_sparepart` | file | ❌ | Foto tambahan baru |
  | `photos[]` | file[] | ❌ | File foto tambahan baru |

- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Produk berhasil diupdate.",
    "data": { "...objek produk lengkap dengan variants..." }
  }
  ```

---

### 5. Menghapus Produk (Delete Product)
Menghapus data produk, semua varian, dan semua file foto yang tersimpan di server.

- **URL**: `DELETE /api/products/{id}`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Produk berhasil dihapus."
  }
  ```

---

### 6. Toggle Visibilitas Produk (Toggle Product Visibility)
Mengubah status tampil/sembunyi (is_active) sebuah produk. Produk yang disembunyikan tidak akan muncul di halaman storefront.

- **URL**: `PUT /api/products/{id}/toggle-visibility`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Produk ditampilkan.",
    "data": {
      "id": 1,
      "nama_sparepart": "Oli Shell Helix HX7",
      "is_active": true
    }
  }
  ```
- **Response (404 Not Found)**:
  ```json
  {
    "success": false,
    "message": "Produk tidak ditemukan."
  }
  ```

---

### 7. Daftar Kategori Produk (List Product Categories)
Mengambil daftar semua kategori produk milik owner beserta jumlah produk di masing-masing kategori dan status visibilitasnya.

- **URL**: `GET /api/products/categories`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "nama_kategori": "LCD",
        "foto_kategori": "20260520_lcd.jpg",
        "foto_url": "http://domain.com/uploads/20260520_lcd.jpg",
        "is_active": true,
        "spareparts_count": 15
      },
      {
        "id": 2,
        "nama_kategori": "Baterai",
        "foto_kategori": "-",
        "foto_url": null,
        "is_active": false,
        "spareparts_count": 8
      }
    ]
  }
  ```

---

### 8. Toggle Visibilitas Kategori (Toggle Category Visibility)
Mengubah status tampil/sembunyi (is_active) sebuah kategori. Kategori yang disembunyikan beserta seluruh produknya tidak akan muncul di halaman storefront.

- **URL**: `PUT /api/products/categories/{id}/toggle-visibility`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Kategori ditampilkan.",
    "data": {
      "id": 1,
      "nama_kategori": "LCD",
      "is_active": true
    }
  }
  ```
- **Response (404 Not Found)**:
  ```json
  {
    "success": false,
    "message": "Kategori tidak ditemukan."
  }
  ```

---

## 📦 Struktur Objek Varian dalam Response

Setiap produk dapat memiliki satu atau lebih varian. Setiap varian berisi informasi harga, stok, SKU, dan atribut-atribut yang membedakannya.

```json
{
  "variant_id": 10,
  "sku": "SP202605201200001",
  "display_name": "Oli Shell Helix HX7 - 1 Liter",
  "stock": 15,
  "prices": {
    "purchase": 75000,
    "wholesale": 90000,
    "retail": 95000,
    "internal": 95000
  },
  "attributes": [
    {
      "attribute_id": 1,
      "name": "Ukuran",
      "value": "1 Liter"
    }
  ]
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `variant_id` | integer | ID unik varian |
| `sku` | string | Stock Keeping Unit |
| `display_name` | string | Nama produk + atribut (contoh: "Oli Shell - 1L") |
| `stock` | integer | Stok varian saat ini |
| `prices.purchase` | integer | Harga beli |
| `prices.wholesale` | integer | Harga grosir/ecer |
| `prices.retail` | integer | Harga retail |
| `prices.internal` | integer | Harga internal (jual toko) |
| `attributes` | array | Daftar atribut (nama & nilai) |

---

## 👁️ Manajemen Visibilitas (Visibility Management)

Fitur ini memungkinkan pemilik toko mengontrol produk dan kategori mana yang ditampilkan di halaman storefront publik.

### Logika Visibilitas:
1. **Produk** memiliki field `is_active` (boolean). Produk dengan `is_active = false` **tidak akan muncul** di storefront dan di sebagian besar query API (kecuali jika secara eksplisit difilter dengan `is_active=false`).
2. **Kategori** juga memiliki field `is_active` (boolean). Jika kategori di-set `is_active = false`, **semua produk** dalam kategori tersebut tidak akan ditampilkan di storefront, meskipun produk individual-nya masih `is_active = true`.
3. **Toggle vs Set**: Endpoint `toggle-visibility` akan **membalikkan** status saat ini (aktif→nonaktif, atau sebaliknya). Untuk set status secara eksplisit, gunakan endpoint **Update Product** dengan parameter `is_active`.

### Contoh Skenario:
```
# Sembunyikan produk tertentu
PUT /api/products/42/toggle-visibility

# Sembunyikan seluruh kategori "Baterai" (ID: 5)
PUT /api/products/categories/5/toggle-visibility

# Lihat semua produk termasuk yang tersembunyi
GET /api/products?is_active=false

# Lihat semua kategori beserta statusnya
GET /api/products/categories
```

---

## 🔄 Integrasi dengan Alur Pembelian (Pembelian)

Ketika melakukan transaksi pembelian barang baru/restok melalui endpoint:
- **`POST /api/pembelian/{id}/finalize`**

Sistem akan otomatis:
1. Jika item tersebut baru (`is_new_item = true`), produk baru + varian baru terdaftar otomatis di database dengan `foto_sparepart` default `"-"`.
2. Jika restok, stok dan harga beli rata-rata tertimbang (WAC) diperbarui otomatis pada varian dan produk terkait.

**Mengedit Produk Hasil Pembelian**:
Karena produk hasil pembelian disimpan pada tabel `spareparts` yang sama, Anda dapat langsung mengedit produk tersebut kapan saja menggunakan endpoint **Update Product**:

```
POST /api/products/{product_id}
Content-Type: multipart/form-data

_method=PUT
nama_sparepart=Nama Baru
photos[]=<file_foto_baru>
```

Contoh use case:
- Mengubah nama produk yang salah ketik saat pembelian
- Menambahkan foto produk yang belum sempat diupload
- Menghapus foto lama dan menggantinya dengan foto baru

