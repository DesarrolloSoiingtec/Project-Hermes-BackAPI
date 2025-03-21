<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use App\Models\Auth\Person;
use App\Models\Other\LegalDocumentsType;
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
        $search = $request->get("search");

        $users = User::join('persons', 'users.id', '=', 'persons.id')
            ->join('legal_documents_types', 'persons.legal_document_type_id', '=', 'legal_documents_types.id')
            ->where(DB::raw("COALESCE(persons.name, '') || ' ' || COALESCE(persons.lastname, '') || ' ' || COALESCE(users.email, '') || ' ' || COALESCE(users.avatar, '')"), "ilike", "%" . $search . "%")
            // ->where('users.is_active', true) // Solo  trae a los usuarios que esten activos
            ->orderBy("users.is_active", "desc")
            ->select('users.*', 'persons.*', 'legal_documents_types.code as type_document')
            ->get();

        return response()->json([
            "users" => UserCollection::make($users),
            "roles" => Role::where("name", "not ilike", "%veterinario%")->get()->map(function($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name,
                ];
            })
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Datos del request:', $request->all());
        $data = $request->all();

        // Procesar la imagen y guardar la ruta en 'avatar'
        if ($request->hasFile('imagen')) {
            $path = Storage::disk('public')->putFile('users', $request->file('imagen'));
            $data['avatar'] = Storage::url($path);
        }

        // Encriptar la contraseña
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        // Formatear la fecha de cumpleaños (si se envía)
        if (isset($data['birthday'])) {
            $data['birthday'] = $data['birthday'] . " 00:00:00";
        }

        $documentType = LegalDocumentsType::where('code', $request->type_document)->first();

        try {
            $user = new User();
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role_id = $data['role_id'];
            $user->is_active = true;
            $user->avatar = $data['avatar'] ?? null;
            $user->save();

            $person = new Person();
            $person->id = $user->id;
            $person->name = $data['name'];
            $person->lastname = $data['lastname'];
            $person->legal_document_type_id = $documentType->id;
            $person->document_number = $data['document_number'];
            $person->phone = $data['phone'];
            $person->gender = $data['gender'];
            $person->birthday = $data['birthday'];
            $person->save();

            return response()->json([
                "message" => 200,
                "user" => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al insertar datos: ' . $e->getMessage());
            return response()->json([
                "message" => 403,
                "message_text" => "Error al insertar datos"
            ]);
        }
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
    public function updateInfo(Request $request, string $id)
    {
        Log::info('Datos del request:', $request->all());

        $documentType = LegalDocumentsType::where('code', $request->type_document)->first();

        $user = Person::findOrFail($id);

        if($request->birthday){
            $request->request->add(["birthday" => $request->birthday." 00:00:00"]);
        }

        // Actualiza los campos del usuario existente
        $user->legal_document_type_id = $documentType->id;
        $user->document_number = $request->document_number;
        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->birthday = $request->birthday;
        $user->save();

        return response()->json([
            "message" => 200,
        ]);
    }

    public function updateCredentials(Request $request, string $id)
    {
        Log::info('Datos del reques2222t:', $request->all());

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

        if ($user->is_active == true){
            // En lugar de eliminar el usuario, actualiza el campo is_active a false
            $user->is_active = false;
            $user->save();
        }else{
            $user->is_active = true;
            $user->save();
        }

        return response()->json([
            "message" => 200,
        ]);
    }
}
