<?php
/**
 * @package  Snowfall Booking Plugin
 */

namespace SnowfallBooking\Api\Callbacks;

class AddonCallbacks
{

    public function addonsSectionManager()
    {
        echo __('Create as many Add-ons as you want.', SBP);
    }

    public function addonsSanitize($input)
    {
        $output = get_option('sbp_addons');


        if (isset($_POST["remove"])) {
            unset($output[$_POST["remove"]]);

            return $output;
        }

        if (count($output) == 0) {
            $output[$input['addon_id']] = $input;

            if (!get_option('sbp_addon_last_id') || $input['addon_id'] > get_option('sbp_addon_last_id')) {
                update_option('sbp_addon_last_id', $input['addon_id']);
            }

            return $output;
        }

        if (isset($input['ajax_request']) && !empty($input['ajax_request'])) {
            $output = $input['ajax_request'];
        } else {
            foreach ($output as $key => $value) {
                if ($input['addon_id'] === $key) {
                    $output[$key] = $input;
                } else {
                    if (!get_option('sbp_addon_last_id') || $input['addon_id'] > get_option('sbp_addon_last_id')) {
                        update_option('sbp_addon_last_id', $input['addon_id']);
                    }
                    $output[$input['addon_id']] = $input;
                }
            }
        }

        flush_rewrite_rules();

        return $output;
    }

    public function textField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $value = '';

        if (isset($_POST["edit_post"])) {
            $input = get_option($option_name);
            $value = $input[$_POST["edit_post"]][$name];
        }

        echo '<input type="text" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" required>';
    }

    public function numberField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $value = '';

        if (isset($_POST["edit_post"])) {
            $input = get_option($option_name);
            $value = $input[$_POST["edit_post"]][$name];
        }

        echo '<input type="number" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" required>';
    }

    public function orderField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $input = get_option($option_name);
        $value = (isset($input)) ? count(get_option($option_name)) + 1 : 1;

        if (isset($_POST["edit_post"])) {
            $input = get_option($option_name);
            $value = $input[$_POST["edit_post"]][$name];
        }

        echo '<input type="hidden" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" required>';
    }

    public function idField($args)
    {
        $name = $args['label_for'];
        $option_name = $args['option_name'];
        $value = get_option('sbp_addon_last_id') + 1;

        if (isset($_POST["edit_post"])) {
            $input = get_option($option_name);
            $value = $input[$_POST["edit_post"]][$name];
        }

        echo '<input type="hidden" class="regular-text" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="' . $value . '" placeholder="' . $args['placeholder'] . '" required>';
    }

    public function checkboxField($args)
    {
        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $checked = false;

        if (isset($_POST["edit_post"])) {
            $checkbox = get_option($option_name);
            $checked = isset($checkbox[$_POST["edit_post"]][$name]) ?: false;
        }

        echo '<div class="' . $classes . '"><input type="checkbox" id="' . $name . '" name="' . $option_name . '[' . $name . ']" value="1" class="" ' . ($checked ? 'checked' : '') . '><label for="' . $name . '"><div></div></label></div>';
    }

    public function wysiwygField($args)
    {
        $html = "";

        $name = $args['label_for'];
        $classes = $args['class'];
        $option_name = $args['option_name'];
        $value = '';

        if (isset($_POST["edit_post"])) {
            $wysiwyg = get_option($option_name);
            $value = isset($wysiwyg[$_POST["edit_post"]][$name]) ? $wysiwyg[$_POST["edit_post"]][$name] : '';
        }

        $html .= '<div class="' . $classes . '">';
        $html .= wp_editor(html_entity_decode($value), esc_attr($name), $settings = array('wpautop' => true, 'media_buttons' => false, 'textarea_name' => esc_attr($option_name . '[' . $name . ']'), 'teeny' => false));
        $html .= '</div>';

        echo $html;
    }
}