function generarPDF() {

    const pdf = new jspdf.jsPDF(
        'l',
        'mm',
        'a4'
    );

    // ===== MEDIDAS =====
    const anchoPagina =
        pdf.internal.pageSize.getWidth();

    const altoPagina =
        pdf.internal.pageSize.getHeight();

    const MARGEN_IZQUIERDO = 10;

    // ===== DATOS =====
    const valTotalVentas =
        document.getElementById('kpi_total_ventas')
        ? document.getElementById('kpi_total_ventas').innerText
        : "0";

    const valMontoTotal =
        document.getElementById('kpi_monto_total')
        ? document.getElementById('kpi_monto_total').innerText
        : "$0.00";

    const valPromedio =
        document.getElementById('kpi_promedio')
        ? document.getElementById('kpi_promedio').innerText
        : "$0.00";

    // ===== COLORES =====
    const azul = [59, 63, 92];

    const azulBrand = [37, 99, 235];

    const grisOscuro = [30, 41, 59];

    const grisClaro = [100, 116, 139];

    // ===== BARRA LATERAL =====
    pdf.setFillColor(...azul);

    pdf.rect(
        0,
        0,
        8,
        altoPagina,
        'F'
    );

    // ===== TITULO =====
    pdf.setFont(
        "helvetica",
        "bold"
    );

    pdf.setFontSize(22);

    pdf.setTextColor(...grisOscuro);

    pdf.text(
        "REPORTE DE VENTAS",
        MARGEN_IZQUIERDO + 5,
        20
    );

    // ===== EMPRESA =====
    pdf.setFontSize(10);

    pdf.setTextColor(...azulBrand);

    pdf.text(
        "NOMBRE EMPRESA",
        MARGEN_IZQUIERDO + 5,
        30
    );

    pdf.setFont(
        "helvetica",
        "normal"
    );

    pdf.setTextColor(...grisClaro);

    pdf.text(
        "Sistema de Ventas",
        MARGEN_IZQUIERDO + 5,
        35
    );

    // ===== FECHA =====
    const cajaX = anchoPagina - 65;

    pdf.setFillColor(
        241,
        245,
        249
    );

    pdf.roundedRect(
        cajaX,
        12,
        50,
        20,
        3,
        3,
        'F'
    );

    pdf.setFontSize(8);

    pdf.text(
        "FECHA DE EMISIÓN",
        cajaX + 5,
        19
    );

    pdf.setFontSize(10);

    pdf.setFont(
        "helvetica",
        "bold"
    );

    pdf.setTextColor(...grisOscuro);

    pdf.text(
        new Date().toLocaleDateString(),
        cajaX + 5,
        27
    );

    // ===== KPIs =====
    const startY = 45;

    const anchoCuadro = 80;

    const altoCuadro = 25;

    const espacio = 8;

    function dibujarKPI(
        x,
        titulo,
        valor
    ) {

        pdf.setDrawColor(
            220,
            220,
            220
        );

        pdf.setFillColor(
            255,
            255,
            255
        );

        pdf.roundedRect(
            x,
            startY,
            anchoCuadro,
            altoCuadro,
            2,
            2,
            'FD'
        );

        pdf.setFillColor(...azul);

        pdf.rect(
            x,
            startY,
            anchoCuadro,
            2,
            'F'
        );

        pdf.setFontSize(8);

        pdf.setFont(
            "helvetica",
            "bold"
        );

        pdf.setTextColor(...grisClaro);

        pdf.text(
            titulo,
            x + 5,
            startY + 8
        );

        pdf.setFontSize(14);

        pdf.setTextColor(...grisOscuro);

        pdf.text(
            valor,
            x + 5,
            startY + 18
        );
    }

    let x = 15;

    dibujarKPI(
        x,
        "TOTAL VENTAS",
        valTotalVentas
    );

    x += anchoCuadro + espacio;

    dibujarKPI(
        x,
        "MONTO TOTAL",
        valMontoTotal
    );

    x += anchoCuadro + espacio;

    dibujarKPI(
        x,
        "PROMEDIO / VENTA",
        valPromedio
    );

    // ===== TABLA =====
    const filas = [];

    document.querySelectorAll(
        "#tablaPDF tbody tr"
    ).forEach(tr => {

        const fila = [];

        tr.querySelectorAll("td").forEach(td => {

            fila.push(
                td.innerText.trim()
            );

        });

        filas.push(fila);

    });

    // ===== PDF TABLE =====
    pdf.autoTable({

        startY: 80,

        head: [[
            "ID",
            "Cliente",
            "Producto",
            "Cantidad",
            "Precio",
            "Total",
            "Fecha",
        ]],

        body: filas,

        theme: 'grid',

        styles: {

            fontSize: 8,

            cellPadding: 2,

            halign: 'center',

            valign: 'middle'

        },

        headStyles: {

            fillColor: azul,

            textColor: [255, 255, 255],

            fontStyle: 'bold'

        },

        alternateRowStyles: {

            fillColor: [245, 247, 250]

        },

        margin: {

            left: 10,

            right: 10

        },

        didDrawPage: function () {

            pdf.setFillColor(...azul);

            pdf.rect(
                0,
                0,
                8,
                altoPagina,
                'F'
            );

        }

    });

    // ===== GUARDAR =====
    pdf.save(
        'Reporte_Ventas.pdf'
    );

}