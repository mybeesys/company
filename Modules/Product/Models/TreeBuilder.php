<?php

namespace Modules\Product\Models;

class TreeBuilder 
{

  public function buildTree($list, $parentKey, $defaultType, $defaultParrentKey, $defaultType1, $defaultParrentKey1)
  {
      $Tree = [];
      $treeId ="0";
      $currentType = $defaultType;
      $currentType1 = $defaultType1;
      $currentParentKey = $defaultParrentKey;
      $currentParentKey1 = $defaultParrentKey1;
      foreach ($list as $item) {
        $treeObject = new TreeObject();
        $treeObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
        $treeObject->data = new TreeData();
        foreach($item->getFillable() as $key) {
              $treeObject->data->$key = $item->$key;
        }
        if(isset($item->name)){
          $treeObject->data->name = $item->name;
        }
        $treeObject->data->id = $item->id;
        $treeObject->data->type = $item->type;
        $treeObject->data->parentKey = $item->parentKey;
        $treeObject->data->parentKey1 = $item->parentKey1;
        $currentType = $item->type;
        //$currentType1 = $item->type1;
        $currentParentKey = $item->parentKey;
        //$currentParentKey1 = $item->parentKey1;
        $currentTreeId = $treeId;
        if($item->children){
          if($item->children1){
            $allChildern = $item->children->toBase()->merge($item->children1);
            $treeObject->children  = $this->buildTree( $allChildern , $treeObject->key, $item->childType, $item->childKey, $item->childType1, $item->childKey1);
          }
          else{
            $treeObject->children  = $this->buildTree( $item->children , $treeObject->key, $item->childType, $item->childKey, $item->childType1, $item->childKey1);
          }
        }
        // if($item->children1){
        //   if(isset($treeObject->children) && count($treeObject->children) > 0){
        //     $treeObject->children = array_merge($treeObject->children , $this->buildTree( $item->children1 ,  $treeObject->key, $item->childType1, $item->childKey1));
        //   }
        //   else
        //   $treeObject->children = $this->buildTree( $item->children1 ,$treeObject->key, $item->childType1, $item->childKey1);
        // }
        $treeId = $currentTreeId;
        $Tree[] = $treeObject;
        $treeId = $treeId+1;
       }
       $finalObject = new TreeObject();
       $finalObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
       $finalObject->data = new TreeData();
       $finalObject->data->type = $currentType;
       $finalObject->data->type1 = $currentType1;
       $finalObject->data->empty = 'Y';
       $finalObject->data->parentKey = $currentParentKey;
       $finalObject->data->parentKey1 = $currentParentKey1;
       $Tree[] = $finalObject;
       $treeId = $treeId+1;
       return $Tree;
  }

  public function buildTreeFromArray($list, $parentKey, $defaultType, $defaultParrentKey, $defaultType1, $defaultParrentKey1)
  {
      $Tree = [];
      $treeId ="0";
      $currentType = $defaultType;
      $currentType1 = $defaultType1;
      $currentParentKey = $defaultParrentKey;
      $currentParentKey1 = $defaultParrentKey1;
      foreach ($list as $item) {
        $treeObject = new TreeObject();
        $treeObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
        $treeObject->data = new TreeData();
        foreach ($item as $key => $value) {
          $treeObject->data->$key = $value;
        }
        if(isset($item["name"])){
          $treeObject->data->name = $item["name"];
        }
        $treeObject->data->id = $item["id"] ?? null;
        $treeObject->data->type = $item["type"] ?? null;
        $treeObject->data->parentKey = $item["parentKey"] ?? null;
        $treeObject->data->parentKey1 = $item["parentKey1"] ?? null;
        $currentType = $item["type"] ?? null;
        $currentParentKey = $item["parentKey"] ?? null;
        $currentTreeId = $treeId;
        if(isset($item["children"])){
            $treeObject->children  = $this->buildTreeFromArray( $item["children"] , $treeObject->key,
             $item["childType"] ?? null, $item["childKey"] ?? null, $item["childType1"] ?? null
             , $item["childKey1"] ?? null);
        }
        $treeId = $currentTreeId;
        $Tree[] = $treeObject;
        $treeId = $treeId+1;
       }
       $finalObject = new TreeObject();
       $finalObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
       $finalObject->data = new TreeData();
       $finalObject->data->type = $currentType;
       $finalObject->data->type1 = $currentType1;
       $finalObject->data->empty = 'Y';
       $finalObject->data->parentKey = $currentParentKey;
       $finalObject->data->parentKey1 = $currentParentKey1;
       $Tree[] = $finalObject;
       $treeId = $treeId+1;
       return $Tree;
  }
}



