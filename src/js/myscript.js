import 'code-prettify';

window.addEventListener("load", function () {

    PR.prettyPrint();

    // store tabs variables
    var tabs = document.querySelectorAll("ul.nav-tabs > li");

    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener("click", switchTab);
    }

    function switchTab(event) {
        document.querySelector("ul.nav-tabs li.active").classList.remove("active");
        document.querySelector(".tab-pane.active").classList.remove("active");

        var clickedTab = event.currentTarget;
        var anchor = event.target;
        var activePaneID = anchor.getAttribute("href");

        clickedTab.classList.add("active");
        document.querySelector(activePaneID).classList.add("active");

    }

});

jQuery(document).ready(function ($) {
    $(document).on('click', '.js-image-upload', function (e) {
        e.preventDefault();
        var $button = $(this);

        var file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload an Image',
            library: {
                type: 'image' // mime type
            },
            button: {
                text: 'Select Image'
            },
            multiple: false
        });

        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();

            if (attachment.sizes.hasOwnProperty('email_logo')) {
                $button.siblings('.image-upload').val(attachment.sizes.email_logo.url);
            } else {
                $button.siblings('.image-upload').val(attachment.url);
            }
        });

        file_frame.open();
    });

    $('#book-btn').on('click', function (e) {
        e.preventDefault();

        $('#member-form').show();
    });

    $('#add-row').on('click', function (e) {
        e.preventDefault();
        let row = $('#all_info_emails_repeater .empty-row.screen-reader-text').clone(true);
        row.removeClass('empty-row screen-reader-text hidden');
        row.insertBefore('#all_info_emails_repeater tbody>tr:first-of-type>td>div:last');
    });

    $('.remove-row').on('click', function (e) {
        e.preventDefault();
        $(this).parent('div').remove();
    });

    $(".sortbale-table").sortable({
            update: function (event, ui) {
                let array = {};
                $.each($('.sortbale-table > tr'), function (index, element) {
                    array[$(element).data('id')] = index + 1;
                });
                $.ajax({
                    url: wp_admin_data.admin_ajax,
                    method: 'post',
                    data: {
                        'array': array,
                        'action': 'set_addons_order'
                    },
                    success: function (response) {
                        // console.log(response.data);
                            $( "div.success-order" ).fadeIn( 300 ).delay( 1500 ).fadeOut( 400 );
                    }
                });
            }
        }
    );
    $(".sortbale-table").disableSelection();
});