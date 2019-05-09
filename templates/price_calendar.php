<div class="wrap">
    <h1><?php echo __('Snowfall Booking Plugin', SBP); ?> <button type="button" class="sbp-registration" data-toggle="modal"
                                                                  data-target="#sbp-form"><?php echo __('+ New Booking', SBP); ?></button></h1>
    <?php settings_errors(); ?>
    <?php
    require_once(plugin_dir_path(dirname(__FILE__)) . 'templates/contact-form.php');
    ?>
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-1"><?php echo __('Price Calendar', SBP); ?></a></li>
        <li><a href="/wp-admin/edit.php?post_type=booking_list"><?php echo __('Booking List', SBP); ?></a></li>
            <li><a href="/wp-admin/admin.php?page=sbp_settings_panel"><?php echo __('Settings page', SBP); ?></a></li>
    </ul>

    <div class="tab-content">
        <div id="tab-1" class="tab-pane active">
            <form method="post" action="options.php">
                <?php
                settings_fields('sbp_calendar_price_settings');
                do_settings_sections('sbp_calendar_price');
                submit_button();
                ?>
            </form>
        </div>
    </div>
</div>