(function () {
    const initialState = window.ppaSingleQueryInteractiveState || null;

    if (!initialState || !initialState.endpoint || !initialState.payload) {
        return;
    }

    const state = {
        endpoint: initialState.endpoint,
        filters: {
            cras: initialState.filters?.cras || null,
            regiao: initialState.filters?.regiao || null,
        },
        payload: initialState.payload,
        loading: false,
    };

    const ids = {
        totalFamiliasValue: 'ppaCardTotalFamiliasValue',
        totalFamiliasFoot: 'ppaCardTotalFamiliasFoot',
        totalPessoasValue: 'ppaCardTotalPessoasValue',
        totalCrasValue: 'ppaCardTotalCrasValue',
        totalBairrosValue: 'ppaCardTotalBairrosValue',
        activeFilters: 'ppaActiveFilters',
        clearFiltersBtn: 'ppaClearFiltersBtn',
        status: 'ppaInteractiveStatus',
        crasCountBadge: 'ppaCrasCountBadge',
        bairroCountBadge: 'ppaBairroCountBadge',
        crasMount: 'ppaCrasTableMount',
        bairroMount: 'ppaBairroTableMount',
    };

    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char];
        });
    };

    const formatNumber = function (value) {
        return Number(value || 0).toLocaleString('pt-BR');
    };

    const getJson = function (url) {
        return fetch(url, {
            headers: {
                'X-Requested-With': 'fetch',
            },
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data?.error || 'Nao foi possivel atualizar o dashboard.');
                }

                return data;
            });
        });
    };

    const buildUrl = function () {
        const url = new URL(state.endpoint, window.location.origin);

        if (state.filters.cras) {
            url.searchParams.set('cras', state.filters.cras);
        }

        if (state.filters.regiao) {
            url.searchParams.set('regiao', state.filters.regiao);
        }

        return url.toString();
    };

    const setStatus = function (message, isError) {
        const node = document.getElementById(ids.status);

        if (!node) {
            return;
        }

        node.textContent = message;
        node.classList.toggle('text-danger', Boolean(isError));
        node.classList.toggle('text-secondary', !isError);
    };

    const renderActiveFilters = function () {
        const container = document.getElementById(ids.activeFilters);
        const clearButton = document.getElementById(ids.clearFiltersBtn);

        if (!container) {
            return;
        }

        const fragments = [];

        if (state.filters.cras) {
            fragments.push(`<span class="badge rounded-pill text-bg-secondary px-3 py-2 ppa-filter-pill">CRAS <span>${escapeHtml(state.filters.cras)}</span></span>`);
        }

        if (state.filters.regiao) {
            fragments.push(`<span class="badge rounded-pill text-bg-secondary px-3 py-2 ppa-filter-pill">Região <span>${escapeHtml(state.filters.regiao)}</span></span>`);
        }

        if (!fragments.length) {
            fragments.push('<span class="text-secondary small fw-semibold">Nenhum filtro cruzado aplicado.</span>');
        }

        container.innerHTML = fragments.join('');

        if (clearButton) {
            clearButton.disabled = !state.filters.cras && !state.filters.regiao;
        }
    };

    const renderCards = function (payload, message) {
        const dados = payload?.dados || {};

        const totalFamiliasValue = document.getElementById(ids.totalFamiliasValue);
        const totalFamiliasFoot = document.getElementById(ids.totalFamiliasFoot);
        const totalPessoasValue = document.getElementById(ids.totalPessoasValue);
        const totalCrasValue = document.getElementById(ids.totalCrasValue);
        const totalBairrosValue = document.getElementById(ids.totalBairrosValue);

        if (totalFamiliasValue) {
            totalFamiliasValue.textContent = formatNumber(dados.total_geral || 0);
        }

        if (totalFamiliasFoot) {
            totalFamiliasFoot.textContent = message || 'Dados consolidados a partir do vínculo ativo configurado no módulo PPA.';
        }

        if (totalPessoasValue) {
            totalPessoasValue.textContent = formatNumber(dados.total_pessoas || 0);
        }

        if (totalCrasValue) {
            totalCrasValue.textContent = formatNumber(dados.cras_total || 0);
        }

        if (totalBairrosValue) {
            totalBairrosValue.textContent = formatNumber(dados.bairro_total || 0);
        }
    };

    const renderCharts = function (payload) {
        const dados = payload?.dados || {};
        const isMobile = window.innerWidth <= 576;
        const pieOptions = isMobile
            ? { showLegend: false, datalabelOffset: 6 }
            : { showLegend: true, legendPosition: 'right', datalabelAnchor: 'center', datalabelAlign: 'center', datalabelOffset: 0, dataLabelFontSize: 15 };

        createChart('ppaGraficoCras', dados.familias_por_cras || [], 'cras', 'pie', 'total', pieOptions);
        createChart('ppaGraficoRegiao', dados.familias_por_regiao || [], 'regiao', 'bar', 'total', { showLegend: false });

        bindChartInteractions(payload);
    };

    const chunk = function (items, size) {
        const pages = [];

        for (let index = 0; index < items.length; index += size) {
            pages.push(items.slice(index, index + size));
        }

        return pages;
    };

    const createCrasTableHtml = function (rows) {
        if (!rows.length) {
            return '<div class="text-center py-5 text-secondary"><h4>Nenhum dado encontrado.</h4></div>';
        }

        const pages = chunk(rows, 8);

        return `<div id="ppaTabelaCrasCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100 ppa-table-cras">
                                <thead>
                                    <tr>
                                        <th scope="col">CRAS</th>
                                        <th scope="col">Regiao</th>
                                        <th scope="col" class="text-end">Familias</th>
                                        <th scope="col" class="text-end">Pessoas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-strategy">${escapeHtml(row.cras)}</td>
                                            <td class="data-table-axis">${escapeHtml(row.regiao)}</td>
                                            <td class="data-table-date text-end">${formatNumber(row.total_familias)}</td>
                                            <td class="data-table-date text-end">${formatNumber(row.total_pessoas)}</td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3 text-secondary fw-bold">
                            Página ${pageIndex + 1} de ${pages.length} (Mudando em 10s...)
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>`;
    };

    const createBairroTableHtml = function (rows) {
        if (!rows.length) {
            return '<div class="text-center py-5 text-secondary"><h4>Nenhum dado encontrado.</h4></div>';
        }

        const pages = chunk(rows, 8);

        return `<div id="ppaTabelaBairroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100 ppa-table-bairro">
                                <thead>
                                    <tr>
                                        <th scope="col">Bairro</th>
                                        <th scope="col">CRAS</th>
                                        <th scope="col">Regiao</th>
                                        <th scope="col" class="text-end">Familias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-strategy">${escapeHtml(row.bairro)}</td>
                                            <td class="data-table-axis">${escapeHtml(row.cras)}</td>
                                            <td class="data-table-axis">${escapeHtml(row.regiao)}</td>
                                            <td class="data-table-date text-end">${formatNumber(row.total_familias)}</td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3 text-secondary fw-bold">
                            Página ${pageIndex + 1} de ${pages.length} (Mudando em 10s...)
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>`;
    };

    const mountCarousel = function (mountId, html, carouselId) {
        const mount = document.getElementById(mountId);

        if (!mount) {
            return;
        }

        mount.innerHTML = html;

        const carouselNode = document.getElementById(carouselId);

        if (carouselNode && window.bootstrap?.Carousel) {
            new window.bootstrap.Carousel(carouselNode, {
                interval: 10000,
                ride: 'carousel',
            });
        }
    };

    const renderTables = function (payload) {
        const dados = payload?.dados || {};
        const crasBadge = document.getElementById(ids.crasCountBadge);
        const bairroBadge = document.getElementById(ids.bairroCountBadge);

        if (crasBadge) {
            crasBadge.textContent = `${(dados.tabela_cras || []).length} CRAS`;
        }

        if (bairroBadge) {
            bairroBadge.textContent = `${(dados.tabela_bairro || []).length} bairros`;
        }

        mountCarousel(ids.crasMount, createCrasTableHtml(dados.tabela_cras || []), 'ppaTabelaCrasCarousel');
        mountCarousel(ids.bairroMount, createBairroTableHtml(dados.tabela_bairro || []), 'ppaTabelaBairroCarousel');
    };

    const applyPayload = function (payload) {
        state.filters = {
            cras: payload?.filters?.cras || null,
            regiao: payload?.filters?.regiao || null,
        };
        state.payload = payload?.dados || {};

        renderActiveFilters();
        renderCards(payload, payload?.mensagem || '');
        renderCharts(payload);
        renderTables(payload);

        setStatus(
            state.filters.cras || state.filters.regiao
                ? 'Filtro cruzado aplicado com sucesso.'
                : 'Clique em um CRAS ou região para filtrar.',
            false
        );
    };

    const refresh = function () {
        if (state.loading) {
            return;
        }

        state.loading = true;
        setStatus('Atualizando dashboard...', false);

        getJson(buildUrl())
            .then(function (payload) {
                applyPayload(payload);
            })
            .catch(function (error) {
                setStatus(error.message || 'Nao foi possivel atualizar o dashboard.', true);
            })
            .finally(function () {
                state.loading = false;
            });
    };

    const toggleFilter = function (key, value) {
        const normalizedValue = value || null;
        state.filters[key] = state.filters[key] === normalizedValue ? null : normalizedValue;
        refresh();
    };

    const bindChartInteractions = function (payload) {
        const crasChart = window.myCharts?.ppaGraficoCras || null;
        const regiaoChart = window.myCharts?.ppaGraficoRegiao || null;
        const crasCanvas = document.getElementById('ppaGraficoCras');
        const regiaoCanvas = document.getElementById('ppaGraficoRegiao');

        if (crasCanvas && crasChart) {
            crasCanvas.onclick = function (event) {
                const elements = crasChart.getElementAtEvent(event);

                if (!elements || !elements.length) {
                    return;
                }

                const index = elements[0]._index;
                const item = payload?.dados?.familias_por_cras?.[index];

                if (!item) {
                    return;
                }

                toggleFilter('cras', item.cras || null);
            };
        }

        if (regiaoCanvas && regiaoChart) {
            regiaoCanvas.onclick = function (event) {
                const elements = regiaoChart.getElementAtEvent(event);

                if (!elements || !elements.length) {
                    return;
                }

                const index = elements[0]._index;
                const item = payload?.dados?.familias_por_regiao?.[index];

                if (!item) {
                    return;
                }

                toggleFilter('regiao', item.regiao || null);
            };
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const clearButton = document.getElementById(ids.clearFiltersBtn);

        renderActiveFilters();

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                state.filters.cras = null;
                state.filters.regiao = null;
                refresh();
            });
        }

        bindChartInteractions({
            dados: initialState.payload,
        });
    });
})();
