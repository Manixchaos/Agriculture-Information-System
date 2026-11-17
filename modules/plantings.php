<?php
return array (
  'title' => 'Planting records',
  'table' => 'plantings',
  'alias' => 'p',
  'pk' => 'planting_id',
  'search' => 
  array (
    0 => 'farm_name',
    1 => 'crop_name',
    2 => 'status_name',
  ),
  'fields' => 
  array (
    'farm_id' => 
    array (
      0 => 'Farm',
      1 => 'lookup',
      2 => true,
      3 => 'farms',
      4 => 'farm_id',
      5 => 'farm_name',
    ),
    'crop_id' => 
    array (
      0 => 'Crop',
      1 => 'lookup',
      2 => true,
      3 => 'crops',
      4 => 'crop_id',
      5 => 'CONCAT(crop_name, \' — \', variety)',
    ),
    'planting_date' => 
    array (
      0 => 'Planting date',
      1 => 'date',
      2 => true,
      3 => 
      array (
      ),
    ),
    'area_planted' => 
    array (
      0 => 'Area planted (ha)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'min' => 0.01,
        'max' => 10000,
      ),
    ),
    'seed_quantity_kg' => 
    array (
      0 => 'Seed quantity (kg)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'min' => 0,
        'max' => 1000000,
      ),
    ),
    'expected_harvest_date' => 
    array (
      0 => 'Expected harvest date',
      1 => 'date',
      2 => true,
      3 => 
      array (
      ),
    ),
    'status_id' => 
    array (
      0 => 'Status',
      1 => 'lookup',
      2 => true,
      3 => 'planting_statuses',
      4 => 'status_id',
      5 => 'status_name',
    ),
  ),
  'columns' => 
  array (
    'planting_id' => 'ID',
    'farm_name' => 'Farm',
    'crop_name' => 'Crop',
    'planting_date' => 'Planted',
    'area_planted' => 'Area (ha)',
    'expected_harvest_date' => 'Expected harvest',
    'status_name' => 'Status',
  ),
  'filters' => 
  array (
    'status_id' => 
    array (
      'label' => 'Status',
      'type' => 'lookup',
      'table' => 'planting_statuses',
      'key' => 'status_id',
      'text' => 'status_name',
      'col' => 'p.status_id',
    ),
    'farm_id' => 
    array (
      'label' => 'Farm',
      'type' => 'lookup',
      'table' => 'farms',
      'key' => 'farm_id',
      'text' => 'farm_name',
      'col' => 'p.farm_id',
    ),
    'crop_id' => 
    array (
      'label' => 'Crop',
      'type' => 'lookup',
      'table' => 'crops',
      'key' => 'crop_id',
      'text' => 'crop_name',
      'col' => 'p.crop_id',
    ),
  ),
  'sortmap' => 
  array (
    'farm_name' => 'f.farm_name',
    'crop_name' => 'c.crop_name',
    'status_name' => 'ps.status_name',
  ),
);
