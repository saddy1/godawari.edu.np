<?php

namespace App\Support;

use RuntimeException;

class MySqlDumpReader
{
    /** @return array<int, array<string, mixed>> */
    public function rows(string $sql, string $table): array
    {
        $rows = [];
        $needle = 'INSERT INTO `'.$table.'`';
        $offset = 0;
        $found = false;

        while (($start = stripos($sql, $needle, $offset)) !== false) {
            $found = true;
            $end = $this->statementEnd($sql, $start);
            $statement = substr($sql, $start, $end - $start);
            $offset = $end + 1;

            if (! preg_match('/INSERT\s+INTO\s+`'.preg_quote($table, '/').'`\s*\((.*?)\)\s*VALUES\s*(.*)$/si', $statement, $parts)) {
                throw new RuntimeException("Could not parse an INSERT statement for {$table}.");
            }

            preg_match_all('/`([^`]+)`/', $parts[1], $columnMatches);
            $columns = $columnMatches[1];

            foreach ($this->parseValueTuples($parts[2]) as $values) {
                if (count($columns) !== count($values)) {
                    throw new RuntimeException("A {$table} INSERT row does not match its column count.");
                }

                $rows[] = array_combine($columns, $values);
            }
        }

        return $found ? $rows : [];
    }

    private function statementEnd(string $sql, int $start): int
    {
        $inString = false;
        $escaped = false;
        $length = strlen($sql);

        for ($index = $start; $index < $length; $index++) {
            $char = $sql[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $inString = false;
                }
            } elseif ($char === "'") {
                $inString = true;
            } elseif ($char === ';') {
                return $index;
            }
        }

        throw new RuntimeException('An INSERT statement is missing its closing semicolon.');
    }

    /** @return array<int, array<int, mixed>> */
    private function parseValueTuples(string $valuesSql): array
    {
        $tuples = [];
        $tuple = [];
        $token = '';
        $inTuple = false;
        $inString = false;
        $escaped = false;
        $length = strlen($valuesSql);

        for ($index = 0; $index < $length; $index++) {
            $char = $valuesSql[$index];

            if (! $inTuple) {
                if ($char === '(') {
                    $inTuple = true;
                    $tuple = [];
                    $token = '';
                }
                continue;
            }

            if ($inString) {
                $token .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $token .= $char;
            } elseif ($char === ',') {
                $tuple[] = $this->decodeValue($token);
                $token = '';
            } elseif ($char === ')') {
                $tuple[] = $this->decodeValue($token);
                $tuples[] = $tuple;
                $tuple = [];
                $token = '';
                $inTuple = false;
            } else {
                $token .= $char;
            }
        }

        if ($inTuple || $inString) {
            throw new RuntimeException('The SQL values are incomplete or malformed.');
        }

        return $tuples;
    }

    private function decodeValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $value = substr($value, 1, -1);

            return strtr($value, [
                "\\0" => "\0",
                "\\n" => "\n",
                "\\r" => "\r",
                "\\Z" => "\x1a",
                "\\'" => "'",
                '\\"' => '"',
                "\\\\" => "\\",
            ]);
        }

        return is_numeric($value) ? $value + 0 : $value;
    }
}
