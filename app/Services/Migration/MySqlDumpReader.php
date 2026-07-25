<?php

namespace App\Services\Migration;

use Generator;
use InvalidArgumentException;
use RuntimeException;

final class MySqlDumpReader
{
    private string $contents;

    /** @var array<string, list<string>> */
    private array $columns = [];

    public function __construct(private readonly string $path)
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("No se puede leer el respaldo: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("No se pudo cargar el respaldo: {$path}");
        }

        $this->contents = $contents;
        $this->columns = $this->extractColumns();
    }

    public function sha256(): string
    {
        return hash_file('sha256', $this->path);
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return array_keys($this->columns);
    }

    public function hasTable(string $table): bool
    {
        return isset($this->columns[$table]);
    }

    public function count(string $table): int
    {
        $count = 0;

        foreach ($this->rows($table) as $_row) {
            $count++;
        }

        return $count;
    }

    /**
     * @return Generator<int, array<string, string|null>>
     */
    public function rows(string $table): Generator
    {
        if (! isset($this->columns[$table])) {
            throw new InvalidArgumentException("La tabla {$table} no existe en el respaldo.");
        }

        $pattern = '/INSERT INTO `'.preg_quote($table, '/').'` VALUES\s*/';
        $offset = 0;
        $length = strlen($this->contents);

        while (preg_match($pattern, $this->contents, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $position = $match[0][1] + strlen($match[0][0]);
            $row = [];
            $token = '';
            $inString = false;
            $escaped = false;
            $depth = 0;
            $quotedValue = false;

            for (; $position < $length; $position++) {
                $character = $this->contents[$position];

                if ($inString) {
                    if ($escaped) {
                        $token .= $this->decodeEscapedCharacter($character);
                        $escaped = false;

                        continue;
                    }

                    if ($character === '\\') {
                        $escaped = true;

                        continue;
                    }

                    if ($character === "'") {
                        if (
                            $position + 1 < $length
                            && $this->contents[$position + 1] === "'"
                        ) {
                            $token .= "'";
                            $position++;

                            continue;
                        }

                        $inString = false;

                        continue;
                    }

                    $token .= $character;

                    continue;
                }

                if ($character === "'") {
                    $inString = true;
                    $quotedValue = true;

                    continue;
                }

                if ($character === '(') {
                    if ($depth === 0) {
                        $row = [];
                        $token = '';
                        $quotedValue = false;
                    }

                    $depth++;

                    continue;
                }

                if ($character === ',' && $depth === 1) {
                    $row[] = $this->normalizeValue($token, $quotedValue);
                    $token = '';
                    $quotedValue = false;

                    continue;
                }

                if ($character === ')' && $depth === 1) {
                    $row[] = $this->normalizeValue($token, $quotedValue);
                    $depth--;

                    if (count($row) !== count($this->columns[$table])) {
                        throw new RuntimeException(
                            "La fila de {$table} tiene ".count($row)
                            .' valores y se esperaban '.count($this->columns[$table]).'.'
                        );
                    }

                    yield array_combine($this->columns[$table], $row);
                    $token = '';
                    $quotedValue = false;

                    continue;
                }

                if ($character === ')') {
                    $depth--;

                    continue;
                }

                if ($character === ';' && $depth === 0) {
                    break;
                }

                if ($depth >= 1) {
                    $token .= $character;
                }
            }

            $offset = $position + 1;
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function extractColumns(): array
    {
        $tables = [];
        preg_match_all(
            '/CREATE TABLE `([^`]+)`\s*\((.*?)\) ENGINE=/s',
            $this->contents,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $columns = [];

            foreach (preg_split('/\R/', $match[2]) as $line) {
                if (preg_match('/^\s*`([^`]+)`\s+/', $line, $columnMatch)) {
                    $columns[] = $columnMatch[1];
                }
            }

            $tables[$match[1]] = $columns;
        }

        return $tables;
    }

    private function normalizeValue(string $value, bool $quoted): ?string
    {
        $value = trim($value);

        if (! $quoted && strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return $value;
    }

    private function decodeEscapedCharacter(string $character): string
    {
        return match ($character) {
            '0' => "\0",
            'b' => "\x08",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => "\x1a",
            default => $character,
        };
    }
}
