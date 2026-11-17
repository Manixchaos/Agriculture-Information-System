<?php
return array (
  'title' => 'Crops',
  'table' => 'crops',
  'alias' => 'c',
  'pk' => 'crop_id',
  'search' => 
  array (
    0 => 'crop_name',
    1 => 'variety',
  ),
  'fields' => 
  array (
    'crop_name' => 
    array (
      0 => 'Crop name',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 80,
      ),
    ),
    'variety' => 
    array (
      0 => 'Variety',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 80,
      ),
    ),
    'category_id' => 
    array (
      0 => 'Category',
      1 => 'lookup',
      2 => true,
      3 => 'crop_categories',
      4 => 'category_id',
      5 => 'category_name',
    ),
    'growing_period_days' => 
    array (
      0 => 'Growing period (days)',
      1 => 'number',
      2 => true,
      3 => 
      array (
        'int' => true,
        'min' => 1,
        'max' => 1000,
      ),
    ),
    'recommended_season_id' => 
    array (
      0 => 'Recommended season',
      1 => 'lookup',
      2 => true,
      3 => 'seasons',
      4 => 'season_id',
      5 => 'season_name',
    ),
  ),
  'columns' => 
  array (
    'crop_id' => 'ID',
    'crop_name' => 'Crop',
    'variety' => 'Variety',
    'category_name' => 'Category',
    'growing_period_days' => 'Growing days',
    'season_name' => 'Season',
  ),
  'filters' => 
  array (
    'category_id' => 
    array (
      'label' => 'Category',
      'type' => 'lookup',
      'table' => 'crop_categories',
      'key' => 'category_id',
      'text' => 'category_name',
      'col' => 'c.category_id',
    ),
    'recommended_season_id' => 
    array (
      'label' => 'Season',
      'type' => 'lookup',
      'table' => 'seasons',
      'key' => 'season_id',
      'text' => 'season_name',
      'col' => 'c.recommended_season_id',
    ),
  ),
  'sortmap' => 
  array (
    'category_name' => 'cc.category_name',
    'season_name' => 's.season_name',
  ),
);
