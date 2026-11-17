<?php
declare(strict_types=1);

/**
 * Base class for every module entity.
 * Holds the PDO connection + module config, and provides the generic
 * CRUD, validation, and lookup helpers that all entities share.
 * Subclasses (Farmer, Farm, Crop, ...) override selectSql() and
 * crossChecks() where they differ.
 */
abstract class Module
{
    protected PDO $pdo;
    protected array $cfg;

    public function __construct(PDO $pdo, array $cfg)
    {
        $this->pdo = $pdo;
        $this->cfg = $cfg;
    }

    public function cfg(): array { return $this->cfg; }
    public function pdo(): PDO { return $this->pdo; }
    public function pk(): string { return $this->cfg['pk']; }
    public function table(): string { return $this->cfg['table']; }
    public function alias(): string { return $this->cfg['alias']; }

    /** Per-module SELECT with JOINs. Override in subclasses. */
    abstract public function selectSql(): string;

    /** Business rules across fields. Override in subclasses. */
    public function crossChecks(array $data, array &$errors): void
    {
        // default: none
    }

    public function isId(string $key): bool { return str_ends_with($key, '_id'); }
    public function isNumeric(string $key): bool {
        return in_array($key, ['area_hectares','area_planted','seed_quantity_kg','quantity_kg','selling_price_per_kg','growing_period_days'], true);
    }
    public function isDate(string $key): bool {
        return $key === 'date_registered' || str_ends_with($key, '_date');
    }

    public function validate(array $in, array &$errors): array
    {
        $data = [];
        foreach ($this->cfg['fields'] as $name => $field) {
            [$label, $type, $required] = $field;
            $o = $type === 'lookup' ? [] : ($field[3] ?? []);
            $v = trim((string)($in[$name] ?? ''));
            $data[$name] = $v === '' ? null : $v;
            if ($v === '') { if ($required) $errors[] = "$label is required."; continue; }
            if ($type === 'text' || $type === 'textarea') {
                if (isset($o['max']) && mb_strlen($v) > $o['max']) $errors[] = "$label must not exceed {$o['max']} characters.";
                if (isset($o['pattern']) && !preg_match($o['pattern'], $v)) $errors[] = $o['pattern_msg'] ?? "$label has an invalid format.";
            } elseif ($type === 'number') {
                $isInt = !empty($o['int']);
                if (!preg_match($isInt ? '/^\d+$/' : '/^\d+(\.\d{1,2})?$/', $v)) {
                    $errors[] = $label . ($isInt ? ' must be a whole number.' : ' must be a valid non-negative number with up to 2 decimal places.');
                    continue;
                }
                $n = (float)$v;
                if (isset($o['min']) && $n < $o['min']) $errors[] = "$label must be at least {$o['min']}.";
                if (isset($o['max']) && $n > $o['max']) $errors[] = "$label must not exceed {$o['max']}.";
            } elseif ($type === 'date') {
                if (!validate_date($v)) { $errors[] = "$label must be a valid date."; continue; }
                if (!empty($o['not_future']) && $v > date('Y-m-d')) $errors[] = "$label cannot be in the future.";
            } elseif ($type === 'lookup') {
                [, , , $t, $k] = $field;
                if (!ctype_digit($v)) { $errors[] = "Please choose a valid $label."; continue; }
                $st = $this->pdo->prepare("SELECT 1 FROM {$t} WHERE {$k}=:v LIMIT 1"); $st->execute(['v' => (int)$v]);
                if (!$st->fetchColumn()) $errors[] = "The selected $label does not exist.";
            }
        }
        return $data;
    }

    public function options(array $field): array
    {
        [, , , $t, $k, $l] = $field;
        return $this->pdo->query("SELECT {$k} AS id, {$l} AS label FROM {$t} ORDER BY label")->fetchAll();
    }

    public function filterOptions(array $f): array
    {
        if ($f['type'] === 'lookup') {
            return $this->pdo->query("SELECT {$f['key']} AS id, {$f['text']} AS label FROM {$f['table']} ORDER BY label")->fetchAll();
        }
        return $this->pdo->query("SELECT DISTINCT {$f['text']} AS id, {$f['text']} AS label FROM {$f['table']} WHERE {$f['text']} IS NOT NULL ORDER BY label")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pk = $this->pk(); $table = $this->table();
        $st = $this->pdo->prepare("SELECT * FROM {$table} WHERE {$pk}=:id");
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $pk = $this->pk(); $table = $this->table();
        $st = $this->pdo->prepare("DELETE FROM {$table} WHERE {$pk}=:id");
        $st->execute(['id' => $id]);
    }

    public function create(array $data): void
    {
        $table = $this->table();
        $cols = implode(',', array_keys($data));
        $params = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $st = $this->pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$params})");
        $st->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $pk = $this->pk(); $table = $this->table();
        $sets = implode(',', array_map(fn($k) => "$k=:$k", array_keys($data)));
        $data['id'] = $id;
        $st = $this->pdo->prepare("UPDATE {$table} SET {$sets} WHERE {$pk}=:id");
        $st->execute($data);
    }

    public function count(string $where = '', array $params = []): int
    {
        $base = $this->selectSql();
        $sql = "SELECT COUNT(*) FROM (" . $base . ($where ? " WHERE $where" : "") . ") AS filtered_records";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function list(string $where = '', array $params = [], string $orderBy = '', int $limit = 10, int $offset = 0): array
    {
        $base = $this->selectSql();
        $sql = $base . ($where ? " WHERE $where" : "");
        if ($orderBy !== '') $sql .= " ORDER BY $orderBy";
        $sql .= " LIMIT $limit OFFSET $offset";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
