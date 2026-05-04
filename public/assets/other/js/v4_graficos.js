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

function getPercentual(valor, totalGeral) {
    if (!totalGeral || totalGeral <= 0) return '0,0';
    return ((valor / totalGeral) * 100).toFixed(1).replace('.', ',');
}

function getLabelTexto(item, totalGeral) {
    const valor = Number(item.total) || 0;
    const totalFormatado = valor.toLocaleString('pt-BR');
    const percentual = getPercentual(valor, totalGeral);
    return `${totalFormatado} (${percentual}%)`;
}

function measureTextWidth(texto, font = 'bold 13px Arial') {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    ctx.font = font;
    return ctx.measureText(texto).width;
}

function getAutoPaddingByType(dataArray, totalGeral, chartType, showLegend) {
    let maiorTexto = 0;

    dataArray.forEach(item => {
        const texto = getLabelTexto(item, totalGeral);
        const largura = measureTextWidth(texto);
        if (largura > maiorTexto) {
            maiorTexto = largura;
        }
    });

    if (chartType === 'horizontalBar') {
        return {
            top: 10,
            right: Math.ceil(maiorTexto + 24),
            bottom: 10,
            left: 0
        };
    }

    if (chartType === 'bar') {
        return {
            top: 35,
            right: 10,
            bottom: 10,
            left: 0
        };
    }

    if (chartType === 'pie' || chartType === 'doughnut' || chartType === 'polarArea') {
        return {
            top: 30,
            right: 40,
            bottom: showLegend ? 30 : 20,
            left: 40
        };
    }

    return {
        top: 10,
        right: 10,
        bottom: 10,
        left: 0
    };
}

/**
 * Cria um gráfico genérico
 *
 * @param {string} canvasId
 * @param {Array} dataArray
 * @param {string} labelProperty
 * @param {string} chartType
 * @param {string} datasetLabel
 * @param {Object} options
 */
function createChart(canvasId, dataArray, labelProperty, chartType, datasetLabel, options = {}) {
    const canvas = document.getElementById(canvasId);

    if (!canvas || !Array.isArray(dataArray) || dataArray.length === 0) {
        return;
    }

    const config = {
        showLegend: options.showLegend ?? ['pie', 'doughnut', 'polarArea'].includes(chartType),
        legendPosition: options.legendPosition || 'bottom',
        datalabelAnchor: options.datalabelAnchor || null,
        datalabelAlign: options.datalabelAlign || null,
        datalabelOffset: options.datalabelOffset ?? null,
        showDataLabels: options.showDataLabels ?? true
    };

    const labels = dataArray.map(item => item[labelProperty]);
    const totais = dataArray.map(item => Number(item.total) || 0);
    const totalGeral = totais.reduce((sum, value) => sum + value, 0);
    const colors = getColors(labels.length);

    const isHorizontalBar = chartType === 'horizontalBar';
    const isVerticalBar = chartType === 'bar';
    const isPieLike = ['pie', 'doughnut', 'polarArea'].includes(chartType);

    const padding = getAutoPaddingByType(dataArray, totalGeral, chartType, config.showLegend);
    const ctx = canvas.getContext('2d');

    const defaultAnchor = isPieLike ? 'end' : (isHorizontalBar ? 'end' : 'end');
    const defaultAlign = isPieLike ? 'end' : (isHorizontalBar ? 'right' : 'top');
    const defaultOffset = isPieLike ? 12 : (isHorizontalBar ? 6 : 4);

    window.myCharts = window.myCharts || {};

    if (window.myCharts[canvasId]) {
        window.myCharts[canvasId].destroy();
    }

    window.myCharts[canvasId] = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: totais,
                backgroundColor: colors,
                borderWidth: 0,
                barPercentage: isHorizontalBar ? 0.72 : 0.8,
                categoryPercentage: isHorizontalBar ? 0.78 : 0.9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: padding
            },
            legend: {
                display: config.showLegend,
                position: config.legendPosition,
                labels: {
                    fontColor: '#cfd8e3',
                    fontSize: 12,
                    boxWidth: 14,
                    padding: 14,
                    generateLabels: function(chart) {
                        const data = chart.data;

                        if (!data.labels.length || !data.datasets.length) {
                            return [];
                        }

                        return data.labels.map((label, i) => {
                            const item = dataArray[i];
                            const texto = `${label}: ${getLabelTexto(item, totalGeral)}`;

                            return {
                                text: texto,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: data.datasets[0].backgroundColor[i],
                                lineWidth: 0,
                                hidden: isNaN(data.datasets[0].data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                index: i
                            };
                        });
                    }
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem) {
                        const index = tooltipItem.index;
                        const label = dataArray[index][labelProperty];
                        return `${label}: ${getLabelTexto(dataArray[index], totalGeral)}`;
                    }
                }
            },
            scales: isPieLike ? {} : {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        padding: 8,
                        fontColor: '#7f8aa3'
                    },
                    gridLines: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false
                    }
                }],
                yAxes: isHorizontalBar ? [{
                    ticks: {
                        fontColor: '#7f8aa3',
                        fontSize: 12
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    }
                }] : [{
                    ticks: {
                        beginAtZero: true,
                        fontColor: '#7f8aa3'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    }
                }]
            },
            plugins: {
                datalabels: {
                    display: config.showDataLabels,
                    formatter: function(value, ctx) {
                        const index = ctx.dataIndex;
                        return getLabelTexto(dataArray[index], totalGeral);
                    },
                    color: '#ffffff',
                    font: {
                        weight: 'bold',
                        size: 13
                    },
                    anchor: config.datalabelAnchor || defaultAnchor,
                    align: config.datalabelAlign || defaultAlign,
                    offset: config.datalabelOffset ?? defaultOffset,
                    clamp: true,
                    clip: false
                }
            }
        }
    });
}