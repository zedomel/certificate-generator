<?php

use Dompdf\Dompdf;

/**
 * PDF Certificate extends TCPDF.
 */
class CERTIFICATEDOMPDF extends Dompdf
{
    /**
     * Background image.
     *
     * @var string
     */
    public $bg_image;

    /**
     * Constructor.
     *
     * @param string $orientation page orientation
     * @param string $unit        page unit
     * @param string $format      page format
     * @param bool   $unicode     use unicode
     * @param string $encoding    encondig
     * @param bool   $diskcache   diskcache
     * @param bool   $pdfa        use pdfa
     * @param string $bg_image    background image
     */
    public function __construct($orientation = 'landscape', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false, $bg_image = '')
    {
        parent::__construct();
        $this->setPaper($format, $orientation);
    }

    public function create_pdf($output = 'output.pdf', $html = '', $y_offset = 0, $data = '', $df = '%B, %d of %Y')
    {
        // Handle content text if needed
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $html = preg_replace('/\{\{\s*'.$key.'\s*\}\}/', trim($value), $html);
            }
        }

        $html = str_replace('{{ %now% }}', strftime($df), $html);
        $this->loadHtml($html);
        $this->render();
        $pdf_gen = $this->output();
        file_put_contents($output, $pdf_gen);
    }
}
