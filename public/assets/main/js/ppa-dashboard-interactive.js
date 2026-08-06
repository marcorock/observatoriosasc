(function () {
    const initialState = window.ppaInitialInteractiveState || null;

    if (!initialState || !initialState.endpoint || !initialState.payload) {
        return;
    }

    const state = {
        endpoint: initialState.endpoint,
        filters: {
            cras: initialState.filters?.cras || null,
            mes_referencia: initialState.filters?.mes_referencia || null,
        },
        payload: initialState.payload,
        ui: initialState.ui || {},
        loading: false,
    };

    const ids = {
        baseValue: 'ppaCardBaseFamiliasValue',
        baseFoot: 'ppaCardBaseFamiliasFoot',
        metaValue: 'ppaCardMetaValue',
        metaPpaValue: 'ppaCardMetaPpaValue',
        metaPactoValue: 'ppaCardMetaPactoValue',
        acompanhadasValue: 'ppaCardAcompanhadasValue',
        acompanhadasFoot: 'ppaCardAcompanhadasFoot',
        percentualValue: 'ppaCardPercentualValue',
        percentualPpaValue: 'ppaCardPercentualPpaValue',
        percentualPactoValue: 'ppaCardPercentualPactoValue',
        periodoValue: 'ppaCardPeriodoValue',
        activeFilters: 'ppaActiveFilters',
        clearFiltersBtn: 'ppaClearFiltersBtn',
        status: 'ppaInteractiveStatus',
        mensalCountBadge: 'ppaMensalCountBadge',
        crasCountBadge: 'ppaCrasCountBadge',
        mensalMount: 'ppaMensalTableMount',
        crasMount: 'ppaCrasTableMount',
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

    const formatNumber = function (value, decimals = 0) {
        return Number(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    };

    const formatPercent = function (value) {
        return `${formatNumber(value, 2)}%`;
    };

    const formatMonth = function (value) {
        if (!value) {
            return '';
        }

        const parts = String(value).split('-');

        if (parts.length !== 3) {
            return value;
        }

        return `${parts[1]}/${parts[0]}`;
    };

    const getJson = function (url) {
        return fetch(url, {
            headers: {
                'X-Requested-With': 'fetch',
            },
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    const message = data?.error || 'Nao foi possivel atualizar o dashboard.';
                    throw new Error(message);
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

        if (state.filters.mes_referencia) {
            url.searchParams.set('mes_referencia', state.filters.mes_referencia);
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

        if (state.filters.mes_referencia) {
            fragments.push(`<span class="badge rounded-pill text-bg-secondary px-3 py-2 ppa-filter-pill">Mês <span>${escapeHtml(formatMonth(state.filters.mes_referencia))}</span></span>`);
        }

        if (!fragments.length) {
            fragments.push('<span class="text-secondary small fw-semibold">Nenhum filtro cruzado aplicado.</span>');
        }

        container.innerHTML = fragments.join('');

        if (clearButton) {
            clearButton.disabled = !state.filters.cras && !state.filters.mes_referencia;
        }
    };

    const renderCards = function (payload, message) {
        const dados = payload?.dados || {};

        const baseValue = document.getElementById(ids.baseValue);
        const baseFoot = document.getElementById(ids.baseFoot);
        const metaValue = document.getElementById(ids.metaValue);
        const metaPpaValue = document.getElementById(ids.metaPpaValue);
        const metaPactoValue = document.getElementById(ids.metaPactoValue);
        const acompanhadasValue = document.getElementById(ids.acompanhadasValue);
        const acompanhadasFoot = document.getElementById(ids.acompanhadasFoot);
        const percentualValue = document.getElementById(ids.percentualValue);
        const percentualPpaValue = document.getElementById(ids.percentualPpaValue);
        const percentualPactoValue = document.getElementById(ids.percentualPactoValue);
        const periodoValue = document.getElementById(ids.periodoValue);
        const percentualOk = Number(dados.percentual_alcancado_total || 0) >= Number(dados.percentual_periodo || 0);

        if (baseValue) {
            baseValue.textContent = formatNumber(dados.total_geral || 0);
        }

        if (baseFoot) {
            baseFoot.textContent = message || state.ui.base_foot || 'Dados consolidados a partir do vínculo ativo configurado no módulo PPA.';
        }

        if (metaValue) {
            metaValue.textContent = formatNumber(dados.meta_familias || 0);
        }

        if (metaPpaValue) {
            metaPpaValue.textContent = formatNumber(dados.meta_ppa_familias || 0);
        }

        if (metaPactoValue) {
            metaPactoValue.textContent = formatNumber(dados.meta_pacto_familias || 0);
        }

        if (acompanhadasValue) {
            acompanhadasValue.textContent = formatNumber(dados.familias_acompanhadas_total || 0);
        }

        if (acompanhadasFoot) {
            acompanhadasFoot.textContent = state.filters.mes_referencia
                ? 'Total de novos acompanhamentos no mês filtrado.'
                : (state.ui.acompanhadas_foot || 'Total acumulado de novos acompanhamentos no RMA.');
        }

        if (percentualValue) {
            percentualValue.textContent = formatPercent(dados.percentual_alcancado_total || 0);
            percentualValue.classList.toggle('text-success', percentualOk);
            percentualValue.classList.toggle('text-danger', !percentualOk && !!periodoValue);
            percentualValue.classList.toggle('text-info', !periodoValue);
        }

        const updateDualProgress = function (node, value) {
            if (!node) {
                return;
            }

            const isOnTrack = Number(value || 0) >= Number(dados.percentual_periodo || 0);
            node.textContent = formatPercent(value || 0);
            const container = node.parentElement;
            container?.classList.toggle('text-success', isOnTrack);
            container?.classList.toggle('text-danger', !isOnTrack);
        };

        updateDualProgress(percentualPpaValue, dados.percentual_alcancado_ppa);
        updateDualProgress(percentualPactoValue, dados.percentual_alcancado_pacto);

        if (periodoValue) {
            periodoValue.textContent = formatPercent(dados.percentual_periodo || 0);
            const periodComparison = percentualPactoValue
                ? Number(dados.percentual_alcancado_pacto || 0) >= Number(dados.percentual_periodo || 0)
                : percentualOk;
            periodoValue.classList.toggle('text-success', periodComparison);
            periodoValue.classList.toggle('text-primary', !periodComparison);
        }
    };

    const renderCharts = function (payload) {
        const dados = payload?.dados || {};

        createChart('ppaGraficoMensal', dados.grafico_mensal || [], 'mes', 'bar', 'total', { showLegend: false });
        createChart('ppaGraficoCrasAcompanhamento', dados.grafico_cras || [], 'cras', 'bar', 'total', { showLegend: false });

        bindChartInteractions(payload);
    };

    const chunk = function (items, size) {
        const pages = [];

        for (let index = 0; index < items.length; index += size) {
            pages.push(items.slice(index, index + size));
        }

        return pages;
    };

    const createMonthlyTableHtml = function (rows) {
        if (!rows.length) {
            return '<div class="text-center py-5 text-secondary"><h4>Nenhum dado mensal encontrado.</h4></div>';
        }

        const pages = chunk(rows, 8);

        return `<div id="ppaTabelaMensalCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-progress-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th scope="col">Mês</th>
                                        <th scope="col" class="text-end">Acompanhadas</th>
                                        <th scope="col" class="text-end">Acumulado</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.mensal_percent_label || '% Mês')}</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.mensal_accum_label || '% Acum.')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-axis">${escapeHtml(row.mes_label)}</td>
                                            <td class="text-end">${formatNumber(row.familias_acompanhadas)}</td>
                                            <td class="text-end">${formatNumber(row.acumulado)}</td>
                                            <td class="text-end">${formatPercent(row.percentual_mes)}</td>
                                            <td class="text-end">${formatPercent(row.percentual_acumulado)}</td>
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

    const createCrasTableHtml = function (rows) {
        if (!rows.length) {
            return '<div class="text-center py-5 text-secondary"><h4>Nenhum dado territorial encontrado.</h4></div>';
        }

        const pages = chunk(rows, 8);

        return `<div id="ppaTabelaCrasProgressCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-progress-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th scope="col">CRAS</th>
                                        <th scope="col" class="text-end">Base PBF</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.cras_meta_label || state.ui.meta_label || 'Meta')}</th>
                                        <th scope="col" class="text-end">Acomp.</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.cras_progress_label || '% Alc.')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-axis">${escapeHtml(row.cras)}</td>
                                            <td class="text-end">${formatNumber(row.base_familias_pbf)}</td>
                                            <td class="text-end">${formatNumber(row.meta_familias)}</td>
                                            <td class="text-end">${formatNumber(row.familias_acompanhadas)}</td>
                                            <td class="text-end">${formatPercent(row.percentual_alcancado)}</td>
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
        const mensalBadge = document.getElementById(ids.mensalCountBadge);
        const crasBadge = document.getElementById(ids.crasCountBadge);

        if (mensalBadge) {
            mensalBadge.textContent = `${(dados.tabela_mensal || []).length} meses`;
        }

        if (crasBadge) {
            crasBadge.textContent = `${(dados.tabela_cras || []).length} CRAS`;
        }

        mountCarousel(ids.mensalMount, createMonthlyTableHtml(dados.tabela_mensal || []), 'ppaTabelaMensalCarousel');
        mountCarousel(ids.crasMount, createCrasTableHtml(dados.tabela_cras || []), 'ppaTabelaCrasProgressCarousel');
    };

    const applyPayload = function (payload) {
        state.filters = {
            cras: payload?.filters?.cras || null,
            mes_referencia: payload?.filters?.mes_referencia || null,
        };
        state.payload = payload?.dados || {};

        renderActiveFilters();
        renderCards(payload, payload?.mensagem || '');
        renderCharts(payload);
        renderTables(payload);

        setStatus(
            state.filters.cras || state.filters.mes_referencia
                ? 'Filtro cruzado aplicado com sucesso.'
                : 'Clique em um CRAS ou mês para filtrar.',
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
        const mensalChart = window.myCharts?.ppaGraficoMensal || null;
        const crasChart = window.myCharts?.ppaGraficoCrasAcompanhamento || null;
        const mensalCanvas = document.getElementById('ppaGraficoMensal');
        const crasCanvas = document.getElementById('ppaGraficoCrasAcompanhamento');
        const mensalRows = payload?.dados?.grafico_mensal || [];

        if (mensalCanvas && mensalChart) {
            mensalCanvas.onclick = function (event) {
                const elements = mensalChart.getElementAtEvent(event);

                if (!elements || !elements.length) {
                    return;
                }

                const index = elements[0]._index;
                const item = mensalRows[index];

                if (!item) {
                    return;
                }

                toggleFilter('mes_referencia', item.mes_referencia || null);
            };
        }

        if (crasCanvas && crasChart) {
            crasCanvas.onclick = function (event) {
                const elements = crasChart.getElementAtEvent(event);

                if (!elements || !elements.length) {
                    return;
                }

                const index = elements[0]._index;
                const item = payload?.dados?.grafico_cras?.[index];

                if (!item) {
                    return;
                }

                toggleFilter('cras', item.cras || null);
            };
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const clearButton = document.getElementById(ids.clearFiltersBtn);

        renderActiveFilters();

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                state.filters.cras = null;
                state.filters.mes_referencia = null;
                refresh();
            });
        }

        bindChartInteractions({
            dados: initialState.payload,
        });
    });
})();
