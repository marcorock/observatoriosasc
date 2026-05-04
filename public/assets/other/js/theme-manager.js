/**
 * ==========================================================
 * THEME MANAGER
 * Controle global de tema claro/escuro do sistema
 * ==========================================================
 *
 * Objetivos:
 * 1. Alternar o tema visual da aplicação
 * 2. Salvar a preferência no navegador
 * 3. Atualizar o botão de troca de tema
 * 4. Notificar os gráficos já renderizados para redesenhar
 *
 * Importante:
 * - Este módulo é independente da função createChart()
 * - Ele apenas conversa com os gráficos já existentes
 * - Funciona melhor quando o sistema usa variáveis CSS
 */

(function () {
    'use strict';

    /**
     * Chaves e seletores usados pelo módulo
     */
    const STORAGE_KEY = 'dashboard_theme';
    const BUTTON_ID = 'themeToggleBtn';

    /**
     * Temas disponíveis
     */
    const THEMES = {
        DARK: 'dark',
        LIGHT: 'light'
    };

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getStoredTheme()
     * ----------------------------------------------------------
     * Lê o tema salvo no localStorage.
     * Se não existir nada salvo, retorna null.
     */
    function getStoredTheme() {
        return localStorage.getItem(STORAGE_KEY);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: saveTheme(theme)
     * ----------------------------------------------------------
     * Salva o tema escolhido no navegador.
     * Assim, ao recarregar a página, o usuário mantém o tema.
     */
    function saveTheme(theme) {
        localStorage.setItem(STORAGE_KEY, theme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getPreferredTheme()
     * ----------------------------------------------------------
     * Define qual tema deve ser usado ao carregar a página.
     *
     * Ordem de prioridade:
     * 1. Tema salvo pelo usuário
     * 2. Preferência do sistema operacional
     * 3. Escuro como padrão
     */
    function getPreferredTheme() {
        const storedTheme = getStoredTheme();

        if (storedTheme === THEMES.DARK || storedTheme === THEMES.LIGHT) {
            return storedTheme;
        }

        const prefersLight = window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: light)').matches;

        return prefersLight ? THEMES.LIGHT : THEMES.DARK;
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getThemeTokens(theme)
     * ----------------------------------------------------------
     * Retorna um conjunto de cores do sistema conforme o tema.
     *
     * Esses valores são aplicados em variáveis CSS no :root,
     * permitindo que o restante da interface responda ao tema.
     */
    function getThemeTokens(theme) {
        if (theme === THEMES.LIGHT) {
            return {
                '--app-bg': '#f8fafc',
                '--app-text': '#0f172a',
                '--card-bg': '#ffffff',
                '--card-border': '#cbd5e1',
                '--muted-text': '#475569',
                '--table-bg': '#ffffff',
                '--table-head-bg': '#e2e8f0',
                '--table-row-bg': '#f8fafc',
                '--table-row-alt-bg': '#eef2f7',
                '--table-border': 'rgba(15, 23, 42, 0.12)',
                '--table-text': '#0f172a',
                '--table-muted': '#475569',
                '--chart-axis-color': '#475569',
                '--chart-legend-color': '#334155',
                '--chart-grid-color': 'rgba(15, 23, 42, 0.08)',
                '--chart-label-color': '#0f172a'
            };
        }

        return {
            '--app-bg': '#0f172a',
            '--app-text': '#f8fafc',
            '--card-bg': '#1e293b',
            '--card-border': '#334155',
            '--muted-text': '#7f8aa3',
            '--table-bg': 'rgba(15, 23, 42, 0.45)',
            '--table-head-bg': 'rgba(30, 41, 59, 0.92)',
            '--table-row-bg': 'rgba(15, 23, 42, 0.52)',
            '--table-row-alt-bg': 'rgba(30, 41, 59, 0.48)',
            '--table-border': 'rgba(148, 163, 184, 0.18)',
            '--table-text': '#f8fafc',
            '--table-muted': '#94a3b8',
            '--chart-axis-color': '#7f8aa3',
            '--chart-legend-color': '#cfd8e3',
            '--chart-grid-color': 'rgba(255,255,255,0.04)',
            '--chart-label-color': '#ffffff'
        };
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: applyThemeTokens(theme)
     * ----------------------------------------------------------
     * Aplica as variáveis CSS do tema atual no documento.
     */
    function applyThemeTokens(theme) {
        const root = document.documentElement;
        const tokens = getThemeTokens(theme);

        Object.entries(tokens).forEach(([key, value]) => {
            root.style.setProperty(key, value);
        });
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: updateThemeButton(theme)
     * ----------------------------------------------------------
     * Atualiza o botão de alternância de tema.
     *
     * Responsabilidades:
     * 1. Atualizar o ícone (lua/sol)
     * 2. Atualizar o texto (Modo Escuro / Modo Claro)
     * 3. Atualizar atributos de acessibilidade
     * 4. Atualizar estilo visual do botão
     *
     * Importante:
     * - Não manipula mais childNodes diretamente
     * - Evita duplicação de ícones (bug anterior)
     * - Trabalha apenas com elementos específicos:
     *   .theme-icon e .theme-label
     */
    function updateThemeButton(theme) {
        const button = document.getElementById(BUTTON_ID);

        /**
         * Se o botão não existir no DOM, encerra a execução
         */
        if (!button) return;

        /**
         * Verifica se o tema atual é escuro
         */
        const isDark = theme === THEMES.DARK;

        /**
         * Define o texto e o ícone conforme o tema
         */
        const label = isDark ? 'Modo Escuro' : 'Modo Claro';
        const icon = isDark ? '🌙' : '☀️';

        /**
         * Busca os elementos internos do botão
         */
        const iconElement = button.querySelector('.theme-icon');
        const labelElement = button.querySelector('.theme-label');

        /**
         * Atualiza atributo de acessibilidade (ARIA)
         * Isso melhora navegação com leitores de tela
         */
        button.setAttribute(
            'aria-label',
            `Alternar para ${isDark ? 'modo claro' : 'modo escuro'}`
        );

        /**
         * Armazena o estado atual do tema no botão
         * (útil para debug ou futuras extensões)
         */
        button.setAttribute('data-theme-current', theme);

        /**
         * Atualiza o ícone do botão
         */
        if (iconElement) {
            iconElement.textContent = icon;
        }

        /**
         * Atualiza o texto do botão
         */
        if (labelElement) {
            labelElement.textContent = label;
        }

        /**
         * Ajusta estilo do botão conforme o tema
         */
        button.classList.toggle('btn-outline-secondary', isDark);
        button.classList.toggle('btn-outline-dark', !isDark);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: setTheme(theme)
     * ----------------------------------------------------------
     * Aplica um tema completo ao sistema.
     *
     * O que ela faz:
     * 1. Marca o tema no HTML
     * 2. Aplica variáveis CSS
     * 3. Salva preferência
     * 4. Atualiza botão
     * 5. Dispara evento global
     * 6. Atualiza gráficos
     */
    function setTheme(theme) {
        const normalizedTheme = theme === THEMES.LIGHT ? THEMES.LIGHT : THEMES.DARK;

        document.documentElement.setAttribute('data-theme', normalizedTheme);
        document.body.setAttribute('data-theme', normalizedTheme);

        applyThemeTokens(normalizedTheme);
        saveTheme(normalizedTheme);
        updateThemeButton(normalizedTheme);

        /**
         * Evento global para qualquer outro módulo ouvir
         */
        document.dispatchEvent(new CustomEvent('theme:changed', {
            detail: { theme: normalizedTheme }
        }));

        /**
         * Atualiza os gráficos já criados
         */
        updateAllChartsTheme(normalizedTheme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: toggleTheme()
     * ----------------------------------------------------------
     * Alterna entre claro e escuro.
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
        const nextTheme = currentTheme === THEMES.DARK ? THEMES.LIGHT : THEMES.DARK;

        setTheme(nextTheme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getThemeChartOptions(theme)
     * ----------------------------------------------------------
     * Retorna as cores que devem ser aplicadas nos gráficos.
     *
     * Essa função não cria gráficos.
     * Ela apenas devolve as cores certas para atualizar os já existentes.
     */
    function getThemeChartOptions(theme) {
        if (theme === THEMES.LIGHT) {
            return {
                axisColor: '#475569',
                legendColor: '#334155',
                gridColor: 'rgba(15, 23, 42, 0.08)',
                dataLabelColor: '#0f172a'
            };
        }

        return {
            axisColor: '#7f8aa3',
            legendColor: '#cfd8e3',
            gridColor: 'rgba(255,255,255,0.04)',
            dataLabelColor: '#ffffff'
        };
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: updateAllChartsTheme(theme)
     * ----------------------------------------------------------
     * Atualiza todos os gráficos já renderizados em window.myCharts.
     *
     * Observação:
     * Isso depende de os gráficos terem sido registrados em
     * window.myCharts, como você já está fazendo.
     */
    function updateAllChartsTheme(theme) {
        if (!window.myCharts) return;

        const chartTheme = getThemeChartOptions(theme);

        Object.values(window.myCharts).forEach(chart => {
            if (!chart || !chart.options) return;

            /**
             * Atualiza legenda
             */
            if (chart.options.legend && chart.options.legend.labels) {
                chart.options.legend.labels.fontColor = chartTheme.legendColor;
            }

            /**
             * Atualiza plugin datalabels
             */
            if (
                chart.options.plugins &&
                chart.options.plugins.datalabels
            ) {
                chart.options.plugins.datalabels.color = chartTheme.dataLabelColor;
            }

            /**
             * Atualiza eixos e grades
             */
            if (chart.options.scales) {
                if (Array.isArray(chart.options.scales.xAxes)) {
                    chart.options.scales.xAxes.forEach(axis => {
                        if (axis.ticks) {
                            axis.ticks.fontColor = chartTheme.axisColor;
                        }
                        if (axis.gridLines) {
                            axis.gridLines.color = chartTheme.gridColor;
                        }
                    });
                }

                if (Array.isArray(chart.options.scales.yAxes)) {
                    chart.options.scales.yAxes.forEach(axis => {
                        if (axis.ticks) {
                            axis.ticks.fontColor = chartTheme.axisColor;
                        }
                        if (axis.gridLines && axis.gridLines.display !== false) {
                            axis.gridLines.color = chartTheme.gridColor;
                        }
                    });
                }
            }

            /**
             * Redesenha o gráfico sem animação pesada
             */
            chart.update(0);
        });
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: bindThemeToggleButton()
     * ----------------------------------------------------------
     * Conecta o botão do header ao toggle de tema.
     */
    function bindThemeToggleButton() {
        const button = document.getElementById(BUTTON_ID);

        if (!button) return;

        button.addEventListener('click', toggleTheme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: initThemeManager()
     * ----------------------------------------------------------
     * Inicializa o módulo na carga da página.
     */
    function initThemeManager() {
        const initialTheme = getPreferredTheme();

        setTheme(initialTheme);
        bindThemeToggleButton();
    }

    /**
     * Inicialização automática após o DOM carregar
     */
    document.addEventListener('DOMContentLoaded', initThemeManager);

    /**
     * Exposição opcional no escopo global
     * Útil se você quiser trocar o tema manualmente em outro lugar.
     */
    window.ThemeManager = {
        setTheme,
        toggleTheme,
        getPreferredTheme
    };
})();
