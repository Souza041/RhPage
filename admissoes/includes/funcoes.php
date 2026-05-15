<?php

function traduzTipoSolicitacao($tipo)
{
    switch ($tipo) {
        case 'admissao':
            return 'Admissão';
        case 'demissao':
            return 'Demissão';
        case 'mudanca_cargo_salario':
            return 'Mudança de Cargo/Salário';
        default:
            return $tipo;
    }
}

function renderTemplate($arquivo, array $dados = [])
{
    return require $arquivo;
}