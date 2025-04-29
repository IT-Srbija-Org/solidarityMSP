function bindSchool(citySelectId, schoolSelectId, schoolOptions = {}) {
    $('#' + citySelectId).on('change', function () {
        var id = $(this).val();

        var options = '<option value="" selected="selected">Učitavam škole...</option>';
        $('#' + schoolSelectId).html(options);

        $.get('/schools', {'city-id': id}, function (data) {
            options = '<option value="' + (schoolOptions.emptyValue || '') + '" selected="selected">' + (schoolOptions.emptyLabel || '') + '</option>';

            for (var i = 0; i < data.length; i++) {
                options += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
            }

            $('#' + schoolSelectId).html(options);
        });
    });
}
