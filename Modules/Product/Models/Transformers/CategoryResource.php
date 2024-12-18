<?php


namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        if(isset($this->data->id))
            return [
                'id' => $this->data->id,
                'parent_id' => $this->data->type == 'category' ? null : 
                                ($this->data->type == 'subcategory' ? 
                                (isset($this->data->parent_id) ? $this->data->parent_id : $this->data->category_id)
                                : $this->data->subcategory_id),
                'type' => $this->data->type,
                'name_ar' => $this->data->name_ar,
                'name_en' => $this->data->name_en,
                'order' => $this->data->order,
                'children' => isset($this->children) ? CategoryResource::collection($this->children) : []
            ];
    }
}