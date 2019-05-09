<div class="front-end-booking_list-form">
    <h2><?= __('Your are interested in: ', SBP); ?></h2>
    <form id="booking_list-form" action="#" method="post"
          data-url="<?php echo admin_url('admin-ajax.php'); ?>">

        <div class="form-group">
            <select name="booking_type" id="booking_type" class="form-control">
                <option value="3"><?php echo __('for Private event', SBP); ?></option>
                <option value="1"><?php echo __('for Corporate event', SBP); ?></option>
            </select>
        </div>

        <div class="form-group">
            <input type="number" class="field-input form-control form-control-danger"
                   placeholder="<?php echo __('Amount of the guests', SBP); ?>" id="amount_guests" name="amount_guests"
                   min="1" required>
            <small class="field-msg error"
                   data-error="invalidAmountGuests"><?php echo __('Amount of the guests is Required', SBP); ?></small>
        </div>

        <div class="form-group">
            <input type="text" class="field-input form-control"
                   placeholder="<?php echo __('First Name', SBP); ?>" id="name" name="first_name"
                   required>
            <small class="field-msg error"
                   data-error="invalidFirstName"><?php echo __('Your Name is Required', SBP); ?></small>
        </div>

        <div class="form-group">
            <input type="text" class="field-input form-control"
                   placeholder="<?php echo __('Surname', SBP); ?>" id="last_name"
                   name="last_name" required>
            <small class="field-msg error"
                   data-error="invalidLastName"><?php echo __('Your Surname is Required', SBP); ?></small>
        </div>

        <div class="form-group">
            <input type="email" class="field-input form-control form-control-danger"
                   placeholder="<?php echo __('Email', SBP); ?>" id="email" name="email"
                   required>
            <small class="field-msg error form-text text-muted"
                   data-error="invalidEmail"><?php echo __('The Email address is not valid', SBP); ?></small>
        </div>

        <div class="form-group">
            <input type="tel" class="field-input form-control"
                   placeholder="<?php echo __('Phone number', SBP); ?>" id="phone" name="phone"
                   required>
            <small class="field-msg error"
                   data-error="invalidPhone"><?php echo __('Your Phone Number is Required', SBP); ?></small>
        </div>

        <div>
            <button type="button" class="sbp-estimate-price"
                    id="sbp-front-addon-modal"><?php echo __('Get a price estimate', SBP); ?></button>
        </div>


        <input type="hidden" name="action" value="sending_estimate">
        <input type="hidden" name="selected_dates" value="" id="selected_dates">
        <input type="hidden" name="total_price" id="total_price" value="0">
        <input type="hidden" name="old_price" id="old_price" value="0">
        <input type="hidden" name="old_calendar_price" id="old_calendar_price" value="0">
        <input type="hidden" name="booked_unix_timestamp" id="booked_unix_timestamp" value="">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("sbp-nonce-front") ?>">

        <div class="modal fade" id="sbp-front-sbp-addons" role="dialog" aria-labelledby="sbp-form"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exampleModalLabel"><?php echo __('Choose Add-ons', SBP); ?></h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="front-end-addons">
                            <?php
                            $options = get_option('sbp_addons');
                            if (isset($options)) { ?>
                                <div class="field-container form-group">
                                    <div class="form-row addons-container">
                                        <?php
                                        foreach ($options as $option) {
                                            if (isset($option['private']) && $option['private'] == 1) {
                                                ?>
                                                <div class="form-group col-md-6">
                                                    <div class="ui-toggle">
                                                        <span><?= $option['addon_name']; ?></span>
                                                        <input type="checkbox" id="addon-<?= $option['addon_id']; ?>"
                                                               name="addons[<?= $option['addon_id']; ?>]" value="1"
                                                               class=""
                                                               data-id="<?= $option['addon_id']; ?>">
                                                        <label for="addon-<?= $option['addon_id']; ?>">
                                                            <div></div>
                                                        </label>
                                                    </div>
                                                    <?php
                                                    if (isset($option['short_info']) && !empty($option['short_info']) && $option['short_info'] != '') {
                                                        ?>
                                                        <div class="small-text">
                                                            <?php
                                                            echo $option['short_info'];
                                                            ?>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                    <div class="small-text text-center">
                                                        <?php
                                                        $vat = isset(get_option('sbp_btn')['vat']) && !empty(get_option('sbp_btn')['vat']) ? get_option('sbp_btn')['vat']/100 : 12;
                                                        $price = $option['addon_price'] * $vat;
                                                        echo $option['addon_price'] + $price;
                                                        ?>
                                                    </div>
                                                </div>

                                                <?php
                                            }
                                        } ?>
                                    </div>
                                    <hr>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <div id="front-end-addon-total-price" class="field-container form-group">
                            <h4><?php echo __('Price', SBP); ?></h4>
                            <p id="total_addon_price_view">0 SEK</p>
                            <hr>
                        </div>

                        <div>
                            <button id="sbp-show-estimate"
                                    class="sbp-estimate-price"><?php echo __('Get a price estimate', SBP); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="sbp-front-estimate" role="dialog" aria-labelledby="sbp-form"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exampleModalLabel"><?php echo __('Summary', SBP); ?></h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="front-end-estimate-data" class="field-container form-group form-row">
                            <div id="front-end-estimate-name" class="col-md-6">
                                <h4><?php echo __('Name:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-email" class="col-md-6">
                                <h4><?php echo __('Email:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-phone" class="col-md-6">
                                <h4><?php echo __('Phone:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-booking-type" class="col-md-6">
                                <h4><?php echo __('Booking Type:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-amount-guests" class="col-md-12">
                                <h4><?php echo __('Amount of the guests:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-selected-dates" class="col-md-12">
                                <h4><?php echo __('Dates:', SBP); ?></h4>
                                <p></p>
                                <hr>
                            </div>

                            <div id="front-end-estimate-addons" class="col-md-12">
                                <h4><?php echo __('Add-ons:', SBP); ?>
                                    <small>(<?= __('Price Per Day & Per Person', SBP); ?>)</small>
                                </h4>
                                <p>-</p>
                                <hr>
                            </div>
                        </div>

                        <div id="front-end-total-price" class="field-container form-group">
                            <h4><?php echo __('Price', SBP); ?></h4>
                            <p id="total_price_view">0 SEK</p>
                            <hr>
                        </div>

<!--                        <div id="front-end-summary" class="field-container form-group">-->
<!--                            <h3 class="modal-title">--><?php //_e('See summary:'); ?><!--</h3>-->
<!--                            <select name="summary" id="summary" class="form-control">-->
<!--                                <option value="1">--><?php //echo __('Download a pdf of the summary', SBP); ?><!--</option>-->
<!--                                <option value="2">--><?php //echo __('Send a summary by email', SBP); ?><!--</option>-->
<!--                            </select>-->
<!--                        </div>-->

                        <div id="front-end-summary">
                            <h3 class="modal-title"><?php _e('See summary:'); ?></h3>
                            <button type="submit" id="sbp-estimate-price-email" data-summary="2"
                                    class="sbp-estimate-price sbp-estimate-price-summary"><?php echo __('Send a summary by email', SBP); ?></button>
                            <button type="submit" id="sbp-estimate-price-pdf" data-summary="1"
                                    class="sbp-estimate-price sbp-estimate-price-summary"><?php echo __('Download a pdf of the summary', SBP); ?></button>
<!--                            <button type="submit" id="sbp-estimate-price"-->
<!--                                    class="sbp-estimate-price">--><?php //echo __('Get a price estimate', SBP); ?><!--</button>-->
                            <button type="cancel" id="sbp-estimate-cancel"
                                    class="sbp-estimate-cancel btn-danger"><?php echo __('Cancel', SBP); ?></button>
                        </div>
                        <input type="hidden" name="summary" value="" id="hidden_summary" class="hidden_summary">
                        <p id="js-thanks"
                           class="tn_hide"><?php echo __('Sent!</br>Check yourself mailbox, please!<br>Thanks!', SBP); ?></p>
                        <small class="field-msg js-form-submission">
                            <?php echo __('Sending in process, please wait', SBP); ?>&hellip;
                        </small>
                        <small class="field-msg success js-form-success"><?php echo __('Estimate Successfully sent, thank you!', SBP); ?>
                        </small>
                        <small class="field-msg error js-form-error"><?php echo __('There was a problem with sending, please
                            try again!', SBP); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<div class="modal fade bd-example-modal-sm choose_date" tabindex="-1" role="dialog" aria-labelledby="chooseDate"
     aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5><span class="blocked">Error</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?= __('Choose a date please!', SBP); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-sm selectedDatesStatus" tabindex="-1" role="dialog" aria-labelledby="chooseDate"
     aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5><span class="blocked">Error</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo __('Selected dates should be with price!', SBP); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-sm selectedDatesBooked" tabindex="-1" role="dialog" aria-labelledby="chooseDate"
     aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5><span class="blocked">Error</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo __('Selected dates are booked!', SBP); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-sm formErrors" tabindex="-1" role="dialog" aria-labelledby="chooseDate"
     aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5><span class="blocked">Error</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

