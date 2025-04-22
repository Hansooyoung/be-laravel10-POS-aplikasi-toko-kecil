<?php

namespace App\Imports;

use App\Models\Kategori;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class KategoriImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Handle different possible column names
        $namaKategori = $row['nama_kategori'] ?? $row['nama'] ?? $row['kategori'] ?? null;

        // Skip if required data is missing
        if (empty($namaKategori)) {
            return null;
        }

        return new Kategori([
            'nama_kategori' => $namaKategori,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
            '*.nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',

            // Alternative column name rules
            'nama' => 'sometimes|required|string|max:255|unique:kategori,nama_kategori',
            'kategori' => 'sometimes|required|string|max:255|unique:kategori,nama_kategori',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi',
            'nama_kategori.unique' => 'Nama kategori sudah ada dalam database',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter',

            'nama.required' => 'Kolom nama wajib diisi',
            'kategori.required' => 'Kolom kategori wajib diisi',
        ];
    }
}