<?php

/*
 * This file is part of the Ocrend Framewok 2 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models;

use app\models as Model;
use app\interfaces\IUsers;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Admins
 *
 * @author Brayan Narváez <prinick@ocrend.com>
 */

class Admins extends Users implements IModels, IUsers {
    /**
      * Característica para establecer conexión con base de datos. 
    */
    use DBModel;

    /**
        * Elimina un usuario 
        *
        * @return void
    */
    public function del() {
      parent::deleteUser($this->id);
    }

    /**
        * Obtiene datos de uno/varios ususarios
        *
        * @param bool $multi: true para múltiples usuarios, false para uno solo
        * @param string $s_user: String con cadena a seleccionar de la tabla users
        * @param string $s_rol: String con cadena a seleccionar de la tabla del rol
        * 
        * @return false|array con información de el/los usuarios
    */
    public function get(bool $multi, string $s_user = '*', string $s_rol = '*') {
      if($multi) {
        return $this->getUsers(1,$s_user,$s_rol);
      }

      return $this->getUserById($this->id,$s_user,$s_rol);
    }

    /**
        * Guarda información de un usuario conectado
        *
        * @return array
    */
    public function saveOwner() : array {
      
    }

    /**
      * __construct()
    */
    public function __construct(IRouter $router = null) {
        parent::__construct($router);
        $this->startDBConexion();
    }

    /**
      * __destruct()
    */ 
    public function __destruct() {
        parent::__destruct();
        $this->endDBConexion();
    }
}