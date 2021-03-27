<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Post;
use App\Helpers\JwtAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth', ['except' => [
                'index', 
                'show', 
                'getImage', 
                'getPostsByCategory', 
                'getPostsByUser'
            ]]);
    }
    
    public function index() {
        $posts = Post::all()->load('category');
        
        return response()->json([
            'code' =>  200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
    
    public function show($id) {
        $post = Post::find($id)->load('category')
                               ->load('user');
        
        if(is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts' => $post
            ];
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe.'
            ];
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function store(Request $request) {
        //Obtener los datos
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        if(!empty($params_array)){
            //Obtener usuario
            $user = $this->getIdentity($request);
                    
            //Validar los datos
            $validate = Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);
            
            if($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha podido guardar el post.'
                ];
            }else{
                //Guardar el post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                
                $post->save();
                
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
            
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Por favor, envia los datos correctamente.'
            ];
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function update($id, Request $request){
        //Obtener los datos
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        //En caso de error devuelve esto
        $data = array(
            'code' => 400,
            'status' => 'error',
            'post' => 'Datos enviados incorrectamente.'
        );

        if(!empty($params_array)){
            //Validar los datos
            $validate = Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);
            
            if($validate->fails()){
               $data['errors'] = $validate->errors();
               return response()->json($data, $data['code']);
            }
            
            //Sacar los datos que no vamos actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);
            unset($params_array['category']);

            //Obtener usuario
            $user = $this->getIdentity($request);

            //Buscar el registro
            $post = Post::where('id', $id)
                        ->where('user_id', $user->sub)
                        ->first();
            
            if(!empty($post) && is_object($post)) {
                //Actualizar el registro
                $post->update($params_array);
                
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'changes' => $params_array
                );
            }
        }
        
        return response()->json($data, $data['code']);
        
    }
    
    public function destroy($id, Request $request) {
        //Obtener usuario
        $user = $this->getIdentity($request);
        
        //Obtener el registro
        $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();
        
        if(!empty($post)){
            //Borramos el registro
            $post->delete();

            $data = array(
                'code' => 200,
                'status' => 'success',
                'post' => $post
            );
        }else{
           $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe.'
            ); 
        }
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity(Request $request) {
        //Obtener usuario
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checktoken($token, true);
        
        return $user;
    }
    
    public function upload(Request $request) {
        //Obtener la imagen
        $image = $request->file('file0');
        
        //Validar imagen
        $validate = Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        if(!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen.'
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();
            
            Storage::disk('images')->put($image_name, File::get($image));
            
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function getImage($filename) {
        //Verificar que exista el fichero
        $isset = Storage::disk('images')->exists($filename);
        
        if($isset){
            //Obtener la imagen
            $file = Storage::disk('images')->get($filename);
            
            //Devolvemos la imagen
            return new Response($file, 200);
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe.'
            ];
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
    
    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
}
