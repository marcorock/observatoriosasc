/**
 * ==========================================================
 * MÓDULO PADRÃO DE GRÁFICOS PARA DASHBOARD TV
 * Chart.js 2.9.4 + chartjs-plugin-datalabels
 * ==========================================================
 *
 * Objetivo:
 * Centralizar a criação dos gráficos em uma única função,
 * permitindo reaproveitamento, padronização visual e
 * configuração dinâmica por preset e opções específicas.
 */

/**
 * ----------------------------------------------------------
 * PALETA DE CORES PADRÃO
 * ----------------------------------------------------------
 * Lista base de cores usada nos gráficos.
 * Se houver mais categorias do que cores, a função getColors()
 * repete essa paleta até atingir a quantidade necessária.
 */
const coresPadrao = [
    '#36A2EB', '#FF6384', '#4BC0C0', '#FFCD56',
    '#9966FF', '#FF9F40', '#C9CBCE', '#E7E9ED',
    '#A18CD1', '#FBC2EB', '#84fab0', '#ffc76d'
];

/**
 * ----------------------------------------------------------
 * REGISTRO GLOBAL DOS GRÁFICOS
 * ----------------------------------------------------------
 * Guarda as instâncias dos gráficos criados.
 * Isso evita que um mesmo canvas receba vários gráficos
 * empilhados caso a função seja chamada novamente.
 */
window.myCharts = window.myCharts || {};

/**
 * ----------------------------------------------------------
 * CONFIGURAÇÃO PADRÃO GLOBAL
 * ----------------------------------------------------------
 * Aqui ficam os valores padrão do sistema.
 * Se nada for passado na chamada da função, estes valores
 * servirão como base.
 *
 * Observações:
 * - showLegend: null significa "deixe a função decidir"
 * - datalabelAnchor / Align / Offset: null significa
 *   "use o comportamento padrão conforme o tipo do gráfico"
 */
const chartDefaults = {
    showLegend: null,
    legendPosition: 'bottom',
    datalabelAnchor: null,
    datalabelAlign: null,
    datalabelOffset: null,
    showDataLabels: true,
    legendFontColor: '#cfd8e3',
    axisFontColor: '#7f8aa3',
    dataLabelColor: '#ffffff',
    dataLabelFontSize: 13,
    dataLabelFontWeight: 'bold'
};

/**
 * ----------------------------------------------------------
 * PRESETS PRONTOS PARA REUTILIZAÇÃO
 * ----------------------------------------------------------
 * Presets são "perfis" visuais/comportamentais.
 * Em vez de passar várias opções manualmente a cada gráfico,
 * você pode usar um preset e manter consistência no dashboard.
 */
const chartPresets = {

    /**
     * Preset para gráfico de pizza em TV
     * - mostra legenda
     * - mostra rótulos dos dados
     * - rótulos ficam um pouco afastados da fatia
     */
    tvPie: {
        showLegend: true,
        legendPosition: 'bottom',
        showDataLabels: true,
        datalabelAnchor: 'end',
        datalabelAlign: 'end',
        datalabelOffset: 12
    },

    /**
     * Preset para gráfico de barras verticais
     * - sem legenda
     * - mostra rótulos acima das colunas
     */
    tvBar: {
        showLegend: false,
        showDataLabels: true,
        datalabelAnchor: 'end',
        datalabelAlign: 'top',
        datalabelOffset: 4
    },

    /**
     * Preset para gráfico de barras horizontais
     * - sem legenda
     * - mostra rótulos no final da barra
     */
    tvHorizontalBar: {
        showLegend: false,
        showDataLabels: true,
        datalabelAnchor: 'end',
        datalabelAlign: 'right',
        datalabelOffset: 6
    },

    /**
     * Preset "clean"
     * - sem legenda
     * - sem rótulos internos
     * Útil quando o gráfico precisa ficar mais limpo.
     */
    clean: {
        showLegend: false,
        showDataLabels: false
    }
};

/**
 * ----------------------------------------------------------
 * FUNÇÃO: getColors(count)
 * ----------------------------------------------------------
 * Retorna uma quantidade de cores suficiente para o número
 * de itens do gráfico.
 *
 * Exemplo:
 * Se houver 5 categorias, retorna 5 cores.
 * Se houver 20 categorias, repete a paleta até completar 20.
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
 * ----------------------------------------------------------
 * FUNÇÃO: getPercentual(valor, totalGeral)
 * ----------------------------------------------------------
 * Calcula o percentual de um valor em relação ao total.
 *
 * Exemplo:
 * valor = 25, totalGeral = 100
 * retorno = "25,0"
 *
 * O resultado vem formatado em pt-BR:
 * - usa vírgula no decimal
 * - com uma casa decimal
 */
