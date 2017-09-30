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
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;
use Ocrend\Kernel\Helpers\Strings;
use Ocrend\Kernel\Helpers\Emails;

/**
 * Controla todos los aspectos de un usuario dentro del sistema.
 *
 * @author Brayan Narváez <prinick@ocrend.com>
 */

class Users extends Models implements IModels {
    /**
     * Característica para establecer conexión con base de datos. 
     */
    use DBModel;

    /**
        * Colección de tablas de roles de usuarios
        *
        * @var array
    */
    const ROLES = [
        'users_normales',
        'users_admins'
    ];

    /**
     * Máximos intentos de inincio de sesión de un usuario
     *
     * @var int
     */
    const MAX_ATTEMPTS = 8;

    /**
     * Tiempo entre máximos intentos en segundos
     *
     * @var int
     */
    const MAX_ATTEMPTS_TIME = 5; # (dos minutos)

    /**
     * Log de intentos recientes con la forma 'email' => (int) intentos
     *
     * @var array
     */
    private $recentAttempts = array();

      /**
       * Hace un set() a la sesión login_user_recentAttempts con el valor actualizado.
       *
       * @return void
       */
    private function updateSessionAttempts() {
        global $session;

        $session->set('login_user_recentAttempts', $this->recentAttempts);
    }

    /**
     * Genera la sesión con el id del usuario que ha iniciado
     *
     * @param string $pass : Contraseña sin encriptar
     * @param string $pass_repeat : Contraseña repetida sin encriptar
     *
     * @throws ModelsException cuando las contraseñas no coinciden
     */
    private function checkPassMatch(string $pass, string $pass_repeat) {
        if ($pass != $pass_repeat) {
            throw new ModelsException('Las contraseñas no coinciden.');
        }
    }

    /**
     * Verifica el email introducido, tanto el formato como su existencia en el sistema
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException en caso de que no tenga formato válido o ya exista
     */
    private function checkEmail(string $email) {
        # Formato de email
        if (!Strings::is_email($email)) {
            throw new ModelsException('El email no tiene un formato válido.');
        }
        # Existencia de email
        $email = $this->db->scape($email);
        $query = $this->db->select('id_user', 'users', "email='$email'", 'LIMIT 1');
        if (false !== $query) {
            throw new ModelsException('El email introducido ya existe.');
        }
    }

