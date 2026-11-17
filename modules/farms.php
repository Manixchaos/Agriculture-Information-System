<?php
return array (
  'title' => 'Farms',
  'table' => 'farms',
  'alias' => 'f',
  'pk' => 'farm_id',
  'search' => 
  array (
    0 => 'farm_name',
    1 => 'location',
  ),
  'fields' => 
  array (
    'farmer_id' => 
    array (
      0 => 'Farmer',
      1 => 'lookup',
      2 => true,
      3 => 'farmers',
      4 => 'farmer_id',
      5 => 'CONCAT(first_name, \' \', last_name)',
    ),
    'farm_name' => 
    array (
      0 => 'Farm name',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 100,
      ),
    ),
    'location' => 
    array (
      0 => 'Location',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 150,
      ),
    ),
    'area_hectares' => 
    array (
      0 => 'Area (hectares)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'min' => 0.01,
        'max' => 10000,
      ),
    ),
    'soil_type_id' => 
    array (
      0 => 'Soil type',
      1 => 'lookup',
      2 => true,
      3 => 'soil_types',
      4 => 'soil_type_id',
      5 => 'soil_type_name',
    ),
    'irrigation_type_id' => 
    array (
      0 => 'Irrigation type',
      1 => 'lookup',
      2 => true,
      3 => 'irrigation_types',
      4 => 'irrigation_type_id',
      5 => 'irrigation_type_name',
    ),
  ),
  'columns' => 
  array (
    'farm_id' => 'ID',
    'farm_name' => 'Farm',
    'farmer_name' => 'Farmer',
    'location' => 'Location',
    'area_hectares' => 'Area (ha)',
    'soil_type_name' => 'Soil',
    'irrigation_type_name' => 'Irrigation',
  ),
  'filters' => 
  array (
    'irrigation_type_id' => 
    array (
      'label' => 'Irrigation type',
      'type' => 'lookup',
      'table' => 'irrigation_types',
      'key' => 'irrigation_type_id',
      'text' => 'irrigation_type_name',
      'col' => 'f.irrigation_type_id',
    ),
    'soil_type_id' => 
    array (
      'label' => 'Soil type',
      'type' => 'lookup',
      'table' => 'soil_types',
      'key' => 'soil_type_id',
      'text' => 'soil_type_name',
      'col' => 'f.soil_type_id',
    ),
  ),
  'sortmap' => 
  array (
    'farmer_name' => 'CONCAT(fr.first_name,\' \',fr.last_name)',
    'soil_type_name' => 'st.soil_type_name',
    'irrigation_type_name' => 'it.irrigation_type_name',
  ),
);
