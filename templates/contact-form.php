<div class="modal fade" id="sbp-form" role="dialog" aria-labelledby="sbp-form"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel"><?php echo __('New Booking', SBP); ?></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="booking_list-form" action="#" method="post"
                      data-url="<?php echo admin_url('admin-ajax.php'); ?>">

                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <div class="field-container form-group">
                                <label for="status"><?php echo __('Status:', SBP); ?></label>
                                <select name="status" id="status" class="form-control">
                                    <option value="2"><span class="booked"><?php echo __('Booked', SBP); ?></span>
                                    </option>
                                    <option value="3"><span
                                                class="pre-booked"><?php echo __('Pre-booked', SBP); ?></span>
                                    </option>
                                    <option value="4"><span class="blocked"><?php echo __('Blocked', SBP); ?></span>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <div id="picker-container">
                                <label for="check_in"><?php echo __('Check in date', SBP); ?></label>
                                <input type="text" class="field-input form-control" id="check_in"
                                       name="check_in" value=""
                                       required>
                                <small class="field-msg error"
                                       data-error="invalidCheckIn"><?php echo __('Selected dates should be with price', SBP); ?></small>
                                <small class="field-msg error"
                                       data-error="invalidCheckInBooked"><?php echo __('Selected dates are booked!', SBP); ?></small>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="check_out"><?php echo __('Check out date', SBP); ?></label>
                            <input type="text" class="field-input form-control" id="check_out"
                                   name="check_out" value=""
                                   required>
                            <small class="field-msg error"
                                   data-error="invalidCheckOut"><?php echo __('Check out date is Required', SBP); ?></small>
                        </div>
                    </div>

                    <hr>

                    <div class="field-container form-group">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <div id="picker-container">
                                    <input type="text" class="field-input form-control"
                                           placeholder="<?php echo __('Name', SBP); ?>" id="name" name="first_name"
                                           required>
                                    <small class="field-msg error"
                                           data-error="invalidFirstName"><?php echo __('Your Name is Required', SBP); ?></small>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <input type="text" class="field-input form-control"
                                       placeholder="<?php echo __('Surname', SBP); ?>" id="last_name"
                                       name="last_name" required>
                                <small class="field-msg error"
                                       data-error="invalidLastName"><?php echo __('Your Surname is Required', SBP); ?></small>
                            </div>
                            <div class="form-group col-md-6">
                                <input type="email" class="field-input form-control form-control-danger"
                                       placeholder="<?php echo __('Email', SBP); ?>" id="email" name="email"
                                       required>
                                <small class="field-msg error form-text text-muted"
                                       data-error="invalidEmail"><?php echo __('The Email address is not valid', SBP); ?></small>
                            </div>
                            <div class="form-group col-md-6">
                                <input type="tel" class="field-input form-control"
                                       placeholder="<?php echo __('Phone number', SBP); ?>" id="phone" name="phone"
                                       required>
                                <small class="field-msg error"
                                       data-error="invalidPhone"><?php echo __('Your Phone Number is Required', SBP); ?></small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="field-container form-group">

                    </div>

                    <div class="form-row">
                        <div class="col">
                            <label for="status"><?php echo __('Booking type:', SBP); ?></label>
                            <select name="booking_type" id="booking_type" class="form-control">
                                <option value="3"><?php echo __('Private', SBP); ?></option>
                                <option value="1"><?php echo __('Corporate', SBP); ?></option>
                            </select>
                        </div>
                        <div class="col">
                            <label for="amount_guests"><?php echo __('Amount of the guests', SBP); ?></label>
                            <input type="number" class="field-input form-control form-control-danger"
                                   placeholder="<?php echo __('Amount of the guests', SBP); ?>" id="amount_guests" name="amount_guests"
                                   min="1">
                            <small class="field-msg error"
                                   data-error="invalidAmountGuests"><?php echo __('Amount of the guests is Required', SBP); ?></small>
                        </div>
                    </div>

                    <hr>

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
                                                       name="addons[<?= $option['addon_id']; ?>]" value="1" class=""
                                                       data-id="<?= $option['addon_id']; ?>">
                                                <label for="addon-<?= $option['addon_id']; ?>">
                                                    <div></div>
                                                </label>
                                            </div>
                                        </div>

                                        <?php
                                    }
                                } ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>

                    <div class="field-container form-group">
                        <div class="form-row">
                            <div class="col">
                                <label for="total_price"><?php echo __('Price', SBP); ?></label>
                                <p id="total_price_view">0 SEK</p>

                            </div>
                            <div class="col">
                                <label for="pay_status"><?php echo __('Status', SBP); ?></label>
                                <select name="pay_status" id="pay_status" class="form-control">
                                    <option value="1"><?php echo __('Not paid', SBP); ?>
                                    </option>
                                    </option>
                                    <option value="2"><?php echo __('Paid', SBP); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="field-container">
                        <div>
                            <button type="submit" class="sbp-estimate-price"><?php echo __('SAVE', SBP); ?></button>
                        </div>
                        <div>
                            <a data-dismiss="modal" aria-label="Close" sty>Cancel</a>
                        </div>
                        <small class="field-msg js-form-submission">
                            <?php echo __('Submission in process, please wait', SBP); ?>&hellip;
                        </small>
                        <small class="field-msg success js-form-success"><?php echo __('Message Successfully submitted, thank you!', SBP); ?>
                        </small>
                        <small class="field-msg error js-form-error"><?php echo __('There was a problem with saving, please
                            try again!', SBP); ?>
                        </small>
                    </div>

                    <input type="hidden" name="action" value="submit_booking">
                    <input type="hidden" name="selected_dates" value="" id="selected_dates">
                    <input type="hidden" name="total_price" id="total_price" value="0">
                    <input type="hidden" name="old_price" id="old_price" value="0">
                    <input type="hidden" name="old_calendar_price" id="old_calendar_price" value="0">
                    <input type="hidden" name="booked_unix_timestamp" id="booked_unix_timestamp" value="">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("sbp-nonce") ?>">

                </form>
                <p id="js-thanks"
                   class="tn_hide"><?php echo __('Booked!', SBP); ?></p>
            </div>
        </div>
    </div>
</div>
