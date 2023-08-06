<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function noRecursive()
    {
        return [
            'id' => $this['id'],
            'attributes' => [
                'name' => $this['name'],
                'email' => $this['email'],
            ]
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        if ($this->manager_id) {
            return [
                'id' => $this->id,
                'attributes' => [
                    'name' => $this->name,
                    'email' => $this->email,
                    'manager' => UserResource::make($this->manager)->noRecursive(),
                ]
            ];
        } else {
            return [
                'id' => $this->id,
                'attributes' => [
                    'name' => $this->name,
                    'email' => $this->email,
                    'workers' => array_map(function ($worker) {
                        return UserResource::make($worker)->noRecursive();
                    }, $this->workers->toArray()),
                ]
            ];
        }
    }
}
