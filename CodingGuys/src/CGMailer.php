<?php
/**
 * User: kit
 * Date: 31/05/15
 * Time: 13:46
 */

namespace CodingGuys;


class CGMailer {
    public static function prepareAttachment($path) {
        $rn = "\r\n";

        if (file_exists($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $ftype = finfo_file($finfo, $path);
            $file = fopen($path, "r");
            $attachment = fread($file, filesize($path));
            $attachment = chunk_split(base64_encode($attachment));
            fclose($file);

            $msg = 'Content-Type: \'' . $ftype . '\'; name="' . basename($path) . '"' . $rn;
            $msg .= "Content-Transfer-Encoding: base64" . $rn;
            $msg .= 'Content-ID: <' . basename($path) . '>' . $rn;
            $msg .= $rn . $attachment . $rn . $rn;
            return $msg;
        } else {
            return false;
        }
    }

    public static function sendMail($to, $subject, $content, $path = '', $cc = '', $bcc = '', $_headers = false) {

        $rn = "\r\n";
        $boundary = md5(rand());
        $boundary_content = md5(rand());

// Headers
        $headers = 'From: Mail System PHP <dailyRepory@coding-guys.com>' . $rn;
        $headers .= 'Mime-Version: 1.0' . $rn;
        $headers .= 'Content-Type: multipart/related;boundary=' . $boundary . $rn;

        //adresses cc and ci
        if ($cc != '') {
            $headers .= 'Cc: ' . $cc . $rn;
        }
        if ($bcc != '') {
            $headers .= 'Bcc: ' . $cc . $rn;
        }
        $headers .= $rn;

// Message Body
        $msg = $rn . '--' . $boundary . $rn;
        $msg.= "Content-Type: multipart/alternative;" . $rn;
        $msg.= " boundary=\"$boundary_content\"" . $rn;

//Body Mode text
        $msg.= $rn . "--" . $boundary_content . $rn;
        $msg .= 'Content-Type: text/plain; charset=ISO-8859-1' . $rn;
        $msg .= strip_tags($content) . $rn;

//Body Mode Html
        $msg.= $rn . "--" . $boundary_content . $rn;
        $msg .= 'Content-Type: text/html; charset=ISO-8859-1' . $rn;
        $msg .= 'Content-Transfer-Encoding: quoted-printable' . $rn;
        if ($_headers) {
            $msg .= $rn . '<img src=3D"cid:template-H.PNG" />' . $rn;
        }
        //equal sign are email special characters. =3D is the = sign
        $msg .= $rn . '<div>' . nl2br(str_replace("=", "=3D", $content)) . '</div>' . $rn;
        if ($_headers) {
            $msg .= $rn . '<img src=3D"cid:template-F.PNG" />' . $rn;
        }
        $msg .= $rn . '--' . $boundary_content . '--' . $rn;

//if attachement
        if ($path != '' && file_exists($path)) {
            $conAttached = self::prepareAttachment($path);
            if ($conAttached !== false) {
                $msg .= $rn . '--' . $boundary . $rn;
                $msg .= $conAttached;
            }
        }

// Fin
        $msg .= $rn . '--' . $boundary . '--' . $rn;

// Function mail()
        mail($to, $subject, $msg, $headers);
    }
}