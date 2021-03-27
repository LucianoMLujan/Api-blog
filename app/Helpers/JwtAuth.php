<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class JwtAuth {

    public $key;

    public function __construct() {
        $this->key = 'esto_es_una_clave_super_segura-99887766';
    }

    public function singup($email, $password, $getToken = null) {

        //Buscar si existe el usuario con las credenciales
        $user = User::where([
            'email' => $email,
            'password' => $password
        ])->first();

        //Comprobar que los datos son correctos
        $singup = false;
        if(is_object($user)) {
            $singup = true;
        }

        //Generar el token con los datos del usuario
        if($singup) {
            $token = array(
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'surname' => $user->surname,
                'description' => $user->description,
                'image' => $user->image,
                'iat' => time(),
                'exp' => time() + (7*24*60*60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);

            if(is_null($getToken)) {
                $data = $jwt;
            }else{
                $data = $decoded;
            }

        }else{
            $data = array(
                'status' => 'error',
                'message' => 'Login incorrecto'
            );
        }
        return $data;
    }

    public function checktoken($jwt, $getIdentity = false) {
        $auth = false;

        try {
            $jwt = str_replace('"', '', $jwt);
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        }catch(\UnexpectedValueException $ex) {
            $auth = false;
        }catch(\DomainException $ex){
            $auth = false;
        }

        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        }else{
            $auth = false;
        }

        if($getIdentity) {
            return $decoded;
        }

        return $auth;
    }


}