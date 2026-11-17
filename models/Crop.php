<?php
declare(strict_types=1);

class Crop extends Module
{
    public function selectSql(): string
    {
        return "SELECT c.*, cc.category_name, s.season_name FROM crops c JOIN crop_categories cc USING(category_id) LEFT JOIN seasons s ON s.season_id=c.recommended_season_id";
    }
}
