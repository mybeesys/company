<?php

namespace Modules\Product\Models;

use Modules\Product\Models\Category;
use Modules\Product\Models\Subcategory;
use Modules\Product\Models\Product;

class TreeCategoryBuilder 
{
  public $Tree = [];
  private $subcategoriesIds =[];
  

  public function buildCategoryTree()
  {
      $treeId = 0;
      $categories = Category::all();
      foreach ($categories as $category) {
        $CategoryTree = new CategoryTreeObject();
        $CategoryTree->key = $treeId;
        $CategoryTree->data = new TreeData();
        $CategoryTree->data->name_ar = $category->name_ar;
        $CategoryTree->data->name_en = $category->name_en;
        $CategoryTree->data->cost = null;
        $CategoryTree->data->price = null;
        $CategoryTree->data->order = $category->order;
        $CategoryTree->data->id = $category->id;
        $CategoryTree->data->type = "Category";
        $CategoryTree->data->active = $category->active;
        $CategoryTree->children  = $this->buildSubCategoryTree( $category->subcategories , $CategoryTree->key);
        $Tree[] = $CategoryTree;
        $treeId = $treeId+1;
       }

       return $Tree;
  }

  public function buildSubCategoryTree($subcategories , $parentTreeId)
  {   
      $childArray=[];
      $treeId = 0 ;
      if($subcategories)
      {
       foreach ($subcategories as $subcategory) {
         if(!array_search($subcategory->id,$this->subcategoriesIds))
            {
              $CategoryTree = new CategoryTreeObject();
              $CategoryTree->key = $parentTreeId ."-". $treeId;
              $CategoryTree->data = new TreeData();
              $CategoryTree->data->name_ar = $subcategory->name_ar;
              $CategoryTree->data->name_en = $subcategory->name_en;
              $CategoryTree->data->cost = null;
              $CategoryTree->data->price = null;
              $CategoryTree->data->order = $subcategory->order;
              $CategoryTree->data->id = $subcategory->id;
              $CategoryTree->data->parent_id = $subcategory->parent_id;
              $CategoryTree->data->category_id = $subcategory->category_id;
              $CategoryTree->data->type = "SubCategory";
              $CategoryTree->data->active = $subcategory->active;
              $this->subcategoriesIds[] = $subcategory->id;
              $CategoryTree->children = array_merge($this->buildProductTree( $subcategory , $CategoryTree->key) ,$this->buildSubCategoryTree( $subcategory->children , $CategoryTree->key)) ;
              $childArray[] = $CategoryTree;
              $treeId = $treeId+1;
            }
         }
       }
       return $childArray;
  }

  public function buildProductTree($subCategory , $parentTreeId)
  {   
      $childArray=[];
      $treeId = 0 ;
      $products = $subCategory->products;
      foreach ($products as $product) {
        $CategoryTree = new CategoryTreeObject();
        $CategoryTree->key = $parentTreeId ."-". $treeId;
        $CategoryTree->data = new TreeData();
        $CategoryTree->data->name_ar = $product->name_ar;
        $CategoryTree->data->name_en = $product->name_en;
        $CategoryTree->data->cost = $product->cost;
        $CategoryTree->data->price = $product->price;
        $CategoryTree->data->order = null;
        $CategoryTree->data->id = $product->id;
        $CategoryTree->data->parent_id = $product->subcategory_id;
        $CategoryTree->data->category_id = $product->category_id;
        $CategoryTree->data->SKU = $product->SKU;
        $CategoryTree->data->barcode = $product->barcode; 
        $CategoryTree->data->class = $product->class;
        $CategoryTree->data->cost = $product->cost;
        $CategoryTree->data->price = $product->price;
        $CategoryTree->data->type = "Product";
        $CategoryTree->data->active = $product->active;
        $childArray[] = $CategoryTree;
        $treeId = $treeId+1;
       }
       return $childArray;
  }
}

class CategoryTreeObject
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
    public   $name_ar;
    public   $name_en;
    public   $cost;
    public   $price;
    public   $order;
    public   $id;
}




