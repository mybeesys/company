<?php


namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        if(isset($this->data->id))
            return [
                'type' => $this->data->type,
                'name_ar' => $this->data->name_ar,
                'name_en' => $this->data->name_en,
                'order' => $this->data->order,
                'children' => isset($this->children) ? CategoryResource::collection($this->children) : []
            ];
    }
}