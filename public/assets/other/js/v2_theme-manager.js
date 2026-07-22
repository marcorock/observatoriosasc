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
 * 4. Atualizar os gráficos já renderizados
 * 5. Aplicar animação suave na transição de tema
 *
 * Importante:
 * - Este módulo é independente da função createChart()
 * - Ele apenas conversa com os gráficos já existentes
 * - Funciona melhor quando o sistema usa variáveis CSS
 */

(function () {
    'use strict';

    /**
     * ----------------------------------------------------------
     * CONSTANTES DO MÓDULO
     * ----------------------------------------------------------
     */
    const STORAGE_KEY = 'dashboard_theme';
    const BUTTON_ID = 'themeToggleBtn';

    /**
     * Temas disponíveis no sistema
     */
    const THEMES = {
        DARK: 'dark',
        LIGHT: 'light'
    };

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getStoredTheme()
     * ----------------------------------------------------------
     * Lê do localStorage o tema salvo pelo usuário.
     *
     * Retorno:
     * - 'dark'
     * - 'light'
     * - null (caso ainda não exista valor salvo)
     */
    function getStoredTheme() {
        return localStorage.getItem(STORAGE_KEY);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: saveTheme(theme)
     * ----------------------------------------------------------
     * Salva o tema atual no navegador.
     *
     * Isso permite manter a escolha do usuário
     * mesmo após atualizar ou reabrir a página.
     */
    function saveTheme(theme) {
        localStorage.setItem(STORAGE_KEY, theme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getPreferredTheme()
     * ----------------------------------------------------------
     * Define qual tema deve ser usado na inicialização.
     *
     * Ordem de prioridade:
     * 1. Tema salvo manualmente no navegador
     * 2. Preferência do sistema operacional
     * 3. Tema escuro como fallback
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
     * Retorna as variáveis visuais do sistema conforme o tema.
     *
     * Essas variáveis serão aplicadas no :root
     * para atualizar o restante da interface.
     */
    function getThemeTokens(theme) {
        if (theme === THEMES.LIGHT) {
            return {
                '--app-bg': '#f8fafc',
                '--app-text': '#0f172a',
                '--card-bg': '#ffffff',
                '--card-border': '#cbd5e1',
                '--muted-text': '#475569',
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
     * Aplica no documento as variáveis CSS do tema atual.
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
     * FUNÇÃO: enableThemeTransition()
     * ----------------------------------------------------------
     * Ativa temporariamente uma classe no <html> para
     * permitir transição suave entre os temas.
     *
     * A classe é removida logo depois para não interferir
     * em outras interações normais da interface.
     */
    function enableThemeTransition() {
        const root = document.documentElement;

        root.classList.add('theme-transitioning');

        window.setTimeout(() => {
            root.classList.remove('theme-transitioning');
        }, 300);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: animateThemeButtonIcon()
     * ----------------------------------------------------------
     * Aplica uma pequena animação no ícone do botão
     * para dar feedback visual ao usuário.
     */
    function animateThemeButtonIcon() {
        const button = document.getElementById(BUTTON_ID);

        if (!button) return;

        const iconElement = button.querySelector('.theme-icon');

        if (!iconElement) return;

        /**
         * Remove a classe antes para permitir reinício da animação
         * em cliques consecutivos.
         */
        iconElement.classList.remove('is-animating');

        /**
         * Força o navegador a recalcular layout,
         * reiniciando corretamente a animação CSS.
         */
        void iconElement.offsetWidth;

        iconElement.classList.add('is-animating');

        window.setTimeout(() => {
            iconElement.classList.remove('is-animating');
        }, 260);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: updateThemeButton(theme)
     * ----------------------------------------------------------
     * Atualiza o botão de alternância de tema.
     *
     * Responsabilidades:
     * 1. Atualizar o ícone
     * 2. Atualizar o texto
     * 3. Atualizar atributos de acessibilidade
     * 4. Ajustar classes visuais do botão
     *
     * Importante:
     * - Trabalha apenas com .theme-icon e .theme-label
     * - Evita duplicação de ícones
     */
    function updateThemeButton(theme) {
        const button = document.getElementById(BUTTON_ID);

        if (!button) return;

        const isDark = theme === THEMES.DARK;
        const label = isDark ? 'Modo Escuro' : 'Modo Claro';
        const icon = isDark ? '🌙' : '☀️';

        const iconElement = button.querySelector('.theme-icon');
        const labelElement = button.querySelector('.theme-label');

        /**
         * Atualiza acessibilidade do botão
         */
        button.setAttribute(
            'aria-label',
            `Alternar para ${isDark ? 'modo claro' : 'modo escuro'}`
        );

        /**
         * Guarda o tema atual no próprio botão
         * para possível uso futuro ou debug
         */
        button.setAttribute('data-theme-current', theme);

        /**
         * Atualiza o ícone visual
         */
        if (iconElement) {
            iconElement.textContent = icon;
        }

        /**
         * Atualiza o texto visual
         */
        if (labelElement) {
            labelElement.textContent = label;
        }

        /**
         * Ajusta classes do botão conforme o tema
         */
        button.classList.toggle('btn-outline-secondary', isDark);
        button.classList.toggle('btn-outline-dark', !isDark);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: getThemeChartOptions(theme)
     * ----------------------------------------------------------
     * Retorna as cores visuais que os gráficos devem usar
     * conforme o tema atual.
     *
     * Esta função não renderiza gráficos.
     * Ela apenas fornece as cores para atualização.
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
     * Atualiza todos os gráficos registrados em window.myCharts.
     *
     * O que é atualizado:
     * - cor da legenda
     * - cor dos datalabels
     * - cor dos eixos
     * - cor das grades
     */
    function updateAllChartsTheme(theme) {
        if (!window.myCharts) return;

        const chartTheme = getThemeChartOptions(theme);

        Object.values(window.myCharts).forEach(chart => {
            if (!chart || !chart.options) return;

            /**
             * Atualiza a cor das legendas
             */
            if (chart.options.legend && chart.options.legend.labels) {
                chart.options.legend.labels.fontColor = chartTheme.legendColor;
            }

            /**
             * Atualiza a cor dos datalabels
             */
            if (
                chart.options.plugins &&
                chart.options.plugins.datalabels
            ) {
                chart.options.plugins.datalabels.color = chartTheme.dataLabelColor;
            }

            /**
             * Atualiza eixos e linhas de grade
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
             * Atualiza o gráfico sem forçar animação pesada
             */
            chart.update(0);
        });
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: setTheme(theme)
     * ----------------------------------------------------------
     * Aplica um tema completo ao sistema.
     *
     * Etapas:
     * 1. Ativa transição suave
     * 2. Marca o tema no HTML/body
     * 3. Aplica variáveis CSS
     * 4. Salva preferência
     * 5. Atualiza o botão
     * 6. Dispara evento global
     * 7. Atualiza os gráficos
     */
    function setTheme(theme) {
        const normalizedTheme = theme === THEMES.LIGHT ? THEMES.LIGHT : THEMES.DARK;

        enableThemeTransition();

        document.documentElement.setAttribute('data-theme', normalizedTheme);
        document.body.setAttribute('data-theme', normalizedTheme);

        applyThemeTokens(normalizedTheme);
        saveTheme(normalizedTheme);
        updateThemeButton(normalizedTheme);

        /**
         * Evento global para outros módulos ouvirem
         */
        document.dispatchEvent(new CustomEvent('theme:changed', {
            detail: { theme: normalizedTheme }
        }));

        updateAllChartsTheme(normalizedTheme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: toggleTheme()
     * ----------------------------------------------------------
     * Alterna entre tema escuro e claro.
     * Também anima o ícone do botão.
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
        const nextTheme = currentTheme === THEMES.DARK ? THEMES.LIGHT : THEMES.DARK;

        animateThemeButtonIcon();
        setTheme(nextTheme);
    }

    /**
     * ----------------------------------------------------------
     * FUNÇÃO: bindThemeToggleButton()
     * ----------------------------------------------------------
     * Vincula o clique do botão ao toggle de tema.
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
     * Inicializa o gerenciador de tema ao carregar a página.
     */
    function initThemeManager() {
        const initialTheme = getPreferredTheme();

        setTheme(initialTheme);
        bindThemeToggleButton();
    }

    /**
     * Inicialização automática após o DOM estar pronto
     */
    document.addEventListener('DOMContentLoaded', initThemeManager);

    /**
     * Exposição opcional no escopo global
     * Permite trocar o tema manualmente em outros pontos do sistema
     */
    window.ThemeManager = {
        setTheme,
        toggleTheme,
        getPreferredTheme
    };
})();