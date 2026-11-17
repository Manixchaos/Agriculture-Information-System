<?php
declare(strict_types=1);

class Farm extends Module
{
    public function selectSql(): string
    {
        return "SELECT f.*, CONCAT(fr.first_name,' ',fr.last_name) AS farmer_name, st.soil_type_name, it.irrigation_type_name FROM farms f JOIN farmers fr USING(farmer_id) LEFT JOIN soil_types st USING(soil_type_id) LEFT JOIN irrigation_types it USING(irrigation_type_id)";
    }
}
