function generarPDF() {

    // ===== TABLA =====
    const tabla = document.getElementById("tablaPDF");

    if (!tabla) {
        console.error("No existe tablaPDF");
        return;
    }

    // ===== KPIs =====
    const totalProductos =
        document.getElementById('total_productos')
        ?.innerText || "0";

    const valorCompra =
        document.getElementById('valor_compra')
        ?.innerText || "$0.00";

    const valorVenta =
        document.getElementById('valor_venta')
        ?.innerText || "$0.00";

    // ===== PDF =====
    const pdf = new jspdf.jsPDF(
        'l', // horizontal
        'mm',
        'a4'
    );

    const anchoPagina =
        pdf.internal.pageSize.getWidth();

    const altoPagina =
        pdf.internal.pageSize.getHeight();

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
        "REPORTE DE INVENTARIO",
        15,
        20
    );

    // ===== EMPRESA =====
    pdf.setFontSize(10);

    pdf.setTextColor(...azulBrand);

    pdf.text(
        "NOMBRE EMPRESA",
        15,
        28
    );

    pdf.setFont(
        "helvetica",
        "normal"
    );

    pdf.setTextColor(...grisClaro);

    pdf.text(
        "Sistema de Inventario",
        15,
        34
    );

    // ===== FECHA =====
    pdf.setFontSize(10);

    pdf.text(
        "Fecha: " +
        new Date().toLocaleDateString(),
        anchoPagina - 70,
        20
    );

    // ===== KPIs =====
    function dibujarKPI(
        x,
        titulo,
        valor
    ) {

        pdf.setDrawColor(220);

        pdf.setFillColor(255);

        pdf.roundedRect(
            x,
            45,
            80,
            25,
            3,
            3,
            'FD'
        );

        pdf.setFillColor(...azul);

        pdf.rect(
            x,
            45,
            80,
            3,
            'F'
        );

        pdf.setFontSize(9);

        pdf.setTextColor(...grisClaro);

        pdf.setFont(
            "helvetica",
            "bold"
        );

        pdf.text(
            titulo,
            x + 5,
            57
        );

        pdf.setFontSize(14);

        pdf.setTextColor(...grisOscuro);

        pdf.text(
            valor,
            x + 5,
            67
        );
    }

    dibujarKPI(
        15,
        "TOTAL PRODUCTOS",
        totalProductos
    );

    dibujarKPI(
        105,
        "VALOR COMPRA",
        valorCompra
    );

    dibujarKPI(
        195,
        "VALOR VENTA",
        valorVenta
    );

    // ===== TABLA =====
    pdf.autoTable({

        html: '#tablaPDF',

        startY: 80,

        styles: {

            fontSize: 8,

            cellPadding: 2,

            overflow: 'linebreak',

            halign: 'center'

        },

        headStyles: {

            fillColor: azul,

            textColor: 255,

            fontStyle: 'bold'

        },

        alternateRowStyles: {

            fillColor: [248, 250, 252]

        },

        bodyStyles: {

            textColor: 50

        },

        margin: {

            left: 15,

            right: 15

        },

        didDrawPage: function () {

            // FOOTER
            const pageCount =
                pdf.internal.getNumberOfPages();

            pdf.setFontSize(8);

            pdf.setTextColor(120);

            pdf.text(
                'Página ' + pageCount,
                anchoPagina - 30,
                altoPagina - 10
            );
        }

    });

    // ===== GUARDAR =====
    pdf.save(
        'Reporte_Inventario.pdf'
    );
}