<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use App\Models\Auth\Person;
use App\Models\Auth\Medical;
use App\Models\Other\LegalDocumentsType;
use App\Models\Other\MedicalSpecialty;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;


class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
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
    public function store(Request $request): JsonResponse
    {
        Log::info("Llegué a crear al usuario", $request->all());
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
            DB::beginTransaction();

            // Primero creamos la persona
            $person = new Person();
            $person->name = $data['name'];
            $person->lastname = $data['lastname'];
            $person->second_name = $data['second_name'] ?? null;
            $person->second_lastname = $data['second_lastname'] ?? null;
            $person->legal_document_type_id = $documentType->id;
            $person->document_number = $data['document_number'];
            $person->phone = $data['phone'];
            $person->gender = $data['gender'];
            $person->birthday = $data['birthday'];
            $person->save();

            // Luego creamos el usuario con el ID de la persona
            $user = new User();
            $user->id = $person->id; // Usamos el ID de la persona
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role_id = $data['role_id'];
            $user->is_active = true;
            $user->avatar = $data['avatar'] ?? null;
            $user->save();

            DB::commit();

            return response()->json([
                "message" => 200,
                "user" => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al insertar datos: ' . $e->getMessage());
            return response()->json([
                "message" => 403,
                "message_text" => "Error al insertar datos: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($filename){
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
    public function updateInfo(Request $request, string $id){
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

    public function updateCredentials(Request $request, string $id){
        Log::info("actualizar credenciales");
        Log::info($request->all());

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
    public function destroy(string $id){
        $user = User::findOrFail($id);
        $person = Person::findOrFail($id);

        if ($user->is_active == true){
            // En lugar de eliminar el usuario, actualiza el campo is_active a false
            $user->is_active = false;
            $user->save();

            // Actualizar también el estado en la tabla persons
            $person->is_active = false;
            $person->save();
        }else{
            $user->is_active = true;
            $user->save();

            // Actualizar también el estado en la tabla persons
            $person->is_active = true;
            $person->save();
        }

        return response()->json([
            "message" => 200,
        ]);
    }

    public function createPatient(Request $request){
        Log::info("Llegué a crear al paciente");
        Log::info('Datos del request:', $request->all());

        $data = $request->all();

        try {
            $user = new User();
            // Asignamos el id del usuario para que coincida con el de la persona existente.
            $user->id = $data['person_id'];
            $user->email = $data['email']; // Puede ser null si es opcional
            $user->password = bcrypt($data['password']);

            // Obtener el role_id a partir del nombre del rol
            $role = Role::where('name', $data['role'])->first();
            if (!$role) {
                return response()->json([
                    "message" => 404,
                    "message_text" => "Role not found"
                ], 404);
            }
            $user->role_id = $role->id;
            $user->is_active = true;
            // Puedes agregar aquí el procesamiento de avatar, foto, etc. si es necesario.
            $user->save();

            return response()->json([
                "message" => 200,
                "user" => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al insertar datos: ' . $e->getMessage());
            return response()->json([
                "message" => 403,
                "message_text" => "Error al insertar datos"
            ], 403);
        }
    }

    public function getPatient(Request $request){
        $person = Person::where("id",$request->id)->first();
        if($person){
            return response()->json([
                "message" => 200,
                "person" => $person
            ]);
        }
        return response()->json([
            "message" => 404,
            "message_text" => "No se encontró el paciente"
        ],404);
    }

    public function userValidation(Request $request){
        Log::info('Datos del request:', $request->all());

        $user = User::find($request->id);
        if ($user) {
            return $this->getPatient($request);
        }

        return response()->json([
            "message" => 404,
            "message_text" => "No se encontró el usuario"
        ], 404);
    }


    // ------------------------------------------------------- >>
    // SECCIÓN PARA CREAR USUARIOS ASISTENCIALES
    // ------------------------------------------------------- >>

    public function createAssistant(Request $request): JsonResponse
    {
        Log::info('Datos del request asistencial:', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'second_lastname' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'type_document' => 'required|string|max:10',
            'document_number' => 'required|string|unique:persons,document_number',
            'birthday' => 'required|date',
            'gender' => 'required|string|in:M,F,T',
            'role_id' => 'required|integer',
            'password' => 'required|string|min:4',
            'medical_registration' => 'required|string',
            'medical_specialty' => 'required|array',
            'imagen' => 'required|file|image|max:2048',
            'signature' => 'required|file|image|max:2048',
        ]);

        DB::beginTransaction();

        try {

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }
            // 1. Guardar imagen del avatar
            $avatarPath = $request->file('imagen')->store('users', 'public');
            Log::info('Avatar guardado en: ' . $avatarPath);

            // 2. Crear usuario como persona
            $person = Person::create([
                'name' => $validated['name'],
                'second_name' => $validated['second_name'] ?? null,
                'lastname' => $validated['lastname'],
                'second_lastname' => $validated['second_lastname'] ?? null,
                'email_patient' => $validated['email'],
                'phone' => $validated['phone'],
                'avatar' => $avatarPath,
                'birthday' => $validated['birthday'],
                'gender' => $validated['gender'],
                'document_number' => $validated['document_number'],
                'legal_document_type_id' => $validated['type_document'],
            ]);
            Log::info('Persona creada con ID: ' . $person->id);

            // 3. Crear usuario con mismo ID
            $user = User::create([
                'id' => $person->id,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'avatar' => $avatarPath,
                'role_id' => $validated['role_id'],
                'is_active' => true,
            ]);
            Log::info('Usuario creado con ID: ' . $user->id);

            // 4. Guardar firma
            $signaturePath = $request->file('signature')->store('signature', 'public');
            Log::info('Firma guardada en: ' . $signaturePath);

            // 5. Crear registro médico
            $medical = Medical::create([
                'id' => $person->id,
                'medical_record' => $validated['medical_registration'],
                'signature' => $signaturePath,
            ]);
            Log::info('Registro médico creado con ID: ' . $medical->id);

            // 6. Ciclar especialidades y asignarlas al médico
            foreach ($validated['medical_specialty'] as $specialtyId) {
                MedicalSpecialty::create([
                    'medical_id' => $medical->id,
                    'specialty_id' => $specialtyId,
                ]);
                Log::info("Especialidad asignada: medical_id={$medical->id}, specialty_id={$specialtyId}");
            }

            DB::commit();

            Log::info('Asistente creado exitosamente con ID de usuario: ' . $user->id);

            return response()->json([
                'message' => 'Asistente creado exitosamente',
                'data' => [
                    'user' => $user,
                    'person' => $person,
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear asistente: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ocurrió un error al crear el asistente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProfile(Request $request): JsonResponse {
        Log::info('Datos del request para obtener perfil:', $request->all());

        $id = $request->id;

        // Buscar la persona por ID
        $person = Person::find($id);

        // Buscar el usuario por ID
        $user = User::find($id);

        if (!$person || !$user) {
            return response()->json([
                "message" => 404,
                "message_text" => "No se encontró el perfil solicitado"
            ], 404);
        }

        // Buscar información del tipo de documento
        $documentType = LegalDocumentsType::find($person->legal_document_type_id);

        // Buscar si es un asistente médico
        $medical = Medical::find($id);

        // Obtener solo los campos específicos
        $personData = [
            'legal_document_type_id' => $person->legal_document_type_id,
            'document_type_name' => $documentType ? $documentType->name : null,
            'document_number' => $person->document_number,
            'name' => $person->name,
            'second_name' => $person->second_name,
            'lastname' => $person->lastname,
            'second_lastname' => $person->second_lastname,
            'phone' => $person->phone,
            'birthday' => $person->birthday,
            'gender' => $person->gender
        ];

        $userData = [
            'email' => $user->email,
            'avatar' => $user->avatar
        ];

        $assistantData = null;
        if ($medical) {
            $assistantData = [
                'medical_record' => $medical->medical_record,
                'signature' => $medical->signature,
                'specialties' => $medical->specialties()->pluck('specialty_id')
            ];
        }

        Log::info("Perfil encontrado:", [
            'person' => $personData,
            'user' => $userData,
            'assistant' => $assistantData
        ]);

        return response()->json([
            "message" => 200,
            "person" => $personData,
            "user" => $userData,
            "assistant" => $assistantData
        ]);
    }

    public function updateProfile(Request $request): JsonResponse {
        Log::info("Datos del request para actualizar perfil:", $request->all());

        $id = $request->id;

        try {
            DB::beginTransaction();

            // Actualizar datos de la persona
            $person = Person::findOrFail($id);
            $person->name = $request->name;
            $person->second_name = $request->second_name;
            $person->lastname = $request->lastname;
            $person->second_lastname = $request->second_lastname;
            $person->phone = $request->phone;
            $person->document_number = $request->document_number;
            $person->legal_document_type_id = $request->legal_document_type_id;
            $person->birthday = $request->birthday;
            $person->gender = $request->gender;
            $person->save();

            // Actualizar datos del usuario
            $user = User::findOrFail($id);
            $user->email = $request->email;

            // Manejar el archivo de avatar si se proporciona
            if($request->hasFile("avatar")){
                if($user->avatar){
                    Storage::delete($user->avatar);
                }
                $path = Storage::putFile("users", $request->file("avatar"));
                $user->avatar = $path;
            }

            $user->save();

            DB::commit();

            return response()->json([
                "message" => 200,
                "message_text" => "Perfil actualizado correctamente"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar perfil: " . $e->getMessage());

            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar el perfil: " . $e->getMessage()
            ], 500);
        }
    }

    public function updateProfileCredentials(Request $request): JsonResponse {
        Log::info("Datos del request para actualizar credenciales:", $request->all());

        $id = $request->id;
        $currentPassword = $request->current_password;
        $newPassword = $request->new_password;

        try {
            // Buscar el usuario
            $user = User::findOrFail($id);

            // Verificar si la contraseña actual coincide
            if (!Hash::check($currentPassword, $user->password)) {
                return response()->json([
                    "message" => 400,
                    "message_text" => "La contraseña actual no es correcta"
                ], 400);
            }

            // Actualizar la contraseña
            $user->password = bcrypt($newPassword);
            $user->save();

            return response()->json([
                "message" => 200,
                "message_text" => "Contraseña actualizada correctamente"
            ]);

        } catch (\Exception $e) {
            Log::error("Error al actualizar credenciales: " . $e->getMessage());

            return response()->json([
                "message" => 500,
                "message_text" => "Error al actualizar credenciales: " . $e->getMessage()
            ], 500);
        }
    }

}
