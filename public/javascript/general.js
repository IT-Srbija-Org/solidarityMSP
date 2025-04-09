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
