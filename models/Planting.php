<?php
declare(strict_types=1);

class Planting extends Module
{
    public function selectSql(): string
    {
        return "SELECT p.*, f.farm_name, c.crop_name, ps.status_name FROM plantings p JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) JOIN planting_statuses ps USING(status_id)";
    }

    public function crossChecks(array $data, array &$errors): void
    {
        if ($errors) return;
        if ($data['expected_harvest_date'] < $data['planting_date']) {
            $errors[] = 'Expected harvest date cannot be earlier than the planting date.';
        }
        $st = $this->pdo->prepare('SELECT area_hectares FROM farms WHERE farm_id=:i');
        $st->execute(['i' => (int)$data['farm_id']]);
        $area = $st->fetchColumn();
        if ($area !== false && (float)$data['area_planted'] > (float)$area) {
            $errors[] = 'Area planted cannot exceed the farm\'s total area (' . number_format((float)$area, 2) . ' ha).';
        }
    }
}
