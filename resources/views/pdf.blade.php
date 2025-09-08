<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Invoice Global Photo Rental</title>

  <style>
    /* Reset & Base Styling */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.4;
      color: #000;
      background-color: #fff;
    }

    /* Main Container */
    .invoice-container {
      max-width: 210mm;
      margin: 0 auto;
      padding: 12mm;
      background-color: #fff;
    }

    /* Grid System */
    .row {
      display: table;
      width: 100%;
      margin-bottom: 8px;
    }

    .col {
      display: table-cell;
      vertical-align: top;
    }

    .col-6 {
      width: 50%;
    }

    .col-4 {
      width: 33.333%;
    }

    .col-8 {
      width: 66.666%;
    }

    .col-3 {
      width: 50px;
    }

    .col-9 {
      width: 75%;
    }

    .col-12 {
      width: 100%;
    }

    /* Typography Hierarchy - Reduced by 30% */
    .h1 {
      font-size: 17px;
      font-weight: 700;
      color: #000;
      margin-bottom: 5px;
    }

    .h2 {
      font-size: 13px;
      font-weight: 600;
      color: #000;
      margin-bottom: 4px;
    }

    .h3 {
      font-size: 11px;
      font-weight: 600;
      color: #000;
      margin-bottom: 6px;
      text-align: center;
      border-bottom: 2px solid #000;
      padding-bottom: 4px;
    }

    .h4 {
      font-size: 10px;
      font-weight: 600;
      color: #000;
      margin-bottom: 3px;
    }

    .text-large {
      font-size: 11px;
      font-weight: 500;
    }

    .text-medium {
      font-size: 8px;
      font-weight: 400;
    }

    .text-small {
      font-size: 7px;
      font-weight: 400;
    }

    .text-xs {
      font-size: 6px;
      font-weight: 400;
    }

    .font-bold {
      font-weight: 700;
    }

    .font-semibold {
      font-weight: 600;
    }

    .font-medium {
      font-weight: 500;
    }

    .font-normal {
      font-weight: 400;
    }

    /* Text Alignment */
    .text-left {
      text-align: left;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    /* Spacing Utilities - Reduced margins */
    .mb-5 {
      margin-bottom: 3px;
    }

    .mb-10 {
      margin-bottom: 6px;
    }

    .mb-15 {
      margin-bottom: 8px;
    }

    .mb-20 {
      margin-bottom: 10px;
    }

    .mt-10 {
      margin-top: 6px;
    }

    .mt-15 {
      margin-top: 8px;
    }

    .mt-20 {
      margin-top: 10px;
    }

    .p-8 {
      padding: 8px;
    }

    .p-10 {
      padding: 10px;
    }

    .px-8 {
      padding-left: 8px;
      padding-right: 8px;
    }

    .py-6 {
      padding-top: 6px;
      padding-bottom: 6px;
    }

    /* Header Section */
    .header-section {
      margin-bottom: 12px;
      border-bottom: 1px solid #000;
      padding-bottom: 8px;
    }

    .company-logo {
      width: 80px;
      height: auto;
    }

    .company-info {
      padding-left: 15px;
    }

    .invoice-info {
      padding-right: 0;
    }

    /* Customer Info Section */
    .customer-section {
      margin-bottom: 12px;
    }

    .info-label {
      display: inline-block;
      width: 140px;
      font-weight: 600;
    }

    /* Table Styling */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }

    .data-table th {
      background-color: #000;
      color: #fff;
      padding: 5px;
      font-weight: 600;
      font-size: 8px;
      text-align: center;
      border: 1px solid #000;
    }

    .data-table td {
      padding: 4px 5px;
      border: 1px solid #000;
      font-size: 7px;
      vertical-align: top;
    }

    .data-table tbody tr:nth-child(even) {
      background-color: #f8f8f8;
    }

    /* Summary Section */
    .summary-table {
      width: 100%;
      margin-bottom: 10px;
    }

    .summary-table td {
      padding: 2px 0;
      font-size: 8px;
    }

    .summary-label {
      width: 60%;
      font-weight: 600;
    }

    .summary-value {
      width: 40%;
      text-align: right;
      font-weight: 500;
    }

    .grand-total {
      border-top: 2px solid #000;
      font-size: 10px;
      font-weight: 700;
      padding-top: 4px !important;
    }

    /* Notes Section */
    .notes-section {
      background-color: #f5f5f5;
      border: 1px solid #000;
      padding: 10px;
      margin-bottom: 20px;
    }

    /* Payment Info */
    .payment-info {
      margin-bottom: 20px;
      padding: 10px;
      background-color: #f9f9f9;
      border-left: 3px solid #000;
    }

    /* Signature Section */
    .signature-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .signature-table th,
    .signature-table td {
      border: 1px solid #000;
      padding: 5px;
      text-align: center;
      font-size: 7px;
    }

    .signature-table th {
      background-color: #f0f0f0;
      font-weight: 600;
    }

    .signature-space {
      height: 40px;
      vertical-align: bottom;
    }

    /* Terms Section */
    .terms-section {
      margin-top: 20px;
    }

    .terms-list {
      list-style: decimal;
      padding-left: 20px;
      margin-top: 10px;
    }

    .terms-list li {
      font-size: 6px;
      line-height: 1.2;
      margin-bottom: 2px;
      text-align: justify;
    }

    /* Dividers */
    .divider {
      height: 1px;
      background-color: #000;
      margin: 15px 0;
    }

    .divider-light {
      height: 1px;
      background-color: #ccc;
      margin: 10px 0;
    }
  </style>
