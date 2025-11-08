<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
{
    public function toArray($request)
    {
        $company = $this->whenLoaded('company');
        $garage  = $this->whenLoaded('garage');

        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $this->phone,
            'role'                 => $this->role,
            'status'               => (int) $this->status,
            'must_change_password' => (bool) $this->must_change_password,
            'company' => $company ? [
                'id'   => $company->id,
                'name' => $company->name,
            ] : null,
            'garage' => $garage ? [
                'id'   => $garage->id,
                'name' => $garage->name,
            ] : null,
        ];
    }
}
