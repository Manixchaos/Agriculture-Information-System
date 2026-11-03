<?php
return [
'farmers'=>[
'title'=>'Farmers','table'=>'farmers','pk'=>'farmer_id','search'=>['first_name','last_name','contact_number','address'],
'fields'=>[
'first_name'=>['First name','text',true],'last_name'=>['Last name','text',true],
'contact_number'=>['Contact number','text',false],'address'=>['Address','text',true],
'date_registered'=>['Date registered','date',true]],
'columns'=>['farmer_id'=>'ID','first_name'=>'First name','last_name'=>'Last name','contact_number'=>'Contact','address'=>'Address','date_registered'=>'Registered']],
'farms'=>[
'title'=>'Farms','table'=>'farms','pk'=>'farm_id','search'=>['farm_name','location'],
'fields'=>[
'farmer_id'=>['Farmer','lookup',true,'farmers','farmer_id',"CONCAT(first_name, ' ', last_name)"],
'farm_name'=>['Farm name','text',true],'location'=>['Location','text',true],
'area_hectares'=>['Area (hectares)','number',true],
'soil_type_id'=>['Soil type','lookup',true,'soil_types','soil_type_id','soil_type_name'],
'irrigation_type_id'=>['Irrigation type','lookup',true,'irrigation_types','irrigation_type_id','irrigation_type_name']],
'columns'=>['farm_id'=>'ID','farm_name'=>'Farm','farmer_name'=>'Farmer','location'=>'Location','area_hectares'=>'Area (ha)','soil_type_name'=>'Soil','irrigation_type_name'=>'Irrigation']],
'crops'=>[
'title'=>'Crops','table'=>'crops','pk'=>'crop_id','search'=>['crop_name','variety'],
'fields'=>[
'crop_name'=>['Crop name','text',true],'variety'=>['Variety','text',true],
'category_id'=>['Category','lookup',true,'crop_categories','category_id','category_name'],
'growing_period_days'=>['Growing period (days)','number',true],
'recommended_season_id'=>['Recommended season','lookup',true,'seasons','season_id','season_name']],
'columns'=>['crop_id'=>'ID','crop_name'=>'Crop','variety'=>'Variety','category_name'=>'Category','growing_period_days'=>'Growing days','season_name'=>'Season']],
'plantings'=>[
'title'=>'Planting records','table'=>'plantings','pk'=>'planting_id','search'=>['farm_name','crop_name','status_name'],
'fields'=>[
'farm_id'=>['Farm','lookup',true,'farms','farm_id','farm_name'],
'crop_id'=>['Crop','lookup',true,'crops','crop_id',"CONCAT(crop_name, ' — ', variety)"],
'planting_date'=>['Planting date','date',true],
'area_planted'=>['Area planted (ha)','number',true],
'seed_quantity_kg'=>['Seed quantity (kg)','number',true],
'expected_harvest_date'=>['Expected harvest date','date',true],
'status_id'=>['Status','lookup',true,'planting_statuses','status_id','status_name']],
'columns'=>['planting_id'=>'ID','farm_name'=>'Farm','crop_name'=>'Crop','planting_date'=>'Planted','area_planted'=>'Area (ha)','expected_harvest_date'=>'Expected harvest','status_name'=>'Status']],
'harvests'=>[
'title'=>'Harvest records','table'=>'harvests','pk'=>'harvest_id','search'=>['farm_name','crop_name','grade_name','remark_description'],
'fields'=>[
'planting_id'=>['Planting record','lookup',true,'plantings','planting_id',"CONCAT('Planting #', planting_id, ' · ', planting_date)"],
'harvest_date'=>['Harvest date','date',true],
'quantity_kg'=>['Quantity (kg)','number',true],
'selling_price_per_kg'=>['Selling price per kg (₱)','number',true],
'grade_id'=>['Quality grade','lookup',false,'quality_grades','grade_id','grade_name'],
'remark_id'=>['Harvest remark','lookup',false,'harvest_remarks','remark_id','remark_description'],
'notes'=>['Notes','textarea',false]],
'columns'=>['harvest_id'=>'ID','crop_name'=>'Crop','farm_name'=>'Farm','harvest_date'=>'Harvest date','quantity_kg'=>'Quantity (kg)','selling_price_per_kg'=>'Price/kg','grade_name'=>'Grade','remark_description'=>'Remark']]
];
