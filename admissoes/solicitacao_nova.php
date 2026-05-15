<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Mailer.php';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="card">
    <h2>Nova Solicitação</h2>

    <div style="background:#eef7ff; border:1px solid #cfe2ff; padding:12px; border-radius:8px; margin-bottom:15px;">
        Ao salvar esta solicitação, ela será registrada com sua assinatura eletrônica vinculada ao seu usuário.
    </div>

    <form method="POST" action="solicitacao_salvar.php" id="formSolicitacao">
        <div class="form-group">
            <label>Tipo de Solicitação</label>
            <select name="tipo" id="tipo" required>
                <option value="">Selecione</option>
                <option value="admissao">Admissão</option>
                <option value="demissao">Demissão</option>
                <option value="mudanca_cargo_salario">Mudança de Cargo/Salário</option>
            </select>
        </div>

        <div id="camposSolicitacao" style="display:none;">

            <div class="row">
                <div class="col-6" id="blocoNomeCandidato">
                    <div class="form-group">
                        <label id="labelNome">Nome do candidato / funcionário</label>
                        <input type="text" name="nome_candidato" id="nome_candidato">
                    </div>
                </div>

                <div class="col-6" id="blocoCpfCandidato">
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" name="cpf_candidato" id="cpf_candidato">
                    </div>
                </div>
            </div>

            <div class="row">

                <div class="col-6" id="blocoCargoAtual">
                    <div class="form-group">
                        <label>Cargo atual</label>
                        <input type="text" name="cargo_atual" id="cargo_atual">
                    </div>
                </div>

                <div class="col-6" id="colSalarioAtual">
                    <div class="form-group">
                        <label>Salário atual</label>
                        <input type="number" step="0.01" name="salario_atual" id="salario_atual">
                    </div>
                </div>

            </div>

            <div class="row">

                <div class="col-6" id="blocoNovoCargo">
                    <div class="form-group">
                        <label id="labelCargoNovo">Novo cargo</label>
                        <input type="text" name="cargo_novo" id="cargo_novo">
                    </div>
                </div>

                <div class="col-6" id="colSalarioNovo">
                    <div class="form-group">
                        <label id="labelSalarioNovo">Novo salário</label>
                        <input type="number" step="0.01" name="salario_novo" id="salario_novo">
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Setor solicitante</label>
                        <input type="text" name="setor_solicitante" id="setor_solicitante">
                    </div>
                </div>
            

                <div class="col-6">
                    <div class="form-group">
                        <label>Unidade / Filial</label>
                        <input type="text" name="unidade_solicitante" id="unidade_solicitante">
                    </div>
                </div>
            </div>

            <div class="form-group" id="blocoNecessidadesAdmissao" style="display:none;">
                <label>Necessidades para admissão</label>

                <label style="font-weight:normal;">
                    <input type="checkbox" name="precisa_equipamentos" value="1" style="width:auto;">
                    Equipamentos
                </label>

                <label style="font-weight:normal;">
                    <input type="checkbox" name="precisa_email_corporativo" value="1" style="width:auto;">
                    E-mail corporativo
                </label>

                <label style="font-weight:normal;">
                    <input type="checkbox" name="precisa_cadastro_sistema" value="1" style="width:auto;">
                    Cadastro no sistema
                </label>
            </div>

            <div class="form-group" id="blocoDataInicio">
                <label id="labelData">Data prevista</label>
                <input type="date" name="data_inicio_prevista" id="data_inicio_prevista">
            </div>

            <div class="form-group">
                <label id="labelMotivo">Motivo / Observação inicial</label>
                <textarea name="motivo" id="motivo" rows="5" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">Salvar Solicitação</button>
            <a href="dashboard.php" class="btn btn-danger">Cancelar</a>
        </form>
    </div>

