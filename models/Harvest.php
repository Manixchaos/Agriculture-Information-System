<?php
declare(strict_types=1);

class Harvest extends Module
{
    public function selectSql(): string
    {
        return "SELECT h.*, c.crop_name, f.farm_name, qg.grade_name, hr.remark_description FROM harvests h JOIN plantings p USING(planting_id) JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) LEFT JOIN quality_grades qg USING(grade_id) LEFT JOIN harvest_remarks hr USING(remark_id)";
    }

    public function crossChecks(array $data, array &$errors): void
    {
        if ($errors) return;
        $st = $this->pdo->prepare('SELECT planting_date FROM plantings WHERE planting_id=:i');
        $st->execute(['i' => (int)$data['planting_id']]);
        $pd = $st->fetchColumn();
        if ($pd && $data['harvest_date'] < $pd) {
            $errors[] = 'Harvest date cannot be earlier than the planting date (' . $pd . ').';
        }
    }
}
