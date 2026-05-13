/**
 * ==========================================================
 * MÓDULO PADRÃO DE GRÁFICOS
 * Base reutilizável para dashboards com Chart.js 2.9.4
 * ==========================================================
 */

/**
 * Paleta padrão do sistema
 */
const coresPadrao = [
    '#36A2EB', '#FF6384', '#4BC0C0', '#FFCD56',
    '#9966FF', '#FF9F40', '#C9CBCE', '#E7E9ED',
    '#A18CD1', '#FBC2EB', '#84fab0', '#ffc76d'
];

/**
 * Registro global dos gráficos renderizados
 * Evita empilhar gráfico no mesmo canvas
 */
window.myCharts = window.myCharts || {};

/**
 * Configuração padrão global do sistema
 * Tudo que não for informado na chamada usará esses valores
 */
const chartDefaults = {
    showLegend: null,          // null = decide automaticamente pelo tipo
    legendPosition: 'bottom',
    datalabelAnchor: null,     // null = usa padrão automático
    datalabelAlign: null,      // null = usa padrão automático
    datalabelOffset: null,     // null = usa padrão automático
    showDataLabels: true,
    legendFontColor: '#cfd8e3',
    axisFontColor: '#7f8aa3',
    dataLabelColor: '#ffffff',
    dataLabelFontSize: 13,
    dataLabelFontWeight: 'bold',

    //Bordinha no texto dos graficos
    dataLabelTextStrokeColor: 'rgba(15, 23, 42, 0.85)',
    dataLabelTextStrokeWidth: 4
};

/**
 * Retorna uma quantidade suficiente de cores
 */
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
 * Calcula percentual formatado
 */
function getPercentual(valor, totalGeral) {
    if (!totalGeral || totalGeral <= 0) return '0,0';
    return ((valor / totalGeral) * 100).toFixed(1).replace('.', ',');
}

/**
 * Retorna texto padrão do rótulo
 * Exemplo: 10 (38,5%)
 */
function getLabelTexto(item, totalGeral) {
    const valor = Number(item.total) || 0;
    const totalFormatado = valor.toLocaleString('pt-BR');
    const percentual = getPercentual(valor, totalGeral);
    return `${totalFormatado} (${percentual}%)`;
}

/**
 * Mede a largura de um texto em pixels
 * Usado para calcular paddings automáticos
 */
function measureTextWidth(texto, font = 'bold 13px Arial') {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    ctx.font = font;
    return ctx.measureText(texto).width;
}

