 (function () {
            // Guarda o intervalo automático da tabela para conseguir reiniciar sem duplicar timers.
            let rotationTimer = null;

            // Escapa caracteres especiais antes de inserir dados no HTML, evitando quebra de layout e XSS.
            const escapeHtml = function (value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[char];
                });
            };

            // Converte datas no formato YYYY-MM-DD para DD/MM/YYYY. Se vier vazio, mostra "-".
            const formatDate = function (value) {
                if (!value) {
                    return '-';
                }

                const parts = String(value).split('-');
                return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
            };

            // Define a classe CSS usada para colorir o selo de status na tabela.
            const statusClass = function (status) {
                const classes = {
                    'Concluído': 'status-toast-success',
                    'Em andamento': 'status-toast-warning',
                    'Não iniciado': 'status-toast-danger',
                    'cancelado': 'status-toast-danger'
                };

                return classes[status] || '';
            };

            // Renderiza novamente as linhas da tabela quando os registros mudam.
            window.renderBscDashboardTable = function (registros) {
                const tbody = document.getElementById('bscDashboardTableBody');
                const subtitle = document.getElementById('bscTableSubtitle');

                // Se a tabela não existe nesta página, encerra sem causar erro.
                if (!tbody) {
                    return;
                }

                // Atualiza o subtítulo com a quantidade atual de registros acompanhados.
                if (subtitle) {
                    subtitle.textContent = `${registros.length} registros acompanhados`;
                }

                // Mostra uma linha informativa quando não há dados para exibir.
                if (!registros.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4">Nenhum registro encontrado.</td></tr>';
                    return;
                }

                // Monta o HTML de todas as linhas usando dados escapados e status formatado.
                tbody.innerHTML = registros.map(function (row) {
                    return `<tr class="bsc-data-row">
                        <td class="data-table-id">${escapeHtml(row.id)}</td>
                        <td class="data-table-strategy">${escapeHtml(row.estrategia)}</td>
                        <td><span class="status-toast ${statusClass(row.situacao)}">${escapeHtml(row.situacao)}</span></td>
                        <td class="data-table-date">${formatDate(row.data_referencia)}</td>
                    </tr>`;
                }).join('');
            };

            // Inicializa a paginação da tabela e a rotação automática das páginas.
            window.initBscTablePagination = function () {
                // Limpa uma rotação anterior antes de calcular a nova paginação.
                window.clearInterval(rotationTimer);

                const rows = Array.from(document.querySelectorAll('.bsc-data-row'));
                const card = document.querySelector('.data-table-card');
                const header = document.querySelector('.data-table-header');
                const tableHead = document.querySelector('.data-table thead');
                const pageInfo = document.getElementById('bscTablePageInfo');
                const progress = document.getElementById('bscTableProgress');
                const pagination = document.getElementById('bscTablePagination');
                const prevButton = document.getElementById('bscTablePrev');
                const nextButton = document.getElementById('bscTableNext');

                // Se faltar algum elemento essencial, esconde a paginação e evita erros de JS.
                if (!rows.length || !pageInfo || !progress || !pagination || !prevButton || !nextButton) {
                    if (pagination) {
                        pagination.style.display = 'none';
                    }
                    if (pageInfo) {
                        pageInfo.textContent = '0/0';
                    }
                    return;
                }

                // Calcula quantas linhas cabem na tabela considerando a altura disponível no card.
                const getRowsPerPage = function () {
                    if (card && header && tableHead && pagination) {
                        const cardStyle = window.getComputedStyle(card);
                        const cardPadding = parseFloat(cardStyle.paddingTop) + parseFloat(cardStyle.paddingBottom);
                        const availableHeight = card.clientHeight - cardPadding - header.offsetHeight - tableHead.offsetHeight - pagination.offsetHeight - 38;
                        const rowHeight = rows[0].offsetHeight || 72;
                        const maxRows = window.innerWidth >= 1200 ? 5 : 8;
                        const minRows = window.innerWidth >= 1200 ? 1 : 4;

                        return Math.max(minRows, Math.min(maxRows, Math.floor(availableHeight / rowHeight) || minRows));
                    }

                    return window.innerWidth < 992 ? 4 : (window.innerHeight >= 1000 ? 6 : 5);
                };

                // Estado atual da paginação: linhas por página, total de páginas e página ativa.
                let rowsPerPage = getRowsPerPage();
                let totalPages = Math.ceil(rows.length / rowsPerPage);
                let currentPage = 0;

                // Exibe apenas as linhas da página atual e atualiza contador/progresso.
                const renderPage = function () {
                    const start = currentPage * rowsPerPage;
                    const end = start + rowsPerPage;

                    rows.forEach(function (row, index) {
                        row.hidden = index < start || index >= end;
                    });

                    pageInfo.textContent = `${currentPage + 1}/${totalPages}`;
                    progress.style.width = `${((currentPage + 1) / totalPages) * 100}%`;
                    pagination.style.display = totalPages > 1 ? 'flex' : 'none';
                };

                // Avança ou volta a página, usando módulo para circular entre início e fim.
                const goToPage = function (direction) {
                    currentPage = (currentPage + direction + totalPages) % totalPages;
                    renderPage();
                };

                // Botão para voltar uma página.
                prevButton.onclick = function () {
                    goToPage(-1);
                };

                // Botão para avançar uma página.
                nextButton.onclick = function () {
                    goToPage(1);
                };

                // Em modo TV, muda de página automaticamente quando existe mais de uma página.
                if (totalPages > 1) {
                    rotationTimer = window.setInterval(function () {
                        goToPage(1);
                    }, 8500);
                }

                renderPage();
            };

            // Atualiza cards, filtros, gráficos e tabela com o payload recebido do backend.
            window.updateBscDashboard = function (payload) {
                const total = document.getElementById('dashboardTotalGeral');
                const status = document.getElementById('dashboardPeriodStatus');
                const periodPill = document.getElementById('dashboardPeriodPill');
                const inicio = document.getElementById('dashboardDataInicio');
                const fim = document.getElementById('dashboardDataFim');

                // Atualiza o total geral do dashboard.
                if (total) {
                    total.textContent = payload.total_geral ?? 0;
                }

                // Atualiza cada card de resumo de situação, procurando o status correspondente no payload.
                document.querySelectorAll('[data-summary-card="situacao"]').forEach(function (card) {
                    const statusName = card.getAttribute('data-status');
                    const match = (payload.situacao || []).find(function (item) {
                        return item.situacao === statusName;
                    });
                    const value = card.querySelector('.summary-value');

                    if (value) {
                        value.textContent = match ? match.total : 0;
                    }
                });

                // Mostra de onde vem o filtro ativo: local, global ou sem filtro.
                if (status && payload.periodo) {
                    const label = payload.periodo.tipo === 'local' ? 'local' : (payload.periodo.tipo === 'global' ? 'global' : 'sem filtro');
                    status.textContent = `Filtro ativo: ${label}`;
                }

                // Atualiza o texto do período exibido no badge/pílula de filtro.
                if (periodPill && payload.periodo) {
                    // Formata datas do filtro mantendo vazio quando não houver valor.
                    const formatPeriodDate = function (value) {
                        if (!value) {
                            return '';
                        }

                        const parts = String(value).split('-');
                        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
                    };

                    const dataInicio = formatPeriodDate(payload.periodo.data_inicio);
                    const dataFim = formatPeriodDate(payload.periodo.data_fim);
                    let text = '📅 Sem filtro';

                    if (dataInicio && dataFim) {
                        text = `📅 ${dataInicio} à ${dataFim}`;
                    } else if (dataInicio) {
                        text = `📅 A partir de ${dataInicio}`;
                    } else if (dataFim) {
                        text = `📅 Até ${dataFim}`;
                    }

                    periodPill.textContent = text;
                }

                // Sincroniza o campo de data inicial com o período recebido.
                if (inicio && payload.periodo) {
                    inicio.value = payload.periodo.data_inicio || '';
                }

                // Sincroniza o campo de data final com o período recebido.
                if (fim && payload.periodo) {
                    fim.value = payload.periodo.data_fim || '';
                }

                // Recria os gráficos com os novos dados filtrados.
                createChart('graficoEixo', payload.situacao || [], 'situacao', 'pie', 'total', pieOptions);
                createChart('graficoEixoBar', payload.eixo || [], 'eixo', 'bar', 'total', { showLegend: false });
                createChart('graficoEixoBarHorizon', payload.eixo || [], 'eixo', 'horizontalBar', 'total', {
                    showLegend: false,
                    datalabelAlign: 'right'
                });

                // Recria a tabela e reinicia a paginação para refletir os registros filtrados.
                window.renderBscDashboardTable(payload.registros || []);
                window.initBscTablePagination();
            };

            // Depois que o HTML carrega, liga a paginação e os eventos do formulário de filtro.
            document.addEventListener('DOMContentLoaded', function () {
                window.initBscTablePagination();

                const form = document.getElementById('dashboardPeriodForm');
                if (!form) {
                    return;
                }

                const filterToggle = document.getElementById('dashboardFilterToggle');
                const filterModal = document.getElementById('dashboardFilterModal');
                const filterClose = document.getElementById('dashboardFilterClose');

                // Abre ou fecha o modal de filtros e mantém atributos de acessibilidade atualizados.
                const setFilterModal = function (isVisible) {
                    if (!filterModal) {
                        return;
                    }

                    filterModal.classList.toggle('is-visible', isVisible);
                    filterModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                    filterToggle?.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
                };

                // Alterna o modal ao clicar no botão de filtro.
                filterToggle?.addEventListener('click', function () {
                    setFilterModal(!filterModal?.classList.contains('is-visible'));
                });

                // Fecha o modal pelo botão de fechar.
                filterClose?.addEventListener('click', function () {
                    setFilterModal(false);
                });

                // Fecha o modal ao clicar fora do conteúdo interno.
                filterModal?.addEventListener('click', function (event) {
                    if (event.target === filterModal) {
                        setFilterModal(false);
                    }
                });

                // Fecha o modal ao pressionar ESC.
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        setFilterModal(false);
                    }
                });

                // Envia o filtro por AJAX e atualiza o dashboard com o JSON retornado.
                const requestPeriod = function (url) {
                    const formData = new FormData(form);

                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'fetch'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(window.updateBscDashboard)
                        .catch(function () {
                            const status = document.getElementById('dashboardPeriodStatus');
                            if (status) {
                                status.textContent = 'Não foi possível atualizar o filtro.';
                            }
                        });
                };

                // Aplica o filtro local usando a URL configurada no atributo data-local-url do form.
                document.getElementById('dashboardApplyLocal')?.addEventListener('click', function () {
                    requestPeriod(form.dataset.localUrl);
                });

                // Limpa o filtro local usando a URL configurada no atributo data-clear-local-url.
                document.getElementById('dashboardClearLocal')?.addEventListener('click', function () {
                    requestPeriod(form.dataset.clearLocalUrl);
                });

                // Aplica o filtro global usando a URL configurada no atributo data-global-url.
                document.getElementById('dashboardApplyGlobal')?.addEventListener('click', function () {
                    requestPeriod(form.dataset.globalUrl);
                });

                // Quando qualquer data muda, aplica automaticamente o filtro local.
                form.querySelectorAll('input[type="date"]').forEach(function (input) {
                    input.addEventListener('change', function () {
                        requestPeriod(form.dataset.localUrl);
                    });
                });
            });
        })();