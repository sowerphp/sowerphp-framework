/*! SowerPHP | (c) 2014 SowerPHP | GPL3+ */
/*jslint browser: true, devel: true, nomen: true, indent: 4 */

/**
 * Envía un formulario para filtrar por diferentes parámetros
 * @param formulario Formulario genérico que se utilizará para enviar elementos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-13
 */
function buscar(formulario) {
    'use strict';
    var i, total = formulario.elements.length, search = [], campo, valor;
    for (i = 0; i < total; i += 1) {
        campo = formulario.elements[i].name;
        valor = formulario.elements[i].value;
        if (!__.empty(valor)) {
            search.push(campo + ':' + valor);
        }
    }
    search = search.join(",");
    if (__.empty(search)) {
        document.location.href = window.location.pathname;
    } else {
        document.location.href = window.location.pathname + '?search=' + search;
    }
    // en teoria nunca se llega aquí, pero está para que no reclame porque
    // buscar() no retorna nada
    return false;
}

/**
 * Función que consulta si efectivamente se desea eliminar el registro
 * @param registro Nombre del registro
 * @param tupla Identificador (generalmente PK) del registro
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-06-26
 */
function eliminar(objeto, registro, tupla) {
    'use strict';
    return Form.confirm(objeto, '¿Eliminar registro ' + registro + '(' + tupla + ')?');
}

/**
 * Función ajustar el div wrapper según tamaño del contenedor del header fijo
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-11-14
 */
function header_fix_adjust_wrapper() {
    var height;
    if (document.getElementById('header_fix_container')) {
        height = document.getElementById('header_fix_container').clientHeight;
        document.getElementById('wrapper').style.marginTop = (height+3)+'px';
    }
    if (document.getElementById('footer_fix_container')) {
        height = document.getElementById('footer_fix_container').clientHeight;
        document.getElementById('wrapper').style.marginBottom = (height+15)+'px';
    }
}

/**
 * Función para autocompletar un input de un formulario
 * @param id Identificador del input
 * @param url URL donde están los posibles valores para el input
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-16
 */
function autocomplete(id, url) {
    var field = id+'Field';
    $(function(){ $('#'+field).keyup(function(){
        if($('#'+field).val()!='' && $('#'+field).val().length > 2) {
            $.getJSON(url, { filter: $('#'+field).val() }, function(data) {
                var items = [];
                $.each(data, function(key, val) {
                    items.push(val.glosa);
                });
                $('#'+field).autocomplete({
                    source: items
                    , autoFocus: true
                    , minLength: 3
                });
            });
        }
    });});
}

function dataTable(tableSelector, aoColumns) {
    var options = {
        aLengthMenu: [
            [5, 10, 20, 50, 100, 250, -1],
            [5, 10, 20, 50, 100, 250, "Todos"]
        ],
        iDisplayLength: 5,
        "aaSorting": [],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ".",
            "thousands": ".",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "<i class='fa fa-arrow-right'></i>",
                "sPrevious": "<i class='fa fa-arrow-left'></i>"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },
        },
    };
    if (aoColumns!==undefined) {
        options.aoColumns = aoColumns;
    }
    $(tableSelector).parent().css('paddingRight', '1.2em');
    return $(tableSelector).DataTable(options);
}

function dataTableNum(data, type, full) {
    if (type=='display') {
        return data.toString().replace(/\B(?=(\d{3})+(?!\d))/g,".");
    }
    return data;
}

function typeahead_substring_matcher(strs) {
    return function findMatches(q, cb) {
        var matches, substringRegex;
        matches = [];
        substrRegex = new RegExp(q, 'i');
        $.each(strs, function(i, str) {
            if (substrRegex.test(str)) {
                matches.push(str);
            }
        });
        cb(matches);
    };
}
