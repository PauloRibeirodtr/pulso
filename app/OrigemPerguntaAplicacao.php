<?php

namespace App;

enum OrigemPerguntaAplicacao: string
{
    case Institucional = 'institucional';
    case Curso = 'curso';
    case Temporaria = 'temporaria';
}
