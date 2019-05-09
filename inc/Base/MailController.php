<?php
/**
 * Created by PhpStorm.
 * User: sergeynosenko
 * Date: 25.09.2018
 * Time: 18:04
 */

namespace SnowfallBooking\Base;

use Braintree\Exception;
use SnowfallBooking\Base\BaseController;

class MailController extends BaseController
{

    private $header;

    protected $body_css = array();

    public $body;

    public $subject;

    public $mail_settings;

    public $emails_addresses;

    public $data = array();

    public $selected_date_with_price = array();

    public $selected_date_subject;

    public $addons_text;

    public $booked_days = 0;

    public function register($data)
    {
        // get Mail Settings
        $this->mail_settings = get_option('sbp_mail');
        $this->emails_addresses = isset(get_option('sbp_btn')['all_info_emails']) && !empty(get_option('sbp_btn')['all_info_emails']) ? get_option('sbp_btn')['all_info_emails'] : false;

        //set booking data
        $this->data = $data;
        $this->setDatePrice();

        $this->setCSS();
        $this->setAddons();

        $this->setHeader();

        $this->setBody();

        $this->setSubject();

        //add image
        add_action('phpmailer_init', array($this, 'attachInlineImage'));

        $this->send_mail();
    }

    public function setDatePrice()
    {
        $selected_price_date = get_option('sbp_calendar_price');
        $arr = explode(',', $this->data['selected_dates']);
        $this->booked_days = count($arr);
        foreach ($arr as $key => $value) {
            if (isset($selected_price_date[trim($value)])) {
                $this->selected_date_with_price[trim($value)] = $selected_price_date[trim($value)];
            } else {
                $this->selected_date_with_price[trim($value)] = '0';
            }
        }

        if (count($arr) > 1) {
            $this->selected_date_subject = $arr[0] . ' — ' . array_pop($arr);
        } else {
            $this->selected_date_subject = $arr[0];
        }
    }

    public function setAddons()
    {
        $this->addons_text = "";
        $addons = $this->data['addons'];

        if (isset($addons) && !empty($addons)) {
            $this->addons_text .= '<ul>';
            foreach ($addons as $key => $val) {
                $addon = $this->getAddon($key);
                if (isset($addon['per_guest']) && $addon['per_guest'] == 1) {
                    $per_guest = __('Per Guest', SBP);
                    $total_price = $addon['addon_price'] * $this->booked_days * $this->data['amount_guests'];
                    $guest = ($this->data['amount_guests'] > 1) ? __('guests', SBP) : __('guest', SBP) ;
                    $day = ($this->booked_days > 1) ? __('days', SBP) : __('day', SBP) ;
                    $this->addons_text .= "<li>" . $addon['addon_name'] . ' — ' . $addon['addon_price'] . __(' SEK', SBP) . ' * ' . $this->booked_days . ' ' . $day . ' * ' . $this->data['amount_guests'] . ' ' . $guest . ' = <b>' . $total_price . __(' SEK', SBP) . '</b> (' . $per_guest . ")<br><small style='" . $this->body_css['small'] . "'>" .
                        $addon['short_info'] . "</small></li>";
                } else {
                    $per_guest = __('Fixed Price', SBP);
                    $total_price = $addon['addon_price'] * $this->booked_days;
                    $day = ($this->booked_days > 1) ? __('days', SBP) : __('day', SBP) ;
                    $this->addons_text .= "<li>" . $addon['addon_name'] . ' — ' . $addon['addon_price'] . __(' SEK', SBP) . ' * ' . $this->booked_days . ' ' . $day . ' = <b>' . $total_price . __(' SEK', SBP) . '</b> (' . $per_guest . ")<br><small style='" . $this->body_css['small'] . "'>" .
                        $addon['short_info'] . "</small></li>";
                }
            }
            $this->addons_text .= '</ul>';
        }
    }

    public function setCSS()
    {
        $this->body_css['header'] = 'background: #b9ced9;text-align: center;padding: 0;display: flex;width:100%;';

        $this->body_css['dib'] = 'margin: 0 auto;';

        $this->body_css['img'] = 'max-width:440px;';

        $this->body_css['h1'] = 'padding: 0 0 20px; margin: 0;';

        $this->body_css['bg'] = 'background-color: #b0c8d4;';

        $this->body_css['p20'] = 'padding: 20px;';

        $this->body_css['sbp_info'] = 'display: table-cell; padding: 0 0 5px;margin: 0;';

        $this->body_css['h3'] = 'color: #305a75;';

        $this->body_css['h3_padding'] = 'padding: 0 0 20px; margin: 0;';

        $this->body_css['sbp_info_desc'] = 'padding: 0 0 5px; margin: 0;';

        $this->body_css['w100'] = 'width:100%;';

        $this->body_css['small'] = 'display:inline-block;font-size:80%;padding-left: 20px;margin-bottom: 10px;font-style:italic';

        $this->body_css['body'] = 'padding: 25px 30px 15px 30px;background: #fff;box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.25);margin: 0 auto;';

        $this->body_css['btn'] = 'color: #fff !important;padding:0 63px;font-size: 15px;font-weight: 600;line-height: 52px;display: inline-block;text-transform: uppercase;text-align: center;background-color: #226fa9;text-decoration: none;';
    }

