<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PermissionCollection extends ResourceCollection
{
    public $collects = PermissionResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
