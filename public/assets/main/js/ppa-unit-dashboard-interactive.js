(function () {
    const initialState = window.ppaUnitInitialInteractiveState || null;

    if (!initialState || !initialState.endpoint || !initialState.payload) {
        return;
    }

    const state = {
        endpoint: initialState.endpoint,
        filters: {
            unidade: initialState.filters?.unidade || null,
            mes_referencia: initialState.filters?.mes_referencia || null,
        },
        payload: initialState.payload,
        ui: initialState.ui || {},
        loading: false,
    };

    const ids = {
        totalValue: 'ppaUnitCardTotalValue',
        totalFoot: 'ppaUnitCardTotalFoot',
        metaValue: 'ppaUnitCardMetaValue',
        acompanhadasValue: 'ppaUnitCardAcompanhadasValue',
        acompanhadasFoot: 'ppaUnitCardAcompanhadasFoot',
        technicalValue: 'ppaUnitCardTechnicalValue',
        middleLevelValue: 'ppaUnitCardMiddleLevelValue',
        percentualValue: 'ppaUnitCardPercentualValue',
        periodoValue: 'ppaUnitCardPeriodoValue',
        activeFilters: 'ppaUnitActiveFilters',
        clearFiltersBtn: 'ppaUnitClearFiltersBtn',
        status: 'ppaUnitInteractiveStatus',
        mensalCountBadge: 'ppaUnitMensalCountBadge',
        unitCountBadge: 'ppaUnitCountBadge',
        mensalMount: 'ppaUnitMensalTableMount',
        unitMount: 'ppaUnitTableMount',
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

        if (state.filters.unidade) {
            url.searchParams.set('unidade', state.filters.unidade);
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

        if (state.filters.unidade) {
            fragments.push(`<span class="badge rounded-pill text-bg-secondary px-3 py-2 ppa-filter-pill">${escapeHtml(state.ui.filter_unit_label || 'Unidade')} <span>${escapeHtml(state.filters.unidade)}</span></span>`);
        }

        if (state.filters.mes_referencia) {
            fragments.push(`<span class="badge rounded-pill text-bg-secondary px-3 py-2 ppa-filter-pill">Mês <span>${escapeHtml(formatMonth(state.filters.mes_referencia))}</span></span>`);
        }

        if (!fragments.length) {
            fragments.push('<span class="text-secondary small fw-semibold">Nenhum filtro cruzado aplicado.</span>');
        }

        container.innerHTML = fragments.join('');

        if (clearButton) {
            clearButton.disabled = !state.filters.unidade && !state.filters.mes_referencia;
        }
    };

    const renderCards = function (payload) {
        const dados = payload?.dados || {};

        const totalValue = document.getElementById(ids.totalValue);
        const totalFoot = document.getElementById(ids.totalFoot);
        const metaValue = document.getElementById(ids.metaValue);
        const acompanhadasValue = document.getElementById(ids.acompanhadasValue);
        const acompanhadasFoot = document.getElementById(ids.acompanhadasFoot);
        const technicalValue = document.getElementById(ids.technicalValue);
        const middleLevelValue = document.getElementById(ids.middleLevelValue);
        const percentualValue = document.getElementById(ids.percentualValue);
        const periodoValue = document.getElementById(ids.periodoValue);
        const percentualOk = Number(dados.percentual_alcancado_total || 0) >= Number(dados.percentual_periodo || 0);

        if (totalValue) {
            totalValue.textContent = formatNumber(dados.total_unidades || 0);
        }

        if (totalFoot) {
            totalFoot.textContent = state.ui.total_foot || 'Unidades do indicador com leitura no periodo.';
        }

        if (metaValue) {
            metaValue.textContent = formatNumber(dados.meta_anual || 0);
        }

        if (acompanhadasValue) {
            acompanhadasValue.textContent = formatNumber(dados.total_inseridos || 0);
        }

        if (acompanhadasFoot) {
            acompanhadasFoot.textContent = state.filters.mes_referencia
                ? 'Total de registros no mês filtrado.'
                : (state.ui.acompanhadas_foot || 'Total acumulado do indicador no periodo.');
        }

        if (technicalValue) {
            technicalValue.textContent = formatNumber(dados.total_atendimentos_tecnicos || 0);
        }

        if (middleLevelValue) {
            middleLevelValue.textContent = formatNumber(dados.total_atendimentos_nivel_medio || 0);
        }

        if (percentualValue) {
            percentualValue.textContent = formatPercent(dados.percentual_alcancado_total || 0);
            percentualValue.classList.toggle('text-success', percentualOk);
            percentualValue.classList.toggle('text-danger', !percentualOk && !!periodoValue);
            percentualValue.classList.toggle('text-info', !periodoValue);
        }

        if (periodoValue) {
            periodoValue.textContent = formatPercent(dados.percentual_periodo || 0);
            periodoValue.classList.toggle('text-success', percentualOk);
            periodoValue.classList.toggle('text-primary', !percentualOk);
        }
    };

    const renderCharts = function (payload) {
        const dados = payload?.dados || {};

        createChart('ppaUnitGraficoMensal', dados.grafico_mensal || [], 'mes', 'bar', 'total', { showLegend: false });
        createChart('ppaUnitGraficoUnidades', dados.grafico_unidades || [], 'unidade', 'bar', 'total', { showLegend: false });

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

        return `<div id="ppaUnitTabelaMensalCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-progress-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th scope="col">Mês</th>
                                        <th scope="col" class="text-end">Inseridos</th>
                                        <th scope="col" class="text-end">Acumulado</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.mensal_percent_label || '% Mês')}</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.mensal_accum_label || '% Acum.')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-axis">${escapeHtml(row.mes_label)}</td>
                                            <td class="text-end">${formatNumber(row.total_inseridos)}</td>
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

    const createUnitTableHtml = function (rows) {
        if (!rows.length) {
            return '<div class="text-center py-5 text-secondary"><h4>Nenhum dado territorial encontrado.</h4></div>';
        }

        const pages = chunk(rows, 8);
        const unitLabel = escapeHtml(state.ui.filter_unit_label || 'Unidade');

        return `<div id="ppaUnitTabelaCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="10000">
            <div class="carousel-inner">
                ${pages.map(function (page, pageIndex) {
                    return `<div class="carousel-item ${pageIndex === 0 ? 'active' : ''}">
                        <div class="table-responsive data-table-shell ppa-progress-table-shell">
                            <table class="table data-table tv-table-large align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th scope="col">${unitLabel}</th>
                                        <th scope="col" class="text-end">Inseridos</th>
                                        <th scope="col" class="text-end">Part. %</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.unit_meta_label || state.ui.meta_label || 'Meta')}</th>
                                        <th scope="col" class="text-end">${escapeHtml(state.ui.unit_progress_label || '% Meta')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${page.map(function (row) {
                                        return `<tr>
                                            <td class="data-table-axis">${escapeHtml(row.unidade)}</td>
                                            <td class="text-end">${formatNumber(row.total_inseridos)}</td>
                                            <td class="text-end">${formatPercent(row.participacao)}</td>
                                            <td class="text-end">${formatNumber(row.meta_anual)}</td>
                                            <td class="text-end">${formatPercent(row.percentual_meta)}</td>
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
        const unitBadge = document.getElementById(ids.unitCountBadge);

        if (mensalBadge) {
            mensalBadge.textContent = `${(dados.tabela_mensal || []).length} meses`;
        }

        if (unitBadge) {
            unitBadge.textContent = `${(dados.tabela_unidades || []).length} ${state.ui.filter_unit_label || 'Unidades'}`;
        }

        mountCarousel(ids.mensalMount, createMonthlyTableHtml(dados.tabela_mensal || []), 'ppaUnitTabelaMensalCarousel');
        mountCarousel(ids.unitMount, createUnitTableHtml(dados.tabela_unidades || []), 'ppaUnitTabelaCarousel');
    };

    const applyPayload = function (payload) {
        state.filters = {
            unidade: payload?.filters?.unidade || null,
            mes_referencia: payload?.filters?.mes_referencia || null,
        };
        state.payload = payload?.dados || {};

        renderActiveFilters();
        renderCards(payload);
        renderCharts(payload);
        renderTables(payload);

        setStatus(
            state.filters.unidade || state.filters.mes_referencia
                ? 'Filtro cruzado aplicado com sucesso.'
                : (state.ui.filter_hint || 'Clique em uma unidade ou mês para filtrar.'),
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
        const mensalChart = window.myCharts?.ppaUnitGraficoMensal || null;
        const unitChart = window.myCharts?.ppaUnitGraficoUnidades || null;
        const mensalCanvas = document.getElementById('ppaUnitGraficoMensal');
        const unitCanvas = document.getElementById('ppaUnitGraficoUnidades');
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

        if (unitCanvas && unitChart) {
            unitCanvas.onclick = function (event) {
                const elements = unitChart.getElementAtEvent(event);

                if (!elements || !elements.length) {
                    return;
                }

                const index = elements[0]._index;
                const item = payload?.dados?.grafico_unidades?.[index];

                if (!item) {
                    return;
                }

                toggleFilter('unidade', item.unidade || null);
            };
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const clearButton = document.getElementById(ids.clearFiltersBtn);

        renderActiveFilters();

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                state.filters.unidade = null;
                state.filters.mes_referencia = null;
                refresh();
            });
        }

        bindChartInteractions({
            dados: initialState.payload,
        });
    });
})();
