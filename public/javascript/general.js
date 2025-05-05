function bindSchool() {
    $('#registration_delegate_city').on('change', function () {
        var id = $(this).val();

        $.get('/schools', {'city-id': id}, function (data) {
            var options = '<option value="" selected="selected">Izaberite školu</option>';

            for (var i = 0; i < data.length; i++) {
                options += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
            }

            $('#registration_delegate_school').html(options);
        });
    });
}

function loadDriverInfo() {
    if(!$('.js-info-button').is(':visible')){
        return false;
    }

    if(localStorage.getItem("info-button-already-shown")){
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

function loadDriver(steps){
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
