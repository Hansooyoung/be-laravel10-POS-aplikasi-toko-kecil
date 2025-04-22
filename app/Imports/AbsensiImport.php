<?php

namespace App\Imports;

use App\Models\Absensi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AbsensiImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Pastikan data yang diperlukan ada
        if (empty($row['user_id']) || empty($row['tanggal'])) {
            return null;
        }

        return new Absensi([
            'user_id' => $row['user_id'],
            'tanggal' => Carbon::parse($row['tanggal'])->format('Y-m-d'), // Pastikan format tanggal sesuai
            'jam_masuk' => $row['jam_masuk'] ?? null,
            'jam_keluar' => $row['jam_keluar'] ?? null,
            'status' => $row['status'],
            'keterangan' => $row['keterangan'] ?? null,
        ]);
    }
}
