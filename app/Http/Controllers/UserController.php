<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Helpers\JwtAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{   
    public function register(Request $request) {
        
        //Obtener los datos por POST
        $json = request()->input('json', null);

        $params = json_decode($json); //Objeto
        $params_array = json_decode($json, true); //Array
        
        if(!empty($params) && !empty($params_array)) {
            //Limpiar datos
            $params_array = array_map('trim', $params_array);

            //Validar Datos
            $validate = Validator::make($params_array, [
                        'name'     => 'required|alpha',
                        'surname'  => 'required|alpha',
                        'email'    => 'required|email|unique:users',
                        'password' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'El usuario no se ha creado.',
                    'errors'  => $validate->errors()
                );
            } else {
                
                //Cifrar la contraseña
                $pwd = hash('sha256', $params->password);
                
                //Creamos el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';
                
                //Guardamos el usuario
                $user->save();
                
                $data = array(
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'El usuario se ha creado correctamente.',
                    'user'    => $user
                );
            }
        }else{
            $data = array(
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Los datos enviados no son correctos.'
            );
        }

        return response()->json($data, $data['code']);
    }
    
    public function login(Request $request) {
        
        $jwtAuth = new JwtAuth();
        
        //Recibir los datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        //Validar Datos
        $validate = Validator::make($params_array, [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if ($validate->fails()) {
            $signup = array(
                'status'  => 'error',
                'code'    => 404,
                'message' => 'El usuario no se ha podido loguear.',
                'errors'  => $validate->errors()
            );
        }else{
            $pwd = hash('sha256', $params->password);
            
            //Devolver token o datos
            $signup = $jwtAuth->singup($params->email, $pwd);
            
            if(!empty($params->gettoken)) {
                $signup = $jwtAuth->singup($params->email, $pwd, true);
            }
        }
        
        return response()->json($signup, 200);
    }
    
    public function update(Request $request) {
        
        //Comprobar que el usuario este identificado
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        
        //Obtenemos los datos
        $json = $request->input('json',  null);
        $params_array = json_decode($json, true);
        
        if($checkToken && !empty($params_array)) {
            
            //Obtener usuario identificado
            $user = $jwtAuth->checkToken($token, true);
            
            //Validar los datos
            $validate = Validator::make($params_array, [
                'name'     => 'required|alpha',
                'surname'  => 'required|alpha',
                'email'    => 'required|email|unique:users,'.$user->sub
            ]);
            
            //Quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);
            
            //Actualizar usuario en BD
            $user_update = User::where('id', $user->sub)->update($params_array);
            
            //Devolvemos array con datos
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
            
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no está identificado.'
            );
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function upload(Request $request) {
        
        //Obtener los datos
        $image = $request->file('file0');
        
        //Validar la imagen
        $validate = Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        //Guardar Imagen
        if(!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen.'
            );
        }else{
            $image_name = time().$image->getClientOriginalName();
            Storage::disk('users')->put($image_name, File::get($image));
            
            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function getImage($fileName) {
        
        $existe = Storage::disk('users')->exists($fileName);
        if($existe){
            $file = Storage::disk('users')->get($fileName);
            return new Response($file, 200);
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe.'
            );
            
            return response()->json($data, $data['code']);
        }
    }
    
    public function detail($id) {
        $user = User::find($id);
        
        if(is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe.'
            );
        }
        
        return response()->json($data, $data['code']);
    }
    
}
