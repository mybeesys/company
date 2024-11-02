<?php
namespace Modules\Product\Models;

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
?>