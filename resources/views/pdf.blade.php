<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Invoice Global Photo Rental</title>

<style>
  /* Main invoice box styling */
  .invoice-box {
    max-width: 800px;
    margin: auto;
    padding: 30px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    line-height: 1.6;
    font-family: Arial, sans-serif;
    color: #333;
    background-color: #f9f9f9;
  }

  /* Table styling */
  .invoice-box table {
    width: 100%;
    line-height: 1.6;
    border-collapse: collapse;
    margin-top: 10px;
  }

  .invoice-box th,
  .invoice-box td {
    padding: 6px 8px;
    text-align: left;
  }

  /* Header Styling */
  .invoice-box .title {
    display: flex;
    align-items: center;
  }

  .invoice-box .title img {
    width: 100px;
    height: auto;
  }

  .invoice-box .title b {
    font-size: 20px;
    color: #2e2e2e;
  }

  .invoice-box .title td:last-child {
    text-align: right;
    vertical-align: top;
  }

  /* Section Styling */
  .invoice-box .section-title {
    font-size: 16px;
    font-weight: bold;
    color: #2e2e2e;
    text-align: center;
    border-bottom: 2px solid #ddd;
    padding-bottom: 8px;
    margin-top: 20px;
  }

  /* Durasi, Total, Diskon, Grand Total */
  .invoice-box table tr.durasi td {
    font-weight: bold;
    border-top: 1px solid #ddd;
    font-size: 13px;
  }

  .invoice-box table tr.total td {
    font-weight: bold;
    background-color: #f1f1f1;
    border-top: 2px solid #ddd;
    border-bottom: 2px solid #ddd;
    font-size: 14px;
  }

  /* Keterangan Column */
  .keterangan {
    font-size: 13px;
    font-weight: bold;
    vertical-align: top;
    padding: 10px;
    background-color: #f1f1f1;
    border: 1px solid #ddd;
  }

  /* Terms and conditions */
  .invoice-box h4 {
    text-align: center;
    font-size: 14px;
    color: #555;
    margin-bottom: 10px;
  }

  .invoice-box ol {
    list-style: decimal;
    font-size: 12px;
    color: #555;
    margin-left: 15px;
  }

  .invoice-box ol li {
    line-height: 1.4;
  }

  /* Responsive Design */
  @media only screen and (max-width: 600px) {
    .invoice-box .title td {
      text-align: center;
      display: block;
    }

    .invoice-box table {
      font-size: 12px;
    }
  }
