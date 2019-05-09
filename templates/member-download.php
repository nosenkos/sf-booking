<div class="wrap">
    <div id="icon-tools" class="icon32"></div>
    <h2><?php echo __('Download Report', SBP); ?></h2>
    <p><?php echo __('Export Booked People', SBP); ?></p>
    <form method="post" action="edit.php?post_type=booking_list&page=sbp-booking-download-list">
        <div class="form-group">
            <label for="export"><?php echo __('Download
        Format', SBP); ?>
                <select name="export" id="export" class="form-control">
                    <option value="sbp-member-download-list-csv"><?php echo __('Download
        CSV', SBP); ?></option>
                    <option value="sbp-member-download-list-xls"><?php echo __('Download
        Excel', SBP); ?></option>
                </select>
            </label>
        </div>
        <div>
            <button type="stubmit" class="button-primary"><?php echo __('Download',SBP);?></button>
        </div>
    </form>
</div>