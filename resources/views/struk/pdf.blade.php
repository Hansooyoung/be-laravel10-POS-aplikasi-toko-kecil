<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            width: 80mm;
            margin: 0;
            padding: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 5px;
        }
        .title {
            font-weight: bold;
            font-size: 14px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">TOKO SUPERMARKET</div>
        <div>Jl. Contoh No. 123, Kota</div>
        <div class="divider"></div>
    </div>

    <div>
        <div>Tanggal: {{ date('d/m/Y H:i:s', strtotime($penjualan->tanggal_penjualan)) }}</div>
        <div>No. Transaksi: {{ $penjualan->id }}</div>
        <div class="divider"></div>

        @foreach($penjualan->detailPenjualan as $item)
        <div class="item">
            <div>{{ $item->barang->nama_barang }}</div>
        </div>
        <div class="item">
            <div>{{ $item->jumlah }} x {{ number_format($item->harga_jual, 0, ',', '.') }}</div>
            <div>{{ number_format($item->sub_total, 0, ',', '.') }}</div>
        </div>
        @endforeach

        <div class="divider"></div>
        <div class="item">
            <div>Total:</div>
            <div>{{ number_format($penjualan->total_penjualan, 0, ',', '.') }}</div>
        </div>

        @if($penjualan->member)
        <div class="item">
            <div>Member:</div>
            <div>{{ $penjualan->member->nama }}</div>
        </div>
        @endif

        @if($penjualan->voucher)
        <div class="item">
            <div>Voucher:</div>
            <div>{{ $penjualan->voucher->nama_voucher }}</div>
        </div>
        @endif

        <div class="item">
            <div>Tunai:</div>
            <div>{{ number_format($tunai, 0, ',', '.') }}</div>
        </div>

        <div class="item">
            <div>Kembalian:</div>
            <div>{{ number_format($kembalian, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="divider"></div>
        <div>TERIMA KASIH</div>
        <div class="divider"></div>
    </div>
</body>
</html>