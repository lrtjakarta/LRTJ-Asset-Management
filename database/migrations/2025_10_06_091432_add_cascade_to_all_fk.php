<?php
// database/migrations/2025_10_06_000002_cascade_all_kode_foreign_keys.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Build drop/add SQL for every FK to parent column named 'kode' that is NOT already CASCADE on update
        $rows = DB::select(<<<'SQL'
SELECT
  con.conname AS constraint_name,
  (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname)) AS child_table,
  (quote_ident(np.nspname) || '.' || quote_ident(tp.relname)) AS parent_table,
  quote_ident(ac.attname) AS child_col,
  quote_ident(ap.attname) AS parent_col,
  CASE con.confdeltype
    WHEN 'a' THEN 'NO ACTION'
    WHEN 'r' THEN 'RESTRICT'
    WHEN 'c' THEN 'CASCADE'
    WHEN 'n' THEN 'SET NULL'
    WHEN 'd' THEN 'SET DEFAULT'
  END AS delete_action,
  -- statements to execute
  ('ALTER TABLE ' || (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname))
     || ' DROP CONSTRAINT ' || quote_ident(con.conname)) AS sql_drop,
  ('ALTER TABLE ' || (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname))
     || ' ADD CONSTRAINT ' || quote_ident(con.conname)
     || ' FOREIGN KEY (' || quote_ident(ac.attname) || ')'
     || ' REFERENCES ' || (quote_ident(np.nspname) || '.' || quote_ident(tp.relname))
     || '(' || quote_ident(ap.attname) || ')'
     || ' ON UPDATE CASCADE'
     || ' ON DELETE ' || CASE con.confdeltype
          WHEN 'a' THEN 'NO ACTION'
          WHEN 'r' THEN 'RESTRICT'
          WHEN 'c' THEN 'CASCADE'
          WHEN 'n' THEN 'SET NULL'
          WHEN 'd' THEN 'SET DEFAULT'
        END
  ) AS sql_add
FROM pg_constraint con
JOIN pg_class tc ON tc.oid = con.conrelid
JOIN pg_namespace nc ON nc.oid = tc.relnamespace
JOIN pg_class tp ON tp.oid = con.confrelid
JOIN pg_namespace np ON np.oid = tp.relnamespace
JOIN pg_attribute ac ON ac.attrelid = tc.oid AND ac.attnum = con.conkey[1]
JOIN pg_attribute ap ON ap.attrelid = tp.oid AND ap.attnum = con.confkey[1]
WHERE con.contype = 'f'
  AND array_length(con.conkey,1) = 1
  AND array_length(con.confkey,1) = 1
  AND ap.attname = 'kode'
  AND con.confupdtype <> 'c' -- not already CASCADE
ORDER BY nc.nspname, tc.relname, con.conname;
SQL);

        foreach ($rows as $r) {
            DB::statement($r->sql_drop);
            DB::statement($r->sql_add);
        }
    }

    public function down(): void
    {
        // Rebuild the same set back to ON UPDATE NO ACTION (best-effort)
        $rows = DB::select(<<<'SQL'
SELECT
  con.conname AS constraint_name,
  (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname)) AS child_table,
  (quote_ident(np.nspname) || '.' || quote_ident(tp.relname)) AS parent_table,
  quote_ident(ac.attname) AS child_col,
  quote_ident(ap.attname) AS parent_col,
  CASE con.confdeltype
    WHEN 'a' THEN 'NO ACTION'
    WHEN 'r' THEN 'RESTRICT'
    WHEN 'c' THEN 'CASCADE'
    WHEN 'n' THEN 'SET NULL'
    WHEN 'd' THEN 'SET DEFAULT'
  END AS delete_action,
  ('ALTER TABLE ' || (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname))
     || ' DROP CONSTRAINT ' || quote_ident(con.conname)) AS sql_drop,
  ('ALTER TABLE ' || (quote_ident(nc.nspname) || '.' || quote_ident(tc.relname))
     || ' ADD CONSTRAINT ' || quote_ident(con.conname)
     || ' FOREIGN KEY (' || quote_ident(ac.attname) || ')'
     || ' REFERENCES ' || (quote_ident(np.nspname) || '.' || quote_ident(tp.relname))
     || '(' || quote_ident(ap.attname) || ')'
     || ' ON UPDATE NO ACTION'
     || ' ON DELETE ' || CASE con.confdeltype
          WHEN 'a' THEN 'NO ACTION'
          WHEN 'r' THEN 'RESTRICT'
          WHEN 'c' THEN 'CASCADE'
          WHEN 'n' THEN 'SET NULL'
          WHEN 'd' THEN 'SET DEFAULT'
        END
  ) AS sql_add
FROM pg_constraint con
JOIN pg_class tc ON tc.oid = con.conrelid
JOIN pg_namespace nc ON nc.oid = tc.relnamespace
JOIN pg_class tp ON tp.oid = con.confrelid
JOIN pg_namespace np ON np.oid = tp.relnamespace
JOIN pg_attribute ac ON ac.attrelid = tc.oid AND ac.attnum = con.conkey[1]
JOIN pg_attribute ap ON ap.attrelid = tp.oid AND ap.attnum = con.confkey[1]
WHERE con.contype = 'f'
  AND array_length(con.conkey,1) = 1
  AND array_length(con.confkey,1) = 1
  AND ap.attname = 'kode'
  AND con.confupdtype = 'c' -- only those we set to CASCADE
ORDER BY nc.nspname, tc.relname, con.conname;
SQL);

        foreach ($rows as $r) {
            DB::statement($r->sql_drop);
            DB::statement($r->sql_add);
        }
    }
};
