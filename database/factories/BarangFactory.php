<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Diskon;
use App\Models\Satuan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Barang>
 */
class BarangFactory extends Factory
{
    protected $model = Barang::class;


    private static $kodeBarangCounter = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $kode = 'BRG' . date('Y') . (10000 + self::$kodeBarangCounter++);

        $diskonId = $this->faker->boolean(70) ? Diskon::inRandomOrder()->value('id') : null;

        return [
            'kode_barang' => $kode,
            'kategori_id' => Kategori::inRandomOrder()->value('id'),
            'user_id'     => User::inRandomOrder()->value('id'),
            'diskon_id'   => $diskonId,
            'satuan_id'   => Satuan::inRandomOrder()->value('id'),
            'barcode'     => $this->faker->unique()->ean13,
            'nama_barang' => $this->faker->word,
            'status'      => $this->faker->randomElement(['Aktif', 'Tidak']),
            'profit_persen' => $this->faker->randomFloat(2, 5, 20),
            'harga_beli'    => $this->faker->randomFloat(2, 10000, 100000),
            'gambar'        => $this->faker->imageUrl(),
            'stok'          => $this->faker->numberBetween(0, 100),
        ];
    }
}
