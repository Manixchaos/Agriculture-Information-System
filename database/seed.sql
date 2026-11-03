INSERT INTO crop_categories(category_name) VALUES
('Cereal'), ('Vegetable'), ('Fruit'), ('Legume'), ('Root Crop')
ON CONFLICT (category_name) DO NOTHING;

INSERT INTO seasons(season_name) VALUES
('Wet Season'), ('Dry Season'), ('Year Round')
ON CONFLICT (season_name) DO NOTHING;

INSERT INTO soil_types(soil_type_name) VALUES
('Loam'), ('Clay Loam'), ('Sandy Loam'), ('Clay')
ON CONFLICT (soil_type_name) DO NOTHING;

INSERT INTO irrigation_types(irrigation_type_name) VALUES
('Rainfed'), ('Irrigated'), ('Drip Irrigation')
ON CONFLICT (irrigation_type_name) DO NOTHING;

INSERT INTO planting_statuses(status_name) VALUES
('Planned'), ('Growing'), ('Harvested'), ('Cancelled')
ON CONFLICT (status_name) DO NOTHING;

INSERT INTO quality_grades(grade_name) VALUES
('Grade A'), ('Grade B'), ('Grade C')
ON CONFLICT (grade_name) DO NOTHING;

INSERT INTO harvest_remarks(remark_description) VALUES
('Good Harvest'), ('Acceptable Quality'), ('Low Yield'), ('Poor Quality'), ('Damaged Harvest')
ON CONFLICT (remark_description) DO NOTHING;

INSERT INTO crops(crop_name, variety, category_id, growing_period_days, recommended_season_id)
SELECT v.crop_name, v.variety, cc.category_id, v.days, s.season_id
FROM (VALUES
 ('Rice','NSIC Rc 222','Cereal',115,'Wet Season'),
 ('Corn','Yellow Corn','Cereal',100,'Dry Season'),
 ('Tomato','Diamante Max','Vegetable',90,'Dry Season'),
 ('Eggplant','Morena','Vegetable',100,'Year Round'),
 ('Banana','Lakatan','Fruit',300,'Year Round'),
 ('Mango','Carabao','Fruit',150,'Dry Season'),
 ('Peanut','BPI Pg 9','Legume',110,'Dry Season'),
 ('Cassava','Golden Yellow','Root Crop',240,'Year Round'),
 ('Sweet Potato','NSIC Sp 30','Root Crop',120,'Year Round'),
 ('Watermelon','Sweet 16','Fruit',85,'Dry Season')
) AS v(crop_name,variety,category,days,season)
JOIN crop_categories cc ON cc.category_name=v.category
JOIN seasons s ON s.season_name=v.season
ON CONFLICT (crop_name, variety) DO NOTHING;
