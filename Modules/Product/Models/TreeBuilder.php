<?php

namespace Modules\Product\Models;

use App\Models\Product;
use Modules\Product\Models\Product as ModelsProduct;

class TreeBuilder
{
  // The buildTreeFromArray method seems fine and doesn't need changes for this specific request.
  public function buildTreeFromArray($list, $parentKey, $defaultType, $defaultParrentKey, $defaultType1, $defaultParrentKey1)
  {
    $Tree = [];
    $treeId = "0";
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
      if (isset($item["name"])) {
        $treeObject->data->name = $item["name"];
      }
      $treeObject->data->id = $item["id"] ?? null;
      $treeObject->data->type = $item["type"] ?? null;
      $treeObject->data->parentKey = $item["parentKey"] ?? null;
      $treeObject->data->parentKey1 = $item["parentKey1"] ?? null;
      $currentType = $item["type"] ?? null;
      $currentParentKey = $item["parentKey"] ?? null;
      $currentTreeId = $treeId;
      if (isset($item["children"])) {
        $treeObject->children  = $this->buildTreeFromArray(
          $item["children"],
          $treeObject->key,
          $item["childType"] ?? null,
          $item["childKey"] ?? null,
          $item["childType1"] ?? null,
          $item["childKey1"] ?? null
        );
      }
      $treeId = $currentTreeId;
      $Tree[] = $treeObject;
      $treeId = $treeId + 1;
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
    $treeId = $treeId + 1;
    return $Tree;
  }

  // ...
  // This section is a part of the TreeBuilder.php file

  public function buildTree($list, $parentKey, $defaultType, $defaultParrentKey, $defaultType1, $defaultParrentKey1)
  {
    $Tree = [];
    $treeId = "0";
    $currentType = $defaultType;
    $currentType1 = $defaultType1;
    $currentParentKey = $defaultParrentKey;
    $currentParentKey1 = $defaultParrentKey1;

    foreach ($list as $item) {
      $treeObject = new TreeObject();
      $treeObject->key = $parentKey != null ? $parentKey . '-' . $treeId : $treeId;
      $treeObject->data = new TreeData();

      foreach ($item->getFillable() as $key) {
        $treeObject->data->$key = $item->$key;
      }

      if (isset($item->establishment)) {
        $treeObject->data->establishment = $item->establishment;
      }

      if (isset($item->name)) {
        $treeObject->data->name = $item->name;
      }

      $treeObject->data->id = $item->id;
      $treeObject->data->type = $item->type;
      $treeObject->data->parentKey = $item->parentKey;
      $treeObject->data->parentKey1 = $item->parentKey1;
      $currentType = $item->type;
      $currentParentKey = $item->parentKey;
      $currentTreeId = $treeId;

      $allChildren = collect();

      if ($item->children) {
        $allChildren = $allChildren->merge($item->children);
      }
      if ($item->children1) {
        $allChildren = $allChildren->merge($item->children1);
      }

      if ($item->type == 'variable') {
        $products = ModelsProduct::where('parent_id', $item->id)
          ->where('type', 'product')
          ->get();
        if ($products->count() > 0) {
          $allChildren = $allChildren->merge($products);
        }
      }

      if ($allChildren->count() > 0) {
        $treeObject->children = $this->buildTree(
          $allChildren,
          $treeObject->key,
          $item->childType,
          $item->childKey,
          $item->childType1,
          $item->childKey1
        );
        if ($treeObject->children) {
          foreach ($treeObject->children as $child) {
            $child->data->category_id = $item->id;
            $child->data->parent_id = null;
          }
        }
      } else {
        if ($item->type === 'product') {
          $treeObject->children = [];
        } else {
          $emptyChild = new TreeObject();
          $emptyChild->key = $treeObject->key . '-' . '0';
          $emptyChild->data = new TreeData();
          $emptyChild->data->empty = 'Y';
          $emptyChild->data->type = 'subcategory';
          $emptyChild->data->category_id = $item->id;
          $treeObject->children = [$emptyChild];
        }
      }

      $treeId = $currentTreeId;
      $Tree[] = $treeObject;
      $treeId = $treeId + 1;
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
    $treeId = $treeId + 1;

    return $Tree;
  }
}

  // ...