    /**
     * Restaura los intentos de un usuario al iniciar sesión
     *
     * @param string $email: Email del usuario a restaurar
     *
     * @throws ModelsException cuando hay un error de lógica utilizando este método
     * @return void
     */
    private function restoreAttempts(string $email) {       
        if (array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email]['attempts'] = 0;
            $this->recentAttempts[$email]['time'] = null;
            $this->updateSessionAttempts();
        } else {
            throw new ModelsException('Error lógico');
        }
       
    }

    /**
     * Genera la sesión con el id del usuario que ha iniciado
     *
     * @param array $user_data: Arreglo con información de la base de datos, del usuario
     *
     * @return void
     */
    private function generateSession(array $user_data) {
        global $session, $config;

        $session->set('user_id', (int) $user_data['id_user']);
        $session->set('unique_session', $config['sessions']['unique']);
    }

    /**
     * Verifica en la base de datos, el email y contraseña ingresados por el usuario
     *
     * @param string $user_email: Email o nickname del usuario que intenta el login
     * @param string $pass: Contraseña sin encriptar del usuario que intenta el login
     *
     * @return bool true: Cuando el inicio de sesión es correcto 
     *              false: Cuando el inicio de sesión no es correcto
     */
    private function authentication(string $user_email,string $pass) : bool {
        $user_email = $this->db->scape($user_email);
        $query = $this->db->select('id_user,pass','users',"email='$user_email' OR user='$user_email'",'LIMIT 1');
        
        # Incio de sesión con éxito
        if(false !== $query && Strings::chash($query[0]['pass'],$pass)) {

            # Restaurar intentos
            $this->restoreAttempts($user_email);

            # Generar la sesión
            $this->generateSession($query[0]);
            return true;
        }

        return false;
    }

    /**
     * Establece los intentos recientes desde la variable de sesión acumulativa
     *
     * @return void
     */
    private function setDefaultAttempts() {
        global $session;

        if (null != $session->get('login_user_recentAttempts')) {
            $this->recentAttempts = $session->get('login_user_recentAttempts');
        }
    }
    
    /**
     * Establece el intento del usuario actual o incrementa su cantidad si ya existe
     *
     * @param string $email: Email del usuario
     *
     * @return void
     */
    private function setNewAttempt(string $email) {
        if (!array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email] = array(
                'attempts' => 0, # Intentos
                'time' => null # Tiempo 
            );
        } 

        $this->recentAttempts[$email]['attempts']++;
        $this->updateSessionAttempts();
    }

    /**
     * Controla la cantidad de intentos permitidos máximos por usuario, si llega al límite,
     * el usuario podrá seguir intentando en self::MAX_ATTEMPTS_TIME segundos.
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException cuando ya ha excedido self::MAX_ATTEMPTS
     * @return void
     */
    private function maximumAttempts(string $email) {
        if ($this->recentAttempts[$email]['attempts'] >= self::MAX_ATTEMPTS) {
            
            # Colocar timestamp para recuperar más adelante la posibilidad de acceso
            if (null == $this->recentAttempts[$email]['time']) {
                $this->recentAttempts[$email]['time'] = time() + self::MAX_ATTEMPTS_TIME;
            }
            
            if (time() < $this->recentAttempts[$email]['time']) {
                # Setear sesión
                $this->updateSessionAttempts();
                # Lanzar excepción
                throw new ModelsException('Ya ha superado el límite de intentos para iniciar sesión.');
            } else {
                $this->restoreAttempts($email);
            }
        }
    }

    /**
     * Realiza la acción de login dentro del sistema
     *
     * @return array : Con información de éxito/falla al inicio de sesión.
     */
    public function login() : array {
        try {
            global $http;

            # Definir de nuevo el control de intentos
            $this->setDefaultAttempts();   

            # Obtener los datos $_POST
            $user_email = strtolower($http->request->get('user_email'));
            $pass = $http->request->get('pass');

            # Verificar que no están vacíos
            if ($this->functions->e($user_email, $pass)) {
                throw new ModelsException('Credenciales incompletas.');
            }
            
            # Añadir intentos
            $this->setNewAttempt($user_email);
        
            # Verificar intentos 
            $this->maximumAttempts($user_email);

            # Autentificar
            if ($this->authentication($user_email, $pass)) {
                return array('success' => 1, 'message' => 'Conectado con éxito.');
            }
            
            throw new ModelsException('Credenciales incorrectas.');

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());
        }        
    }

    /**
     * Realiza la acción de registro dentro del sistema
     *
     * @return array : Con información de éxito/falla al registrar el usuario nuevo.
     */
    public function register() : array {
        try {
            global $http;

            # Obtener los datos $_POST
            $user = $http->request->get('user');
            $name = $http->request->get('name');
            $lastname = $http->request->get('lastname');
            $email = $http->request->get('email');
            $pass = $http->request->get('pass');
            $pass_repeat = $http->request->get('pass_repeat');

            # Verificar que no están vacíos
            if ($this->functions->e($name, $email, $pass, $pass_repeat, $user)) {
                throw new ModelsException('Los campos marcados con <span class="required">*</span> son necesarios.');
            }

            # Verificar email 
            $this->checkEmail($email);

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Registrar al usuario
            $this->db->insert('users', array(
                'user' => $user,
                'name' => $name,
                'lastname' => $lastname,
                'email' => $email,
                'pass' => Strings::hash($pass)
            ));

            # Iniciar sesión
            /*$this->generateSession(array(
                'id_user' => $this->db->lastInsertId()
            ));*/

            return array('success' => 1, 'message' => 'Registrado con éxito.');
        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());
        }        
    }
    
    /**
      * Envía un correo electrónico al usuario que quiere recuperar la contraseña, con un token y una nueva contraseña.
      * Si el usuario no visita el enlace, el sistema no cambiará la contraseña.
      *
      * @return array<string,integer|string>
    */  
    public function lostpass() {
        try {
            global $http, $config;

            # Obtener datos $_POST
            $email = $http->request->get('email');
            
            # Campo lleno
            if ($this->functions->emp($email)) {
                throw new ModelsException('El campo email debe estar lleno.');
            }

            # Filtro
            $email = $this->db->scape($email);

            # Obtener información del usuario 
            $user_data = $this->db->select('id_user,name', 'users', "email='$email'", 'LIMIT 1');

            # Verificar correo en base de datos 
            if (false === $user_data) {
                throw new ModelsException('El email no está registrado en el sistema.');
            }

            # Generar token y contraseña 
            $token = md5(time());
            $pass = uniqid();

            # Construir mensaje y enviar mensaje
            $HTML = 'Hola <b>'. $user_data[0]['name'] .'</b>, ha solicitado recuperar su contraseña perdida, si no ha realizado esta acción no necesita hacer nada.
					<br />
					<br />
					Para cambiar su contraseña por <b>'. $pass .'</b> haga <a href="'. $config['site']['url'] . 'lostpass/cambiar/&token='.$token.'&user='.$user_data[0]['id_user'].'" target="_blank">clic aquí</a>.';

            # Enviar el correo electrónico
            $dest = array();
			$dest[$email] = $user_data[0]['name'];
			$email = Emails::send_mail($dest,Emails::plantilla($HTML),'Recuperar contraseña perdida');

            # Verificar si hubo algún problema con el envío del correo
            if(false === $email) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            # Actualizar datos 
            $id_user = $user_data[0]['id_user'];
            $this->db->update('users',array(
                'tmp_pass' => Strings::hash($pass),
                'token' => $token
            ),"id_user='$id_user'",'LIMIT 1');

            return array('success' => 1, 'message' => 'Se ha enviado un enlace a su correo electrónico.');
        } catch(ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());
        }
    }

    /**
     * Cambia la contraseña de un usuario en el sistema, luego de que éste haya solicitado cambiarla.
     * Luego retorna al sitio de inicio con la variable GET success=(bool)
     *
     * La URL debe tener la forma URL/lostpass/cambiar/&token=TOKEN&user=ID
     *
     * @return void
     */  
    public function changeTemporalPass() {
        global $config, $http;
        
        # Obtener los datos $_GET 
        $id_user = $http->query->get('user');
        $token = $http->query->get('token');

        if (!$this->functions->emp($token) && is_numeric($id_user) && $id_user >= 1) {
            # Filtros a los datos
            $id_user = $this->db->scape($id_user);
            $token = $this->db->scape($token);
            # Ejecutar el cambio
            $this->db->query("UPDATE users SET pass=tmp_pass, tmp_pass='', token=''
            WHERE id_user='$id_user' AND token='$token' LIMIT 1;");
            # Éxito
            $success = true;
        }
        
        # Devolover al sitio de inicio
        $this->functions->redir($config['site']['url'] . '?sucess=' . (int) isset($success));
    }

    /**
     * Desconecta a un usuario si éste está conectado, y lo devuelve al inicio
     *
     * @return void
     */    
    public function logout() {
        global $session;

        if (null != $session->get('user_id')) {
            $session->remove('user_id');
        }

        $this->functions->redir();
    }

    /**
        * Obtiene los usuarios con su rol correspondiente haciendo merge
        *
        * @param int $id: Id del usuario
        * @param false|array $result : El resultado de la query del usuario
        * @param string $select_rol: se usa para obtener los datos de la tabla del rol correspondiente
        * 
        * @return false|array con la información
    */
    private function getWithRol(int $id, $result, string $select_rol) {
        # Verificar si hay resultado
        if(false === $result) {
            return false;
        }

        # Simplificar
        $result = $result[0];

        # Verificar rol
        if(!array_key_exists('rol',$result)) {
            throw new \RuntimeException('Se necesita conocer el rol del usuario.');
        }

        # Obtenemos datos del rol
        $result_rol = $this->db->select($select_rol,self::ROLES[$result['rol']],"id_user='$id'",'LIMIT 1');
        
        return array_merge($result,$result_rol[0]);
    }

    /**
     * Obtiene datos de un usuario según su id en la base de datos
     *    
     * @param int $id: Id del usuario a obtener
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios 
     * @param string $select_rol: Por defecto es *, se usa para obtener los datos de la tabla del rol correspondiente
     *
     * @return false|array con información del usuario
     */   
    public function getUserById(int $id, string $select = '*', string $select_rol = '*') {
        $result = $this->db->select($select,'users',"id_user='$id'",'LIMIT 1');

        return $this->getWithRol($id,$result,$select_rol);
    }
    
    /**
     * Obtiene a todos los usuarios
     *    
     * @param int $rol: El número del rol
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios 
     * @param string $select_rol: Por defecto es *, se usa para obtener los datos de la tabla del rol correspondiente
     *
     * @return false|array con información de los usuarios
     */  
    public function getUsers(int $rol, string $select = '*', string $select_rol = '*') {
        if(!array_key_exists($rol,self::ROLES)) {
            throw new \RuntimeException('El rol no existe para obtener todos los usuarios');
        }

        # Obtener la tabla
        $tabla = self::ROLES[$rol];

        return $this->db->query_select("SELECT users.$select, $tabla.$select_rol 
        FROM users
        INNER JOIN $tabla ON users.id_user = $tabla.id_user
        WHERE users.rol='$rol'");
    }

    /**
     * Obtiene datos del usuario conectado actualmente
     *
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
      * @param string $select_rol: Por defecto es *, se usa para obtener los datos de la tabla del rol correspondiente
     *
     * @throws ModelsException si el usuario no está logeado
     * @return array con datos del usuario conectado
     */
    public function getOwnerUser(string $select = '*', string $select_rol = '*') : array {
        if(null !== $this->id_user) {    
               
            $user = $this->db->select($select,'users',"id_user='$this->id_user'",'LIMIT 1');

            # Si se borra al usuario desde la base de datos y sigue con la sesión activa
            if(false === $user) {
                $this->logout();
            }

            return $this->getWithRol($this->id_user,$user,$select_rol);
        } 
           
        throw new \RuntimeException('El usuario no está logeado.');
    }

    /**
        * Elimina un usuario por su id
        *
        * @param int $id: Id del usuario a borrar
        *
        * @return void
    */
    public function deleteUser(int $id) {
        $this->db->delete('users',"id_user='$id'",'LIMIT 1');
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