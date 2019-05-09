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
use Dompdf\Dompdf;
use Dompdf\Options;

class PDFController extends BaseController
{
    public $dompdf;

    protected $body_css = array();

    public $body;

    public $subject;

    public $mail_settings;

    public $data = array();

    public $selected_date_with_price = array();

    public $selected_date_subject;

    public $addons_text;

    public $booked_days = 0;

    public function register($data)
    {
        // get Mail Settings
        $this->mail_settings = get_option('sbp_mail');

        //set booking data
        $this->data = $data;
        $this->setDatePrice();

        $this->setCSS();
        $this->setAddons();

        $this->setBody();

//        $this->output_pdf();
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
            $this->selected_date_subject = $arr[0] . '—' . array_pop($arr);
        } else {
            $this->selected_date_subject = $arr[0];
        }
    }

    public function setAddons()
    {
        $this->addons_text = "";
        $addons = $this->data['addons'];

        if (isset($addons) && !empty($addons)) {
            $this->addons_text .= '<ul style="' . $this->body_css['h3_padding'] . '">';
            foreach ($addons as $key => $val) {
                $addon = $this->getAddon($key);
                if (isset($addon['per_guest']) && $addon['per_guest'] == 1) {
                    $per_guest = __('Per Guest', SBP);
                    $total_price = $addon['addon_price'] * $this->booked_days * $this->data['amount_guests'];
                    $guest = ($this->data['amount_guests'] > 1) ? __('guests', SBP) : __('guest', SBP);
                    $day = ($this->booked_days > 1) ? __('days', SBP) : __('day', SBP);
                    $this->addons_text .= "<li>" . $addon['addon_name'] . ' — ' . $addon['addon_price'] . __(' SEK', SBP) . ' * ' . $this->booked_days . ' ' . $day . ' * ' . $this->data['amount_guests'] . ' ' . $guest . ' = <b>' . $total_price . __(' SEK', SBP) . '</b> (' . $per_guest . ")<br><small style='" . $this->body_css['small'] . "'>" .
                        $addon['short_info'] . "</small></li>";
                } else {
                    $per_guest = __('Fixed Price', SBP);
                    $total_price = $addon['addon_price'] * $this->booked_days;
                    $day = ($this->booked_days > 1) ? __('days', SBP) : __('day', SBP);
                    $this->addons_text .= "<li>" . $addon['addon_name'] . ' — ' . $addon['addon_price'] . __(' SEK', SBP) . ' * ' . $this->booked_days . ' ' . $day . ' = <b>' . $total_price . __(' SEK', SBP) . '</b> (' . $per_guest . ")<br><small style='" . $this->body_css['small'] . "'>" .
                        $addon['short_info'] . "</small></li>";
                }
            }
            $this->addons_text .= '</ul>';
        }
    }

    public function setCSS()
    {
        $this->body_css['header'] = 'background: #b9ced9;text-align: center;padding: 0;width:100%;';

        $this->body_css['dib'] = 'margin: 0 auto;box-sizing:border-box';

        $this->body_css['img'] = 'max-width:440px;box-sizing:border-box';

        $this->body_css['h1'] = 'padding: 0 0 20px; margin: 0;box-sizing:border-box;';

        $this->body_css['bg'] = 'background-color: #b0c8d4;';

        $this->body_css['p20'] = 'padding: 20px;';

        $this->body_css['sbp_info'] = 'display: inline-block; padding: 0 0 5px;margin: 0;';

        $this->body_css['h3'] = 'color: #305a75;';

        $this->body_css['h3_padding'] = 'padding: 0 0 20px; margin: 0;';

        $this->body_css['sbp_info_desc'] = 'padding: 0 0 5px; margin: 0;';

        $this->body_css['td'] = 'padding: 0;';

        $this->body_css['w100'] = 'width:100%;';

        $this->body_css['small'] = 'display:inline-block;font-size:80%;padding-left: 20px;margin-bottom: 10px;font-style:italic';

        $this->body_css['body'] = 'width: 100%;padding: 25px 30px 15px 30px;background: #fff;box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.25);margin:0;border-spacing: 0;border-collapse: unset;';

        $this->body_css['btn'] = 'color: #fff !important;padding:0 63px;font-size: 15px;font-weight: 600;line-height: 52px;display: inline-block;vertical-align:middle;text-transform: uppercase;text-align: center;background-color: #226fa9;text-decoration: none;';
    }

    public function setBody()
    {
        $front_page = get_site_url();
        $image = ($this->mail_settings['email_logo'] && $this->mail_settings['email_logo'] != "") ? $this->mail_settings['email_logo'] : get_theme_mod('header_logo', '');

        $this->body .= '<html><head>';
        $this->body .= '</head>';
        $this->body .= '<body style="' . $this->body_css['bg'] . ' ' . $this->body_css['p20'] . '">';
        $this->body .= '<table class="container" cellpadding="0" style="' . $this->body_css['body'] . '"><tbody>';
        $this->body .= '<tr class="header" style="' . $this->body_css['header'] . '"><td style="' . $this->body_css['dib'] . '"><a href="' . $front_page . '" target="_blank"><img src="' . $image . '" height="94" alt="' . __('Logo', SBP) . '" style="' . $this->body_css['img'] . '"></a></td></tr>';
        $this->body .= '</tbody></table>';
        $this->body .= '<table class="container" cellpadding="0" style="' . $this->body_css['body'] . '"><tbody>';
        if (isset($this->mail_settings['email_confirmation']) && $this->mail_settings['email_confirmation'] && $this->mail_settings['email_confirmation'] != ""):
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
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . ' ' . $this->body_css['w100'] . '">' . __('Selected Dates: ', SBP) . '</h3><small><b>' . __('Price Per Day (EXCL. VAT)', SBP) . '</b></small>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<ul style="' . $this->body_css['h3_padding'] . '">';
            foreach ($this->selected_date_with_price as $key => $value) {
                $this->body .= '<li>' . $key . ' — <b>' . number_format($value, 0, ',', ' ') . __(' SEK', SBP) . '</b></li>';
            }
            $this->body .= '</ul>';
            $this->body .= '</td>';
            $this->body .= '</tr>';
        endif;

        if ($this->data['addons']):
            $this->body .= '<tr>';
            $this->body .= '<td class="sbp_info">';
            $this->body .= '<h3 style="' . $this->body_css['h3'] . ' ' . $this->body_css['sbp_info'] . ' ' . $this->body_css['w100'] . '">' . __('Add-ons: ', SBP) . '</h3><small><b>' . __('Price Per Day (EXCL. VAT) & Per Person', SBP) . '</b></small>';
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
//
        $this->body .= '</tbody></table>';
        $this->body .= '</body></html>';
    }


    public function output_pdf()
    {
        // Uncomment only if you want to save pdf in server
        if (!file_exists($this->upload_path . '/sbp')) {
            mkdir($this->upload_path . '/sbp', 0777, true);
            file_put_contents($this->upload_path . '/sbp/index.php', '<?php // silence is gold ?>');

        }

        $date = date('h-i-s_m-d-Y', time());
        $filename = $date . '_ulvhall-booking_' . $this->selected_date_subject . '.pdf';

//        error_log(print_r($this->body, true));

        // options. Step 1
        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $options->isHtml5ParserEnabled(true);

        // Create onject. Step 2
        $this->dompdf = new Dompdf($options);

        // load html. Step 3
        $html = preg_replace('/>\s+</', "><", $this->body);
        $this->dompdf->loadHtml($html, 'UTF-8');

        // set Paper. Step 4
        $this->dompdf->setPaper('A4');

        // Render the HTML as PDF
        $this->dompdf->render();

        $output = $this->dompdf->output();

        // Uncomment only if you want to save data in server
        if (!file_put_contents($this->upload_path . '/sbp/' . $filename, $output)) {
            throw new \Exception("Unexpected error with PDF Creating!!!");
        }

        return $this->upload_url . '/sbp/' . $filename;

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