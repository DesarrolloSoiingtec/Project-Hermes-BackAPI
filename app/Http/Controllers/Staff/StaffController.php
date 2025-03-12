<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\StoreStaffRequest;


class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $requestData = json_decode($request->getContent(), true);
        log::info($requestData);

        $search = $request->get("search");

        $users = User::where(DB::raw("users.name || ' ' || COALESCE(users.lastname,'') || ' ' || users.email"),"ilike","%".$search."%")->orderBy("id","desc")->get();

        return response()->json([
            "users" => UserCollection::make($users),
            "roles" => Role::where("name","not ilike","%veterinario%")->get()->map(function($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffRequest $request)
    {
        // Obtener datos validados
        $validated = $request->validated();

        // Procesar la imagen y guardar la ruta en 'avatar'
        if ($request->hasFile('imagen')) {
            $path = Storage::putFile('users', $request->file('imagen'));
            $validated['avatar'] = $path;
        }

        // Encriptar la contraseña
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Formatear la fecha de cumpleaños (si se envía)
        if (isset($validated['birthday'])) {
            $validated['birthday'] = $validated['birthday'] . " 00:00:00";
        }

        // Crear el usuario utilizando solo los campos permitidos
        $user = User::create($validated);

        // Asignar el rol correspondiente
        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        return response()->json([
            "message" => 200,
            "user"    => new UserResource($user),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($filename)
    {
        $path = 'users/' . $filename;

        // Verificar si el archivo existe en el disco local
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // Retornar el archivo con los encabezados adecuados
        return Storage::disk('local')->response($path);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $is_user_exists = User::where("email",$request->email)->where("id","<>",$id)->first();
        if($is_user_exists){
            return response()->json([
                "message" => 403,
                "message_text" => "El usuario ya existe"
            ]);
        }
        $user = User::findOrFail($id);
        if($request->hasFile("imagen")){
            if($user->avatar){
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("users",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }
        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }
        if($request->birthday){
            $request->request->add(["birthday" => $request->birthday." 00:00:00"]);
        }
        $user->update($request->all());

        if($request->role_id && $request->role_id != $user->role_id){
            $role_old = Role::findOrFail($user->role_id);
            $user->removeRole($role_old);

            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);
        }

        return response()->json([
            "message" => 200,
            "user" => UserResource::make($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if($user->avatar){
            Storage::delete($user->avatar);
        }
        $user->delete();

        return response()->json([
            "message" => 200,
        ]);
    }
}