function getPercentual(valor, totalGeral) {
    if (!totalGeral || totalGeral <= 0) return '0,0';
    return ((valor / totalGeral) * 100).toFixed(1).replace('.', ',');
}

/**
 * ----------------------------------------------------------
 * FUNÇÃO: getLabelTexto(item, totalGeral)
 * ----------------------------------------------------------
 * Monta o texto padrão que será usado nos rótulos:
 *
 * Exemplo:
 * 150 (37,5%)
 *
 * Isso facilita reaproveitar o mesmo formato em:
 * - datalabels
 * - tooltip
 * - legend customizada
 */
function getLabelTexto(item, totalGeral) {
    const valor = Number(item.total) || 0;
    const totalFormatado = valor.toLocaleString('pt-BR');
    const percentual = getPercentual(valor, totalGeral);

    return `${totalFormatado} (${percentual}%)`;
}

/**
 * ----------------------------------------------------------
 * FUNÇÃO: measureTextWidth(texto, font)
 * ----------------------------------------------------------
 * Mede em pixels a largura de um texto.
 *
 * Isso é útil porque alguns gráficos, como horizontalBar,
 * precisam reservar espaço extra para os rótulos não ficarem
 * cortados no lado direito.
 */
function measureTextWidth(texto, font = 'bold 13px Arial') {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    ctx.font = font;

    return ctx.measureText(texto).width;
}

/**
 * ----------------------------------------------------------
 * FUNÇÃO: getAutoPaddingByType(...)
 * ----------------------------------------------------------
 * Define o padding automático conforme o tipo do gráfico.
 *
 * Por que isso é necessário?
 * Porque cada gráfico ocupa espaço de forma diferente:
 *
 * - horizontalBar:
 *   precisa de espaço à direita para os rótulos
 *
 * - bar:
 *   precisa de espaço no topo para os rótulos acima das colunas
 *
 * - pie/doughnut/polarArea:
 *   precisa de folga em volta para fatias, rótulos e legenda
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
 * ----------------------------------------------------------
 * FUNÇÃO: buildChartConfig(chartType, presetName, options)
 * ----------------------------------------------------------
 * Junta três camadas de configuração:
 *
 * 1. chartDefaults  -> padrão global do sistema
 * 2. chartPresets   -> preset escolhido
 * 3. options        -> opções passadas na chamada
 *
 * Ordem de prioridade:
 * options > preset > defaults
 *
 * Ou seja:
 * o que for passado explicitamente na chamada sempre vence.
 */
function buildChartConfig(chartType, presetName = null, options = {}) {
    const preset = presetName && chartPresets[presetName]
        ? chartPresets[presetName]
        : {};

    const merged = {
        ...chartDefaults,
        ...preset,
        ...options
    };

    return {
        /**
         * Se merged.showLegend for null ou undefined,
         * ativa automaticamente legenda para gráficos de pizza.
         */
        showLegend: merged.showLegend ?? ['pie', 'doughnut', 'polarArea'].includes(chartType),

        /**
         * Posição da legenda:
         * top, bottom, left, right
         */
        legendPosition: merged.legendPosition || 'bottom',

        /**
         * Configurações do plugin datalabels.
         * Se vier null, a função principal decide automaticamente.
         */
        datalabelAnchor: merged.datalabelAnchor ?? null,
        datalabelAlign: merged.datalabelAlign ?? null,
        datalabelOffset: merged.datalabelOffset ?? null,

        /**
         * Define se os rótulos de dados aparecerão no gráfico.
         */
        showDataLabels: merged.showDataLabels ?? true,

        /**
         * Cores e estilos de fonte
         */
        legendFontColor: merged.legendFontColor || '#cfd8e3',
        axisFontColor: merged.axisFontColor || '#7f8aa3',
        dataLabelColor: merged.dataLabelColor || '#ffffff',
        dataLabelFontSize: merged.dataLabelFontSize || 13,
        dataLabelFontWeight: merged.dataLabelFontWeight || 'bold'
    };
}

/**
 * ----------------------------------------------------------
 * FUNÇÃO PRINCIPAL: createChart(...)
 * ----------------------------------------------------------
 * Cria um gráfico genérico e reutilizável.
 *
 * Parâmetros:
 * - canvasId: ID do canvas onde o gráfico será desenhado
 * - dataArray: array de objetos com os dados
 * - labelProperty: nome do campo que será usado como label
 * - chartType: tipo do gráfico (pie, bar, horizontalBar...)
 * - datasetLabel: nome do dataset
 * - presetName: nome do preset a aplicar
 * - options: sobrescreve qualquer configuração específica
 */
