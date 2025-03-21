<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            'name' => $this->resource->name,
            'lastname' => $this->resource->lastname,
            "full_name" => $this->resource->name.' '.$this->resource->lastname,
            'email' => $this->resource->email,
            "gender" => $this->resource->gender,
            'role_id' => $this->resource->role_id,
            "role" => [
                "name" => $this->resource->role->name,
            ],
            "role_name" => $this->resource->role->name,
            // http://127.0.0.1:8000/storage/imagen1.png
            "avatar" => $this->resource->avatar ? url('/storage/users/' . basename($this->resource->avatar)) : NULL,
            "type_document" => $this->resource->type_document,
            "document_number" => $this->resource->document_number,
            "phone"=> $this->resource->phone,
            "designation"=> $this->resource->designation,
            "birthday" => $this->resource->birthday ? Carbon::parse($this->resource->birthday)->format("Y/m/d") : null,
            "is_active" => $this->resource->is_active,
        ];
    }
}