</head>

<body>
  <div class="invoice-container">
    <!-- Header Section -->
    <div class="header-section">
      <div class="row">
        <div class="col col-3">
          <img src="{{ public_path('storage/LOGO-GPR.png') }}" alt="Logo" class="company-logo" />
        </div>
        <div class="col col-6 company-info" style="padding-left: 8px;">
          <div class="h1" style="margin-top: 0px; margin-bottom: 2px;">Global Photo Rental</div>
          <div class="text-small font-normal" style="margin-bottom: 1px;">WA: 0812-1234-9564</div>
          <div class="text-small font-normal" style="margin-bottom: 1px;">IG: global.photorental</div>
          <div class="text-small font-normal" style="margin-bottom: 1px;">Alamat: Jln Kepu Selatan No. 11A RT 03</div>
          <div class="text-small font-normal">RW 03, Kec. Kemayoran, Jakarta Pusat</div>
        </div>
        <div class="col signature-table invoice-info text-right">
          <div class="h4">Invoice #: {{ $record->booking_transaction_id ?? 'N/A' }}</div>
          <div class="text-medium font-normal">Tanggal: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y H:mm') }}</div>
          <div class="text-small font-normal">Dicetak oleh: {{ $currentUser?->name ?? 'Admin' }}</div>
        </div>
      </div>
    </div>

    <!-- Customer Information Section -->
    <div class="customer-section">
      <div class="row">
        <div class="col col-6">
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">Nama:</span>
            <span class="font-normal">{{ $record->customer?->name ?? 'N/A' }}</span>
          </div>
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">No Telepon:</span>
            <span class="font-normal">{{ $record->customer?->phone_number ?? 'N/A' }}</span>
          </div>
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">Email:</span>
            <span class="font-normal">{{ $record->customer?->email ?? 'N/A' }}</span>
          </div>
        </div>
        <div class="col col-6">
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">Booking ID:</span>
            <span class="font-normal">{{ $record->booking_transaction_id ?? 'N/A' }}</span>
          </div>
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">Tanggal Mulai Sewa:</span>
            <span class="font-normal">
              @if($record->start_date)
              {{ \Carbon\Carbon::parse($record->start_date)->locale('id')->isoFormat('dddd, D MMMM Y H:mm') }}
              @else
              N/A
              @endif
            </span>
          </div>
          <div class="text-medium mb-5">
            <span class="info-label font-semibold">Tanggal Selesai Sewa:</span>
            <span class="font-normal">
              @if($record->end_date)
              {{ \Carbon\Carbon::parse($record->end_date)->locale('id')->isoFormat('dddd, D MMMM Y H:mm') }}
              @else
              N/A
              @endif
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Details Section -->
    <div class="h3">Detail Produk</div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 5%;">No</th>
          <th style="width: 45%;">Produk</th>
          <th style="width: 20%;">No Seri</th>
          <th style="width: 10%;">Jml</th>
          <th style="width: 20%;">Harga</th>
        </tr>
      </thead>
      <tbody>
        @foreach($record->DetailTransactions as $detail)
        <tr>
          <td class="text-center">{{ $loop->iteration }}</td>
          <td>
            @if ($detail->bundling_id == null)
            <span class="font-medium">{{ $detail->product?->name ?? 'N/A' }}</span>
            @if($detail->product && $detail->product->rentalIncludes)
            @foreach($detail->product->rentalIncludes as $rentalInclude)
            <br><span class="text-xs">&nbsp;&nbsp;• {{ $rentalInclude->includedProduct?->name ?? 'N/A' }}</span>
            @endforeach
            @endif
            @else
            <span class="font-semibold">{{ $detail->bundling?->name ?? 'N/A' }}</span>
            @if($detail->bundling && $detail->bundling->products)
            @foreach($detail->bundling->products as $product)
            <br><span class="text-xs">&nbsp;&nbsp;— {{ $product->name ?? 'N/A' }}</span>
            @if($product->rentalIncludes)
            @foreach($product->rentalIncludes as $rentalInclude)
            <br><span class="text-xs">&nbsp;&nbsp;&nbsp;&nbsp;• {{ $rentalInclude->includedProduct?->name ?? 'N/A' }}</span>
            @endforeach
            @endif
            @endforeach
            @endif
            @endif
          </td>
          <td class="text-xs">
            @if ($detail->bundling_id == null)
            @if($detail->productItems && $detail->productItems->count() > 0)
            @foreach($detail->productItems as $productItem)
            {{ $productItem->serial_number ?? 'N/A' }}<br>
            @endforeach
            @else
            N/A
            @endif
            @else
            @if($detail->bundling && $detail->bundling->products)
            @foreach($detail->bundling->products as $product)
            @if($product->items && $product->items->count() > 0)
            @foreach($product->items->take($detail->quantity ?? 1) as $item)
            {{ $item->serial_number ?? 'N/A' }}<br>
            @endforeach
            @endif
            @endforeach
            @else
            N/A
            @endif
            @endif
          </td>
          <td class="text-center font-medium">{{ $detail->quantity ?? 0 }}</td>
          <td class="text-center font-medium">
            @php
            $subtotalPrice = 0;
            if ($detail->bundling_id && $detail->bundling) {
            $subtotalPrice = ($detail->bundling->price ?? 0) * ($detail->quantity ?? 1);
            } elseif ($detail->product_id && $detail->product) {
            $subtotalPrice = ($detail->product->price ?? 0) * ($detail->quantity ?? 1);
            }
            @endphp
            Rp{{ number_format($subtotalPrice, 0, ',', '.') }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Summary Section -->
    <div class="row mt-15">
      <div class="col col-6">
        <div class="notes-section">
          <div class="text-small font-semibold mb-5">Keterangan:</div>
          <div class="text-xs">{{ $record->note ?? '-' }}</div>
        </div>
      </div>
      <div class="col col-6">
        <table class="summary-table">
          <tr>
            <td class="summary-label">Durasi:</td>
            <td class="summary-value">{{ $record->duration ?? 0 }} Hari</td>
          </tr>
          <tr>
            <td class="summary-label">Total:</td>
            <td class="summary-value">
              @php
              $totalPrice = 0;
              foreach ($record->DetailTransactions as $detail) {
              if ($detail->bundling_id && $detail->bundling) {
              $price = $detail->bundling->price ?? 0;
              $qty = $detail->quantity ?? 1;
              } elseif ($detail->product) {
              $price = $detail->product->price ?? 0;
              $qty = $detail->quantity ?? 1;
              } else {
              $price = $detail->price ?? 0;
              $qty = $detail->quantity ?? 1;
              }
              $totalPrice += $price * $qty;
              }
              @endphp
              Rp{{ number_format($totalPrice * ($record->duration ?? 1), 0, ',', '.') }}
            </td>
          </tr>
          <tr>
            <td class="summary-label">Diskon:</td>
            <td class="summary-value">
              @php
              $diskon = 0;
              $totalPriceForDiscount = 0;

              foreach ($record->DetailTransactions as $detail) {
              if ($detail->bundling_id && $detail->bundling) {
              $price = $detail->bundling->price ?? 0;
              $qty = $detail->quantity ?? 1;
              } elseif ($detail->product) {
              $price = $detail->product->price ?? 0;
              $qty = $detail->quantity ?? 1;
              } else {
              $price = $detail->price ?? 0;
              $qty = $detail->quantity ?? 1;
              }
              $totalPriceForDiscount += $price * $qty;
              }

              if ($record->promo && $record->promo->rules) {
              $rules = $record->promo->rules;
              $duration = $record->duration ?? 1;
              $groupSize = isset($rules[0]['group_size']) ? (int) $rules[0]['group_size'] : 1;
              $payDays = isset($rules[0]['pay_days']) ? (int) $rules[0]['pay_days'] : $groupSize;
              $discountedDays = (int) ($duration / $groupSize) * $payDays;
              $remainingDays = $duration % $groupSize;
              $daysToPay = $discountedDays + $remainingDays;

              $diskon = match ($record->promo->type ?? 'none') {
              'day_based' => (int) ((int) ($totalPriceForDiscount * $duration) - ($totalPriceForDiscount * $daysToPay)),
              'percentage' => (int) (($totalPriceForDiscount * $duration) * (($rules[0]['percentage'] ?? 0) / 100)),
              'nominal' => min($rules[0]['nominal'] ?? 0, (int) ($totalPriceForDiscount * $duration)),
              default => 0,
              };
              }
              @endphp
              Rp{{ number_format($diskon, 0, ',', '.') }}
            </td>
          </tr>
          @if($record->additional_services && is_array($record->additional_services) && count($record->additional_services) > 0)
          @foreach($record->additional_services as $service)
          @if(isset($service['name']) && isset($service['amount']) && $service['amount'] > 0)
          <tr>
            <td class="summary-label">{{ $service['name'] }}:</td>
            <td class="summary-value">Rp{{ number_format($service['amount'], 0, ',', '.') }}</td>
          </tr>
          @endif
          @endforeach
          @endif
          
          @php
          $totalAdditionalServices = 0;
          if ($record->additional_services && is_array($record->additional_services)) {
              foreach ($record->additional_services as $service) {
                  if (isset($service['amount']) && $service['amount'] > 0) {
                      $totalAdditionalServices += $service['amount'];
                  }
              }
          }
          
          // Legacy support for old structure
          $totalAdditionalServices += ($record->additional_fee_1_amount ?? 0);
          $totalAdditionalServices += ($record->additional_fee_2_amount ?? 0);
          $totalAdditionalServices += ($record->additional_fee_3_amount ?? 0);
          @endphp
          
          @if($totalAdditionalServices > 0)
          <tr style="background-color: #e8f4fd; color: #1e40af;">
            <td class="summary-label font-semibold">Total Additional Services:</td>
            <td class="summary-value font-semibold">Rp{{ number_format($totalAdditionalServices, 0, ',', '.') }}</td>
          </tr>
          @endif
          
          @if($record->booking_status === 'cancel' && $record->cancellation_fee && $record->cancellation_fee > 0)
          <tr style="background-color: #ffe6e6; color: #d63031;">
            <td class="summary-label font-semibold">Biaya Pembatalan (50%):</td>
            <td class="summary-value font-semibold">Rp{{ number_format($record->cancellation_fee, 0, ',', '.') }}</td>
          </tr>
          @endif
          <tr class="grand-total">
            <td class="summary-label">Grand Total:</td>
            <td class="summary-value">Rp{{ number_format($record->grand_total ?? 0, 0, ',', '.') }}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Payment Information -->
    <div class="payment-info">
      <div class="text-small font-semibold mb-5">Pembayaran dapat dilakukan melalui:</div>
      <div class="text-xs font-normal">Bank BCA: 0910079531 (Dissa Mustika)</div>
    </div>

    <!-- Signature Section -->
    <div class="h3">Tanda Terima</div>
    <table class="signature-table">
      <thead>
        <tr>
          <th>Diserahkan Oleh:</th>
          <th>Diterima Oleh:</th>
          <th>Dikembalikan Oleh:</th>
          <th>Diterima Oleh:</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="signature-space">
            <div style="margin-top: 25px;">{{ $currentUser?->name ?? 'Admin' }}</div>
          </td>
          <td class="signature-space">
            <div style="margin-top: 25px;">{{ $record->customer?->name ?? 'N/A' }}</div>
          </td>
          <td class="signature-space">
            <div style="margin-top: 25px;">{{ $record->customer?->name ?? 'N/A' }}</div>
          </td>
          <td class="signature-space">
            <div style="margin-top: 25px;">{{ $currentUser?->name ?? 'Admin' }}</div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Terms and Conditions -->
    <div class="terms-section">
      <div class="h4 text-center">Syarat dan Ketentuan</div>
      <ol class="terms-list">
        <li>Pihak yang menyewa wajib meninggalkan KTP Asli</li>
        <li>Lama peminjaman 24 jam dihitung sejak jadwal yang tertera pada form di Invoice ini</li>
        <li>Keterlambatan pengembalian alat 3 jam pertama dari jam tertera di form dikenakan denda 30% dari total sewa, keterlambatan lebih dari 3 jam dihitung penambahan pembayaran penuh 1 hari dengan konfirmasi kepada pihak Global Photo Rental sebelumnya</li>
        <li>Apabila dalam waktu 1x24 jam unit sewa tidak dikembalikan tanpa konfirmasi atau pemberitahuan, pihak yang menyewa akan dilaporkan ke kepolisian setempat</li>
        <li>Kerusakan/kehilangan barang sewaan selama peminjaman menjadi tanggung jawab pihak yang menyewa dan wajib mengganti biaya perbaikan atau komponen unit yang rusak/hilang. Apabila kerusakan tidak bisa diperbaiki maka pihak yang menyewa wajib mengganti dengan yang unit baru</li>
        <li>Setiap alat dilengkapi dengan sticker Global Photo Rental. Dilarang keras merusak atau melepaskan sticker yang menempel pada alat, sticker yang dicopot atau dilepaskan tanpa izin akan dikenakan denda Rp500.000</li>
      </ol>
    </div>
  </div>
</body>

</html>