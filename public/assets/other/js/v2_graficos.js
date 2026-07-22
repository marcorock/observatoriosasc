const coresPadrao = [
    '#36A2EB', '#FF6384', '#4BC0C0', '#FFCD56', '#9966FF', '#FF9F40',
    '#C9CBCE', '#E7E9ED', '#A18CD1', '#FBC2EB', '#84fab0', '#ffc76d'
];

function getColors(count) {
    if (count <= coresPadrao.length) {
        return coresPadrao.slice(0, count);
    }

    const colors = [...coresPadrao];
    while (colors.length < count) {
        colors.push(...coresPadrao);
    }
    return colors.slice(0, count);
}

/**
 * Calcula o espaço necessário à direita para não cortar os datalabels.
 */
function getAutoRightPadding(dataArray, totalGeral) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // mesma fonte usada no datalabel
    ctx.font = 'bold 13px Arial';

    let maiorLargura = 0;

    dataArray.forEach(item => {
        const percentual = totalGeral > 0
            ? ((Number(item.total) / totalGeral) * 100).toFixed(1).replace('.', ',')
            : '0,0';

        const total = Number(item.total).toLocaleString('pt-BR');
        const texto = `${total} (${percentual}%)`;

        const largura = ctx.measureText(texto).width;
        if (largura > maiorLargura) {
            maiorLargura = largura;
        }
    });

    // margem extra para respirar
    return Math.ceil(maiorLargura + 20);
}

function createChart(canvasId, dataArray, labelProperty, chartType, datasetLabel, anchorPosition = 'end') {
    const labels = dataArray.map(item => item[labelProperty]);
    const totais = dataArray.map(item => Number(item.total));
    const totalGeral = totais.reduce((sum, value) => sum + value, 0);

    const rightPadding = getAutoRightPadding(dataArray, totalGeral);

    const ctx = document.getElementById(canvasId).getContext('2d');

    new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: totais, // barra baseada no valor real
                backgroundColor: getColors(labels.length),
                borderWidth: 0,
                barPercentage: 0.72,
                categoryPercentage: 0.78
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    right: rightPadding,
                    bottom: 10,
                    left: 0
                }
            },
            legend: {
                display: false
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem) {
                        const index = tooltipItem.index;
                        const valor = Number(dataArray[index].total);
                        const percentual = totalGeral > 0
                            ? ((valor / totalGeral) * 100).toFixed(1).replace('.', ',')
                            : '0,0';

                        const total = valor.toLocaleString('pt-BR');
                        const label = dataArray[index][labelProperty];

                        return `${label}: ${total} (${percentual}%)`;
                    }
                }
            },
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        padding: 8
                    },
                    gridLines: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        fontColor: '#7f8aa3',
                        fontSize: 12
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    }
                }]
            },
            plugins: {
                datalabels: {
                    formatter: function(value, ctx) {
                        const index = ctx.dataIndex;
                        const valor = Number(dataArray[index].total);

                        const percentual = totalGeral > 0
                            ? ((valor / totalGeral) * 100).toFixed(1).replace('.', ',')
                            : '0,0';

                        const total = valor.toLocaleString('pt-BR');

                        return `${total} (${percentual}%)`;
                    },
                    color: '#ffffff',
                    font: {
                        weight: 'bold',
                        size: 13
                    },
                    anchor: 'end',
                    align: 'right',
                    offset: 6,
                    clamp: false,
                    clip: false
                }
            }
        }
    });
}