<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\Sparepart;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\KategoriSparepart;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $userDetail;
    private $kategori;
    private $shift;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a user
        $this->user = User::factory()->create([
            'email' => 'testowner_' . uniqid() . '@example.com',
        ]);

        // 2. Create UserDetail
        $this->userDetail = UserDetail::create([
            'kode_user' => $this->user->id,
            'fullname' => 'Test Owner',
            'jabatan' => '0', // Super Admin (always active subscription)
            'id_upline' => $this->user->id, // Upline is itself
            'status_user' => '1',
            'foto_user' => '-',
            'alamat_user' => '-',
            'no_telp' => '-',
            'kode_invite' => '-',
            'saldo' => 0,
            'link_twitter' => '-',
            'link_facebook' => '-',
            'link_instagram' => '-',
            'link_linkedin' => '-',
        ]);

        // 3. Create a category
        $this->kategori = KategoriSparepart::create([
            'nama_kategori' => 'Test Category',
            'kode_owner' => $this->user->id,
            'foto_kategori' => '-',
        ]);

        // 4. Create and activate a shift
        $this->shift = Shift::create([
            'user_id' => $this->user->id,
            'kode_owner' => $this->user->id,
            'start_time' => now(),
            'status' => 'open',
            'modal_awal' => 100000,
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_products()
    {
        // Create dummy products
        $product = Sparepart::create([
            'kode_sparepart' => 'SP_TEST_LIST_1',
            'kode_kategori' => $this->kategori->id,
            'nama_sparepart' => 'Sparepart Test List',
            'desc_sparepart' => 'Description test',
            'harga_beli' => 10000,
            'harga_jual' => 20000,
            'harga_ecer' => 18000,
            'harga_pasang' => 25000,
            'stok_sparepart' => 10,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => '-',
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ])
            ->assertJsonFragment([
                'nama_sparepart' => 'Sparepart Test List',
            ]);
    }

    public function test_can_store_product_with_photos()
    {
        Storage::fake('public');

        // Create 3 fake photos
        $photo1 = UploadedFile::fake()->image('photo1.jpg');
        $photo2 = UploadedFile::fake()->image('photo2.jpg');
        $photo3 = UploadedFile::fake()->image('photo3.jpg');

        $payload = [
            'nama_sparepart' => 'Sparepart Test Store',
            'kode_kategori' => $this->kategori->id,
            'desc_sparepart' => 'Description test store',
            'stok_sparepart' => 15,
            'harga_beli' => 15000,
            'harga_jual' => 30000,
            'harga_ecer' => 28000,
            'harga_pasang' => 35000,
            'is_active' => 1,
            'photos' => [$photo1, $photo2],
            'foto_sparepart' => $photo3, // combined single upload
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan.',
            ]);

        $productData = $response->json('data');
        $this->assertEquals('Sparepart Test Store', $productData['nama_sparepart']);
        
        // Assert photos are uploaded and created in db (total of 3 photos)
        $this->assertCount(3, $productData['photos']);
        $this->assertNotEmpty($productData['main_photo']);
        $this->assertEquals($productData['main_photo'], $productData['photos'][0]['photo_path']);

        // Assert Variant was automatically created
        $this->assertDatabaseHas('product_variants', [
            'sparepart_id' => $productData['id'],
            'stock' => 15,
            'purchase_price' => 15000,
            'retail_price' => 30000,
        ]);
    }

    public function test_cannot_store_product_with_more_than_5_photos()
    {
        $photos = [];
        for ($i = 0; $i < 6; $i++) {
            $photos[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $payload = [
            'nama_sparepart' => 'Sparepart Photo Overload',
            'kode_kategori' => $this->kategori->id,
            'stok_sparepart' => 5,
            'harga_beli' => 1000,
            'harga_jual' => 2000,
            'harga_pasang' => 500,
            'photos' => $photos,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal.',
            ])
            ->assertJsonValidationErrors(['photos']);
    }

    public function test_can_update_product_and_modify_photos()
    {
        // 1. Create a product with 2 photos
        $product = Sparepart::create([
            'kode_sparepart' => 'SP_TEST_UPDATE',
            'kode_kategori' => $this->kategori->id,
            'nama_sparepart' => 'Sparepart Test Update',
            'harga_beli' => 10000,
            'harga_jual' => 20000,
            'harga_pasang' => 5000,
            'stok_sparepart' => 10,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => ['existing1.jpg', 'existing2.jpg'],
        ]);

        ProductVariant::create([
            'sparepart_id' => $product->id,
            'sku' => $product->kode_sparepart,
            'purchase_price' => 10000,
            'retail_price' => 20000,
            'stock' => 10,
        ]);

        // 2. Perform PUT request to delete the photo at index 0 (existing1.jpg) and add 2 new ones
        $newPhoto1 = UploadedFile::fake()->image('new1.jpg');
        $newPhoto2 = UploadedFile::fake()->image('new2.jpg');

        $payload = [
            'nama_sparepart' => 'Sparepart Updated Name',
            'delete_photo_ids' => [0], // delete photo 0 (existing1.jpg)
            'photos' => [$newPhoto1, $newPhoto2],  // add 2 new photos
        ];

        // Using POST with _method=PUT to support multipart/form-data with file uploads
        $response = $this->postJson("/api/products/{$product->id}", array_merge($payload, ['_method' => 'PUT']));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produk berhasil diupdate.',
            ]);

        $productData = $response->json('data');
        $this->assertEquals('Sparepart Updated Name', $productData['nama_sparepart']);

        // Photos assertion: original was 2, deleted 1, added 2. Total should be 3.
        $this->assertCount(3, $productData['photos']);
        
        // Assert existing2.jpg remains, existing1.jpg is deleted
        $freshPhotos = $product->fresh()->photos;
        $this->assertNotContains('existing1.jpg', $freshPhotos);
        $this->assertContains('existing2.jpg', $freshPhotos);
    }

    public function test_can_delete_product()
    {
        $product = Sparepart::create([
            'kode_sparepart' => 'SP_TEST_DELETE',
            'kode_kategori' => $this->kategori->id,
            'nama_sparepart' => 'Sparepart Test Delete',
            'harga_beli' => 10000,
            'harga_jual' => 20000,
            'harga_pasang' => 5000,
            'stok_sparepart' => 10,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => '-',
        ]);

        ProductVariant::create([
            'sparepart_id' => $product->id,
            'sku' => $product->kode_sparepart,
            'purchase_price' => 10000,
            'retail_price' => 20000,
            'stock' => 10,
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produk berhasil dihapus.',
            ]);

        $this->assertDatabaseMissing('spareparts', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['sparepart_id' => $product->id]);
    }

    public function test_search_products_with_multiple_keywords()
    {
        // 1. Create a dummy category for testing
        $kategori = KategoriSparepart::create([
            'nama_kategori' => 'LCD Test',
            'kode_owner' => $this->user->id,
            'foto_kategori' => '-',
        ]);

        // 2. Create products with names resembling the user's report
        // Product A (should match "lcd redmi note 14")
        $productA = Sparepart::create([
            'kode_sparepart' => 'SP_REDMI_NOTE_14',
            'kode_kategori' => $kategori->id,
            'nama_sparepart' => 'lcd redmi note 13 4G / note 14 4G / Note 14 5G',
            'harga_beli' => 100000,
            'harga_jual' => 150000,
            'harga_ecer' => 140000,
            'harga_pasang' => 20000,
            'stok_sparepart' => 5,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => '-',
        ]);

        // Product B (should NOT match "lcd redmi note 14" because it lacks "note")
        $productB = Sparepart::create([
            'kode_sparepart' => 'SP_REDMI_14',
            'kode_kategori' => $kategori->id,
            'nama_sparepart' => 'lcd redmi 14',
            'harga_beli' => 90000,
            'harga_jual' => 130000,
            'harga_ecer' => 120000,
            'harga_pasang' => 20000,
            'stok_sparepart' => 5,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => '-',
        ]);

        // Product C (should NOT match "lcd redmi note 14" because it lacks "redmi", "note", "14")
        $productC = Sparepart::create([
            'kode_sparepart' => 'SP_OPPO_A18',
            'kode_kategori' => $kategori->id,
            'nama_sparepart' => 'Lcd oppo a18',
            'harga_beli' => 100000,
            'harga_jual' => 130000,
            'harga_ecer' => 120000,
            'harga_pasang' => 20000,
            'stok_sparepart' => 2,
            'kode_owner' => $this->user->id,
            'is_active' => true,
            'foto_sparepart' => '-',
        ]);

        // 3. Search for "lcd redmi note 14"
        $response = $this->getJson('/api/products?q=lcd+redmi+note+14');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify only Product A matches (contains 'lcd', 'redmi', 'note', '14')
        $this->assertCount(1, $data);
        $this->assertEquals('lcd redmi note 13 4G / note 14 4G / Note 14 5G', $data[0]['nama_sparepart']);

        // 4. Search for "lcd redmi 14" (should match both Product A and Product B)
        $response2 = $this->getJson('/api/products?q=lcd+redmi+14');
        $response2->assertStatus(200);
        $data2 = $response2->json('data');

        $this->assertCount(2, $data2);
        $names = collect($data2)->pluck('nama_sparepart')->toArray();
        $this->assertContains('lcd redmi note 13 4G / note 14 4G / Note 14 5G', $names);
        $this->assertContains('lcd redmi 14', $names);
    }
}
