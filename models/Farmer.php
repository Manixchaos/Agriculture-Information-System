<?php
declare(strict_types=1);

class Farmer extends Module
{
    public function selectSql(): string
    {
        return "SELECT f.* FROM farmers f";
    }
}
