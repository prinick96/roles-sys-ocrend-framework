<?php

/*
 * This file is part of the Ocrend Framewok 2 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace app\interfaces;

/**
 * Interface para conectar a todas las clases de tipos de usuarios
 *
 * @author Brayan Narváez <prinick@ocrend.com>
*/
interface IUsers {
    /**
        * Elimina un usuario 
        *
        * @return void
    */
    public function del();
    /**
        * Obtiene datos de uno/varios ususarios
        *
        * @param bool $multi: true para múltiples usuarios, false para uno solo
        * @param string $s_user: String con cadena a seleccionar de la tabla users
        * @param string $s_rol: String con cadena a seleccionar de la tabla del rol
        * 
        * @return false|array con información de el/los usuarios
    */
    public function get(bool $multi, string $s_user = '*', string $s_rol = '*');
    /**
        * Guarda información de un usuario conectado
        *
        * @return array
    */
    public function saveOwner() : array;
}