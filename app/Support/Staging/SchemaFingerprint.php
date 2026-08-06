<?php

namespace App\Support\Staging;

use Illuminate\Support\Facades\DB;

class SchemaFingerprint
{
    /**
     * Build a database-name-independent structural fingerprint from MySQL metadata.
     */
    public function hash(): string
    {
        return hash('sha256', implode("\n", $this->lines())."\n");
    }

    /**
     * @return list<string>
     */
    public function lines(): array
    {
        $queries = [
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'TABLE',TABLE_NAME,TABLE_TYPE,IFNULL(ENGINE,''),IFNULL(TABLE_COLLATION,'')) AS fingerprint_line
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME
SQL,
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'COLUMN',TABLE_NAME,LPAD(ORDINAL_POSITION,4,'0'),COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,IFNULL(COLUMN_DEFAULT,'<NULL>'),EXTRA,IFNULL(GENERATION_EXPRESSION,''),IFNULL(CHARACTER_SET_NAME,''),IFNULL(COLLATION_NAME,'')) AS fingerprint_line
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME,ORDINAL_POSITION
SQL,
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'INDEX',TABLE_NAME,INDEX_NAME,NON_UNIQUE,LPAD(SEQ_IN_INDEX,4,'0'),IFNULL(COLUMN_NAME,''),IFNULL(COLLATION,''),IFNULL(SUB_PART,''),NULLABLE,INDEX_TYPE,IFNULL(EXPRESSION,'')) AS fingerprint_line
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX
SQL,
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'FK',k.TABLE_NAME,k.CONSTRAINT_NAME,LPAD(k.ORDINAL_POSITION,4,'0'),k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE) AS fingerprint_line
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.REFERENTIAL_CONSTRAINTS r
  ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA
 AND r.TABLE_NAME=k.TABLE_NAME
 AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME
WHERE k.CONSTRAINT_SCHEMA=DATABASE()
  AND k.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY k.TABLE_NAME,k.CONSTRAINT_NAME,k.ORDINAL_POSITION
SQL,
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'CHECK',tc.TABLE_NAME,cc.CONSTRAINT_NAME,cc.CHECK_CLAUSE) AS fingerprint_line
FROM information_schema.TABLE_CONSTRAINTS tc
JOIN information_schema.CHECK_CONSTRAINTS cc
  ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA
 AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
WHERE tc.CONSTRAINT_SCHEMA=DATABASE()
  AND tc.CONSTRAINT_TYPE='CHECK'
ORDER BY tc.TABLE_NAME,cc.CONSTRAINT_NAME
SQL,
            <<<'SQL'
SELECT CONCAT_WS(CHAR(9),'VIEW',TABLE_NAME,REPLACE(VIEW_DEFINITION,CONCAT('`',TABLE_SCHEMA,'`.'),''),CHECK_OPTION,IS_UPDATABLE,SECURITY_TYPE) AS fingerprint_line
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME
SQL,
        ];

        $lines = [];
        foreach ($queries as $query) {
            foreach (DB::select($query) as $row) {
                $lines[] = (string) $row->fingerprint_line;
            }
        }

        return $lines;
    }
}
