<?php

namespace App\Support\Barcode;

/**
 * Self-contained barcode renderer (no external packages: packagist is not
 * reachable from this environment).
 *
 * Supported symbologies:
 *  - EAN-13 / UPC-A (UPC-A is encoded as EAN-13 with a leading 0)
 *  - CODE128 (auto: Code C for pure numeric runs, Code B otherwise)
 *
 * Backends:
 *  - PNG via ext/GD when available (crisp labels for thermal printers)
 *  - Inline SVG everywhere else (browser printing, dompdf 3 supports it)
 *
 * The API is deliberately tiny: bars() returns module runs, png()/svg()
 * paint them.
 */
class BarcodeRenderer
{
    private const EAN_L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
    private const EAN_G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
    private const EAN_R = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];

    /** First-digit parity table for EAN-13 (L=left-A, G=left-B). */
    private const EAN_PARITY = [
        0 => 'LLLLLL', 1 => 'LLGLGG', 2 => 'LLGGLG', 3 => 'LLGGGL', 4 => 'LGLLGG',
        5 => 'LGGLLG', 6 => 'LGGGLL', 7 => 'LGLGLG', 8 => 'LGLGGL', 9 => 'LGGLGL',
    ];

    /** CODE128 symbol patterns: digits 1-4 = widths of bar/space alternation. */
    private const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const CODE128_START_B = 104;
    private const CODE128_START_C = 105;

    /** Symbology sizes: [moduleWidthPx, heightPx] keyed by the Barcode.size column. */
    private const SIZES = [
        'small' => [1, 40],
        'standard' => [2, 70],
        'large' => [3, 100],
    ];

    /**
     * Return the dark-module runs for a value: [[xStartModule, widthModule], ...].
     *
     * @return array{bars: array<int, array{0: int, 1: int}>, modules: int, digits: string}
     */
    public function bars(string $type, string $value): array
    {
        return match (strtoupper($type)) {
            'EAN13', 'UPC' => $this->ean13Bars($value),
            default => $this->code128Bars($value),
        };
    }

    /** Render a PNG (GD). Returns raw bytes or null when GD is unavailable. */
    public function png(string $type, string $value, string $size = 'standard'): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        [$module, $height] = self::SIZES[$size] ?? self::SIZES['standard'];
        $data = $this->bars($type, $value);
        $quiet = 9 * $module; // quiet zone (>= 9 modules on each side)
        $textBand = 22;
        $width = $data['modules'] * $module + 2 * $quiet;

        $img = imagecreatetruecolor($width, $height + $textBand);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        foreach ($data['bars'] as [$start, $runWidth]) {
            imagefilledrectangle(
                $img,
                $quiet + $start * $module,
                0,
                $quiet + ($start + $runWidth) * $module - 1,
                $height - 1,
                $black
            );
        }

        $label = $data['digits'];
        if (function_exists('imagestring') && $label !== '') {
            $font = 4;
            $charW = imagefontwidth($font);
            if (strlen($label) * $charW <= $width) {
                $textX = (int) (($width - strlen($label) * $charW) / 2);
                imagestring($img, $font, $textX, $height + 4, $label, $black);
            }
        }

        ob_start();
        imagepng($img);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    /** Render an inline-SVG string (works in browsers and dompdf 3). */
    public function svg(string $type, string $value, string $size = 'standard'): string
    {
        [$module, $height] = self::SIZES[$size] ?? self::SIZES['standard'];
        $data = $this->bars($type, $value);
        $quiet = 9 * $module;
        $textBand = 22;
        $width = $data['modules'] * $module + 2 * $quiet;
        $totalHeight = $height + $textBand;

        $rects = '';
        foreach ($data['bars'] as [$start, $runWidth]) {
            $rects .= sprintf(
                '<rect x="%d" y="0" width="%d" height="%d"/>',
                $quiet + $start * $module,
                $runWidth * $module,
                $height
            );
        }

        $label = htmlspecialchars($data['digits'], ENT_QUOTES);
        $text = '';
        if ($label !== '') {
            $text = sprintf(
                '<text x="%d" y="%d" text-anchor="middle" font-family="monospace" font-size="12" fill="#000">%s</text>',
                (int) ($width / 2),
                $height + 15,
                $label
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">'
            .'<rect width="%d" height="%d" fill="#fff"/><g fill="#000">%s</g>%s</svg>',
            $width,
            $totalHeight,
            $width,
            $totalHeight,
            $width,
            $totalHeight,
            $rects,
            $text
        );
    }

    /*
     * ---------------------------------------------------------------------
     * EAN-13 (UPC-A = leading 0)
     * ---------------------------------------------------------------------
     */
    private function ean13Bars(string $value): array
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) === 12) {
            $digits = \App\Models\Barcode::ean13($digits); // UPC-A body or missing check digit
        }

        if (strlen($digits) !== 13) {
            throw new \InvalidArgumentException("EAN13 requires 12/13 digits, got [{$value}]");
        }

        $parity = self::EAN_PARITY[(int) $digits[0]];
        $modules = '101';

        foreach (range(1, 6) as $i) {
            $d = (int) $digits[$i];
            $modules .= $parity[$i - 1] === 'L' ? self::EAN_L[$d] : self::EAN_G[$d];
        }

        $modules .= '01010';

        foreach (range(7, 12) as $i) {
            $modules .= self::EAN_R[(int) $digits[$i]];
        }

        $modules .= '101';

        return ['bars' => $this->darkRuns($modules), 'modules' => 95, 'digits' => $digits];
    }

    /*
     * ---------------------------------------------------------------------
     * CODE128 (auto B/C)
     * ---------------------------------------------------------------------
     */
    private function code128Bars(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('CODE128 value is empty');
        }

        $isNumeric = ctype_digit($value);
        $codes = [];

        if ($isNumeric && strlen($value) >= 4) {
            // Code C encodes digit PAIRS; pad a leading zero for odd lengths.
            if (strlen($value) % 2 === 1) {
                $value = '0'.$value;
            }
            $codes[] = self::CODE128_START_C;
            foreach (str_split($value, 2) as $pair) {
                $codes[] = (int) $pair;
            }
        } else {
            if (preg_match('/[\x80-\xff]/', $value)) {
                $value = preg_replace('/[\x80-\xff]/', '?', $value);
            }
            $codes[] = self::CODE128_START_B;
            foreach (str_split($value) as $char) {
                $codes[] = max(0, min(94, ord($char) - 32));
            }
        }

        $checksum = $codes[0];
        foreach (array_slice($codes, 1) as $i => $code) {
            $checksum += $code * ($i + 1);
        }
        $codes[] = $checksum % 103;
        $codes[] = 106; // stop

        $modules = '';
        foreach ($codes as $code) {
            $modules .= self::CODE128_PATTERNS[$code];
        }

        return ['bars' => $this->darkRuns($modules), 'modules' => strlen($modules), 'digits' => $value];
    }

    /** Turn a "1011…" module string into [[start, runWidth], …] dark runs. */
    private function darkRuns(string $modules): array
    {
        $runs = [];
        $length = strlen($modules);
        $start = null;

        for ($i = 0; $i < $length; $i++) {
            if ($modules[$i] === '1' && $start === null) {
                $start = $i;
            } elseif ($modules[$i] === '0' && $start !== null) {
                $runs[] = [$start, $i - $start];
                $start = null;
            }
        }
        if ($start !== null) {
            $runs[] = [$start, $length - $start];
        }

        return $runs;
    }
}
