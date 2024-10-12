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
}

class TreeObject
{
  public $key;
  public $data;
  public $children;

  public function  __construct()
  {
    $data = new TreeData();
    $children =[];
  }
}


class TreeData
{
}