<script>
(function () {
    var tipo = document.getElementById('tipo');
    var camposSolicitacao = document.getElementById('camposSolicitacao');

    var labelNome = document.getElementById('labelNome');
    var labelData = document.getElementById('labelData');
    var labelMotivo = document.getElementById('labelMotivo');

    var nome = document.getElementById('nome_candidato');
    var cargoAtual = document.getElementById('cargo_atual');
    var cargoNovo = document.getElementById('cargo_novo');
    var salarioAtual = document.getElementById('salario_atual');
    var salarioNovo = document.getElementById('salario_novo');
    var dataInicio = document.getElementById('data_inicio_prevista');
    var motivo = document.getElementById('motivo');

    var blocoCargoAtual = document.getElementById('blocoCargoAtual');
    var blocoNovoCargo = document.getElementById('blocoNovoCargo');
    var blocoSalarios = document.getElementById('blocoSalarios');
    var colSalarioAtual = document.getElementById('colSalarioAtual');
    var colSalarioNovo = document.getElementById('colSalarioNovo');
    var blocoDataInicio = document.getElementById('blocoDataInicio');

    var blocoNecessidadesAdmissao = document.getElementById('blocoNecessidadesAdmissao');

    var blocoNomeCandidato = document.getElementById('blocoNomeCandidato');
    var blocoCpfCandidato = document.getElementById('blocoCpfCandidato');
    var cpf = document.getElementById('cpf_candidato');

    var labelCargoNovo = document.getElementById('labelCargoNovo');
    var labelSalarioNovo = document.getElementById('labelSalarioNovo');

    function mostrar(el) {
        if (el) {
            el.style.display = '';
        }
    }

    function esconder(el) {
        if (el) {
            el.style.display = 'none';
        }
    }

    function limparRequired() {
        if (nome) nome.required = false;
        if (cargoAtual) cargoAtual.required = false;
        if (cargoNovo) cargoNovo.required = false;
        if (salarioAtual) salarioAtual.required = false;
        if (salarioNovo) salarioNovo.required = false;
        if (dataInicio) dataInicio.required = false;
        if (motivo) motivo.required = false;
        if (cpf) cpf.required = false;
    }

    function limparCamposOcultos() {
        if (blocoCargoAtual && blocoCargoAtual.style.display === 'none' && cargoAtual) {
            cargoAtual.value = '';
        }

        if (blocoNovoCargo && blocoNovoCargo.style.display === 'none' && cargoNovo) {
            cargoNovo.value = '';
        }

        if (colSalarioAtual && colSalarioAtual.style.display === 'none' && salarioAtual) {
            salarioAtual.value = '';
        }

        if (colSalarioNovo && colSalarioNovo.style.display === 'none' && salarioNovo) {
            salarioNovo.value = '';
        }

        if (blocoDataInicio && blocoDataInicio.style.display === 'none' && dataInicio) {
            dataInicio.value = '';
        }
    }

    function configurarFormulario() {
        var valor = tipo ? tipo.value : '';

        if (!valor) {
            if (camposSolicitacao) {
                camposSolicitacao.style.display = 'none';
            }
            limparRequired();
            return;
        }

        if (camposSolicitacao) {
            camposSolicitacao.style.display = 'block';
        }

        mostrar(blocoCargoAtual);
        mostrar(blocoNovoCargo);
        mostrar(blocoSalarios);
        mostrar(colSalarioAtual);
        mostrar(colSalarioNovo);
        mostrar(blocoDataInicio);

        mostrar(blocoNomeCandidato);
        mostrar(blocoCpfCandidato);
        esconder(blocoNecessidadesAdmissao);

        if (labelCargoNovo) labelCargoNovo.innerText = 'Novo cargo';
        if (labelSalarioNovo) labelSalarioNovo.innerText = 'Novo salário';

        limparRequired();

        if (nome) nome.required = false;
        if (motivo) motivo.required = false;

        if (valor === 'admissao') {

            esconder(blocoNomeCandidato);
            esconder(blocoCpfCandidato);
            esconder(blocoDataInicio);

            mostrar(blocoNecessidadesAdmissao);

            if (labelNome) labelNome.innerText = 'Nome do candidato';
            if (labelData) labelData.innerText = 'Data prevista de início';
            if (labelMotivo) labelMotivo.innerText = 'Motivo / Observação inicial';

            if (labelCargoNovo) labelCargoNovo.innerText = 'Cargo';
            if (labelSalarioNovo) labelSalarioNovo.innerText = 'Salário';

            esconder(blocoCargoAtual);
            mostrar(blocoNovoCargo);

            esconder(colSalarioAtual);
            mostrar(colSalarioNovo);

            if (cargoAtual) cargoAtual.required = false;
            if (salarioAtual) salarioAtual.required = false;

            if (cargoNovo) cargoNovo.required = true;
            if (salarioNovo) salarioNovo.required = true;
            if (dataInicio) dataInicio.required = false;
        }

        if (valor === 'demissao') {
            if (labelNome) labelNome.innerText = 'Nome do funcionário';
            if (labelData) labelData.innerText = 'Data prevista para desligamento';
            if (labelMotivo) labelMotivo.innerText = 'Motivo da demissão / Observação inicial';

            mostrar(blocoCargoAtual);
            esconder(blocoNovoCargo);
            esconder(colSalarioNovo);
            mostrar(blocoDataInicio);

            if (nome) nome.required = true;

            if (cargoAtual) cargoAtual.required = true;
            if (dataInicio) dataInicio.required = true;
        }

        if (valor === 'mudanca_cargo_salario') {
            if (labelNome) labelNome.innerText = 'Nome do funcionário';
            if (labelData) labelData.innerText = 'Data prevista da mudança';
            if (labelMotivo) labelMotivo.innerText = 'Justificativa da mudança';

            mostrar(blocoCargoAtual);
            mostrar(blocoNovoCargo);
            mostrar(blocoSalarios);
            mostrar(colSalarioAtual);
            mostrar(colSalarioNovo);
            mostrar(blocoDataInicio);

            if (nome) nome.required = true;

            if (cargoAtual) cargoAtual.required = true;
            if (cargoNovo) cargoNovo.required = true;
            if (salarioAtual) salarioAtual.required = true;
            if (salarioNovo) salarioNovo.required = true;
        }

        limparCamposOcultos();
    }

    if (tipo) {
        tipo.addEventListener('change', configurarFormulario);
        configurarFormulario();
    }
})();
</script>


<?php include __DIR__ . '/includes/footer.php'; ?>