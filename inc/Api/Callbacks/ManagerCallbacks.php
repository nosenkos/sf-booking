<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Api\Callbacks;

use SnowfallBooking\Base\BaseController;

class ManagerCallbacks extends BaseController
{
    public function checkboxSanitize($input)
    {
        $output = array();

        foreach ($this->managers as $key => $value) {
            $output[$key] = isset($input[$key]) ? true : false;
        }

        return $output;
    }

    public function textSanitize($input)
    {
        if (get_option('sbp_calendar_price') && get_option('sbp_calendar_price') !== null && !empty(get_option('sbp_calendar_price'))) {
            $output = get_option('sbp_calendar_price');
        } else {
            $output = array();
        }

        if (isset($input['adminCalendar']) && !empty($input['adminCalendar']) && isset($input['adminPrice']) && !empty($input['adminPrice'])) {
            $arr = explode(',', $input['adminCalendar']);
            foreach ($arr as $key => $value) {
                $output[trim($value)] = esc_html($input['adminPrice']);
            }
        } else {
            $arr = explode(',', $input['adminCalendar']);
            foreach ($arr as $key => $value) {
                if (isset($output[trim($value)])) {
                    unset($output[trim($value)]);
                }
            }

        }

        return $output;
    }

    public function btnSanitize($input)
    {
        $output = array();

        if (isset($input['all_info_emails']) && !empty($input['all_info_emails'])) {
            foreach (array_filter($input['all_info_emails'], function ($value) {
                return $value !== '';
            }) as $key => $item) {
                if (!filter_var($item, FILTER_VALIDATE_EMAIL)) {
                    add_settings_error('all_info_emails', 'input', __('Invalid email format: ') . $item, 'error');
                    unset($input['all_info_emails'][$key]);
                }
            }
        }

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output[$key] = array_filter($value, function ($value) {
                    return $value !== '';
                });
            } else {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    public function mailSanitize($input)
    {
        $output = array();

        foreach ($input as $key => $value) {
            $output[$key] = esc_html($value);
        }

        return $output;
    }

    public function adminSectionManager()
    {
        echo __('Set up rent price for each date.', SBP);
    }

    public function adminSectionBtnManager()
    {
        echo __('All info from booking flow should be send to:', SBP);
    }

    public function adminSectionMailManager()
    {
        echo __('Manage the Mail of this Plugin by fill fields from the following list.', SBP);
    }

    public function checkboxField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $checkbox = get_option($option_name);
        $checked = isset($checkbox[$name]) ? ($checkbox[$name] ? true : false) : false;

        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="1" class="" ' . ($checked ? 'checked' : '') . '><label for="' . $name . '"><div></div></label></div>';
    }

    public function textField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $placeholder = $args['placeholder'];
        $option_name = $args['option_name'];
        $value = get_option($option_name)[$name];
        $admin_email = (isset($args['admin_email']) && !empty($args['admin_email']) && $args['admin_email'] != "") ? "<span>&lt;" . $args['admin_email'] . "&gt; &lt;--" . __('Admin Email', SBP) . "</span>" : "";

        echo '<div class="' . $classes . '"><input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" placeholder="' . __($placeholder, SBP) . '"> ' . __($admin_email, SBP) . '</div>';
    }

    public function textEmailField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $placeholder = $args['placeholder'];
        $option_name = $args['option_name'];
        $values = isset(get_option($option_name)[$name]) && count(get_option($option_name)[$name]) > 0 ? get_option($option_name)[$name] : false;
        if ($values) {
            foreach ($values as $key => $value) {
                echo '<div class="' . $classes . '"><input type="email" class="regular-text" id="' . $name . $key . '" name="' . $option_name . '[' . $name . '][' . $key . ']" value="' . $value . '" placeholder="' . __($placeholder, SBP) . '" autocomplete="off" ><a class="button button-small remove-row" href="#' . $key . '">' . __('Remove', SBP) . '</a></div>';
            }
        } else {
            echo '<div class="' . $classes . '"><input type="email" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . '][]" value="" placeholder="' . __($placeholder, SBP) . '" autocomplete="off" ><a class="button button-small remove-row" href="#1">' . __('Remove', SBP) . '</a></div>';
        }
        echo '<div class="' . $classes . 'empty-row screen-reader-text hidden"><input type="email" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . '][]" value="" placeholder="' . __($placeholder, SBP) . '" autocomplete="false" ><a class="button button-small remove-row" href="#">' . __('Remove', SBP) . '</a></div>';
        echo '<p><a id="add-row" class="button-small button-primary" href="#">' . __('Add Email', SBP) . '</a></p>';
    }

    public function calendarField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $placeholder = $args['placeholder'];
        $option_name = $args['option_name'];

        echo '<div class="' . $classes . '"><input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="" placeholder="' . __($placeholder, SBP) . '">
                    <div class="ui-toggle can-toggle vat">
                                                <span>'.__('EXCL.', SBP).'</span>
                                                <input type="checkbox" id="vat-status" checked>
                                                <label for="vat-status">
                                                    <div></div>
                                                </label>
                                                 <span>'.__('INCL.', SBP).'</span>
                                            </div></div>';
    }

    public function numberField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $placeholder = $args['placeholder'];
        $option_name = $args['option_name'];
        $currency = (isset($args['currency']) && !empty($args['currency']) && $args['currency'] != "") ? "<span>" . $args['currency'] . "</span>" : "";
        $value = (isset($args['show_value']) && $args['show_value'] == true && isset(get_option($option_name)[$name]) && !empty(get_option($option_name)[$name])) ? get_option($option_name)[$name] : "";

        echo '<div class="' . $classes . '"><input type="number" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" placeholder="' . __($placeholder, SBP) . '"> ' . __($currency, SBP) . '</div>';
    }

    public function selectField($args)
    {
        $html = "";

        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $list = $args['selectFields'];
        $select = get_option($option_name)[$name];

        $html .= '<div class="' . $classes . '"><select id="' . $name . '" name="' . $option_name . '[' . $name . ']" >';
        $html .= '<option value="" ' . (($select == "") ? 'selected="selected"' : '') . '>--' . __('Default', SBP) . '--</option>';
        foreach ($list as $row):
            $html .= '<option value="' . $row['value'] . '" ' . (($select == $row['value']) ? 'selected="selected"' : '') . '>' . $row['text'] . '</option>';
        endforeach;
        $html .= '</select>';
        $html .= ' <span>' . __('Default leads on Front Page of your site.', SBP) . '</span>';
        $html .= '</div>';

        echo $html;
    }

    public function fileUpload($args)
    {
        $html = "";

        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $value = get_option($option_name)[$name];

        $html .= '<div class="' . $classes . '">';
        $html .= '<input type="text" class="regular-text widefat image-upload" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '">';
        $html .= '<button type="button" class="button button-primary js-image-upload">' . __("Select Image", SBP) . '</button>';
        $html .= ' <span>' . __('Image should have resolution 440x94px.', SBP) . '</span>';
        $html .= '</div>';

        echo $html;
    }

    public function wysiwygField($args)
    {
        $html = "";

        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $value = (isset(get_option($option_name)[$name])) ? get_option($option_name)[$name] : '';


        $html .= '<div class="' . $classes . '">';
        $html .= wp_editor(html_entity_decode($value), esc_attr($name), $settings = array('wpautop' => true, 'media_buttons' => false, 'textarea_name' => esc_attr($option_name . '[' . $name . ']'), 'teeny' => false));
        $html .= '</div>';

        echo $html;
    }
}