<?php
return array (
  'title' => 'Harvest records',
  'table' => 'harvests',
  'alias' => 'h',
  'pk' => 'harvest_id',
  'search' => 
  array (
    0 => 'farm_name',
    1 => 'crop_name',
    2 => 'grade_name',
    3 => 'remark_description',
  ),
  'fields' => 
  array (
    'planting_id' => 
    array (
      0 => 'Planting record',
      1 => 'lookup',
      2 => true,
      3 => 'plantings p JOIN farms f ON f.farm_id=p.farm_id JOIN crops c ON c.crop_id=p.crop_id',
      4 => 'p.planting_id',
      5 => 'CONCAT(\'#\', p.planting_id, \' · \', f.farm_name, \' · \', c.crop_name, \' · \', p.planting_date)',
    ),
    'harvest_date' => 
    array (
      0 => 'Harvest date',
      1 => 'date',
      2 => true,
      3 => 
      array (
      ),
    ),
    'quantity_kg' => 
    array (
      0 => 'Quantity (kg)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'min' => 0,
        'max' => 9999999,
      ),
    ),
    'selling_price_per_kg' => 
    array (
      0 => 'Selling price per kg (₱)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'min' => 0,
        'max' => 100000,
      ),
    ),
    'grade_id' => 
    array (
      0 => 'Quality grade',
      1 => 'lookup',
      2 => false,
      3 => 'quality_grades',
      4 => 'grade_id',
      5 => 'grade_name',
    ),
    'remark_id' => 
    array (
      0 => 'Harvest remark',
      1 => 'lookup',
      2 => false,
      3 => 'harvest_remarks',
      4 => 'remark_id',
      5 => 'remark_description',
    ),
    'notes' => 
    array (
      0 => 'Notes',
      1 => 'textarea',
      2 => false,
      3 => 
      array (
        'max' => 200,
      ),
    ),
  ),
  'columns' => 
  array (
    'harvest_id' => 'ID',
    'crop_name' => 'Crop',
    'farm_name' => 'Farm',
    'harvest_date' => 'Harvest date',
    'quantity_kg' => 'Quantity (kg)',
    'selling_price_per_kg' => 'Price/kg',
    'grade_name' => 'Grade',
    'remark_description' => 'Remark',
  ),
  'filters' => 
  array (
    'grade_id' => 
    array (
      'label' => 'Quality grade',
      'type' => 'lookup',
      'table' => 'quality_grades',
      'key' => 'grade_id',
      'text' => 'grade_name',
      'col' => 'h.grade_id',
    ),
    'remark_id' => 
    array (
      'label' => 'Remark',
      'type' => 'lookup',
      'table' => 'harvest_remarks',
      'key' => 'remark_id',
      'text' => 'remark_description',
      'col' => 'h.remark_id',
    ),
  ),
  'sortmap' => 
  array (
    'crop_name' => 'c.crop_name',
    'farm_name' => 'f.farm_name',
    'grade_name' => 'qg.grade_name',
    'remark_description' => 'hr.remark_description',
  ),
);
