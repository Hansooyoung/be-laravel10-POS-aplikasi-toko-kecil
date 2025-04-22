<?php

namespace Tests\Feature;

use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\AuthHelper;
use Tests\TestCase;

class KategoriControllerTest extends TestCase
{
    // Traits used in this test class
    use RefreshDatabase, AuthHelper; // RefreshDatabase ensures a clean database for each test
                                     // AuthHelper provides authentication helper methods

    /**
     * Test that admin can create a new category
     * @test
     */
    public function admin_dapat_menyimpan_kategori_baru()
    {
        // Send POST request to create a new category with admin auth
        $response = $this->withAdminAuth()
            ->postJson('/api/kategori', [
                'nama_kategori' => 'Kategori 1'
            ]);

        // Assert the response status is HTTP 201 (Created)
        $response->assertStatus(Response::HTTP_CREATED)
            // Assert the response contains the expected JSON structure
            ->assertJson([
                'message' => 'Kategori berhasil ditambahkan',
                'data' => ['nama_kategori' => 'Kategori 1']
            ]);

        // Verify the category was actually saved to the database
        $this->assertDatabaseHas('kategori', ['nama_kategori' => 'Kategori 1']);
    }

    /**
     * Test that category creation requires a name
     * @test
     */
    public function menyimpan_kategori_memerlukan_nama_kategori()
    {
        // Send POST request without required 'nama_kategori' field
        $response = $this->withAdminAuth()
            ->postJson('/api/kategori', []);

        // Assert the response status is HTTP 422 (Unprocessable Entity)
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            // Assert the response contains validation error for 'nama_kategori'
            ->assertJsonValidationErrors(['nama_kategori']);
    }

    /**
     * Test that category name must be unique
     * @test
     */
    public function nama_kategori_harus_unik()
    {
        // Create a category first
        Kategori::create(['nama_kategori' => 'Kategori 2']);

        // Try to create another category with the same name
        $response = $this->withAdminAuth()
            ->postJson('/api/kategori', [
                'nama_kategori' => 'Kategori 2'
            ]);

        // Assert the response contains validation error for duplicate name
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['nama_kategori']);
    }

    /**
     * Test that category name cannot exceed 255 characters
     * @test
     */
    public function nama_kategori_tidak_boleh_melebihi_255_karakter()
    {
        // Create a string with 256 characters
        $response = $this->withAdminAuth()
            ->postJson('/api/kategori', [
                'nama_kategori' => str_repeat('a', 256)
            ]);

        // Assert the response contains validation error for length
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['nama_kategori']);
    }

    /**
     * Test that admin can create multiple unique categories
     * @test
     */
    public function admin_dapat_menyimpan_beberapa_kategori_unik()
    {
        // Array of categories to create
        $kategoris = [
            ['nama_kategori' => 'Kategori 1'],
            ['nama_kategori' => 'Kategori 2'],
            ['nama_kategori' => 'Kategori 3']
        ];

        // Loop through each category and create it
        foreach ($kategoris as $kategori) {
            $response = $this->withAdminAuth()
                ->postJson('/api/kategori', $kategori);
            $response->assertStatus(Response::HTTP_CREATED);
        }

        // Verify all 3 categories were created
        $this->assertCount(3, Kategori::all());
    }

    /**
     * Test that admin can view category details
     * @test
     */
    public function admin_dapat_melihat_detail_kategori()
    {
        // Create a test category
        $kategori = Kategori::create(['nama_kategori' => 'Kategori Show']);

        // Request the category details
        $response = $this->withAdminAuth()
            ->getJson("/api/kategori/{$kategori->id}");

        // Assert the response contains the correct data
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Kategori berhasil ditemukan',
                'data' => ['nama_kategori' => 'Kategori Show']
            ]);
    }

    /**
     * Test that viewing non-existent category returns 404
     * @test
     */
    public function menampilkan_404_untuk_kategori_tidak_ada()
    {
        // Request a non-existent category ID
        $response = $this->withAdminAuth()
            ->getJson('/api/kategori/999');

        // Assert 404 Not Found response
        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Kategori tidak ditemukan'
            ]);
    }

    /**
     * Test that admin can update a category
     * @test
     */
    public function admin_dapat_memperbarui_kategori()
    {
        // Create a category to update
        $kategori = Kategori::create(['nama_kategori' => 'Kategori Lama']);

        // Send PUT request to update the category
        $response = $this->withAdminAuth()
            ->putJson("/api/kategori/{$kategori->id}", [
                'nama_kategori' => 'Kategori Baru'
            ]);

        // Assert the response indicates successful update
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Kategori berhasil diperbarui',
                'data' => ['nama_kategori' => 'Kategori Baru']
            ]);

        // Verify the database was actually updated
        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'nama_kategori' => 'Kategori Baru'
        ]);
    }

    /**
     * Test that category name must be unique when updating
     * @test
     */
    public function update_kategori_harus_nama_unik()
    {
        // Create two categories
        Kategori::create(['nama_kategori' => 'Kategori Lain']);
        $kategori = Kategori::create(['nama_kategori' => 'Kategori Lama']);

        // Try to update second category to have same name as first
        $response = $this->withAdminAuth()
            ->putJson("/api/kategori/{$kategori->id}", [
                'nama_kategori' => 'Kategori Lain'
            ]);

        // Assert validation error for duplicate name
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['nama_kategori']);
    }

}