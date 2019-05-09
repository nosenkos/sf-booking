import flatpickr from "flatpickr";
import rangePlugin from 'flatpickr/dist/plugins/rangePlugin';
import {eachDay, compareAsc} from 'date-fns'
import {Swedish} from "flatpickr/dist/l10n/sv";


jQuery(document).ready(function ($) {
    if (wp_calendar.language === 'sv') {
        flatpickr.localize(Swedish);
    }

    const singleCalendarSelectedDates = document.getElementById("sbp_booking_list_selected_dates");
    let sbpForm = document.getElementById('booking_list-form');
    let thanksText = document.getElementById('js-thanks');


    let singleCalendarArr = [];

    if (singleCalendarSelectedDates != null) {
        for (let singleDisable of singleCalendarSelectedDates.value.split(',')) {
            singleCalendarArr.push(singleDisable);
        }

        singleCalendarArr = [singleCalendarArr[0], singleCalendarArr[singleCalendarArr.length - 1]]
    }

    let total_price = document.getElementById('total_price') != null ? document.getElementById('total_price').value : 0;
    let dayPrice = 0;
    let oldPrice = +document.getElementById('old_price').value;

    let calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
    let vat = wp_calendar.vat;
    // Make calendar view with VAT
    for (let price in calendarPrices) {
        calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
    }

    let bookingStatus = wp_calendar.bookingStatus;
    let allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
    // Make calendar addons price with VAT
    for (let addon in allAddons) {
        allAddons[addon]['addon_price'] = +allAddons[addon]['addon_price'] + +allAddons[addon]['addon_price'] * +vat;
    }

    //fill by function setDisabledBooking()
    let resultSetDisabledBooking = setDisabledBooking(bookingStatus, singleCalendarArr);
    let disabledBooking = resultSetDisabledBooking['disabledBooking'];

    let booked = resultSetDisabledBooking['booked'];
    let preBooked = resultSetDisabledBooking['preBooked'];
    let blocked = resultSetDisabledBooking['blocked'];

    let data = {};

    // today
    let today = new Date();
    let dd = today.getDate();
    let mm = today.getMonth(); //January is 0!
    let yyyy = today.getFullYear();

// looking for Min Price
    let minPrice = setMinPrice(calendarPrices, disabledBooking);

    // console.log(minPrice)

// Otherwise, selectors are also supported
    const calendarInput = document.getElementById("booking_calendar");
    let calendarOptions = {
        inline: true,
        mode: "range",
        minDate: "today",
        dateFormat: "d-m-Y",
        disable: disabledBooking,
        locale: {
            "firstDayOfWeek": 1 // start week on Monday
        },
        onChange: function (selectedDates, dateStr, instance) {
            // if (selectedDates[0] !== undefined && selectedDates[0] !== null && selectedDates[1] !== undefined && selectedDates[1] !== null) {
            setTotalPrice(selectedDates, calendarPrices);

            let tar = document.getElementById('booking_type').value;

            setDefaultAddons(tar, allAddons);
            // }
        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            // Utilize dayElem.dateObj, which is the corresponding Date
            let curDate = numeroAdosCaracteres(dayElem.dateObj.getDate()) + "-" + numeroAdosCaracteres(dayElem.dateObj.getMonth() + 1) + "-" + dayElem.dateObj.getFullYear();
            let compareDates = compareAsc(
                new Date(dayElem.dateObj.getFullYear(), dayElem.dateObj.getMonth(), dayElem.dateObj.getDate()),
                new Date(yyyy, mm, dd)
            );

            if (compareDates >= 0 && calendarPrices[curDate] != null && calendarPrices[curDate] !== "") {
                if (booked.includes(curDate)) {
                    dayElem.classList.add('booked');
                    dayElem.innerHTML += "<span class='price'>-</span>";
                } else if (preBooked.includes(curDate)) {
                    dayElem.innerHTML += "<span class='price'>-</span>";
                    dayElem.classList.add('pre-booked');
                } else if (blocked.includes(curDate)) {
                    dayElem.innerHTML += "<span class='price'>-</span>";
                    dayElem.classList.add('blocked');
                } else {
                    if (calendarPrices[curDate] == minPrice) {
                        dayElem.classList.add('min-price');
                    }

                    dayElem.innerHTML += "<span class='price'>" + formatMoney(calendarPrices[curDate], 0, ".", " ") + " :-</span>";
                }
            }

            // dummy logic
            // dayElem.innerHTML += "<span class='event'>" + curDate + "</span>";
        },
    };
    if (calendarInput != null) {
        var front_end_calendar = flatpickr(calendarInput, calendarOptions);
    }

    if (sbpForm != null) {
        sbpForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // reset the form messages
            resetMessages();

            // collect all the data
            let data = {
                summary: sbpForm.querySelector('[name="summary"]').value,
                selectedDates: sbpForm.querySelector('[name="selected_dates"]').value,
                firstName: sbpForm.querySelector('[name="first_name"]').value,
                lastName: sbpForm.querySelector('[name="last_name"]').value,
                phone: sbpForm.querySelector('[name="phone"]').value,
                email: sbpForm.querySelector('[name="email"]').value,
                bookingType: sbpForm.querySelector('[name="booking_type"]').value,
                amountGuests: sbpForm.querySelector('[name="amount_guests"]').value,
                nonce: sbpForm.querySelector('[name="nonce"]').value
            };

            // Uncomment this if you need add Cancel in dropdown
            // if (data.summary === "3") {
            //     // reset the form messages
            //     resetMessages();
            //     sbpForm.reset();
            //     document.getElementById('booking_calendar')._flatpickr.clear();
            //     resetHiddenFormFields();
            //     jQuery('#sbp-front-estimate').modal('hide');
            //     document.getElementById('sbp-estimate-price').innerHTML = wp_calendar.submit;
            //     return;
            // }

            let selectedDatesStatus = false;
            let selectedDatesBooked = false;
            let calendarPrices = wp_calendar.calendarPrice;
            let bookingStatus = wp_calendar.bookingStatus;

            let disabledBooking = [];

            for (let book in bookingStatus) {
                for (let item of bookingStatus[book].split(',')) {
                    if (item !== "") {
                        disabledBooking.push(item);
                    }
                }
            }

            for (let booked of data.selectedDates.split(',')) {
                if (disabledBooking.includes(booked)) {
                    selectedDatesBooked = true;
                    break;
                }
            }

            for (let price of data.selectedDates.split(',')) {
                if (calendarPrices[price] == undefined) {
                    selectedDatesStatus = true;
                    break;
                }
            }

            if (selectedDatesBooked) {
                sbpForm.querySelector('[data-error="invalidCheckInBooked"]').classList.add('show');
                return;
            }

            if (!data.amountGuests) {
                sbpForm.querySelector('[data-error="invalidAmountGuests"]').classList.add('show');
                return;
            }

            if (!data.firstName) {
                sbpForm.querySelector('[data-error="invalidFirstName"]').classList.add('show');
                return;
            }

            if (!data.lastName) {
                sbpForm.querySelector('[data-error="invalidLastName"]').classList.add('show');
                return;
            }

            if (!validateEmail(data.email)) {
                sbpForm.querySelector('[data-error="invalidEmail"]').classList.add('show');
                return;
            }

            if (!data.phone) {
                sbpForm.querySelector('[data-error="invalidPhone"]').classList.add('show');
                return;
            }

            if (!data.selectedDates) {
                jQuery('.choose_date').modal('show');
                return;
            }

            // ajax http post request
            let url = sbpForm.dataset.url;
            let params = new URLSearchParams(new FormData(sbpForm));

            sbpForm.querySelector('.js-form-submission').classList.add('show');

            fetch(url, {
                method: "POST",
                body: params
            }).then(res => res.json()
            ).catch(error => {
                resetMessages();
                sbpForm.querySelector('.js-form-error').classList.add('show');
            }).then(response => {
                resetMessages();
                // console.log(response);
                if (response === 0 || response.status === 'error') {
                    sbpForm.querySelector('.js-form-error').classList.add('show');
                    return;
                }

                if (data.summary === "1") {
                    window.open(response.data);
                    jQuery('#sbp-front-estimate').modal('hide');
                    resetHiddenFormFields();
                    sbpForm.reset();
                    document.getElementById('booking_calendar')._flatpickr.clear();
                } else if (data.summary === "2") {
                    let responseDisbaledBooking = setDisabledBooking(response.bookingStatus, singleCalendarArr);
                    disabledBooking = responseDisbaledBooking['disabledBooking'];
                    booked = responseDisbaledBooking['booked'];
                    preBooked = responseDisbaledBooking['preBooked'];
                    blocked = responseDisbaledBooking['blocked'];
                    front_end_calendar.set('disable', disabledBooking);
                    document.getElementById('booking_calendar')._flatpickr.clear();

                    sbpForm.querySelector('.js-form-success').classList.add('show');

                    document.getElementById('front-end-estimate-data').classList.add('tn_hide');
                    // document.getElementById('sbp-estimate-price').classList.add('tn_hide');
                    // document.getElementById('sbp-estimate-price-email').classList.add('tn_hide');
                    // document.getElementById('sbp-estimate-price-pdf').classList.add('tn_hide');
                    // document.getElementById('sbp-estimate-cancel').classList.add('tn_hide');
                    document.getElementById('front-end-total-price').classList.add('tn_hide');
                    document.getElementById('front-end-summary').classList.add('tn_hide');
                    thanksText.classList.remove('tn_hide');

                    resetHiddenFormFields();
                    sbpForm.reset();
                } else {
                    sbpForm.querySelector('.js-form-error').classList.add('show');
                }
            });
        });

    }

    jQuery('.sbp-estimate-price-summary').on('click', function (e) {
        jQuery('#hidden_summary').val($(this).data('summary'));
    });

    jQuery('#sbp-estimate-cancel').on('click', function (e) {
        e.preventDefault();
        resetMessages();
        sbpForm.reset();
        document.getElementById('booking_calendar')._flatpickr.clear();
        resetHiddenFormFields();
        jQuery('#sbp-front-estimate').modal('hide');
        // document.getElementById('sbp-estimate-price').innerHTML = wp_calendar.submit;
    });

    jQuery('#sbp-front-addon-modal').on('click', function (e) {
        e.preventDefault();

        // reset the form messages
        resetMessages();

        // collect all the data
        data = {
            selectedDates: sbpForm.querySelector('[name="selected_dates"]').value,
            firstName: sbpForm.querySelector('[name="first_name"]').value,
            lastName: sbpForm.querySelector('[name="last_name"]').value,
            phone: sbpForm.querySelector('[name="phone"]').value,
            email: sbpForm.querySelector('[name="email"]').value,
            bookingType: sbpForm.querySelector('[name="booking_type"]').value,
            amountGuests: sbpForm.querySelector('[name="amount_guests"]').value,
            nonce: sbpForm.querySelector('[name="nonce"]').value
        };

        let dateArr = data.selectedDates.split(',');

        let selectedDatesStatus = false;
        let selectedDatesBooked = false;
        for (let price of dateArr) {
            if (calendarPrices[price] == undefined) {
                selectedDatesStatus = true;
                break;
            }
        }

        for (let booked of dateArr) {
            if (disabledBooking.includes(booked)) {
                selectedDatesBooked = true;
                break;
            }
        }

        let $modalErrors = jQuery('.formErrors');

        if (!data.amountGuests) {
            let errorInnerHtml = sbpForm.querySelector('[data-error="invalidAmountGuests"]').innerHTML;
            $modalErrors.find('.modal-body').text(errorInnerHtml);
            $modalErrors.modal('show');
            return;
        }

        if (!data.firstName) {
            let errorInnerHtml = sbpForm.querySelector('[data-error="invalidFirstName"]').innerHTML;
            $modalErrors.find('.modal-body').text(errorInnerHtml);
            $modalErrors.modal('show');
            return;
        }

        if (!data.lastName) {
            let errorInnerHtml = sbpForm.querySelector('[data-error="invalidLastName"]').innerHTML;
            $modalErrors.find('.modal-body').text(errorInnerHtml);
            $modalErrors.modal('show');
            return;
        }

        if (!data.phone) {
            let errorInnerHtml = sbpForm.querySelector('[data-error="invalidPhone"]').innerHTML;
            $modalErrors.find('.modal-body').text(errorInnerHtml);
            $modalErrors.modal('show');
            return;
        }

        if (!validateEmail(data.email)) {
            let errorInnerHtml = sbpForm.querySelector('[data-error="invalidEmail"]').innerHTML;
            $modalErrors.find('.modal-body').text(errorInnerHtml);
            $modalErrors.modal('show');
            return;
        }

        if (!data.selectedDates) {
            jQuery('.choose_date').modal('show');
            return;
        }

        if (selectedDatesStatus) {
            jQuery('.selectedDatesStatus').modal('show');
            return;
        }

        if (selectedDatesBooked) {
            jQuery('.selectedDatesBooked').modal('show');
            return;
        }
        if (document.querySelectorAll('.addons-container input[type="checkbox"]').length > 0) {
            jQuery('#sbp-front-sbp-addons').modal('show');
        } else {
            pasteEstimateTable();
            jQuery('#sbp-front-estimate').modal('show');
        }
    });

    jQuery('#sbp-show-estimate').on('click', function (e) {
        e.preventDefault();

        jQuery('#sbp-front-sbp-addons').modal('hide');
        jQuery('#sbp-front-estimate').modal('show');

        pasteEstimateTable();

    });

    let top = 0;
    let $body = jQuery("body");

    jQuery('.formErrors').on('shown.bs.modal', function (e) {
        top = jQuery(window).scrollTop();
        // console.log(top);
        $body.css({
            overflow: "hidden",
            width: "100%",
            position: "fixed",
            'margin-top': -top
        });
    });

    jQuery('.formErrors').on('hidden.bs.modal', function (e) {
        $body.css({
            overflow: "",
            width: "",
            position: "",
            'margin-top': ""
        });
        jQuery("html, body").animate({scrollTop: top}, 0);
    });

    jQuery('#sbp-front-sbp-addons').on('shown.bs.modal', function (e) {
        top = jQuery(window).scrollTop();
        // console.log(top);
        $body.css({
            overflow: "hidden",
            width: "100%",
            position: "fixed",
            'margin-top': -top
        });
    });

    jQuery('#sbp-front-sbp-addons').on('hidden.bs.modal', function (e) {
        $body.css({
            overflow: "",
            width: "",
            position: "",
            'margin-top': ""
        });
        jQuery("html, body").animate({scrollTop: top}, 0);
    });

    jQuery('#sbp-front-estimate').on('shown.bs.modal', function (e) {
        top = jQuery(window).scrollTop();
        // console.log(top);
        $body.css({
            overflow: "hidden",
            width: "100%",
            position: "fixed",
            'margin-top': -top
        });
        jQuery('body').addClass('modal-open');
    });

    jQuery('#sbp-front-estimate').on('hidden.bs.modal', function (e) {
        $body.css({
            overflow: "",
            width: "",
            position: "",
            'margin-top': ""
        });
        jQuery("html, body").animate({scrollTop: top}, 0);

        jQuery('body').removeClass('modal-open');

        document.getElementById('front-end-estimate-data').classList.remove('tn_hide');
        // document.getElementById('sbp-estimate-price').classList.remove('tn_hide');
        document.getElementById('sbp-estimate-price-email').classList.remove('tn_hide');
        document.getElementById('sbp-estimate-price-pdf').classList.remove('tn_hide');
        document.getElementById('front-end-total-price').classList.remove('tn_hide');
        document.getElementById('front-end-summary').classList.remove('tn_hide');

        thanksText.classList.add('tn_hide');
        sbpForm.querySelector('.js-form-success').classList.remove('show');
        sbpForm.querySelector('.js-form-error').classList.remove('show');
    });

    jQuery('#booking_type').on('change', function (e) {
        let tar = e.target.value;
        if (tar == 3) {
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            for (let price in calendarPrices) {
                calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
            }

            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            // Make calendar addons price with VAT
            for (let addon in allAddons) {
                allAddons[addon]['addon_price'] = +allAddons[addon]['addon_price'] + +allAddons[addon]['addon_price'] * +vat;
            }
            document.getElementById('vat-status').checked = true;
            minPrice = setMinPrice(calendarPrices, disabledBooking);
            front_end_calendar.redraw();

            setTotalPrice(front_end_calendar.selectedDates, calendarPrices);
        } else {
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            document.getElementById('vat-status').checked = false;
            minPrice = setMinPrice(calendarPrices, disabledBooking);
            front_end_calendar.redraw();

            setTotalPrice(front_end_calendar.selectedDates, calendarPrices);
        }

        setDefaultAddons(tar, allAddons);
    });

    jQuery('#amount_guests').on('change', function (e) {

        let tar = document.getElementById('booking_type').value;
        setDefaultAddons(tar, allAddons);
        setTotalPrice(front_end_calendar.selectedDates, calendarPrices);

    });

    jQuery('body').on('click', '.addons-container input[type="checkbox"]', function (e) {
        let total_price = document.getElementById('total_price').value;
        let amount_guests = document.getElementById('amount_guests').value;
        let dayCount = document.getElementById('selected_dates') !== null ? document.getElementById('selected_dates') : document.getElementById('sbp_booking_list_selected_dates');
        dayCount = dayCount.value.split(',').length;

        if (e.target.checked == true) {
            if (allAddons[e.target.dataset.id]['per_guest']) {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +amount_guests * +dayCount;
            } else {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +dayCount;
            }
            total_price = +total_price + dayPrice;
            oldPrice += dayPrice;
        } else {
            if (allAddons[e.target.dataset.id]['per_guest']) {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +amount_guests * +dayCount;
            } else {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +dayCount;
            }
            total_price = +total_price - dayPrice;
            oldPrice -= dayPrice;
        }

        document.getElementById('total_price').value = total_price;
        document.getElementById('old_price').value = oldPrice;
        document.getElementById('total_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
        document.getElementById('total_addon_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
    });

    // document.getElementById('summary').addEventListener('change', (e) => {
    //     let tar = e.target.value;
    //     if (tar === "3") {
    //         document.getElementById('sbp-estimate-price').innerHTML = wp_calendar.cancel;
    //     } else {
    //         document.getElementById('sbp-estimate-price').innerHTML = wp_calendar.submit;
    //     }
    // });

    // clear selected dates
    document.getElementById('clear_selected_dates').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('booking_calendar')._flatpickr.clear();
        document.getElementById('total_price').value = '';
        document.getElementById('old_price').value = '';
        document.getElementById('selected_dates').value = '';
        document.getElementById('old_calendar_price').value = '';
    });

    // VAT toggle
    document.getElementById('vat-status').addEventListener('click', function (e) {
        let tar = document.getElementById('booking_type').value;

        if (e.target.checked != true) {
            document.getElementById('booking_type').value = '1';
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            minPrice = setMinPrice(calendarPrices, disabledBooking);
            setTotalPrice(front_end_calendar.selectedDates, calendarPrices);
            front_end_calendar.redraw()
        } else {
            document.getElementById('booking_type').value = '3';
            for (let price in calendarPrices) {
                calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
            }

            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            // Make calendar addons price with VAT
            for (let addon in allAddons) {
                allAddons[addon]['addon_price'] = +allAddons[addon]['addon_price'] + +allAddons[addon]['addon_price'] * +vat;
            }

            minPrice = setMinPrice(calendarPrices, disabledBooking);
            setTotalPrice(front_end_calendar.selectedDates, calendarPrices);
            front_end_calendar.redraw();
        }

        setDefaultAddons(tar, allAddons);
    });

    function pasteEstimateTable() {
        document.querySelector('#front-end-estimate-name p').innerHTML = data.firstName + ' ' + data.lastName;
        document.querySelector('#front-end-estimate-email p').innerHTML = data.email;
        document.querySelector('#front-end-estimate-phone p').innerHTML = data.phone;
        document.querySelector('#front-end-estimate-booking-type p').innerHTML = getBookingType(data.bookingType);
        document.querySelector('#front-end-estimate-amount-guests p').innerHTML = data.amountGuests;
        data.selectedDates.split(',').forEach(function (el, i) {
            if (i === 0) {
                document.querySelector('#front-end-estimate-selected-dates p').innerHTML = "";
            }
            document.querySelector('#front-end-estimate-selected-dates p').innerHTML += el + ' <b>(' + calendarPrices[el] + ' :-)</b><br />';

        });

        if (document.querySelectorAll('.addons-container input[type="checkbox"]:checked').length > 0) {
            document.querySelectorAll('.addons-container input[type="checkbox"]:checked').forEach(function (el, i) {
                if (i === 0) {
                    document.querySelector('#front-end-estimate-addons p').innerHTML = "";
                }
                // console.log(allAddons[el.dataset.id])
                let addonTotalPrice = 0;
                if (allAddons[el.dataset.id].per_guest === "1") {
                    addonTotalPrice = +allAddons[el.dataset.id].addon_price * +data.amountGuests * +data.selectedDates.split(',').length;
                } else {
                    addonTotalPrice = +allAddons[el.dataset.id].addon_price * +data.selectedDates.split(',').length;
                }
                document.querySelector('#front-end-estimate-addons p').innerHTML += allAddons[el.dataset.id].addon_name + ' <b>(' + addonTotalPrice + ' :-)</b><br />';
            });
        } else {
            document.querySelector('#front-end-estimate-addons p').innerHTML = "-";
        }
    }

    function setTotalPrice(selectedDates, calendarPrices) {
        if (selectedDates[0] !== undefined && selectedDates[0] !== null && selectedDates[1] !== undefined && selectedDates[1] !== null) {
            let start_year = selectedDates[0].getFullYear();
            let end_year = selectedDates[1].getFullYear();
            let start_month = selectedDates[0].getMonth();
            let end_month = selectedDates[1].getMonth();
            let start_day = selectedDates[0].getDate();
            let end_day = selectedDates[1].getDate();

            document.getElementById('booked_unix_timestamp').value = Math.round((Date.UTC(start_year, start_month, start_day) / 1000));

            let result = eachDay(
                new Date(start_year, start_month, start_day),
                new Date(end_year, end_month, end_day)
            );

            const dateArr = result.map(date => front_end_calendar.formatDate(date, "d-m-Y"));

            document.getElementById('selected_dates').value = dateArr.join(',');

            dayPrice = 0;
            for (let date of dateArr) {
                if (calendarPrices[date] != null && calendarPrices[date] !== "") {
                    dayPrice += +calendarPrices[date];
                }
            }

            total_price = dayPrice;
            oldPrice = dayPrice;

            document.getElementById('total_price').value = total_price;
            document.getElementById('old_price').value = 0;
            document.getElementById('old_calendar_price').value = dayPrice;
            document.getElementById('total_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
            document.getElementById('total_addon_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
        } else if (selectedDates[0] !== undefined && selectedDates[0] !== null) {
            let start_year = selectedDates[0].getFullYear();
            let start_month = selectedDates[0].getMonth();
            let start_day = selectedDates[0].getDate();

            document.getElementById('booked_unix_timestamp').value = Math.round((Date.UTC(start_year, start_month, start_day) / 1000));

            let result = eachDay(
                new Date(start_year, start_month, start_day),
                new Date(start_year, start_month, start_day)
            );

            const dateArr = result.map(date => front_end_calendar.formatDate(date, "d-m-Y"));


            document.getElementById('selected_dates').value = dateArr.join(',');

            dayPrice = 0;
            for (let date of dateArr) {
                if (calendarPrices[date] != null && calendarPrices[date] !== "") {
                    dayPrice += +calendarPrices[date];
                }
            }

            total_price = dayPrice;
            oldPrice = dayPrice;

            document.getElementById('total_price').value = total_price;
            document.getElementById('old_price').value = 0;
            document.getElementById('old_calendar_price').value = dayPrice;
            document.getElementById('total_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
            document.getElementById('total_addon_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
        }
    }

    function setDefaultAddons(tar, allAddons) {
        let innetText = '';
        for (let addon in allAddons) {
            if (allAddons[addon][getBookingType(tar)] != undefined && allAddons[addon][getBookingType(tar)] == 1) {
                let shortInfo = (allAddons[addon]['short_info'] != undefined) ? allAddons[addon]['short_info'] : '';
                innetText += '<div class="form-group col-md-6">\n' +
                    '                                            <div class="ui-toggle">\n' +
                    '                                                <span>' + allAddons[addon]['addon_name'] + '</span>\n' +
                    '                                                <input type="checkbox" id="addon-' + allAddons[addon]['addon_id'] + '"\n' +
                    '                                                       name="addons[' + allAddons[addon]['addon_id'] + ']" value="1" class=""' +
                    '                                                       data-id="' + allAddons[addon]['addon_id'] + '">\n' +
                    '                                                <label for="addon-' + allAddons[addon]['addon_id'] + '">\n' +
                    '                                                    <div></div>\n' +
                    '                                                </label>\n' +
                    '                                            </div>\n' +
                    '                                            <div class="small-text">' + shortInfo + '</div>\n' +
                    '                                            <div class="small-text text-center"><b>' + allAddons[addon]['addon_price'] + ' SEK</b></div>\n' +
                    '                                        </div>'
            }
        }

        document.querySelector('.addons-container').innerHTML = innetText;
    }

    function setDisabledBooking(bookingStatus, singleCalendarArr) {
        let disabledBooking = [];
        let booked = [];
        let preBooked = [];
        let blocked = [];

        for (let book in bookingStatus) {
            for (let item of bookingStatus[book].split(',')) {

                if (!singleCalendarArr.includes(item)) {
                    switch (book) {
                        case 'booked':
                            booked.push(item);
                            break;
                        case 'pre-booked':
                            preBooked.push(item);
                            break;
                        case 'blocked':
                            blocked.push(item);
                            break;
                    }

                    if (item !== "") {
                        disabledBooking.push(item);
                    }
                }
            }
        }

        return {'disabledBooking': disabledBooking, 'booked': booked, 'preBooked': preBooked, 'blocked': blocked}
    }
});

function numeroAdosCaracteres(fecha) {
    if (fecha > 9) {
        return "" + fecha;
    } else {
        return "0" + fecha;
    }
}

function resetHiddenFormFields() {
    document.getElementById('total_price').value = '';
    document.getElementById('old_price').value = '';
    document.getElementById('selected_dates').value = '';
    document.getElementById('old_calendar_price').value = '';
    document.getElementById('booked_unix_timestamp').value = '';
    document.getElementById('total_price_view').innerHTML = '0 SEK';
    document.getElementById('total_addon_price_view').innerHTML = '0 SEK';
}

function setMinPrice(calendarPrices, disabledBooking) {
    // looking for Min Price
    let minPriceArr = [];

    // today
    let today = new Date();
    let dd = today.getDate();
    let mm = today.getMonth(); //January is 0!
    let yyyy = today.getFullYear();

    for (let minPrice in calendarPrices) {
        let currDateSplit = minPrice.split('-');
        let currDate = new Date(currDateSplit[2], currDateSplit[1], currDateSplit[0]);
        let currDD = currDate.getDate();
        let curMM = currDate.getMonth() - 1; //January is 0!
        let curYYYY = currDate.getFullYear();

        let compareDates = compareAsc(
            new Date(curYYYY, curMM, currDD),
            new Date(yyyy, mm, dd)
        );

        if (compareDates >= 0 && !disabledBooking.includes(minPrice) && calendarPrices[minPrice] > 0) {
            minPriceArr.push(calendarPrices[minPrice]);
        }
    }

    return Math.min(...minPriceArr);
}

function getBookingType(type) {
    switch (type) {
        case '1':
            return "corporate";
            break;

        case '3':
            return "private";
            break;
    }
}

function resetMessages() {
    document.querySelectorAll('.field-msg').forEach(f => f.classList.remove('show'));
}

function validateEmail(email) {
    let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function formatMoney(n, c, d, t) {
    var c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
        j = (j = i.length) > 3 ? j % 3 : 0;

    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}