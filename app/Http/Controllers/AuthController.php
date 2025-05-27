<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Auth\Person;
use App\Models\User;
use App\Models\LoginLog;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        // Obtenemos las credenciales de email y password desde la request.
        $credentials = request(['email', 'password']);

        // Agregamos la condición de que el usuario debe estar activo.
        $credentials['is_active'] = true;

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized or inactive account'], 401);
        }

        // Obtener el usuario autenticado
        $user = auth()->user();

        SystemLog::create([
            'user_id' => $user->id,
            'guard_name' => 'login',
            'action' => 'login',
            'description' => 'Authenticated in the system',
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $role = auth('api')->user()->role;
        $rolePermissions = $role ? $role->permissions->pluck('name') : collect([]);

        $baseUrl = rtrim(env("APP_URL"), '/');
        $avatar = auth('api')->user()->avatar;
        if ($avatar) {
            if (substr($avatar, 0, 8) === '/storage') {
                $avatar_url = $baseUrl . $avatar;
            } else {
                $avatar_url = $baseUrl . "/storage/" . $avatar;
            }
        } else {
            $avatar_url = null;
        }

        // Modelo personas
        $userId = auth('api')->user()->id;
        $person = Person::findOrFail($userId);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            "User" => [
                "id" => auth('api')->user()->id,
                "email" => auth('api')->user()->email,
                "avatar_url" => $avatar_url,
                "role" => auth('api')->user()->role,
                "is_active" => auth('api')->user()->is_active,
                "permissions" => $rolePermissions,
                "name" =>  $person->name,
                "lastname" =>  $person->lastname,
            ]
        ]);
    }
}
