<div class="wrap">
    <h1><?php echo __('Settings Panel', SBP); ?></h1>
    <?php settings_errors(); ?>

    <ul class="nav nav-tabs">
        <li class="<?php echo !isset($_POST["edit_post"]) ? 'active' : '' ?>"><a
                    href="#tab-1"><?php echo __('Your Add-ons', SBP); ?></a></li>
        <li class="<?php echo isset($_POST["edit_post"]) ? 'active' : '' ?>">
            <a href="#tab-2">
                <?php echo isset($_POST["edit_post"]) ? __('Edit', SBP) : __('Add', SBP) ?><?php echo __(' Add-on', SBP); ?>
            </a>
        </li>
        <li class="<?php echo isset($_GET["mail_settings"]) ? 'active' : '' ?>">
            <a href="#tab-3">
                <?php echo __(' Mail & PDF Settings', SBP); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div id="tab-1" class="tab-pane <?php echo !isset($_POST["edit_post"]) ? 'active' : '' ?>">

            <h3><?php echo __('Manage Your Add-ons', SBP); ?></h3>

            <?php
            $options = get_option('sbp_addons') ?: array();

            echo '<table class="cpt-table"><tr><th style="width: 8%">' . __('Name', SBP) . '</th><th style="width: 9%">' . __('Price', SBP) . '</th><th class="text-center" style="width: 6%">' . __('Corporate', SBP) . '</th><th class="text-center" style="width: 6%">' . __('Private', SBP) . '</th><th class="text-center" style="width: 11%">' . __('Per Guest/Fixed', SBP) . '</th><th class="text-center" style="width: 50%">' . __('Short Information', SBP) . '</th><th class="text-center" style="width: 10%">' . __('Actions', SBP) . '</th></tr><tbody class="sortbale-table">';
            $i = 0;
            foreach ($options as $option) {
                $i += 1;
                $corporate = isset($option['corporate']) ? __("TRUE", SBP) : __("FALSE", SBP);
                $private = isset($option['private']) ? __("TRUE", SBP) : __("FALSE", SBP);
                $per_guest_fixed = isset($option['per_guest']) && $option['per_guest'] == 1 ? __("Per Guest", SBP) : __("Fixed", SBP);
                $short_info = (isset($option['short_info']) && !empty($option['short_info']) && $option['short_info'] != "") ? $option['short_info'] : '-';

                echo "<tr data-id=". $option['addon_id'] ."><td>{$option['addon_name']}</td><td>" . __($option['addon_price'], SBP) . " :-</td><td class=\"text-center\">" . __($corporate, SBP) . "</td><td class=\"text-center\">" . __($private, SBP) . "</td><td class=\"text-center\">" . __($per_guest_fixed, SBP) . "</td><td class=\"\">" . __($short_info, SBP) . "</td><td class=\"text-center\">";

                echo '<form method="post" action="" class="inline-block">';
                echo '<input type="hidden" name="edit_post" value="' . $option['addon_id'] . '">';
                submit_button(__('Edit', SBP), 'primary small', 'submit', false);
                echo '</form> ';

                echo '<form method="post" action="options.php" class="inline-block">';
                settings_fields('sbp_addons_settings');
                echo '<input type="hidden" name="remove" value="' . $option['addon_id'] . '">';
                submit_button(__('Delete', SBP), 'delete small', 'submit', false, array(
                    'onclick' => 'return confirm("' . __('Are you sure you want to delete this Add-on? The data associated with it will not be deleted.', SBP) . '");'
                ));
                echo '</form></td></tr>';
            }

            echo '</tbody></table>';
            echo '<div class="alert-box success-order">'.__('The Add-ons order has been updated!',SBP).'</div>';
            ?>

            <form method="post" action="options.php" id="all_info_emails_repeater">
                <?php
                settings_fields('sbp_btn_settings');
                do_settings_sections('sbp_btn_panel');
                submit_button();
                ?>
            </form>

        </div>

        <div id="tab-2" class="tab-pane <?php echo isset($_POST["edit_post"]) ? 'active' : '' ?>">
            <form method="post" action="options.php">
                <?php
                settings_fields('sbp_addons_settings');
                do_settings_sections('sbp_settings_panel');
                submit_button();
                ?>
            </form>
        </div>
        <div id="tab-3" class="tab-pane <?php echo isset($_GET["mail_settings"]) ? 'active' : '' ?>">
            <form method="post" action="options.php">
                <?php
                settings_fields('sbp_mail_settings');
                do_settings_sections('sbp_mail_panel');
                submit_button();
                ?>
            </form>
        </div>
    </div>
</div>