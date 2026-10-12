<?php

namespace App\Services;

/**
 * Minimal dependency-free PDF writer for tabular reports (A4 landscape,
 * Helvetica). Used when barryvdh/laravel-dompdf is not installed, so PDF
 * export works out of the box. If dompdf IS installed, ReportController
 * prefers it for richer rendering.
 */
class SimplePdf
{
    private const PAGE_W = 841.89;  // A4 landscape, points
    private const PAGE_H = 595.28;
    private const MARGIN = 28;

    private array $pages = [];
    private string $stream = '';
    private float $y = 0;

    public function __construct()
    {
        $this->newPage();
    }

    public function newPage(): void
    {
        if ($this->stream !== '') {
            $this->pages[] = $this->stream;
        }
        $this->stream = '';
        $this->y = self::PAGE_H - self::MARGIN;
    }

    public function remaining(): float
    {
        return $this->y - self::MARGIN;
    }

    public function text(float $x, float $size, string $text, bool $bold = false, float $gray = 0): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->stream .= sprintf(
            "BT /%s %.2f Tf %.3f g %.2f %.2f Td (%s) Tj ET\n",
            $font, $size, $gray, $x, $this->y, $this->escape($text),
        );
    }

    public function row(array $cells, array $xs, array $widths, float $size, bool $bold = false, float $gray = 0): void
    {
        foreach ($cells as $i => $cell) {
            $this->stream .= sprintf(
                "BT /%s %.2f Tf %.3f g %.2f %.2f Td (%s) Tj ET\n",
                $bold ? 'F2' : 'F1', $size, $gray, $xs[$i], $this->y,
                $this->escape($this->fit((string) $cell, $widths[$i], $size)),
            );
        }
    }

    public function advance(float $points): void
    {
        $this->y -= $points;
    }

    /**
     * Draw a row where long cell values wrap onto multiple lines (up to
     * $maxLines) instead of being truncated. Returns the number of lines
     * the tallest cell used, so the caller can advance accordingly.
     */
    public function rowWrap(array $cells, array $xs, array $widths, float $size, bool $bold = false, int $maxLines = 3): int
    {
        $lineHeight = $size + 3;
        $wrapped = [];
        $rowLines = 1;
        foreach ($cells as $i => $cell) {
            $lines = $this->wrap((string) $cell, $widths[$i], $size, $maxLines);
            $wrapped[$i] = $lines;
            $rowLines = max($rowLines, count($lines));
        }
        foreach ($wrapped as $i => $lines) {
            foreach ($lines as $n => $line) {
                $this->stream .= sprintf(
                    "BT /%s %.2f Tf 0 g %.2f %.2f Td (%s) Tj ET
",
                    $bold ? 'F2' : 'F1', $size, $xs[$i], $this->y - $n * $lineHeight,
                    $this->escape($line),
                );
            }
        }

        return $rowLines;
    }

    /**
     * Word-wrap text into lines that fit the given column width.
     */
    private function wrap(string $text, float $width, float $size, int $maxLines): array
    {
        $maxChars = max(3, (int) floor(($width - 3) / ($size * 0.52)));
        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }
        $words = preg_split('/\s+/', $text) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            // hard-break words longer than a line
            while (mb_strlen($word) > $maxChars) {
                if ($current !== '') { $lines[] = $current; $current = ''; }
                $lines[] = mb_substr($word, 0, $maxChars);
                $word = mb_substr($word, $maxChars);
            }
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($candidate) <= $maxChars) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = $lines[$maxLines - 1];
            $lines[$maxLines - 1] = mb_substr($last, 0, max(1, $maxChars - 1)).'…';
        }

        return $lines;
    }

    public function hline(float $x1, float $x2): void
    {
        $this->stream .= sprintf("0.6 g %.2f %.2f m %.2f %.2f l S 0 g\n", $x1, $this->y + 2, $x2, $this->y + 2);
    }

    public static function contentWidth(): float
    {
        return self::PAGE_W - 2 * self::MARGIN;
    }

    public static function margin(): float
    {
        return self::MARGIN;
    }

    /**
     * Truncate a string to fit a column width (Helvetica avg glyph ≈ 0.5em).
     */
    private function fit(string $text, float $width, float $size): string
    {
        $maxChars = (int) floor($width / ($size * 0.52));
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $maxChars - 1)).'…';
    }

    private function escape(string $text): string
    {
        // The built-in fonts only know Latin-1. Replace common typographic characters
        // first, then convert. Works whether or not the PHP iconv extension is installed
        // (calling iconv() without the extension crashed the whole PDF export).
        $text = strtr($text, [
            '₦' => 'NGN ', '—' => '-', '–' => '-', '…' => '...', '‘' => "'", '’' => "'",
            '“' => '"', '”' => '"', '•' => '-', '›' => '>', '‹' => '<', '×' => 'x',
        ]);

        if (function_exists('iconv')) {
            $latin = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        } elseif (function_exists('mb_convert_encoding')) {
            $latin = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        } else {
            $latin = false;
        }
        if ($latin === false || $latin === '') {
            $latin = preg_replace('/[^\x20-\x7E]/', '?', $text);
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $latin);
    }

    /**
     * Assemble the final PDF bytes.
     */
    public function output(): string
    {
        if ($this->stream !== '') {
            $this->pages[] = $this->stream;
            $this->stream = '';
        }

        $objects = [];
        $pageCount = count($this->pages);

        // 1: catalog, 2: pages tree, 3: F1, 4: F2, then page+content pairs
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $kids = [];
        $objNum = 5;
        $pageObjs = [];
        foreach ($this->pages as $content) {
            $pageObjNum = $objNum++;
            $contentObjNum = $objNum++;
            $kids[] = "{$pageObjNum} 0 R";
            $pageObjs[$pageObjNum] = sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>",
                self::PAGE_W, self::PAGE_H, $contentObjNum,
            );
            $pageObjs[$contentObjNum] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content);
        }
        $objects[2] = "<< /Type /Pages /Kids [".implode(' ', $kids)."] /Count {$pageCount} >>";
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects += $pageObjs;
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxObj = max(array_keys($objects));
        $pdf .= "xref\n0 ".($maxObj + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size ".($maxObj + 1)." /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