    public function setHeader()
    {
        $from = ($this->mail_settings['email_from'] && $this->mail_settings['email_from'] != "") ? $this->mail_settings['email_from'] : __('Ulvhäll', SBP);

        // write the email content
        $this->header .= "MIME-Version: 1.0\n";
        $this->header .= "Content-Type: text/html; charset=utf-8\n";
        $this->header .= "From: " . $from . " <" . $this->admin_email . ">\n";
        $this->header .= "Reply-To: " . $from . " <" . $this->admin_email . ">";
    }

    public function setBody()
    {
        $front_page = get_site_url();

        $this->body .= '<html><head>';
        $this->body .= '</head>';
        $this->body .= '<body style="' . $this->body_css['bg'] . ' ' . $this->body_css['p20'] . '">';
        $this->body .= '<table width="500px" class="container"  cellspacing="0" cellpadding="0" style="' . $this->body_css['body'] . '"><tbody>';
        $this->body .= '<tr class="header" style="' . $this->body_css['header'] . '"><td style="' . $this->body_css['dib'] . '"><a href="' . $front_page . '" target="_blank"><img src="cid:tn_logo" height="94" style="' . $this->body_css['img'] . '"></a></td></tr>';
        $this->body .= '</tbody></table>';
        $this->body .= '<table width="500px" class="container"  cellspacing="0" cellpadding="0" style="' . $this->body_css['body'] . '"><tbody>';
        if ($this->mail_settings['email_confirmation'] && $this->mail_settings['email_confirmation'] != ""):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info_desc">';
            $this->body .= $this->wpautop_with_class(htmlspecialchars_decode(wpautop($this->mail_settings['email_confirmation'])));
            $this->body .= '</td>';
            $this->body .= '</tr>';

            $this->body .= '<tr>';
            $this->body .= '<td class="hr">';
            $this->body .= '<hr>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;


        if ($this->data['first_name'] || $this->data['last_name']):
            $this->body .= '<tr>';
            $this->body .= '<td>';
            $this->body .= '<h1 style="' . $this->body_css['h1'] . '">' . $this->data['first_name'] . " " . $this->data['last_name'] . '</h1>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['email']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Email: ', SBP) . '</h3>';
            $this->body .= '<p style="' . $this->body_css['sbp_info'] . '"><a href="mailto:' . $this->data['email'] . '" target="_blank">' . $this->data['email'] . '</a></p>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['phone']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Phone: ', SBP) . '</h3>';
            $this->body .= '<p style="' . $this->body_css['sbp_info'] . '"><a href="tel:' . $this->data['phone'] . '" target="_blank">' . $this->data['phone'] . '</a></p>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['booking_type']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Booking Type: ', SBP) . '</h3>';
            $this->body .= '<p style="' . $this->body_css['sbp_info'] . '">' . $this->getBookingType($this->data['booking_type']) . '</p>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['amount_guests']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Amount of the guests: ', SBP) . '</h3>';
            $this->body .= '<p style="' . $this->body_css['sbp_info'] . '">' . $this->data['amount_guests'] . '</p>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['selected_dates']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Selected Dates: ', SBP) . '</h3><small><b>' . __('Price Per Day (EXCL. VAT)', SBP) . '</b></small>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
            foreach ($this->selected_date_with_price as $key => $value) {
                $this->body .= '<tr>';
                $this->body .= '<td class="sbp_info">';
                $this->body .= '<p style="' . $this->body_css['sbp_info'] . '">' . $key . ' — <b>' . number_format($value, 0, ',', ' ') . __(' SEK', SBP) . '</b></p>';
                $this->body .= '</td>';
                $this->body .= '</tr>';
            }
        endif;

        if ($this->data['addons']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Add-ons: ', SBP) . '</h3><small><b>' . __('Price Per Day (EXCL. VAT) & Per Person', SBP) . '</b></small>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= $this->addons_text;
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['booking_type'] == 3):
            $inclVat = '<span><b>' . __(' (INCL. VAT)', SBP) . '</b></span>';
        else:
            $inclVat = '<span><b>' . __(' (EXCL. VAT)', SBP) . '</b></span>';
        endif;

        if ($this->data['total_price']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . '">' . __('Total Price: ', SBP) . '</h3>';
            $this->body .= '<p style="' . $this->body_css['sbp_info'] . '"><b>' . number_format($this->data['total_price'], 0, ',', ' ') . __(' SEK', SBP) . '</b>' . $inclVat . '</p>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        $this->body .= '<tr>';
        $this->body .= '<td class="hr">';
        $this->body .= '<hr>';
        $this->body .= '</td>';
        $this->body .= '</tr>';

        $this->body .= '<tr>';
        $this->body .= '<td class="btn">';
        $this->body .= '<a href="' . $front_page . '" class="sbp-booking" target="_blank" style="' . $this->body_css['btn'] . '">' . __('Check New Dates', SBP) . '</a>';
        $this->body .= '</td>';
        $this->body .= '</tr>';

        $this->body .= '</tbody></table>';
        $this->body .= '</body></html>';
    }

    public function setSubject()
    {
        $subject = ($this->mail_settings['email_subject'] && $this->mail_settings['email_subject'] != "") ? $this->mail_settings['email_subject'] : __('Ulvhäll Booking, Date', SBP);
        $this->subject = $subject . ": " . $this->selected_date_subject;
        $this->subject = "=?utf-8?B?" . base64_encode($this->subject) . "?=";
    }

    public function attachInlineImage()
    {
        global $phpmailer;
        $image = ($this->mail_settings['email_logo'] && $this->mail_settings['email_logo'] != "") ? $this->mail_settings['email_logo'] : get_theme_mod('header_logo', '');
        if ($image) {
            $file_info = explode('/', $image);//phpmailer will load this file
            $file_name = array_slice($file_info, -1, 1);
            $file_path = implode('/', array_slice($file_info, 4));

            $file = dirname(__FILE__, 5) . "/" . $file_path;
            $uid = 'tn_logo'; //will map it to this UID
            $name = $file_name[0]; //this will be the file name for the attachment

//            $phpmailer->isSMTP();
//            $phpmailer->Host = 'smtp.mailtrap.io';
//            $phpmailer->SMTPAuth = true;
//            $phpmailer->Port = 2525;
//            $phpmailer->Username = 'ef3bb4469b1f4e';
//            $phpmailer->Password = '67b76fad914e3c';
            $phpmailer->AddEmbeddedImage($file, $uid, $name);
        }
    }

    public function send_mail()
    {
        $multiple_recipients = array(
            $this->data['email']
        );

        if ($this->emails_addresses && count($this->emails_addresses) > 0) {
            foreach ($this->emails_addresses as $address) {
                $multiple_recipients[] = $address;
            }
        } else {
            $multiple_recipients[] = $this->admin_email;
        }

        if (empty($multiple_recipients) && count($multiple_recipients) == 0) {
            throw new \Exception("The User Email is Empty!");
        }

        if (!wp_mail($multiple_recipients, $this->subject, $this->body, $this->header)) {
            throw new \Exception("Unexpected error with Sending!!!");
        }
    }

    public function wpautop_with_class($args)
    {
        $page_cont = $args;
        $added_class = str_replace(array('<p style="', '<h1 style="', '<h2 style="', '<h3 style="', '<h4 style="', '<h5 style="', '<h6 style="', '<p>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>'),
            array('<p style="' . $this->body_css['sbp_info_desc'],
                '<h1 style="' . $this->body_css['h3_padding'],
                '<h2 style="' . $this->body_css['h3_padding'],
                '<h3 style="' . $this->body_css['h3_padding'],
                '<h4 style="' . $this->body_css['h3_padding'],
                '<h5 style="' . $this->body_css['h3_padding'],
                '<h6 style="' . $this->body_css['h3_padding'],
                '<p style="' . $this->body_css['sbp_info_desc'] . '">',
                '<h1 style="' . $this->body_css['h3_padding'] . '">',
                '<h2 style="' . $this->body_css['h3_padding'] . '">',
                '<h3 style="' . $this->body_css['h3_padding'] . '">',
                '<h4 style="' . $this->body_css['h3_padding'] . '">',
                '<h5 style="' . $this->body_css['h3_padding'] . '">',
                '<h6 style="' . $this->body_css['h3_padding'] . '">'
            ),
            $page_cont);
        return $added_class;
    }
}