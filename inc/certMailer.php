<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class CertMailer.
 */
class CertMailer
{
    /**
     * PHPMailer instance.
     *
     * @var PHPMailer
     */
    private $_mailer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_mailer = new PHPMailer();
        $this->_mailer->isSMTP();
        $this->_mailer->SMTPDebug = SMTP_DEBUG;
        if (SMTP_DEBUG) {
            $this->_mailer->Debugoutput = 'html';
        }
        $this->_mailer->CharSet = CHARSET;
        $this->_mailer->Host = MAIL_HOST;
        $this->_mailer->Port = MAIL_PORT;
        $this->_mailer->SMTPSecure = SMTP_SECURE;
        $this->_mailer->SMTPAuth = SMTP_AUTH;
        if ($this->_mailer->SMTPAuth) {
            $this->_mailer->Username = MAIL_USERNAME;
            $this->_mailer->Password = MAIL_PASSWORD;
        }
        $this->_mailer->isHTML(HTML_BODY);
        $this->_mailer->SMTPKeepAlive = SMTP_ALIVE;
    }

    /**
     * Send email with PDF certificate as attachment.
     *
     * @param string $to                recipient email
     * @param string $subject           email subject
     * @param string $message           email message
     * @param string $from              sender email
     * @param string $from_name         sender name
     * @param string $attachment        main attachment (certificate)
     * @param array  $other_attachments additional attachments
     */
    public function send_mail($to, $subject, $message, $from, $from_name = '', $attachment = '', $other_attachments = [])
    {
        $this->_mailer->setFrom($from, $from_name);
        // $this->_mailer->addReplyTo($from, $from_name);
        $this->_mailer->addAddress($to, '');
        $this->_mailer->Subject = $subject;
        $this->_mailer->Body = $message;
        $this->_mailer->addAttachment($attachment);

        foreach ($other_attachments as $attch) {
            $this->_mailer->addAttachment($attch);
        }

        //send the message, check for errors
        if (!$this->_mailer->send()) {
            echo 'Mailer Error: '.$this->_mailer->ErrorInfo;
        }

        $this->_mailer->clearAddresses();
        $this->_mailer->clearReplyTos();
        $this->_mailer->clearAttachments();
        $this->_mailer->clearAllRecipients();
    }
}