function createChart(canvasId, dataArray, labelProperty, chartType, datasetLabel, presetName = null, options = {}) {
    const canvas = document.getElementById(canvasId);

    /**
     * Validação básica:
     * se o canvas não existir ou os dados forem inválidos,
     * a função encerra sem tentar renderizar.
     */
    if (!canvas || !Array.isArray(dataArray) || dataArray.length === 0) {
        return;
    }

    /**
     * Monta a configuração final do gráfico
     * usando defaults + preset + options
     */
    const config = buildChartConfig(chartType, presetName, options);

    /**
     * Extrai labels e totais do array de dados
     */
    const labels = dataArray.map(item => item[labelProperty]);
    const totais = dataArray.map(item => Number(item.total) || 0);

    /**
     * Soma geral dos totais
     * usada para calcular os percentuais
     */
    const totalGeral = totais.reduce((sum, value) => sum + value, 0);

    /**
     * Gera a lista de cores conforme a quantidade de labels
     */
    const colors = getColors(labels.length);

    /**
     * Identifica o tipo do gráfico para aplicar regras específicas
     */
    const isHorizontalBar = chartType === 'horizontalBar';
    const isPieLike = ['pie', 'doughnut', 'polarArea'].includes(chartType);

    /**
     * Calcula o padding automático do gráfico
     */
    const padding = getAutoPaddingByType(dataArray, totalGeral, chartType, config.showLegend);

    /**
     * Valores padrão de posicionamento dos datalabels,
     * caso o usuário não passe nada explicitamente
     */
    const defaultAnchor = isPieLike ? 'end' : (isHorizontalBar ? 'end' : 'end');
    const defaultAlign = isPieLike ? 'end' : (isHorizontalBar ? 'right' : 'top');
    const defaultOffset = isPieLike ? 12 : (isHorizontalBar ? 6 : 4);

    const ctx = canvas.getContext('2d');

    /**
     * Se já existir um gráfico nesse canvas,
     * destrói antes de criar outro.
     *
     * Isso evita gráficos duplicados ou sobrepostos.
     */
    if (window.myCharts[canvasId]) {
        window.myCharts[canvasId].destroy();
    }

    /**
     * Criação efetiva do gráfico
     */
    window.myCharts[canvasId] = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: totais,
                backgroundColor: colors,
                borderWidth: 0,

                /**
                 * Ajustes visuais para barras
                 * horizontalBar usa uma proporção ligeiramente diferente
                 */
                barPercentage: isHorizontalBar ? 0.72 : 0.8,
                categoryPercentage: isHorizontalBar ? 0.78 : 0.9
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,

            /**
             * Espaçamento interno do gráfico
             */
            layout: {
                padding: padding
            },

            /**
             * Legenda
             * Pode ser ligada/desligada dinamicamente
             */
            legend: {
                display: config.showLegend,
                position: config.legendPosition,
                labels: {
                    fontColor: config.legendFontColor,
                    fontSize: 12,
                    boxWidth: 14,
                    padding: 14,

                    /**
                     * Personaliza os textos da legenda
                     * Exemplo:
                     * Eixo A: 150 (37,5%)
                     */
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

            /**
             * Tooltip ao passar o mouse
             */
            tooltips: {
                callbacks: {
                    label: function(tooltipItem) {
                        const index = tooltipItem.index;
                        const label = dataArray[index][labelProperty];

                        return `${label}: ${getLabelTexto(dataArray[index], totalGeral)}`;
                    }
                }
            },

            /**
             * Escalas
             * Gráficos tipo pizza não usam eixos
             */
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

            /**
             * Plugin de rótulos dos dados
             * Exibe total + percentual diretamente no gráfico
             */
            plugins: {
                datalabels: {
                    display: config.showDataLabels,

                    /**
                     * Texto que será exibido em cada dado
                     */
                    formatter: function(value, ctx) {
                        const index = ctx.dataIndex;
                        return getLabelTexto(dataArray[index], totalGeral);
                    },

                    color: config.dataLabelColor,

                    font: {
                        weight: config.dataLabelFontWeight,
                        size: config.dataLabelFontSize
                    },

                    /**
                     * Posicionamento do datalabel
                     * Se não vier em config, usa padrão automático
                     */
                    anchor: config.datalabelAnchor || defaultAnchor,
                    align: config.datalabelAlign || defaultAlign,
                    offset: config.datalabelOffset ?? defaultOffset,

                    /**
                     * clamp: tenta manter o rótulo dentro da área útil
                     * clip: false evita cortar o rótulo visualmente
                     */
                    clamp: true,
                    clip: false
                }
            }
        }
    });
}