<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfService
{
    public static function render(string $view, array $data, string $filename)
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($fontDirs, [
                public_path('fonts'),
            ]),
            'fontdata' => $fontData + [
                'notosans'           => ['R' => 'NotoSans-Regular.ttf'],
                'notosansbengali'    => ['R' => 'NotoSansBengali-Regular.ttf'],
                'notonaskharabic'    => ['R' => 'NotoNaskhArabic-Regular.ttf'],
                'notosansdevanagari' => ['R' => 'NotoSansDevanagari-Regular.ttf'],
                'notosanssc'         => ['R' => 'NotoSansSC-Regular.ttf'],
                'notosansjp'         => ['R' => 'NotoSansJP-Regular.ttf'],
                'notosanskr'         => ['R' => 'NotoSansKR-Regular.ttf'],
            ],
            'default_font' => 'notosans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $html = view($view, $data)->render();
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
