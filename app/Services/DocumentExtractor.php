<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * DocumentExtractor — turns uploaded files / remote URLs into plain
 * text suitable for stuffing into an AI prompt.
 *
 * Supported inputs:
 *   - PDF       via smalot/pdfparser
 *   - DOCX      via ZipArchive (read word/document.xml, strip XML)
 *   - TXT / MD  via file_get_contents
 *   - URL       via HTTP GET + strip_tags
 *
 * Each call is capped to PER_SOURCE_CHAR_LIMIT to keep AI prompts
 * within sensible token budgets.
 */
class DocumentExtractor
{
    /** Hard cap per source. Combined sources are capped further by the caller. */
    public const PER_SOURCE_CHAR_LIMIT = 60_000;

    public function fromPath(string $path, ?string $originalName = null, ?string $mime = null): string
    {
        $ext = strtolower(pathinfo($originalName ?? $path, PATHINFO_EXTENSION));

        $text = match (true) {
            $ext === 'pdf' || ($mime && str_contains($mime, 'pdf'))
                => $this->fromPdf($path),
            $ext === 'docx' || ($mime && str_contains($mime, 'wordprocessingml'))
                => $this->fromDocx($path),
            in_array($ext, ['txt', 'md', 'csv', 'html', 'htm'], true)
                || ($mime && str_starts_with($mime, 'text/'))
                => $this->fromText($path),
            default => throw new RuntimeException(
                "Unsupported file type: " . ($originalName ?: $path)
                . " (extension: {$ext}, mime: " . ($mime ?: 'unknown') . ")"
            ),
        };

        return $this->cap($this->cleanWhitespace($text));
    }

    public function fromUrl(string $url): string
    {
        $url = trim($url);
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        try {
            $response = Http::timeout(20)
                ->withUserAgent('KynexEdu DocumentExtractor (+https://kynexedu.com)')
                ->withHeaders(['Accept' => 'text/html,application/pdf,*/*'])
                ->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to fetch URL: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            throw new RuntimeException("URL returned HTTP {$response->status()}: {$url}");
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $body = (string) $response->body();

        if (str_contains($contentType, 'pdf')) {
            $tmp = tempnam(sys_get_temp_dir(), 'docx-');
            file_put_contents($tmp, $body);
            try {
                return $this->cap($this->cleanWhitespace($this->fromPdf($tmp)));
            } finally {
                @unlink($tmp);
            }
        }

        if (str_contains($contentType, 'html')) {
            return $this->cap($this->cleanWhitespace($this->htmlToText($body)));
        }

        // Plain text or unknown — best-effort treat as text.
        return $this->cap($this->cleanWhitespace($body));
    }

    protected function fromPdf(string $path): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning('PDF parse failed', ['path' => $path, 'error' => $e->getMessage()]);
            throw new RuntimeException("Could not read PDF: {$e->getMessage()}");
        }
    }

    protected function fromDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open DOCX archive.');
        }
        try {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml === false) {
                throw new RuntimeException('DOCX missing word/document.xml');
            }
            // Insert paragraph breaks at </w:p> so prose stays readable
            // after we strip the XML tags.
            $xml = str_replace(['</w:p>', '<w:tab/>', '<w:br/>'], ["\n\n", "\t", "\n"], $xml);
            $text = strip_tags($xml);
            return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            $zip->close();
        }
    }

    protected function fromText(string $path): string
    {
        $text = @file_get_contents($path);
        if ($text === false) {
            throw new RuntimeException('Could not read file');
        }
        // If it's HTML, strip tags too.
        if (stripos($text, '<html') !== false || stripos($text, '<body') !== false) {
            return $this->htmlToText($text);
        }
        return $text;
    }

    protected function htmlToText(string $html): string
    {
        // Drop scripts and styles before stripping.
        $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        // Newlines around block-level elements.
        $html = preg_replace('#</?(p|div|br|li|h[1-6]|tr)[^>]*>#i', "\n", $html);
        $text = strip_tags($html);
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function cleanWhitespace(string $text): string
    {
        // Collapse Windows line endings, trim trailing whitespace,
        // limit consecutive blank lines.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    protected function cap(string $text, int $limit = self::PER_SOURCE_CHAR_LIMIT): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . "\n\n[...content truncated to {$limit} chars]";
    }
}
