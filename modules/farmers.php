<?php
return array (
  'title' => 'Farmers',
  'table' => 'farmers',
  'alias' => 'f',
  'pk' => 'farmer_id',
  'search' => 
  array (
    0 => 'first_name',
    1 => 'last_name',
    2 => 'contact_number',
    3 => 'address',
  ),
  'fields' => 
  array (
    'first_name' => 
    array (
      0 => 'First name',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 50,
        'pattern' => '/^[A-Za-z\\s\'\\-]+$/',
        'pattern_msg' => 'First name may contain only letters, spaces, hyphens, and apostrophes.',
      ),
    ),
    'last_name' => 
    array (
      0 => 'Last name',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 50,
        'pattern' => '/^[A-Za-z\\s\'\\-]+$/',
        'pattern_msg' => 'Last name may contain only letters, spaces, hyphens, and apostrophes.',
      ),
    ),
    'contact_number' => 
    array (
      0 => 'Contact number',
      1 => 'text',
      2 => false,
      3 => 
      array (
        'max' => 20,
        'pattern' => '/^[0-9+\\-\\s()]{7,20}$/',
        'pattern_msg' => 'Contact number may contain only digits, spaces, + - ( ) and must be 7 to 20 characters.',
      ),
    ),
    'address' => 
    array (
      0 => 'Address',
      1 => 'text',
      2 => true,
      3 => 
      array (
        'max' => 150,
      ),
    ),
    'date_registered' => 
    array (
      0 => 'Date registered',
      1 => 'date',
      2 => true,
      3 => 
      array (
        'not_future' => true,
      ),
    ),
  ),
  'columns' => 
  array (
    'farmer_id' => 'ID',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'contact_number' => 'Contact',
    'address' => 'Address',
    'date_registered' => 'Registered',
  ),
  'filters' => 
  array (
    'address' => 
    array (
      'label' => 'Address',
      'type' => 'distinct',
      'table' => 'farmers',
      'text' => 'address',
      'col' => 'f.address',
    ),
  ),
  'sortmap' => 
  array (
  ),
);
