<?php

namespace App;

enum StatusAplicacao: string
{
    case Planejada = 'planejada';
    case Aberta = 'aberta';
    case Encerrada = 'encerrada';
}