</style>
  </head>

  <body>
    <div class="invoice-box">
      <table cellpadding="0" cellspacing="0">
        <tr class="top">
          <td colspan="2">
            <table>
              <tr>
                <td class="title">
                  <table>
                    <tr>
                      <td>
                        <img
                          src="{{ public_path('images/LOGO GPR.png') }}"
                          alt="Logo"
                          style="width: auto; height: 100px;"
                        />
                      </td>
                      <td style="text-align: left; padding-left: 15px;">
                        <b style="font-size: 22px; color: #333;">Global Photo Rental</b>
                        <p style="font-size: 12px; line-height: 0; color: #777;">WA: 0812-1234-9564</p>
                        <p style="font-size: 12px; line-height: 0;color: #777;">IG: global.photorental</p>
                        <p style="font-size: 12px; line-height: 0;color: #777;">Alamat: Jln Kepu Selatan No. 11A RT 03 </p>
                        <p style="font-size: 12px; line-height: 0;color: #777;">RW 03, Kec. Kemayoran, Jakarta Pusat</p>
                      </td>
                      <td style="vertical-align: bottom; text-align: right;">
                        <p style="font-size: 14px; color: #555;">
                          <b>Invoice #: {{$record->booking_transaction_id }}</b><br />
                          Tanggal: {{ \Carbon\Carbon::now()->isoFormat(' D MMMM Y H:mm') }}
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="information">
          <td colspan="2">
            <table style="margin-top: -30px; margin-bottom: 0px;">
              <tr style="font-size: 14px; color: #555;">
                <td>
                  <strong>Nama:</strong> {{ $record->user->name }}<br />
                  <strong>No Telepon:</strong> {{ $record->user->phone_number }}<br />
                  <strong>Email:</strong> {{ $record->user->email }}
                </td>
                <td>
                  <strong>Booking ID:</strong> {{ $record->booking_transaction_id }}<br />
                  <strong>Tanggal Mulai Sewa:</strong> {{ \Carbon\Carbon::parse($record->start_date)->isoFormat('dddd, D MMMM Y H:mm') }}<br />
                  <strong>Tanggal Selesai Sewa:</strong> {{ \Carbon\Carbon::parse($record->end_date)->isoFormat('dddd, D MMMM Y H:mm') }}
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td colspan="2">
            <h3 class="section-title" style="margin-top: -20px;">Detail Produk</h3>
            <table style="margin-bottom: -5px">
				<thead>
                <tr style="font-size: 10px; text-align: center">
                  <th style="font-size: 10px; text-align: center">No</th>
                  <th style="font-size: 10px; text-align: center">Produk</th>
                  <th style="font-size: 10px; text-align: center">No Seri</th>
                  <th style="font-size: 10px; text-align: center">Jml</th>
                  
                  <th style="font-size: 10px; text-align: center">Harga</th>
                </tr>
              </thead>
              <tbody>
                @foreach($record->DetailTransactions as $detail)
                <tr style="font-size: 10px">
                  <td style="text-align: center;">{{ $loop->iteration }}</td>
                  <td style="text-align: left;">
                    @if ($detail->bundling_id == null)
                    {{ $detail->product->name }}
                    @foreach($detail->product->rentalIncludes as $rentalInclude)
                    <br />{{ $rentalInclude->includedProduct->name }}
                    @endforeach

                    @else
                    @foreach($detail->bundling->products as $product)
                    <br /> {{ $product->name }}
                    @foreach($product->rentalIncludes as $rentalInclude)
                    <br />{{ $rentalInclude->includedProduct->name }}

                    @endforeach

                    @endforeach
                    @endif
                  </td>
                  <td style="text-align: center;">
                    @if ($detail->bundling_id == null)
                    {{ $detail->product->id }}
                    @foreach($detail->product->rentalIncludes as $rentalInclude)
                    <br />{{ $rentalInclude->includedProduct->id }}
                    @endforeach

                    @else
                    @foreach($detail->bundling->products as $product)
                    <br /> {{ $product->id }}
                    @foreach($product->rentalIncludes as $rentalInclude)
                    <br />{{ $rentalInclude->includedProduct->id }}

                    @endforeach

                    @endforeach
                    @endif





                  </td>
                  <td style="text-align: center;">
                    @if ($detail->bundling_id == null)
                    {{ $detail->product->quantity }}

                    @else
                    <br /> {{ $detail->bundling->quantity}}
                        {{-- {{ Log::info($detail->bundling->quantity)}} --}}
                    @endif


                  
                  </td>
                  <td style="text-align: center;">Rp
                    @if ($detail->bundling_id == null)

                    {{ number_format($detail->product->price, 0, ',', '.') }}
                    @else
                    {{ number_format($detail->bundling->price, 0, ',', '.') }}

                      
                    @endif

                  
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </td>
        </tr>
        <table>
          <tr>
              <td rowspan="4" style="font-size: 8px; font-weight: bold; text-align: left; vertical-align: top; padding: 0px; width: 400px;">
                  Keterangan: <br>
                  {{ ($record->note) }}
              </td>
              <td></td>
              <td style="font-size: 8px; font-weight: bold; padding-top: 0px; padding-bottom: 0px; text-align: right; border-top: 1px solid #ddd; width: 200px">
                  Durasi: {{ ($record->duration) }} Hari
              </td>
          </tr>
          <tr>
              <td></td>
              <td style="font-size: 8px; font-weight: bold; padding-top: 0px; padding-bottom: 0px; text-align: right;">
                @php
    $totalPrice = 0;

    foreach ($record->DetailTransactions as $detail) {
      $totalPrice += $detail->bundling_id ? $detail->bundling->price * $detail->bundling->quantity : $detail->product->price * $detail->product->quantity;

    }

