import flatpickr from "flatpickr";
import rangePlugin from 'flatpickr/dist/plugins/rangePlugin';
import {eachDay, compareAsc} from 'date-fns';
import { Swedish } from "flatpickr/dist/l10n/sv";


jQuery(document).ready(function ($) {
    if(wp_calendar.language === 'sv_SE')  {
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

        singleCalendarArr = [singleCalendarArr[0], singleCalendarArr[singleCalendarArr.length-1]]
    }

    let total_price = document.getElementById('total_price') != null ? document.getElementById('total_price').value : 0;
    let dayPrice = 0;
    let oldPrice = 0;

    let calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
    let vat = wp_calendar.vat;
    // Make calendar view with VAT
    for(let price in calendarPrices) {
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
    const calendarInput = document.getElementById("adminCalendar");
    let calendarOptions = {
        inline: true,
        mode: "multiple",
        minDate: "today",
        dateFormat: "d-m-Y",
        disable: disabledBooking,
        locale: {
            "firstDayOfWeek": 1 // start week on Monday
        },
        onChange: function (selectedDates, dateStr, instance) {
        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            // Utilize dayElem.dateObj, which is the corresponding Date
            let curDate = numeroAdosCaracteres(dayElem.dateObj.getDate()) + "-" + numeroAdosCaracteres(dayElem.dateObj.getMonth() + 1) + "-" + dayElem.dateObj.getFullYear();
            let compareDates = compareAsc(
                new Date(dayElem.dateObj.getFullYear(), dayElem.dateObj.getMonth(), dayElem.dateObj.getDate()),
                new Date(yyyy, mm, dd)
            );

            if (compareDates >= 0 && calendarPrices[curDate] != null && calendarPrices[curDate] !== "") {
                if (calendarPrices[curDate] == minPrice) {
                    dayElem.classList.add('min-price');
                }

                if (booked.includes(curDate)) {
                    dayElem.classList.add('booked');
                } else if (preBooked.includes(curDate)) {
                    dayElem.classList.add('pre-booked');
                } else if (blocked.includes(curDate)) {
                    dayElem.classList.add('blocked');
                }

                dayElem.innerHTML += "<span class='price'>" + formatMoney(calendarPrices[curDate], 0, ".", " ") + " :-</span>";
            }

            // dummy logic
            // dayElem.innerHTML += "<span class='event'>" + curDate + "</span>";
        },
    };
    if (calendarInput != null) {
        var fp = flatpickr(calendarInput, calendarOptions);
        // VAT toggle
        document.getElementById('vat-status').addEventListener('click', function(e) {
            if(e.target.checked != true) {
                calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
                minPrice = setMinPrice(calendarPrices, disabledBooking);
                fp.redraw()
            } else {
                for(let price in calendarPrices) {
                    calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
                }
                minPrice = setMinPrice(calendarPrices, disabledBooking);
                fp.redraw();
            }
        });
    }

    const bookingCalendar = document.getElementById("check_in");
    let bookingCalendarOptions = {
        "plugins": [new rangePlugin({input: "#check_out"})],
        minDate: "today",
        dateFormat: "d-m-Y",
        altInput: true,
        disable: disabledBooking,
        locale: {
            "firstDayOfWeek": 1 // start week on Monday
        },
        onChange: function (selectedDates, dateStr, instance) {
            const dateArr = selectedDates.map(date => this.formatDate(date, "Y-m-d"));

        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            let curDate = numeroAdosCaracteres(dayElem.dateObj.getDate()) + "-" + numeroAdosCaracteres(dayElem.dateObj.getMonth() + 1) + "-" + dayElem.dateObj.getFullYear();

            if (booked.includes(curDate)) {
                dayElem.classList.add('booked');
            } else if (preBooked.includes(curDate)) {
                dayElem.classList.add('pre-booked');
            } else if (blocked.includes(curDate)) {
                dayElem.classList.add('blocked');
            }
        },
        onClose: function (selectedDates, dateStr, instance) {
            if (selectedDates != undefined) {
                total_price = document.getElementById('total_price').value;

                setTotalPrice(selectedDates, calendarPrices, this);

                let tar = document.getElementById('booking_type').value;
                setDefaultAddons(tar, allAddons);
            }


            // console.log(splitted);

        }
    };

    if (bookingCalendar != null) {
        var booking_fp = flatpickr(bookingCalendar, bookingCalendarOptions);
    }


    // Single Page
    const singleCalendarInput = document.getElementById("sbp_booking_list_calendar");
    let oldCalPrice = document.getElementById('old_calendar_price') !== null ? document.getElementById('old_calendar_price').value : 0;
    let singleCalendarOptions = {
        inline: true,
        mode: "range",
        minDate: "today",
        dateFormat: "d-m-Y",
        disable: disabledBooking,
        locale: {
            "firstDayOfWeek": 1 // start week on Monday
        },
        defaultDate: singleCalendarArr,
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates[0] !== undefined && selectedDates[0] !== null && selectedDates[1] !== undefined && selectedDates[1] !== null) {
                document.querySelector('[data-error="invalidCheckIn"]').classList.add('hidden');
                document.querySelector('[data-error="invalidCheckInBooked"]').classList.add('hidden');

                let start_year = selectedDates[0].getFullYear();
                let end_year = selectedDates[1].getFullYear();
                let start_month = selectedDates[0].getMonth();
                let end_month = selectedDates[1].getMonth();
                let start_day = selectedDates[0].getDate();
                let end_day = selectedDates[1].getDate();

                let result = eachDay(
                    new Date(start_year, start_month, start_day),
                    new Date(end_year, end_month, end_day)
                );

                const dateArr = result.map(date => this.formatDate(date, "d-m-Y"));



                let selectedDatesStatus = false;
                let selectedDatesBooked = false;
                for (let price of dateArr) {
                    if(calendarPrices[price] == undefined) {
                        selectedDatesStatus = true;
                        break;
                    }
                }

                for (let booked of dateArr) {
                    if(disabledBooking.includes(booked)) {
                        selectedDatesBooked = true;
                        break;
                    }
                }

                if(selectedDatesStatus) {
                    document.querySelector('[data-error="invalidCheckIn"]').classList.remove('hidden');
                    return;
                }

                if(selectedDatesBooked) {
                    document.querySelector('[data-error="invalidCheckInBooked"]').classList.remove('hidden');
                    return;
                }


                total_price = document.getElementById('total_price').value;
                total_price -= oldCalPrice;
                oldCalPrice = 0;

                document.getElementById('sbp_booking_list_selected_dates').value = dateArr.join(',');
                dayPrice = 0;
                for (let date of dateArr) {
                    if (calendarPrices[date] != null && calendarPrices[date] !== "") {
                        dayPrice += +calendarPrices[date];
                    }
                }

                total_price = dayPrice;
                oldPrice = dayPrice;

                let tar = document.getElementById('booking_type').value;
                setDefaultAddons(tar, allAddons);

                document.getElementById('total_price').value = total_price;
                document.getElementById('old_price').value = 0;
                document.getElementById('old_calendar_price').value = dayPrice;
                document.getElementById('total_price_view').innerHTML = formatMoney(total_price, 0, ".", " ") + ' SEK';
                document.getElementById('booked_unix_timestamp').value = Math.round((Date.UTC(start_year, start_month, start_day) / 1000 ));
            }
        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            // Utilize dayElem.dateObj, which is the corresponding Date
            let curDate = numeroAdosCaracteres(dayElem.dateObj.getDate()) + "-" + numeroAdosCaracteres(dayElem.dateObj.getMonth() + 1) + "-" + dayElem.dateObj.getFullYear();
            let compareDates = compareAsc(
                new Date(dayElem.dateObj.getFullYear(), dayElem.dateObj.getMonth(), dayElem.dateObj.getDate()),
                new Date(yyyy, mm, dd)
            );

            if (booked.includes(curDate)) {
                dayElem.classList.add('booked');
            } else if (preBooked.includes(curDate)) {
                dayElem.classList.add('pre-booked');
            } else if (blocked.includes(curDate)) {
                dayElem.classList.add('blocked');
            }

            if (compareDates >= 0 && calendarPrices[curDate] != null && calendarPrices[curDate] !== "") {
                if (calendarPrices[curDate] == minPrice) {
                    dayElem.classList.add('min-price');
                }

                dayElem.innerHTML += "<span class='price'>" + formatMoney(calendarPrices[curDate], 0, ".", " ") + " :-</span>";
            }

            // dummy logic
            // dayElem.innerHTML += "<span class='event'>" + curDate + "</span>";
        },
    };
    if (singleCalendarInput != null) {
        var single_fp = flatpickr(singleCalendarInput, singleCalendarOptions);
        // console.log(document.getElementById('booking_type').value === '1')
        if(document.getElementById('booking_type').value === '1') {
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            single_fp.redraw();
        }
        document.getElementById('vat-status').addEventListener('click', function(e) {
            if(e.target.checked != true) {
                calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
                minPrice = setMinPrice(calendarPrices, disabledBooking);
                single_fp.redraw()
            } else {
                for(let price in calendarPrices) {
                    calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
                }
                minPrice = setMinPrice(calendarPrices, disabledBooking);
                single_fp.redraw();
            }
        });
    }

    if(sbpForm != null) {
        sbpForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // reset the form messages
            resetMessages();

            // collect all the data
            let data = {
                status: sbpForm.querySelector('[name="status"]').value,
                checkIn: sbpForm.querySelector('[name="check_in"]').value,
                checkOut: sbpForm.querySelector('[name="check_out"]').value,
                selectedDates: sbpForm.querySelector('[name="selected_dates"]').value,
                firstName: sbpForm.querySelector('[name="first_name"]').value,
                lastName: sbpForm.querySelector('[name="last_name"]').value,
                phone: sbpForm.querySelector('[name="phone"]').value,
                email: sbpForm.querySelector('[name="email"]').value,
                bookingType: sbpForm.querySelector('[name="booking_type"]').value,
                amountGuests: sbpForm.querySelector('[name="amount_guests"]').value,
                payStatus: sbpForm.querySelector('[name="pay_status"]').value,
                nonce: sbpForm.querySelector('[name="nonce"]').value
            };

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
                if(disabledBooking.includes(booked)) {
                    selectedDatesBooked = true;
                    break;
                }
            }

            for (let price of data.selectedDates.split(',')) {
                if(calendarPrices[price] == undefined) {
                    selectedDatesStatus = true;
                    break;
                }
            }



            // validate everything
            if (selectedDatesStatus) {
                sbpForm.querySelector('[data-error="invalidCheckIn"]').classList.add('show');
                return;
            }

            if (selectedDatesBooked) {
                sbpForm.querySelector('[data-error="invalidCheckInBooked"]').classList.add('show');
                return;
            }

            if (!data.checkOut) {
                sbpForm.querySelector('[data-error="invalidCheckOut"]').classList.add('show');
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

            if (!data.phone) {
                sbpForm.querySelector('[data-error="invalidPhone"]').classList.add('show');
                return;
            }

            if (!validateEmail(data.email)) {
                sbpForm.querySelector('[data-error="invalidEmail"]').classList.add('show');
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
                let responseDisbaledBooking = setDisabledBooking(response.bookingStatus, singleCalendarArr);
                disabledBooking = responseDisbaledBooking['disabledBooking'];
                booked = responseDisbaledBooking['booked'];
                preBooked = responseDisbaledBooking['preBooked'];
                blocked = responseDisbaledBooking['blocked'];
                if(fp !== undefined) {
                    fp.set('disable', disabledBooking);
                }
                if(booking_fp !== undefined) {
                    booking_fp.set('disable', disabledBooking);
                }

                sbpForm.querySelector('.js-form-success').classList.add('show');

                sbpForm.classList.add('tn_hide');
                thanksText.classList.remove('tn_hide');

                sbpForm.reset();
            });
        });

    }

    jQuery('#sbp-form').on('hidden.bs.modal', function () {
        sbpForm.classList.remove('tn_hide');
        thanksText.classList.add('tn_hide');
        sbpForm.querySelector('.js-form-success').classList.remove('show');
        sbpForm.querySelector('.js-form-error').classList.remove('show');
        document.getElementById('total_price_view').innerHTML = '0 SEK';
    });

    jQuery('#booking_type').on('change', function (e) {
        let tar = e.target.value;
        // console.log(tar === '3')
        if (tar === '3') {
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            for (let price in calendarPrices) {
                calendarPrices[price] = +calendarPrices[price] + +calendarPrices[price] * +vat;
            }

            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            // Make calendar addons price with VAT
            for (let addon in allAddons) {
                allAddons[addon]['addon_price'] = +allAddons[addon]['addon_price'] + +allAddons[addon]['addon_price'] * +vat;
            }
            if(booking_fp !== undefined){
                setTotalPrice(booking_fp.selectedDates, calendarPrices, booking_fp);
            }
            if(single_fp !== undefined) {
                setTotalPrice(single_fp.selectedDates, calendarPrices, single_fp);
                document.getElementById('vat-status').checked = true;
                single_fp.redraw();
            }
        } else {
            calendarPrices = Object.assign({}, wp_calendar.calendarPrice);
            allAddons = JSON.parse(JSON.stringify(wp_calendar.allAddons));
            // console.log(booking_fp !== undefined)
            if(booking_fp !== undefined) {
                setTotalPrice(booking_fp.selectedDates, calendarPrices, booking_fp);
            }
            // console.log(single_fp !== undefined)
            if(single_fp !== undefined) {
                document.getElementById('vat-status').checked = false;
                single_fp.redraw();

                setTotalPrice(single_fp.selectedDates, calendarPrices, single_fp);
            }
        }

        setDefaultAddons(tar, allAddons);
    });

    jQuery('#amount_guests').on('change', function (e) {

        let tar = document.getElementById('booking_type').value;
        setDefaultAddons(tar, allAddons);
        if(booking_fp !== undefined) {
            setTotalPrice(booking_fp.selectedDates, calendarPrices, booking_fp);
        }
        if(single_fp !== undefined) {
            setTotalPrice(single_fp.selectedDates, calendarPrices, single_fp);
        }

    });

    jQuery('body').on('click', '.addons-container input[type="checkbox"]', function (e) {
        let total_price = document.getElementById('total_price').value;
        let amount_guests = document.getElementById('amount_guests').value;
        let dayCount = document.getElementById('selected_dates') !== null ? document.getElementById('selected_dates') : document.getElementById('sbp_booking_list_selected_dates');
        dayCount = dayCount.value.split(',').length;

        if (e.target.checked == true){
            if(allAddons[e.target.dataset.id]['per_guest']) {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +amount_guests * +dayCount;
            } else {
                dayPrice = +allAddons[e.target.dataset.id]['addon_price'] * +dayCount;
            }
            total_price = +total_price + dayPrice;
            oldPrice += dayPrice;
        } else {
            if(allAddons[e.target.dataset.id]['per_guest']) {
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
    });

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

    function setTotalPrice(selectedDates, calendarPrices, flatPickrInstanse) {
        if (selectedDates[0] !== undefined && selectedDates[0] !== null && selectedDates[1] !== undefined && selectedDates[1] !== null) {
            let start_year = selectedDates[0].getFullYear();
            let end_year = selectedDates[1].getFullYear();
            let start_month = selectedDates[0].getMonth();
            let end_month = selectedDates[1].getMonth();
            let start_day = selectedDates[0].getDate();
            let end_day = selectedDates[1].getDate();

            let result = eachDay(
                new Date(start_year, start_month, start_day),
                new Date(end_year, end_month, end_day)
            );
            const dateArr = result.map(date => flatPickrInstanse.formatDate(date, "d-m-Y"));
            if(single_fp !== undefined) {
                document.getElementById('sbp_booking_list_selected_dates').value = dateArr.join(',');
            } else {
                document.getElementById('selected_dates').value = dateArr.join(',');
            }

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
            document.getElementById('booked_unix_timestamp').value = Math.round((Date.UTC(start_year, start_month, start_day) / 1000 ));
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
                    '                                        </div>'
            }
        }

        document.querySelector('.addons-container').innerHTML = innetText;
    }
});

function numeroAdosCaracteres(fecha) {
    if (fecha > 9) {
        return "" + fecha;
    } else {
        return "0" + fecha;
    }
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