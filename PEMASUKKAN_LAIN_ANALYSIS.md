# Analisis: Pencatatan PemasukkanLain ke KasPerusahaan

## Kesimpulan Utama
**PemasukkanLain BELUM terintegrasi secara lengkap ke KasPerusahaan.** Tidak ada relasi polymorphic dan tidak ada observer yang mencatat transaksinya ke ledger kas.

---

## 1. LOKASI PEMBUATAN PemasukkanLain

**File:** [app/Http/Controllers/Admin/DashboardController.php](app/Http/Controllers/Admin/DashboardController.php#L267)

**Method:** `create_pemasukkan_lain()` (line 267-305)

```php
public function create_pemasukkan_lain(Request $request)
{
    $validate = $request->validate([
        'tgl_pemasukan' => ['required'],
        'jumlah_pemasukan' => ['required'],
    ]);

    if ($validate) {
        $create = PemasukkanLain::create([
            'tgl_pemasukkan' => $request->tgl_pemasukan,
            'judul_pemasukan' => $request->judul_pemasukan,
            'catatan_pemasukkan' => $request->catatan_pemasukan,
            'jumlah_pemasukkan' => $request->jumlah_pemasukan,
            'kode_owner' => $this->getThisUser()->id_upline,
            'shift_id' => Shift::getActiveShift(auth()->user()->id)->id ?? null,
        ]);

        // Mencatat histori laci (drawer/cash drawer) - BUKAN KAS PERUSAHAAN
        $kategoriId = $request->input('id_kategorilaci');
        $uangMasuk = $request->input('jumlah_pemasukan');
        $keterangan = $request->input('judul_pemasukan') . "-" . $request->input('catatan_pemasukan');
        
        $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);
        // ...
    }
}
```

**Masalah:** Di sini HANYA mencatat ke `laci` (drawer history), BUKAN ke KasPerusahaan!

---

## 2. STATUS MODEL PemasukkanLain

**File:** [app/Models/PemasukkanLain.php](app/Models/PemasukkanLain.php)

### STATUS: ❌ TIDAK ADA RELASI POLYMORPHIC

Model PemasukkanLain hanya memiliki `fillable` attributes, TANPA relasi ke KasPerusahaan:

```php
class PemasukkanLain extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_pemasukkan',
        'judul_pemasukan',
        'catatan_pemasukkan',
        'jumlah_pemasukkan',
        'kode_owner',
        'shift_id'
    ];
    // ❌ TIDAK ADA: public function kas() { return $this->morphOne(...); }
}
```

---

## 3. BANDINGKAN DENGAN MODEL LAIN YANG SUDAH TERINTEGRASI

### Model yang SUDAH memiliki morphOne ke KasPerusahaan:
- [app/Models/Pembelian.php](app/Models/Pembelian.php#L32)
- [app/Models/Penjualan.php](app/Models/Penjualan.php#L37)
- [app/Models/PengeluaranOperasional.php](app/Models/PengeluaranOperasional.php#L102)
- [app/Models/TransaksiModal.php](app/Models/TransaksiModal.php#L32)
- [app/Models/Aset.php](app/Models/Aset.php#L33)
- [app/Models/AlokasiLaba.php](app/Models/AlokasiLaba.php#L33)
- [app/Models/Penarikan.php](app/Models/Penarikan.php#L86)
- [app/Models/Sevices.php](app/Models/Sevices.php#L66)

**Contoh dari Pembelian:**
```php
public function kas()
{
    return $this->morphOne(KasPerusahaan::class, 'sourceable');
}
```

---

## 4. STRUKTUR KasPerusahaan DENGAN POLYMORPHIC

**File:** [app/Models/KasPerusahaan.php](app/Models/KasPerusahaan.php)

```php
class KasPerusahaan extends Model
{
    // Relasi polymorphic
    public function sourceable()
    {
        return $this->morphTo();
    }
    
    // Kolom untuk menyimpan relasi
    protected $fillable = [
        'sourceable_id',
        'sourceable_type',  // <-- Menyimpan "App\\Models\\PemasukkanLain"
        'kode_owner',
        'tanggal',
        'deskripsi',
        'debit',
        'kredit',
        'saldo',
        'shift_id',
    ];
}
```

---

## 5. POLA YANG SEHARUSNYA DIIMPLEMENTASIKAN

### A. Observer untuk PemasukkanLain
**Akan ditempatkan di:** `app/Observers/PemasukkanLainObserver.php` (BELUM ADA ❌)

Lihat contoh dari [app/Observers/PengeluaranOperasionalObserver.php](app/Observers/PengeluaranOperasionalObserver.php):

```php
class PemasukkanLainObserver
{
    public function created(PemasukkanLain $pemasukkanLain)
    {
        // Cek duplikasi
        $existingKas = KasPerusahaan::where('sourceable_id', $pemasukkanLain->id)
            ->where('sourceable_type', PemasukkanLain::class)
            ->exists();

        if (!$existingKas) {
            $this->catatKas($pemasukkanLain);
        }
    }

    public function updated(PemasukkanLain $pemasukkanLain)
    {
        // Update KasPerusahaan jika ada perubahan jumlah
        // ...
    }

    public function deleted(PemasukkanLain $pemasukkanLain)
    {
        // Hapus dari KasPerusahaan
        // ...
    }

    protected function catatKas($pemasukkanLain)
    {
        KasPerusahaan::create([
            'sourceable_id' => $pemasukkanLain->id,
            'sourceable_type' => PemasukkanLain::class,
            'kode_owner' => $pemasukkanLain->kode_owner,
            'tanggal' => $pemasukkanLain->tgl_pemasukkan,
            'deskripsi' => $pemasukkanLain->judul_pemasukan,
            'debit' => $pemasukkanLain->jumlah_pemasukkan,
            'kredit' => 0,
            'saldo' => /* hitung running balance */,
            'shift_id' => $pemasukkanLain->shift_id,
        ]);
    }
}
```

### B. Registrasi Observer
**File:** [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L43-L48)

Observer harus didaftarkan di `boot()` method:
```php
public function boot()
{
    // ...
    PemasukkanLain::observe(PemasukkanLainObserver::class);  // ❌ BELUM ADA
    // ...
}
```

---

## 6. TRAIT YANG DIGUNAKAN UNTUK MENCATAT KAS

**File:** [app/Traits/ManajemenKasTrait.php](app/Traits/ManajemenKasTrait.php#L23)

Method `catatKas()` yang digunakan oleh banyak controller:
```php
protected function catatKas(
    Model $sumberModel,      // Instansi PemasukkanLain, Penjualan, dll
    float $debit,            // Jumlah pemasukan
    float $kredit,           // Jumlah pengeluaran
    string $deskripsi,
    $tanggal = null
)
{
    DB::transaction(function () use ($sumberModel, $debit, $kredit, $deskripsi, $tanggal) {
        // Ambil saldo terakhir dengan lock untuk mencegah race condition
        $lastKas = KasPerusahaan::where('kode_owner', $sumberModel->kode_owner)
                            ->latest('id')
                            ->lockForUpdate()
                            ->first();
        
        $saldoTerakhir = $lastKas ? $lastKas->saldo : 0;
        $saldoBaru = $saldoTerakhir + $debit - $kredit;

        // Catat ke KasPerusahaan via polymorphic relation
        $sumberModel->kas()->create([
            'kode_owner' => $sumberModel->kode_owner,
            'tanggal' => $tanggal ?? now(),
            'deskripsi' => $deskripsi,
            'debit' => $debit,
            'kredit' => $kredit,
            'saldo' => $saldoBaru,
            'shift_id' => $shiftId,
        ]);
    });
}
```

---

## 7. RINGKASAN TEMUAN

| Aspek | Status | Lokasi |
|-------|--------|--------|
| **Model PemasukkanLain** | ❌ Tidak ada relasi `morphOne` | [app/Models/PemasukkanLain.php](app/Models/PemasukkanLain.php) |
| **Observer** | ❌ Tidak ada observer | - (Seharusnya: `app/Observers/PemasukkanLainObserver.php`) |
| **Registrasi Observer** | ❌ Tidak terdaftar | [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php#L48) |
| **Pencatatan KAS** | ✅ Ada hanya laci history | [app/Http/Controllers/Admin/DashboardController.php#L267](app/Http/Controllers/Admin/DashboardController.php#L267) |
| **Controller mencatat KAS** | ❌ Tidak ada | `create_pemasukkan_lain()` hanya catat laci |

---

## 8. REKOMENDASI

Untuk membuat PemasukkanLain terintegrasi penuh dengan KasPerusahaan seperti model lainnya:

1. **Tambah relasi di model PemasukkanLain:**
   ```php
   public function kas()
   {
       return $this->morphOne(KasPerusahaan::class, 'sourceable');
   }
   ```

2. **Buat Observer PemasukkanLainObserver** mengikuti pola PengeluaranOperasionalObserver

3. **Daftarkan observer** di AppServiceProvider

4. **Hapus logika catatKas dari controller** (akan auto-handled oleh observer)

---

**Dibuat:** 12 Maret 2026
