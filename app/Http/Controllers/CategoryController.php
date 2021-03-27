<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }
    
    public function index() {
        $categories = Category::all();
        
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories
        ]);
    }
    
    public function show($id) {
        $category = Category::find($id);
        
        if(is_object($category)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'category' => $category
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La categoria no existe.'
            );
        }
        
        return response()->json($data, $data['code']);
    }
    
    public function store(Request $request) {
        //Obtener los datos
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        if (!empty($params_array)) {
            //Validar informacion
            $validate = Validator::make($params_array, [
                        'name' => 'required|alpha'
            ]);

            //Guardar la categoria
            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Error al guardar la categoria.'
                );
            }else{
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                );
            }
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error no se ha enviado ninguna categoria.'
            );
        }

        return response()->json($data, $data['code']);
    }
    
    public function update($id, Request $request) {
        //Obtener los datos
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        if(!empty($params_array)) {
            //Validar los datos
            $validate = Validator::make($params_array, [
                'name' => 'required|alpha'
            ]);

            //Sacar lo que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['created_at']);
            
            //Actualizar el registro
            $category = Category::where('id', $id)->update($params_array);
            
            $data = array(
                'code' => 200,
                'status' => 'success',
                'category' => $params_array
            );
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error no se ha enviado ninguna categoria.'
            );
        }
        
        return response()->json($data, $data['code']);
    }
    
}