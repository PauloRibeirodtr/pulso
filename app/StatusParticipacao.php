<?php

namespace App;

enum StatusParticipacao: string
{
    case NaoIniciada = 'nao_iniciada';
    case EmAndamento = 'em_andamento';
    case Finalizada = 'finalizada';
}
