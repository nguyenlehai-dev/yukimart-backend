<?php

namespace App\Modules\Document\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentCollection extends ResourceCollection
{
    public $collects = DocumentResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
