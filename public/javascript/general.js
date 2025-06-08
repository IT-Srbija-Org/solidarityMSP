function bindSchool() {
    $('#registration_delegate_city').on('change', function () {
        const id = $(this).val();

        $.get('/schools', {'city-id': id}, function (data) {
            let options = '<option value="" selected="selected">Izaberite školu</option>';

            for (let i = 0; i < data.length; i++) {
                options += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
            }

            $('#registration_delegate_school').html(options);
        });
    });
}

function bindDamagedEducator() {
    $('#transaction_new_school, #transaction_new_period').on('change', function () {
        const id = $('#transaction_new_school').val();
        const periodId = $('#transaction_new_period').val();

        if (!id || !periodId) {
            return false;
        }

        $.get('/admin/damaged-educator/list-ajax', {'period-id': periodId, 'school-id': id}, function (data) {
            let options = '<option value="" selected="selected">Izaberite oštećenog</option>';

            for (let i = 0; i < data.length; i++) {
                options += '<option value="' + data[i].id + '">' + data[i].name + ' (' + data[i].accountNumber + ')</option>';
            }

            $('#transaction_new_damagedEducator').html(options).attr('disabled', false);
        });
    });
}

function loadDriverInfo() {
    if (!$('.js-info-button').is(':visible')) {
        return false;
    }

    if (localStorage.getItem("info-button-already-shown")) {
        return false;
    }

    const driver = window.driver.js.driver;
    const driverObj = driver();

    driverObj.highlight({
        element: ".js-info-button",
        popover: {
            title: "Uputstva za korišćenje",
            description: "Na svakoj stranici na kojoj je dostupna ova opcija, klikom na dugme pokrećete jednostavan vodič koji će vam pomoći da brže i lakše razumete šta se prikazuje na stranici i koje sve akcije možete preduzeti."
        }
    });

    localStorage.setItem("info-button-already-shown", true);
}

function loadDriver(steps) {
    const driver = window.driver.js.driver;
    const driverObj = driver({
        showProgress: true,
        doneBtnText: 'Završi',
        closeBtnText: 'Zatvori',
        nextBtnText: 'Sledeće',
        prevBtnText: 'Prethodno',
        steps: steps,
    });

    $('.js-info-button').on('click', function () {
        driverObj.drive();
    });
}

function loadSelect2() {
    $('.js-select2').select2();
}
