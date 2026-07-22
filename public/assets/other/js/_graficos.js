// Paleta de cores e função getColors permanecem as mesmas
    const coresPadrao = [
        '#36A2EB', '#FF6384', '#4BC0C0', '#FFCD56', '#9966FF', '#FF9F40',
        '#C9CBCE', '#E7E9ED', '#A18CD1', '#FBC2EB', '#84fab0', '#ffc76d'
    ];
    // Função para obter cores suficientes para o número de categorias
    function getColors(count) {
        return coresPadrao.slice(0, Math.min(count, coresPadrao.length));
    }

    // --- FUNÇÃO GENÉRICA PARA CRIAR GRÁFICOS (AJUSTADA) ---
    function createChart(canvasId, dataArray, labelProperty, chartType, datasetLabel, anchorPosition = 'end') {
        const labels = dataArray.map(item => item[labelProperty]);
        const total = dataArray.reduce((sum, item) => sum + item.total, 0);
        const percentuais = dataArray.map(item => ((item.total / total) * 100).toFixed(1));

        new Chart(document.getElementById(canvasId).getContext('2d'), {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    // Adiciona o rótulo do conjunto de dados para a legenda
                    label: datasetLabel,
                    data: percentuais,
                    backgroundColor: getColors(labels.length),
                }]
            },
            options: {
                responsive: true,              // se adapta ao container
                maintainAspectRatio: false,    // permite ocupar a altura do card
                legend: {
                    position: 'top', // posição da legenda "top","left","bottom","right"
                    align: 'center' // legenda no topo alinhada à direita
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const index = tooltipItem.index;
                            const percentual = data.datasets[0].data[index];
                            const valorOriginal = dataArray[index].total;
                            const label = dataArray[index][labelProperty];
                            return `${label}: ${percentual}% (${valorOriginal})`;
                        }
                    }
                },
                // plugins: {
                //     datalabels: {
                //         formatter: (value, ctx) => `${value}%`,
                //         color: '#813232',
                //         font: {
                //             weight: 'bold',
                //             size: 15
                //         },
                //         // Posição do titulo dos gráficos
                //         anchor: anchorPosition,  // align: "start", "center", "end", "left", "right", "top", "bottom".
                //     }
                // }
                plugins: {
                    datalabels: {
                        formatter: function(value, ctx) {
                            const index = ctx.dataIndex;
                            const item = dataArray[index];
                            const percentual = Number(value).toFixed(1).replace('.', ',');
                            const total = Number(item.total).toLocaleString('pt-BR');

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
                        clamp: true
                    }
                }
            }
        });
    }