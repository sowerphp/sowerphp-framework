/*! SowerPHP | (c) 2014 SowerPHP | AGPL3+ */
/*jslint browser: true, devel: true, nomen: true, indent: 4 */

/**
 * Constructor de la clase
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-09
 */
function __() {
    'use strict';
    return;
}

/**
 * Método que determina si un objeto es/está vacío o no
 * @param obj Objeto que se está revisando
 * @return =true si es vacio
 */
__.empty = function(obj) {
    'use strict';
    if (obj === undefined || obj === null || obj === "") { return true; }
    if (typeof obj === "number" && isNaN(obj)) { return true; }
    if (obj instanceof Date && isNaN(Number(obj))) { return true; }
    return false;
};

/**
 * Método que determina si un string es la representación entera de un número
 * @param value Valor que se desea verificar si es una representación entera
 * @return =true valor pasado es un entero
 */
__.isInt = function(value) {
    'use strict';
    if ((parseFloat(value) === parseInt(value, 10)) && !isNaN(value)) {
        return true;
    }
    return false;
};

/**
 * Método que determina si un string es la representación decimal de un número
 * @param value Valor que se desea verificar si es una representación decimal
 * @return =true valor pasado es un decimal
 */
__.isFloat = function(value) {
    'use strict';
    if (!isNaN(parseFloat(value))) {
        return true;
    }
    return false;
};

/**
 * Método que formatea un número usando separador de miles
 * @param n Número a formatear (ej: 1234)
 * @return Número formateado (ej: 1.234)
 * @author http://ur1.ca/h1cvs
 */
__.num = function (n) {
    var number = n.toString(), result = "", isNegative = false;
    if (number.indexOf("-") > -1) {
        number = number.substr(1);
        isNegative = true;
    }
    number = Math.round(number).toString();
    while (number.length > 3) {
        result = "." + number.substr(number.length - 3) + result;
        number = number.substring(0, number.length - 3);
    }
    result = number + result;
    if (isNegative) { result = "-" + result; }
    return result;
};

/**
 * Obtiene el dígito verificador a partir del rut sin este
 * @param numero Rut sin puntos ni digito verificador
 * @return char dígito verificador del rut ingresado
 * @author http://ur1.ca/h1d8v
 * @version 2011-04-21
 */
__.rutDV = function(numero) {
    'use strict';
    var nuevo_numero, i, j, suma, n_dv;
    nuevo_numero = numero.toString().split("").reverse().join("");
    for (i = 0, j = 2, suma = 0; i < nuevo_numero.length; i += 1, j = j === 7 ? 2 : j + 1) {
        suma += (parseInt(nuevo_numero.charAt(i), 10) * j);
    }
    n_dv = 11 - (suma % 11);
    return ((n_dv === 11) ? 0 : ((n_dv === 10) ? "K" : n_dv));
};

/**
 * Método que abre un popup
 * @param url Dirección web que se debe abrir en el popup
 * @param w Ancho de la ventana que se abrirá
 * @param h Alto de la ventana que se abrirá
 * @param s Si se muestran ("yes") o no ("no") los scrollbars
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-13
 */
__.popup = function(url, w, h, s) {
    'use strict';
    s = s || "no";
    window.open(
        url,
        '_blank',
        "width=" + w + ",height=" + h + ",directories=no,location=no,menubar=no,scrollbars=" + s + ",status=no,toolbar=no,resizable=no"
    );
    return false;
}


/**
 * Método que genera una tabla HTML y entrega el elemento para ser insertado
 * @param titles Títulos de las columnas de la tabla
 * @param data Datos de la tabla
 * @return Elemento <table>
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-04-07
 */
__.table = function(titles, data) {
    var table = document.createElement("table");
    var thead = document.createElement("thead");
    var tbody = document.createElement("tbody");
    var tr, th, td, cols = [], i, j;
    table.setAttribute("class", "table table-striped");
    // agregar titulos de la tabla
    tr = document.createElement("tr");
    for (var col in titles) {
        cols.push(col);
        th = document.createElement("th");
        th.textContent = titles[col];
        tr.appendChild(th);
    }
    thead.appendChild(tr);
    table.appendChild(thead);
    // agregar datos de la tabla
    for (i = 0; i < data.length; i++) {
        tr = document.createElement("tr");
        for (j = 0; j < cols.length; j++) {
            td = document.createElement("td");
            td.textContent = data[i][cols[j]];
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    // entregar tabla
    return table;
}

/**
 * Método que copia un string al portapapeles
 * @link https://hackernoon.com/copying-text-to-clipboard-with-javascript-df4d4988697f
 */
__.copy = function(string_to_copy, message) {
    // copiar
    var el = document.createElement('textarea');
    el.value = string_to_copy;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    var selected = document.getSelection().rangeCount > 0 ? document.getSelection().getRangeAt(0) : false;
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    if (selected) {
        document.getSelection().removeAllRanges();
        document.getSelection().addRange(selected);
    }
    // mensaje de copiado
    if (typeof message === 'undefined') {
        message = '¡Copiado!';
    }
    bootbox.dialog({
        message: '<div class="text-center">' + message + '</div>',
        centerVertical: true,
        closeButton: true,
        onEscape: true
    });
    window.setTimeout(function(){
        bootbox.hideAll();
    }, 1000);
}

/**
 * Método que crea un formulario para compartir un mensaje por teléfono
 */
__.share = function(telephone, message, method = 'whatsapp') {
    bootbox.prompt({
        title: 'Enviar mensaje por ' + method,
        message: '<p>Teléfono:</p>',
        centerVertical: true,
        locale: 'es',
        backdrop: true,
        buttons: {
            confirm: {
                label: 'Enviar',
                className: 'btn-success'
            },
            cancel: {
                className: 'btn-danger'
            }
        },
        value: telephone ? telephone : '+56 9 ',
        callback: function(telephone) {
            var url = null;
            if (telephone) {
                telephone = telephone.replace(/\+| /g,'');
                if (method == 'whatsapp') {
                    url = 'https://wa.me/' + telephone + '?text=' + encodeURI(message);
                }
                if (url) {
                    var win = window.open(url, '_blank');
                    win.focus();
                }
            }
        }
    });
}
