<?php
/*
 * Module definitions.
 * Field format: 'column' => [Label, type, required, options]
 *   type text/textarea/date/number: options = ['max'=>..,'min'=>..,'int'=>true,'pattern'=>..,'not_future'=>true]
 *   type lookup: [Label,'lookup',required, table, key column, label expression]
 * 'filters' build the filter dropdowns; 'sortmap' maps column keys to SQL for sorting.
 */
return [
'farmers'=>[
'title'=>'Farmers','table'=>'farmers','alias'=>'f','pk'=>'farmer_id','search'=>['first_name','last_name','contact_number','address'],
'fields'=>[
'first_name'=>['First name','text',true,['max'=>50]],
'last_name'=>['Last name','text',true,['max'=>50]],
'contact_number'=>['Contact number','text',false,['max'=>20,'pattern'=>'/^[0-9+\-\s()]{7,20}$/','pattern_msg'=>'Contact number may contain only digits, spaces, + - ( ) and must be 7 to 20 characters.']],
'address'=>['Address','text',true,['max'=>150]],
'date_registered'=>['Date registered','date',true,['not_future'=>true]]],
'columns'=>['farmer_id'=>'ID','first_name'=>'First name','last_name'=>'Last name','contact_number'=>'Contact','address'=>'Address','date_registered'=>'Registered'],
'filters'=>['address'=>['label'=>'Address','type'=>'distinct','table'=>'farmers','text'=>'address','col'=>'f.address']],
'sortmap'=>[]],

'farms'=>[
'title'=>'Farms','table'=>'farms','alias'=>'f','pk'=>'farm_id','search'=>['farm_name','location'],
'fields'=>[
'farmer_id'=>['Farmer','lookup',true,'farmers','farmer_id',"CONCAT(first_name, ' ', last_name)"],
'farm_name'=>['Farm name','text',true,['max'=>100]],
'location'=>['Location','text',true,['max'=>150]],
'area_hectares'=>['Area (hectares)','number',true,['min'=>0.01,'max'=>10000]],
'soil_type_id'=>['Soil type','lookup',true,'soil_types','soil_type_id','soil_type_name'],
'irrigation_type_id'=>['Irrigation type','lookup',true,'irrigation_types','irrigation_type_id','irrigation_type_name']],
'columns'=>['farm_id'=>'ID','farm_name'=>'Farm','farmer_name'=>'Farmer','location'=>'Location','area_hectares'=>'Area (ha)','soil_type_name'=>'Soil','irrigation_type_name'=>'Irrigation'],
'filters'=>[
 'irrigation_type_id'=>['label'=>'Irrigation type','type'=>'lookup','table'=>'irrigation_types','key'=>'irrigation_type_id','text'=>'irrigation_type_name','col'=>'f.irrigation_type_id'],
 'soil_type_id'=>['label'=>'Soil type','type'=>'lookup','table'=>'soil_types','key'=>'soil_type_id','text'=>'soil_type_name','col'=>'f.soil_type_id']],
'sortmap'=>['farmer_name'=>"CONCAT(fr.first_name,' ',fr.last_name)",'soil_type_name'=>'st.soil_type_name','irrigation_type_name'=>'it.irrigation_type_name']],

'crops'=>[
'title'=>'Crops','table'=>'crops','alias'=>'c','pk'=>'crop_id','search'=>['crop_name','variety'],
'fields'=>[
'crop_name'=>['Crop name','text',true,['max'=>80]],
'variety'=>['Variety','text',true,['max'=>80]],
'category_id'=>['Category','lookup',true,'crop_categories','category_id','category_name'],
'growing_period_days'=>['Growing period (days)','number',true,['int'=>true,'min'=>1,'max'=>1000]],
'recommended_season_id'=>['Recommended season','lookup',true,'seasons','season_id','season_name']],
'columns'=>['crop_id'=>'ID','crop_name'=>'Crop','variety'=>'Variety','category_name'=>'Category','growing_period_days'=>'Growing days','season_name'=>'Season'],
'filters'=>[
 'category_id'=>['label'=>'Category','type'=>'lookup','table'=>'crop_categories','key'=>'category_id','text'=>'category_name','col'=>'c.category_id'],
 'recommended_season_id'=>['label'=>'Season','type'=>'lookup','table'=>'seasons','key'=>'season_id','text'=>'season_name','col'=>'c.recommended_season_id']],
'sortmap'=>['category_name'=>'cc.category_name','season_name'=>'s.season_name']],

'plantings'=>[
'title'=>'Planting records','table'=>'plantings','alias'=>'p','pk'=>'planting_id','search'=>['farm_name','crop_name','status_name'],
'fields'=>[
'farm_id'=>['Farm','lookup',true,'farms','farm_id','farm_name'],
'crop_id'=>['Crop','lookup',true,'crops','crop_id',"CONCAT(crop_name, ' — ', variety)"],
'planting_date'=>['Planting date','date',true,[]],
'area_planted'=>['Area planted (ha)','number',true,['min'=>0.01,'max'=>10000]],
'seed_quantity_kg'=>['Seed quantity (kg)','number',true,['min'=>0,'max'=>1000000]],
'expected_harvest_date'=>['Expected harvest date','date',true,[]],
'status_id'=>['Status','lookup',true,'planting_statuses','status_id','status_name']],
'columns'=>['planting_id'=>'ID','farm_name'=>'Farm','crop_name'=>'Crop','planting_date'=>'Planted','area_planted'=>'Area (ha)','expected_harvest_date'=>'Expected harvest','status_name'=>'Status'],
'filters'=>[
 'status_id'=>['label'=>'Status','type'=>'lookup','table'=>'planting_statuses','key'=>'status_id','text'=>'status_name','col'=>'p.status_id'],
 'farm_id'=>['label'=>'Farm','type'=>'lookup','table'=>'farms','key'=>'farm_id','text'=>'farm_name','col'=>'p.farm_id'],
 'crop_id'=>['label'=>'Crop','type'=>'lookup','table'=>'crops','key'=>'crop_id','text'=>'crop_name','col'=>'p.crop_id']],
'sortmap'=>['farm_name'=>'f.farm_name','crop_name'=>'c.crop_name','status_name'=>'ps.status_name']],

'harvests'=>[
'title'=>'Harvest records','table'=>'harvests','alias'=>'h','pk'=>'harvest_id','search'=>['farm_name','crop_name','grade_name','remark_description'],
'fields'=>[
'planting_id'=>['Planting record','lookup',true,
  'plantings p JOIN farms f ON f.farm_id=p.farm_id JOIN crops c ON c.crop_id=p.crop_id','p.planting_id',
  "CONCAT('#', p.planting_id, ' · ', f.farm_name, ' · ', c.crop_name, ' · ', p.planting_date)"],
'harvest_date'=>['Harvest date','date',true,[]],
'quantity_kg'=>['Quantity (kg)','number',true,['min'=>0,'max'=>9999999]],
'selling_price_per_kg'=>['Selling price per kg (₱)','number',true,['min'=>0,'max'=>100000]],
'grade_id'=>['Quality grade','lookup',false,'quality_grades','grade_id','grade_name'],
'remark_id'=>['Harvest remark','lookup',false,'harvest_remarks','remark_id','remark_description'],
'notes'=>['Notes','textarea',false,['max'=>200]]],
'columns'=>['harvest_id'=>'ID','crop_name'=>'Crop','farm_name'=>'Farm','harvest_date'=>'Harvest date','quantity_kg'=>'Quantity (kg)','selling_price_per_kg'=>'Price/kg','grade_name'=>'Grade','remark_description'=>'Remark'],
'filters'=>[
 'grade_id'=>['label'=>'Quality grade','type'=>'lookup','table'=>'quality_grades','key'=>'grade_id','text'=>'grade_name','col'=>'h.grade_id'],
 'remark_id'=>['label'=>'Remark','type'=>'lookup','table'=>'harvest_remarks','key'=>'remark_id','text'=>'remark_description','col'=>'h.remark_id']],
'sortmap'=>['crop_name'=>'c.crop_name','farm_name'=>'f.farm_name','grade_name'=>'qg.grade_name','remark_description'=>'hr.remark_description']]
];
