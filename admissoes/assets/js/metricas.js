(function () {
    var config = window.METRICAS_CONFIG || { periodo: 30 };

    var graficos = {
        pedidosDia: null,
        tipos: null,
        sla: null
    };

    function montarQuery() {
        var params = new URLSearchParams();
        params.set('periodo', config.periodo);

        var solicitante = document.getElementById('filtro_solicitante').value.trim();
        var nomeCandidato = document.getElementById('filtro_nome_candidato').value.trim();
        var status = document.getElementById('filtro_status').value;
        var tipo = document.getElementById('filtro_tipo').value;

        if (solicitante !== '') {
            params.set('solicitante', solicitante);
        }

        if (nomeCandidato !== '') {
            params.set('nome_candidato', nomeCandidato);
        }

        if (status !== '') {
            params.set('status', status);
        }

        if (tipo !== '') {
            params.set('tipo', tipo);
        }

        return params.toString();
    }

    function carregarResumo() {
        fetch('ajax/metricas_resumo.php?' + montarQuery())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                document.getElementById('card_total_periodo').textContent = data.cards.total_periodo;
                document.getElementById('card_aberto').textContent = data.cards.aberto;
                document.getElementById('card_andamento').textContent = data.cards.andamento;
                document.getElementById('card_resolvido').textContent = data.cards.resolvido;
                document.getElementById('tempo_medio_conclusao').textContent = data.tempo_medio_horas + ' h';

                renderizarTabelaSetores(data.setores);
            })
            .catch(function (error) {
                console.error('Erro ao carregar resumo:', error);
            });
    }

    function carregarGraficos() {
        fetch('ajax/metricas_graficos.php?' + montarQuery())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                renderizarGraficoPedidosDia(data.por_dia);
                renderizarGraficoTipos(data.por_tipo);
                renderizarGraficoSla(data.sla);
                preencherSla(data.sla);

                var tempoMedio = document.getElementById('tempo_medio_conclusao').textContent.replace(' h', '');
                preencherAbasSecundarias(tempoMedio, data.por_tipo);
            })
            .catch(function (error) {
                console.error('Erro ao carregar gráficos:', error);
            });
    }

    function renderizarTabelaSetores(setores) {
        var tbody = document.querySelector('#tabela_setores tbody');
        tbody.innerHTML = '';

        if (!setores || setores.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2">Nenhum dado encontrado.</td></tr>';
            return;
        }

        setores.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + escapeHtml(item.setor) + '</td><td>' + item.total + '</td>';
            tbody.appendChild(tr);
        });
    }

    function renderizarGraficoPedidosDia(dados) {
        var ctx = document.getElementById('graficoPedidosDia').getContext('2d');

        if (graficos.pedidosDia) {
            graficos.pedidosDia.destroy();
        }

        var labels = dados.map(function (item) { return item.dia; });
        var valores = dados.map(function (item) { return parseInt(item.total, 10); });

        graficos.pedidosDia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pedidos',
                    data: valores,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }

    function renderizarGraficoTipos(dados) {
        var ctx = document.getElementById('graficoTipos').getContext('2d');

        if (graficos.tipos) {
            graficos.tipos.destroy();
        }

        var labels = dados.map(function (item) { return item.label; });
        var valores = dados.map(function (item) { return parseInt(item.total, 10); });

        graficos.tipos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: valores,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }

    function renderizarGraficoSla(dados) {
        var ctx = document.getElementById('graficoSla').getContext('2d');

        if (graficos.sla) {
            graficos.sla.destroy();
        }

        graficos.sla = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Dentro do prazo', 'Em vencimento', 'Vencidos'],
                datasets: [{
                    data: [
                        parseInt(dados.dentro_prazo, 10),
                        parseInt(dados.em_vencimento, 10),
                        parseInt(dados.vencidos, 10)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }

    function obterTotaisPorTipo(dadosTipos) {
        var totais = {
            admissao: 0,
            demissao: 0,
            mudanca: 0
        };

        dadosTipos.forEach(function (item) {
            var label = (item.label || '').toLowerCase();

            if (label.indexOf('admiss') !== -1) {
                totais.admissao = parseInt(item.total, 10) || 0;
            } else if (label.indexOf('demiss') !== -1) {
                totais.demissao = parseInt(item.total, 10) || 0;
            } else if (label.indexOf('mudan') !== -1) {
                totais.mudanca = parseInt(item.total, 10) || 0;
            }
        });

        return totais;
    }

    function preencherAbasSecundarias(tempoMedio, dadosTipos) {
        var totais = obterTotaisPorTipo(dadosTipos);

        document.getElementById('conteudo_admissoes').innerHTML =
            '<div class="kpi-linha"><span>Total de admissões</span><strong>' + totais.admissao + '</strong></div>' +
            '<div class="kpi-linha"><span>Tempo médio geral</span><strong>' + tempoMedio + ' h</strong></div>';

        document.getElementById('conteudo_demissoes').innerHTML =
            '<div class="kpi-linha"><span>Total de demissões</span><strong>' + totais.demissao + '</strong></div>' +
            '<div class="kpi-linha"><span>Tempo médio geral</span><strong>' + tempoMedio + ' h</strong></div>';

        document.getElementById('conteudo_mudancas').innerHTML =
            '<div class="kpi-linha"><span>Total de mudanças</span><strong>' + totais.mudanca + '</strong></div>' +
            '<div class="kpi-linha"><span>Tempo médio geral</span><strong>' + tempoMedio + ' h</strong></div>';
    }

    function preencherSla(sla) {
        document.getElementById('conteudo_sla').innerHTML =
            '<div class="kpi-linha"><span>Dentro do prazo</span><strong>' + sla.dentro_prazo + '</strong></div>' +
            '<div class="kpi-linha"><span>Em vencimento</span><strong>' + sla.em_vencimento + '</strong></div>' +
            '<div class="kpi-linha"><span>Vencidos</span><strong>' + sla.vencidos + '</strong></div>';
    }

    function configurarAbas() {
        var botoes = document.querySelectorAll('.aba-metrica');
        var abas = document.querySelectorAll('.conteudo-aba');

        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                var alvo = botao.getAttribute('data-aba');

                botoes.forEach(function (b) {
                    b.classList.remove('ativa');
                });

                abas.forEach(function (aba) {
                    aba.classList.remove('ativo');
                });

                botao.classList.add('ativa');
                document.getElementById('aba-' + alvo).classList.add('ativo');
            });
        });
    }

    function configurarFiltros() {
        document.getElementById('btnAplicarFiltros').addEventListener('click', function () {
            carregarResumo();
            carregarGraficos();
        });

        document.getElementById('btnLimparFiltros').addEventListener('click', function () {
            document.getElementById('filtro_solicitante').value = '';
            document.getElementById('filtro_nome_candidato').value = '';
            document.getElementById('filtro_status').value = '';
            document.getElementById('filtro_tipo').value = '';

            carregarResumo();
            carregarGraficos();
        });
    }

    function escapeHtml(texto) {
        if (texto === null || texto === undefined) {
            return '';
        }

        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    configurarAbas();
    configurarFiltros();
    carregarResumo();
    carregarGraficos();
})();