<?php

namespace Modules\Product\Models;


class TreeBuilder 
{
  
  

  public function buildTree($list, $parentKey, $defaultType, $defaultParrentKey)
  {
      $Tree = [];
      $treeId = "0";
      $currentType = $defaultType;
      $currentParentKey = $defaultParrentKey;
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
        $currentType = $item->type;
        $currentParentKey = $item->parentKey;
        if($item->childs){
            $treeObject->children  = $this->buildTree( $item->childs , $treeId, $item->childType, $item->childKey);
        }
        $Tree[] = $treeObject;
        $treeId = $treeId+1;
       }
       $finalObject = new TreeObject();
       $finalObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
       $finalObject->data = new TreeData();
       $finalObject->data->type = $currentType;
       $finalObject->data->empty = 'Y';
       $finalObject->data->parentKey = $currentParentKey;
       $Tree[] = $finalObject;
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