/**
 * Define padding automático por tipo de gráfico
 */
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

    if (['pie', 'doughnut', 'polarArea'].includes(chartType)) {
        // Reserva espaço proporcional ao maior texto do datalabel
        // para evitar corte nas bordas do canvas.
        const espacamentoBase = window.innerWidth >= 1200
            ? Math.max(22, Math.min(48, Math.ceil(maiorTexto * 0.32)))
            : Math.max(40, Math.ceil(maiorTexto * 0.55));

        return {
            top: espacamentoBase,
            right: espacamentoBase,
            bottom: showLegend ? Math.max(30, espacamentoBase) : espacamentoBase + 10,
            left: espacamentoBase
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
 * Monta a configuração final do gráfico
 * Junta:
 * 1. valores padrão do sistema
 * 2. opções passadas na chamada
 */
function buildChartConfig(chartType, options = {}) {
    return {
        showLegend: options.showLegend ?? (
            chartDefaults.showLegend ?? ['pie', 'doughnut', 'polarArea'].includes(chartType)
        ),
        legendPosition: options.legendPosition || chartDefaults.legendPosition,
        datalabelAnchor: options.datalabelAnchor ?? chartDefaults.datalabelAnchor,
        datalabelAlign: options.datalabelAlign ?? chartDefaults.datalabelAlign,
        datalabelOffset: options.datalabelOffset ?? chartDefaults.datalabelOffset,
        showDataLabels: options.showDataLabels ?? chartDefaults.showDataLabels,
        legendFontColor: options.legendFontColor || chartDefaults.legendFontColor,
        axisFontColor: options.axisFontColor || chartDefaults.axisFontColor,
        dataLabelColor: options.dataLabelColor || chartDefaults.dataLabelColor,
        dataLabelFontSize: options.dataLabelFontSize || chartDefaults.dataLabelFontSize,
        dataLabelFontWeight: options.dataLabelFontWeight || chartDefaults.dataLabelFontWeight,

        //Bordinha no texto dos graficos
        dataLabelTextStrokeColor: options.dataLabelTextStrokeColor || chartDefaults.dataLabelTextStrokeColor,
        dataLabelTextStrokeWidth: options.dataLabelTextStrokeWidth || chartDefaults.dataLabelTextStrokeWidth
    };
}

/**
 * Cria um gráfico genérico e reutilizável
 *
 * @param {string} canvasId        ID do canvas
 * @param {Array} dataArray        Dados do gráfico
 * @param {string} labelProperty   Nome do campo que será usado como label
 * @param {string} chartType       Tipo do gráfico: bar, horizontalBar, pie...
 * @param {string} datasetLabel    Nome do dataset
 * @param {Object} options         Configurações opcionais
 */
function createChart(canvasId, dataArray, labelProperty, chartType, datasetLabel, options = {}) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) {
        return;
    }

    if (window.myCharts[canvasId]) {
        window.myCharts[canvasId].destroy();
        delete window.myCharts[canvasId];
    }

    canvas.style.width = '100%';
    canvas.style.height = '100%';

    if (!Array.isArray(dataArray) || dataArray.length === 0) {
        const emptyCtx = canvas.getContext('2d');
        emptyCtx.clearRect(0, 0, canvas.width, canvas.height);
        return;
    }

    const config = buildChartConfig(chartType, options);

    const labels = dataArray.map(item => item[labelProperty]);
    const totais = dataArray.map(item => Number(item.total) || 0);
    const totalGeral = totais.reduce((sum, value) => sum + value, 0);
    const colors = getColors(labels.length);

    const isHorizontalBar = chartType === 'horizontalBar';
    const isPieLike = ['pie', 'doughnut', 'polarArea'].includes(chartType);

    const padding = getAutoPaddingByType(dataArray, totalGeral, chartType, config.showLegend);

    const defaultAnchor = isPieLike ? 'end' : (isHorizontalBar ? 'end' : 'end');
    const defaultAlign = isPieLike ? 'end' : (isHorizontalBar ? 'right' : 'top');
    const defaultOffset = isPieLike
        ? (window.innerWidth <= 576 ? 6 : 10)
        : (isHorizontalBar ? 6 : 4);

    const ctx = canvas.getContext('2d');

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
                    fontColor: config.legendFontColor,
                    fontSize: window.innerWidth >= 1200 ? 14 : 12,
                    boxWidth: window.innerWidth >= 1200 ? 16 : 14,
                    padding: window.innerWidth >= 1200 ? 16 : 14,
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
                        fontColor: config.axisFontColor
                    },
                    gridLines: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false
                    }
                }],
                yAxes: isHorizontalBar ? [{
                    ticks: {
                        fontColor: config.axisFontColor,
                        fontSize: 12
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    }
                }] : [{
                    ticks: {
                        beginAtZero: true,
                        fontColor: config.axisFontColor
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
                    color: config.dataLabelColor,

                    //Bordinha no texto dos graficos
                    textStrokeColor: config.dataLabelTextStrokeColor,
                    textStrokeWidth: config.dataLabelTextStrokeWidth,


                    font: {
                        weight: config.dataLabelFontWeight,
                        size: isPieLike && window.innerWidth >= 1200 ? 13 : (window.innerWidth <= 576 && isPieLike ? 11 : config.dataLabelFontSize)
                    },

                    // Para pizza, deixa o rótulo um pouco menos "agressivo"
                    anchor: config.datalabelAnchor || defaultAnchor,
                    align: config.datalabelAlign || defaultAlign,
                    offset: config.datalabelOffset ?? defaultOffset,

                    clamp: isPieLike,
                    clip: false
                }
            }
        }
    });
}
