<?php

/**
 * PDF Certificate extends TCPDF.
 */
class CERTIFICATEPDF extends TCPDF
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
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false, $bg_image = '')
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->bg_image = $bg_image;

        // set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('Ceritifcate-Generator');
        $this->SetTitle('');
        $this->SetSubject('');
        $this->SetKeywords('TCPDF, PDF, certificate');

        // set header and footer fonts
        $this->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(0);

        // remove default footer
        $this->setPrintFooter(false);

        // set auto page breaks
        $this->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        // set font
        $this->SetFont(DEFAULT_FONT, '', DEFAULT_FONT_SIZE);
    }

    //Page header
    public function Header()
    {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set background image
        if (!empty($this->bg_image)) {
            $this->Image($this->bg_image, 0, 0, $this->w, $this->h, '', '', '', false, 300, '', false, false, 0);
        }
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }

    public function create_pdf($output = 'output.pdf', $html = '', $y_offset = 0, $data = '', $df = 'd \d\e F \d\e Y')
    {
        // add a page
        $this->AddPage();

        // Handle content text if needed
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $html = preg_replace('/\{\{\s*'.$key.'\s*\}\}/', trim($value), $html);
            }
        }

        $html = str_replace('{{ %now% }}', date($df), $html);

        // Print a text
        $w = $this->getPageWidth();
        $this->writeHTMLCell($w, 0, 0, $y_offset, $html, 0, 0, false, true, 'C', true);

        $this->endPage();
        //Output and close PDF document
        $this->Output($output, 'F');
        $this->Close();
    }
}