@endphp  

                
                Total: Rp{{ number_format($totalPrice * $record->duration, 0, ',', '.') }}
              </td>
          </tr>
          <tr>
              <td></td>
              <td style="font-size: 8px; font-weight: bold; padding-top: 0px; padding-bottom: 0px; text-align: right;">
                  @php
                      $diskon = 0;
                      $totalPrice = 0;

                      foreach ($record->DetailTransactions as $detail) {
      $totalPrice += $detail->bundling_id ? $detail->bundling->price * $detail->bundling->quantity : $detail->product->price * $detail->product->quantity;

    }

                      if ($record->promo) {
                          $rules = $record->promo->rules;
                          $duration = $record->duration;
                          $groupSize = isset($rules[0]['group_size']) ? (int) $rules[0]['group_size'] : 1;
                          $payDays = isset($rules[0]['pay_days']) ? (int) $rules[0]['pay_days'] : $groupSize;
                          $discountedDays = (int) ($duration / $groupSize) * $payDays;
                          $remainingDays = $duration % $groupSize;
                          $daysToPay = $discountedDays + $remainingDays;




      
                          $diskon = match ($record->promo->type) {
                              // 'day_based' => (int) ((int) ($totalPrice * $duration / ($rules[0]['group_size'] ?? 1)) * ($rules[0]['pay_days'] ?? ($rules[0]['group_size'] ?? 1)) + ($totalPrice * $duration % ($rules[0]['group_size'] ?? 1))),
                              'day_based' => (int) ((int) ($totalPrice * $duration) - ($totalPrice * $daysToPay)),

                              'percentage' => (int) (($totalPrice * $duration) * ($rules[0]['percentage'] ?? 0 / 100)),
                              'nominal' => min($rules[0]['nominal'] ?? 0, (int) ($total * $duration)),
                              default => 0,
                          };
                      }
                  @endphp
                  Diskon: Rp{{ number_format($diskon, 0, ',', '.') }}
              </td>
          </tr>
          <tr>
              <td></td>
              <td style="font-size: 12px; font-weight: bold; padding-top: 0px; padding-bottom: 0px; text-align: right; border-top: 1px solid #ddd;">
                  Grand Total: Rp{{ number_format($record->grand_total, 0, ',', '.') }}
              </td>
          </tr>
      </table>
      
		<tr>
			<td colspan="2">
			  <p
			  style="
				font-size: 8px;
				text-align: left;
				margin-top: -10px
			  "
			>
			  Pembayaran dapat dilakukan melalui:
			</p>            <p style="list-style: decimal; font-size: 8px; text-align: justify; line-height: 0px">
				Bank BCA
				</p>
				<p style="list-style: decimal; font-size: 8px; text-align: justify; margin-top: 0px; line-height: 0px">
					0910079531
					</p>				
					<p style="list-style: decimal; font-size: 8px; text-align: justify; margin-top: 0px; line-height: 0px">
						Dissa Mustika
						</p>				
			</p>
			</td>
		  </tr>
        <tr>
          <td colspan="2">
			<h4 class="section-title" style="margin-top: -20px; margin-bottom: -10px">Tanda Terima</h4>
            <table style="margin-top: -10px; font-size: 10px; color: #555;">
              <thead>
                <tr>
                  <th style="text-align: center;">Diserahkan Oleh:</th>
                  <th style="text-align: center;">Diterima Oleh:</th>
                  <th style="text-align: center;">Dikembalikan Oleh:</th>
                  <th style="text-align: center;">Diserahkan Oleh:</th>
                </tr>
              </thead>



              <tbody>
                <tr>
                  <td style="text-align: center;"><p></p><p></p><p></p><p></p>
					(Nama Petugas)
				</td>
                  <td style="text-align: center;"><p></p><p></p><p></p><p></p>{{ $record->user->name }}</td>
                  <td style="text-align: center;"><p></p><p></p><p></p><p></p>{{ $record->user->name }}</td>
                  <td style="text-align: center;"><p></p><p></p><p></p><p></p>(Nama Petugas)</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>

        <tr>
          <td colspan="2">
			<h4
			style="
			  font-size: x-small;
			  text-align: center;
			  margin-bottom: -5px;
			  margin-top: -20px
			"
		  >
			Syarat dan Ketentuan
		  </h4>            <ol style="list-style: decimal; font-size:xx-small; text-align: justify; margin-top: 0px">
              <li>Pihak yang menyewa diwajibkan meninggalkan 2 identitas asli (KTP/SIM/Kartu Keluarga/STNK/BPKP dll) yang masih berlaku</li>
              <li>Lama peminjaman 24 jam dihitung sejak jadwal yang tertera pada form di Invoice ini</li>
              <li>Keterlambatan pengembalian unit sewa akan dikenakan denda 30% dari total biaya sewa dengan toleransi terlambat maksimal 3  jam, keterlambatan lebih dari 3 jam dihitung penambahan pembayaran penuh 1 hari dengan konfirmasi kepada pihak Global  Photo Rental sebelumnya</li>
			  <li>pabila dalam waktu 1x24 jam unit sewa tidak dikembalikan tanpa konfirmasi atau pemberitahuan, pihak yang menyewa akan  dilaporkan ke kepolisian setempat</li>
              <li>Kerusakan/kehilangan barang sewaan selama peminjaman menjadi tanggung jawab pihak yang menyewa dan wajib mengganti  biaya perbaikan atau komponen unit yang rusak/hilang. Apabila kerusakan tidak bisa diperbaiki maka pihak yang menyewa wajib  mengganti dengan yang unit baru</li>
            </ol>
          </td>
        </tr>
      </table>
    </div>
  </body>
</html>
