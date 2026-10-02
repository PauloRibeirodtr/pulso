<?php

namespace App;

enum StatusSolicitacaoAcompanhamento: string
{
    case Pendente = 'pendente';
    case Atendida = 'atendida';
}
