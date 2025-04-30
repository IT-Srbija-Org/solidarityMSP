(function ( $ ) {
    'use strict';

    $( document ).ready(
        function () {
            initSelect2();
        }
    );

    function initSelect2() {
        var select = $( '.select2-item' );

        if ( select.length && typeof select.select2 === 'function' ) {
            select.select2();
        }
    }

    function bindSchool() {
        $( '#registration_delegate_city' ).on(
            'change',
            function () {
                var id = $( this ).val();

                $.get(
                    '/schools',
                    { 'city-id': id },
                    function ( data ) {
                        var options = '<option value="" selected="selected">Izaberite školu</option>';

                        for ( var i = 0; i < data.length; i++ ) {
                            options += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
                        }

                        $( '#registration_delegate_school' ).html( options );
                    }
                );
            }
        );
    }

    window.bindSchool = bindSchool;

})( jQuery );
