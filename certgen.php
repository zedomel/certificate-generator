<?php

require_once 'vendor/autoload.php';
require 'inc/config.php';
require 'inc/certPDF.php';
require 'inc/certMailer.php';

/*
 * Options:
 * c: certificate content (file or string)
 * i: background image
 * o: output (file or directory)
 * y: offset y position of content text
 * d: data file (CSV)
 * p: page size (defaulf: A4)
 * e: e-mail index column in data file
 * m: e-mail message
 * r: reply-to/from e-mail
 * s: e-mail subject
 * a: list of attchament
 */

ini_set('memory_limit', MEMORY_LIMIT);
setlocale(LC_ALL, LOCALE);
date_default_timezone_set('Etc/UTC');

// p: page size ex: A4, (299,400)
// i: html input file
// c: not used
// y: y offset
// d: data file CSV
// e:
// s:
// m:
// r:
// a:
// h:
// o: output file/directory
// f: font file

$def_options = [
    ['i', 'input', \GetOpt\GetOpt::REQUIRED_ARGUMENT, 'HTML template file'],
    ['o', 'output', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'PDF output file/directory', 'output.pdf'],
    ['y', 'offset', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Y offset of HTML cell', 0],
    ['d', 'data', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'CSV file with data to fill template', ''],
    ['p', 'page', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Page size', 'A4'],
    ['e', 'email_col', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Email column name in CSV file', 'email'],
    ['s', 'subject', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Email subject', 'Certificate'],
    ['m', 'message', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Email message or a path to file with message', 'Here is your certificate'],
    ['r', 'replyto', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Reply to email', 'example@certificategenerator.com'],
    ['a', 'attach', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Additional attachment'],
    ['?', 'help', \GetOpt\GetOpt::NO_ARGUMENT, 'Show this help and quit'],
    ['f', 'font', \GetOpt\GetOpt::OPTIONAL_ARGUMENT, 'Add font to TCPDF', ''],
];
$getopt = new \GetOpt\GetOpt($def_options);
try {
    try {
        $getopt->process();
    } catch (Missing $exception) {
        // catch missing exceptions if help is requested
        if (!$getopt->getOption('help')) {
            throw $exception;
        }
    }
} catch (Exception $exception) {
    file_put_contents('php://stderr', $exception->getMessage().PHP_EOL);
    echo PHP_EOL.$getopt->getHelpText();
    exit;
}

if ($getopt->getOption('help')) {
    echo $getopt->getHelpText();
    exit;
}

$options = $getopt->getOptions();
print_r($options);

if (isset($options['f'])) {
    $font_file = $options['f'];
    // convert TTF font to TCPDF format and store it on the fonts folder
    $fontname = TCPDF_FONTS::addTTFfont($font_file);
    echo 'Font added: '.$fontname."\n";
    echo "Change DEFAULT_FONT value to {$fontname} in config.php to use the new font!\n";
    exit;
}

// if (!isset($options['c']) || !isset($options['o'])) {
//     exit;
// }

// Background image
$img_file = '';
if (isset($options['i'])) {
    $img_file = realpath($options['i']);
}

if (!isset($options['c'])) {
    exit;
}

// Text
$input_html = file_get_contents($options['c']);

// Page size
$size = 'A4';
if (isset($options['p'])) {
    if (preg_match('/\d+\.*\d*\s*,\s*\d+\.*\d*/', $options['p'])) {
        $size = array_map('floatval', split('\s*,\s*', $options['p']));
    } elseif (is_numeric($options['p'])) {
        $size = [floatval($options['p']), floatval($options['p'])];
    } else {
        $size = $options['p'];
    }
}

// CSV Data file
$data_file = '';
if (isset($options['d'])) {
    $data_file = $options['d'];
}

// Output file/directory
$output = $options['o'];
if (is_dir($output)) {
    $output = realpath(rtrim($output, '/\\'));
}

$y_offset = 0;
if (isset($options['y'])) {
    $y_offset = floatval($options['y']);
}

// Send PDF by email
$email_col_name = 'email';
$send_by_email = true;
if (isset($options['e'])) {
    $email_col_name = $options['e'];
}

$email_subject = 'Certificate of participation';
if (isset($options['s'])) {
    $email_subject = $options['s'];
}

$email_message = isset($options['m']) ? file_get_contents($options['m']) : 'Here is your certificate';
if (isset($options['r'])) {
    if (preg_match('/(.*);(.*@.*)/', $options['r'], $matches)) {
        $email_from_name = $matches[1];
        $email_from = $matches[2];
    } else {
        $email_from = $options['r'];
        $email_from_name = $email_from;
    }
} else {
    $email_from = 'example@certificategenerator.com';
    $email_from_name = $email_from;
}

// Get any email attchament
$attchments = [];
if (isset($options['a'])) {
    $attchments = explode(',', $options['a']);
}

if (!empty($data_file)) {
    if (false === is_dir($output)) {
        echo "Output is not a directory. Please, provide a directory to output PDF's\n";
        exit;
    }

    if (false !== ($handle = fopen($data_file, 'r'))) {
        $csv_header = fgetcsv($handle, 1000, DELIMITER);
        $send_by_email = in_array($email_col_name, $csv_header);

        $i = 0;
        $mailer = new CertMailer();
        while (false !== ($data = fgetcsv($handle, 1000, DELIMITER))) {
            if (count($data) > 0) {
                $row = [];
                foreach ($data as $key => $value) {
                    $row[$csv_header[$key]] = preg_replace('/\x{FEFF}/u', '', $value);
                }
                print_r($row);

                $output_file = isset($row[$email_col_name]) ? $output.DIRECTORY_SEPARATOR.strtolower(trim($row[$email_col_name])).'.pdf' : $output.DIRECTORY_SEPARATOR.$i.'pdf';
                // create new PDF document
                $pdf = new CERTIFICATEPDF('L', 'cm', $size, true, 'UTF-8', false, false);
                $pdf->create_pdf($output_file, $input_html, $y_offset, $row);
                unset($pdf);
                if ($send_by_email && file_exists($output_file)) {
                    $email_to = $row[$email_col_name];
                    $mailer->send_mail($email_to, $email_subject, $email_message, $email_from, $email_from_name, $output_file, $attchments);
                }
                //echo $output_file."\n";
                ++$i;
            }
        }

        fclose($handle);
    }
} else {
    $pdf = new CERTIFICATEPDF('L', 'cm', $size, true, 'UTF-8', false, false);
    $pdf->create_pdf($output_file, $input_html, $y_offset);
}
