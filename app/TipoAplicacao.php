<?php

namespace App;

enum TipoAplicacao: string
{
    case Diagnostico = 'diagnostico';
    case Monitoramento = 'monitoramento';
}
