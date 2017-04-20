<?php

require "tcpdf/tcpdf.php";


class CERTIFICATEPDF extends TCPDF {

	public $img_file;

    public function __construct($orientation='P', $unit='mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false, $img_bg = '' ){
        parent::__construct( $orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa );
        $this->img_file = $img_bg;
    }

	//Page header
    public function Header() {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set background image
        $this->Image($this->img_file, 0, 0, $this->w, $this->h, '', '', '', false, 300, '', false, false, 0 );
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }
}

$options = getopt("i:o:c:y:d:fs:");

print_r($options);

setlocale(LC_ALL, 'pt_BR' ); 

if ( ! isset( $options[ 'c' ] ) || !isset( $options ['i'] ) || !isset( $options[ 'o' ] ) ) {
    exit;
}

// Background image
$img_file = $options['i'];

// Page size
$size = 'A4';
if ( isset( $options[ 's' ] ) ){
    $size = array_map('floatval', split('\s*,\s*', $options[ 's' ] ) );
}

// CSV Data file
$data_file = '';
if ( isset( $options[ 'd' ] ) ){
    $data_file = $options[ 'd' ];
}

// Output file/directory
$output = $options[ 'o' ];
if ( strpos($output, DIRECTORY_SEPARATOR ) === false ){
    $output = getcwd() . DIRECTORY_SEPARATOR . $output;
}

// Text
if ( isset( $options[ 'f' ] ) ){
    $content = file_get_contents( $options[ 'c' ] );
}
else{
    $content = $options[ 'c' ];
}

$y_offset = 0;
if ( isset( $options[ 'y' ] ) ){
    $y_offset = floatval( $options[ 'y'] );
}

if ( !empty( $data_file ) ){
    if ( is_dir( $output ) === FALSE ){
        echo "Output is not a directory. Please, provide a directory to output PDF's\n";
        exit;
    }

    if ( ( $handle = fopen( $data_file, 'r' ) ) !== FALSE) { 
        while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== FALSE ) {
            $output_file = $output . DIRECTORY_SEPARATOR . strtolower( trim( $data[0] ) ) . '.pdf';
            create_pdf( $size, $img_file, $output_file, $content, $y_offset, $data );    
            echo $output_file . " created.\n";
        }   

        fclose($handle);
    }
}
else{
    create_pdf( $size, $img_file, $output, $content, $y_offset );
}



function create_pdf($size = 'A4', $img_file = '', $output = 'output.pdf', $html = '', $y_offset = 0, $data = '', $df = 'd \d\e F \d\e Y' ){

    // create new PDF document
    $pdf = new CERTIFICATEPDF( 'L', 'cm', $size , true, 'UTF-8', false, false, $img_file );

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Ceritifcate-Generator');
    $pdf->SetTitle('');
    $pdf->SetSubject('');
    $pdf->SetKeywords('TCPDF, PDF, certificate');

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);

    // remove default footer
    $pdf->setPrintFooter(false);

    // set auto page breaks
    $pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ---------------------------------------------------------

    // set font
    $pdf->SetFont('ubuntu', '', 14);

    // add a page
    $pdf->AddPage();

    // Handle content text if needed
    if ( !empty( $data ) ){
        $num = count($data);
        for( $c = 0; $c < $num; $c++ ){
            $html = str_replace('%'. ($c+1) , $data[ $c ] , $html );
        }
    }

    $html = str_replace('%now', date($df), $html );

    // Print a text
    $pdf->writeHTMLCell($size[0] - 5, 0, 2.5, $y_offset, $html, 0, 0, false, true, 'C', true );

    //Close and output PDF document
    $pdf->Output($output, 'F');
}

?>