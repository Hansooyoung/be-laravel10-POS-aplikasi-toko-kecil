<?php

namespace App\Services;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;
use Exception;

class PrinterService
{
    public function printReceipt($data)
    {
        $connector = null;
        $printer = null;

        try {
            // Koneksi ke printer USB
            $connector = new FilePrintConnector("POS-50");
            $printer = new Printer($connector);

            /* Header Struk */
            $this->printHeader($printer);

            /* Informasi Transaksi */
            $this->printTransactionInfo($printer, $data);

            /* Daftar Barang */
            $this->printItems($printer, $data['items']);

            /* Total Pembayaran */
            $this->printPaymentInfo($printer, $data);

            /* Footer */
            $this->printFooter($printer, $data);

            $printer->cut(Printer::CUT_PARTIAL);

            return ['success' => true, 'message' => 'Struk berhasil dicetak'];

        } catch (Exception $e) {
            \Log::error('Printer Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mencetak struk: ' . $e->getMessage()];
        } finally {
            // Pastikan printer dan konektor ditutup bahkan jika terjadi error
            if ($printer !== null) {
                $printer->close();
            }
            if ($connector !== null) {
                $connector->finalize();
            }
        }
    }

    protected function printHeader($printer)
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);

        try {
            $logo = EscposImage::load(resource_path('images/logo.png'), false);
            $printer->bitImage($logo);
        } catch (Exception $e) {
            // Skip logo jika error
        }

        $printer->setTextSize(2, 2);
        $printer->text("TOKO ANDA\n");
        $printer->setTextSize(1, 1);
        $printer->text("Jl. Contoh No. 123\n");
        $printer->text("Telp: 08123456789\n");
        $printer->feed();
    }

    protected function printTransactionInfo($printer, $data)
    {
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        // Ganti kode_transaksi dengan kode_penjualan
        $printer->text("No. Penjualan: " . $data['kode_penjualan'] . "\n");
        $printer->text("Tanggal    : " . $data['tanggal'] . "\n");
        $printer->text("Kasir      : " . $data['kasir'] . "\n");
        $printer->text("Member     : " . $data['member'] . "\n");
        $printer->feed();
        $printer->text("--------------------------------\n");
    }

    protected function printItems($printer, $items)
    {
        foreach ($items as $item) {
            $printer->text($this->formatItemRow(
                $item['nama'],
                $item['qty'],
                $item['harga'],
                $item['subtotal']
            ));
        }
        $printer->text("--------------------------------\n");
    }

    protected function printPaymentInfo($printer, $data)
    {
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->text("TOTAL   : " . number_format($data['total'], 0, ',', '.') . "\n");
        $printer->text("TUNAI   : " . number_format($data['tunai'], 0, ',', '.') . "\n");
        $printer->text("KEMBALI : " . number_format($data['kembalian'], 0, ',', '.') . "\n");
        $printer->feed(2);
    }

    protected function printFooter($printer, $data)
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima kasih telah berbelanja\n");
        $printer->text("Barang yang sudah dibeli\n");
        $printer->text("tidak dapat ditukar/dikembalikan\n");

        // Informasi tambahan
        $printer->feed();
        $printer->text("No. Penjualan: " . $data['kode_penjualan'] . "\n");
        $printer->text($data['tanggal'] . "\n");
        $printer->feed(3);
    }

    protected function formatItemRow($name, $qty, $price, $subtotal)
    {
        return sprintf("%-20s %3s x %8s = %10s\n",
            substr($name, 0, 20),
            str_pad($qty, 3, ' ', STR_PAD_LEFT),
            str_pad(number_format($price, 0, ',', '.'), 8, ' ', STR_PAD_LEFT),
            str_pad(number_format($subtotal, 0, ',', '.'), 10, ' ', STR_PAD_LEFT)
        );
    }
}
