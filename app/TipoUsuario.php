<?php

namespace App;

enum TipoUsuario: string
{
    case Estudante = 'estudante';
    case Servidor = 'servidor';
    case Admin = 'admin';
}
