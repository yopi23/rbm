<?php

namespace App\Services;

class QrisGeneratorService
{
    // Data Merchant Statis Anda (diambil dari string QRIS Anda sebelumnya)
    private $merchantData = [
        'pan' => '00020101021126570011ID.DANA.WWW011893600915340189065002094018906500303UMI51440014ID.CO.QRIS.WWW0215ID10232513870240303UMI5204481453033605802ID5910YOYOYCELL 6013Kab. Sukabumi6105431816304FD55', // Nomor dari DANA Anda
        'id' => 'ID1023251387024',    // ID Merchant Anda
        'name' => 'YOYOYCELL',
        'city' => 'Kab. Sukabumi',
    ];

    /**
     * Membuat string QRIS dinamis.
     * @param int $amount Nominal transaksi
     * @param string $referenceId ID unik transaksi (contoh: nomor invoice)
     * @return string String QRIS yang siap diubah menjadi gambar
     */
    public function generate(int $amount, string $referenceId): string
    {
        // Gabungkan data statis dan dinamis
        $payload = $this->buildPayload($amount, $referenceId);

        // Hitung checksum CRC16
        $crc = $this->crc16_ccitt_false($payload . '6304');

        // Gabungkan payload dengan checksum
        return $payload . '6304' . strtoupper(sprintf('%04s', dechex($crc)));
    }

    /**
     * Membangun payload utama QRIS.
     */
    private function buildPayload(int $amount, string $referenceId): string
    {
        $tags = [
            $this->buildTag('00', '01'), // Payload Format Indicator
            $this->buildTag('01', '11'), // Point of Initiation Method (11 untuk statis, 12 untuk dinamis, 11 umum digunakan)

            // Merchant Account Information (DANA)
            $this->buildTag('26',
                $this->buildTag('00', 'ID.DANA.WWW') .
                $this->buildTag('01', $this->merchantData['pan'])
            ),

            $this->buildTag('51', // Merchant Account Information (QRIS)
                $this->buildTag('00', 'ID.CO.QRIS.WWW') .
                $this->buildTag('02', $this->merchantData['id']) .
                $this->buildTag('03', 'UMI')
            ),

            $this->buildTag('52', '4814'), // Merchant Category Code
            $this->buildTag('53', '360'), // Transaction Currency (IDR)
            $this->buildTag('54', $amount), // === INI BAGIAN DINAMIS === (Transaction Amount)
            $this->buildTag('58', 'ID'), // Country Code
            $this->buildTag('59', $this->merchantData['name']), // Merchant Name
            $this->buildTag('60', $this->merchantData['city']), // Merchant City

            // Additional Data Field Template
            $this->buildTag('62',
                $this->buildTag('07', $referenceId) // === INI BAGIAN DINAMIS === (Reference ID / Invoice)
            ),
        ];

        return implode('', $tags);
    }

    /**
     * Helper untuk membuat format Tag-Length-Value.
     */
    private function buildTag(string $id, string $value): string
    {
        return $id . sprintf('%02s', strlen($value)) . $value;
    }

    /**
     * Menghitung checksum CRC16-CCITT-FALSE. Ini wajib ada di QRIS.
     */
    private function crc16_ccitt_false(string $data): int
    {
        $crc = 0xFFFF;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
            }
        }
        return $crc & 0xFFFF;
    }
}
