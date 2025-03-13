<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\Vendor;
use App\Models\Diskon;
use App\Models\Member;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {

        $this->call(UserSeeder::class);
        // Kategori::factory()->count(5)->create();
        $this->call(KategoriSeeder::class);
        $this->call(SatuanSeeder::class);
        $this->call(VendorSeeder::class);
        $this->call(DiskonSeeder::class);
        $this->call(VoucherSeeder::class);
        $this->call(MemberSeeder::class);
        // Barang::factory()->count(10)->create();

    }
}
