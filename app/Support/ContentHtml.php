<?php

namespace App\Support;

use App\Models\Post;
use Throwable;

/**
 * Normalize Publii / editor HTML for clean reading on the public site.
 */
class ContentHtml
{
    /** @var array<int, string|null>|null */
    private static ?array $publiiSlugMap = null;

    public static function prepare(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = self::rewriteMediaUrls($html);
        $html = self::rewriteInternalLinks($html);
        $html = self::stripEditorNoise($html);
        $html = self::sanitizeTableInlineStyles($html);
        $html = self::normalizeTables($html);
        $html = self::enhanceImages($html);
        $html = self::fixOrphanLists($html);
        $html = self::normalizeWhitespace($html);

        return $html;
    }

    /**
     * Resolve Publii placeholders like #INTERNAL_LINK#/page/182 → /gnulinux-402
     */
    private static function rewriteInternalLinks(string $html): string
    {
        if (! str_contains($html, '#INTERNAL_LINK#')) {
            return $html;
        }

        return preg_replace_callback(
            '/#INTERNAL_LINK#\/(page|post)\/(\d+)/i',
            function (array $m) {
                $publiiId = (int) $m[2];
                $slug = self::slugForPubliiId($publiiId);
                if ($slug) {
                    return url('/'.$slug);
                }

                // Fallback: leave a harmless hash so the page doesn't break
                return '#missing-page-'.$publiiId;
            },
            $html
        ) ?? $html;
    }

    private static function slugForPubliiId(int $publiiId): ?string
    {
        if (self::$publiiSlugMap === null) {
            self::$publiiSlugMap = [];
            try {
                self::$publiiSlugMap = Post::query()
                    ->whereNotNull('publii_id')
                    ->pluck('slug', 'publii_id')
                    ->map(fn ($s) => (string) $s)
                    ->all();
            } catch (Throwable) {
                self::$publiiSlugMap = [];
            }
        }

        $slug = self::$publiiSlugMap[$publiiId] ?? null;

        return $slug !== null && $slug !== '' ? $slug : null;
    }

    /**
     * Drop hardcoded light-theme table colors from Publii so dark mode works.
     */
    private static function sanitizeTableInlineStyles(string $html): string
    {
        if (! str_contains(strtolower($html), '<table')) {
            return $html;
        }

        // On table/th/td/tr: remove background-color / color / border-color light-theme locks
        return preg_replace_callback(
            '/<(table|thead|tbody|tfoot|tr|th|td)\b([^>]*)>/iu',
            function (array $m) {
                $tag = $m[1];
                $attrs = $m[2];

                if (preg_match('/\sstyle\s*=\s*([\'"])(.*?)\1/is', $attrs, $sm)) {
                    $style = $sm[2];
                    // strip forced light palette + fixed widths that leave a blank column look
                    $style = preg_replace(
                        '/(?:^|;)\s*(?:background(?:-color)?|color|border-color|width|height|min-width|max-width)\s*:\s*[^;]+/iu',
                        '',
                        $style
                    ) ?? $style;
                    $style = trim($style, " \t\n\r\0\x0B;");
                    if ($style === '') {
                        $attrs = preg_replace('/\sstyle\s*=\s*([\'"]).*?\1/is', '', $attrs) ?? $attrs;
                    } else {
                        $attrs = preg_replace(
                            '/\sstyle\s*=\s*([\'"]).*?\1/is',
                            ' style="'.$style.'"',
                            $attrs
                        ) ?? $attrs;
                    }
                }

                // Publii often sets bgcolor="#ecf0f1"
                $attrs = preg_replace('/\sbgcolor\s*=\s*([\'"]).*?\1/i', '', $attrs) ?? $attrs;
                $attrs = preg_replace('/\swidth\s*=\s*([\'"]).*?\1/i', '', $attrs) ?? $attrs;
                $attrs = preg_replace('/\sheight\s*=\s*([\'"]).*?\1/i', '', $attrs) ?? $attrs;

                return '<'.$tag.$attrs.'>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Wrap tables for scroll, mark empty cells, improve first/index columns.
     */
    private static function normalizeTables(string $html): string
    {
        if (! str_contains(strtolower($html), '<table')) {
            return $html;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__content_root">'.$html.'</div>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->getElementById('__content_root');
        if (! $root) {
            return $html;
        }

        $tables = [];
        foreach ($root->getElementsByTagName('table') as $table) {
            $tables[] = $table;
        }

        foreach ($tables as $table) {
            // Wrap in scroll container once
            $parent = $table->parentNode;
            if ($parent && (! $parent instanceof \DOMElement || $parent->getAttribute('class') !== 'table-scroll')) {
                $wrap = $dom->createElement('div');
                $wrap->setAttribute('class', 'table-scroll');
                $parent->insertBefore($wrap, $table);
                $wrap->appendChild($table);
            }

            // Drop decorative index column (ـ / - / 1,2,3…) common in Publii finance tables
            self::removeIndexColumn($table);

            $rows = [];
            foreach ($table->getElementsByTagName('tr') as $row) {
                $rows[] = $row;
            }
            foreach ($rows as $row) {
                if (! $row instanceof \DOMElement) {
                    continue;
                }
                $cells = [];
                foreach ($row->childNodes as $child) {
                    if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                        $cells[] = $child;
                    }
                }
                foreach ($cells as $cell) {
                    $text = self::cellText($cell);

                    // Empty / placeholder cells → visible dash
                    if ($text === '') {
                        while ($cell->firstChild) {
                            $cell->removeChild($cell->firstChild);
                        }
                        $cell->appendChild($dom->createTextNode('—'));
                        $cell->setAttribute('class', trim(($cell->getAttribute('class') ?: '').' is-empty'));
                    }
                }
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out !== '' ? $out : $html;
    }

    /**
     * Ensure images have alt, loading, and decoding attributes for SEO/a11y.
     */
    private static function enhanceImages(string $html): string
    {
        return preg_replace_callback(
            '/<img\b([^>]*?)>/iu',
            function (array $m) {
                $attrs = $m[1];

                if (! preg_match('/\balt\s*=/i', $attrs)) {
                    $attrs .= ' alt=""';
                }

                if (! preg_match('/\bloading\s*=/i', $attrs)) {
                    $attrs .= ' loading="lazy"';
                }

                if (! preg_match('/\bdecoding\s*=/i', $attrs)) {
                    $attrs .= ' decoding="async"';
                }

                // Prefer width/height preservation if already present; no forced sizes
                return '<img'.$attrs.'>';
            },
            $html
        ) ?? $html;
    }

    private static function rewriteMediaUrls(string $html): string
    {
        $html = str_replace('#DOMAIN_NAME#', asset('media/posts').'/', $html);

        $mediaBase = rtrim(asset('media'), '/').'/';

        // Absolute site media (root or old /laravel-test) → current APP_URL media
        $html = preg_replace(
            '#https?://(?:www\.)?sudoshz\.ir/(?:laravel-test/)?media/#i',
            $mediaBase,
            $html
        ) ?? $html;

        // Root-relative /laravel-test/media/... leftovers
        $html = preg_replace('#(?<=["\'\(])/laravel-test/media/#', $mediaBase, $html) ?? $html;

        // Root-relative /media/... under subdirectory deploy
        $html = preg_replace('#(?<=["\'\(])/media/#', $mediaBase, $html) ?? $html;

        // Bare media/posts without leading slash
        $html = preg_replace(
            '#(?<=["\'\(])(?!https?:|//|/)media/(posts|website|tags|slider)/#',
            $mediaBase.'$1/',
            $html
        ) ?? $html;

        return $html;
    }

    private static function stripEditorNoise(string $html): string
    {
        // Remove spans that only carry Publii editor CSS variables / font noise
        $html = preg_replace_callback(
            '/<span\b([^>]*)>(.*?)<\/span>/is',
            function (array $m) {
                $attrs = $m[1];
                $inner = $m[2];
                if (preg_match('/\bstyle\s*=\s*([\'"])(.*?)\1/is', $attrs, $sm)) {
                    $style = $sm[2];
                    // Drop pure editor chrome styles
                    if (
                        str_contains($style, 'var(--')
                        || str_contains($style, 'editor-font')
                        || preg_match('/font-family\s*:\s*var\(/i', $style)
                    ) {
                        return $inner;
                    }
                }

                return $m[0];
            },
            $html
        ) ?? $html;

        // Remove empty paragraphs / trailing br clutter
        $html = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/iu', '', $html) ?? $html;
        $html = preg_replace('/(?:<br\s*\/?>\s*){3,}/i', '<br><br>', $html) ?? $html;

        // data-link-popup-id and empty class=""
        $html = preg_replace('/\sdata-link-popup-id="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/\sclass=""/i', '', $html) ?? $html;

        return $html;
    }

    /**
     * Fix broken list markup from Publii (orphan <li>, li mixed with nested ul).
     */
    private static function fixOrphanLists(string $html): string
    {
        if (! str_contains($html, '<li')) {
            return $html;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__content_root">'.$html.'</div>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->getElementById('__content_root');
        if (! $root) {
            return $html;
        }

        self::wrapOrphanLisInNode($dom, $root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out !== '' ? $out : $html;
    }

    private static function wrapOrphanLisInNode(\DOMDocument $dom, \DOMElement $parent): void
    {
        // Recurse first
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                self::wrapOrphanLisInNode($dom, $child);
            }
        }

        // Don't wrap inside existing lists
        if (in_array(strtolower($parent->tagName), ['ul', 'ol'], true)) {
            return;
        }

        $buffer = [];
        $flush = function () use ($dom, $parent, &$buffer) {
            if ($buffer === []) {
                return;
            }
            $ul = $dom->createElement('ul');
            $ul->setAttribute('class', 'content-list');
            $first = $buffer[0];
            $parent->insertBefore($ul, $first);
            foreach ($buffer as $li) {
                $ul->appendChild($li);
            }
            $buffer = [];
        };

        $nodes = [];
        foreach ($parent->childNodes as $child) {
            $nodes[] = $child;
        }

        foreach ($nodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->tagName) === 'li') {
                $buffer[] = $child;
                continue;
            }
            // Whitespace-only text between lis keeps the buffer open
            if ($child instanceof \DOMText && trim($child->wholeText) === '') {
                continue;
            }
            $flush();
        }
        $flush();
    }

    private static function normalizeWhitespace(string $html): string
    {
        // Don't collapse inside pre/code
        return $html;
    }

    private static function cellText(\DOMElement $cell): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent ?? '') ?? '');
        $text = str_replace("\xC2\xA0", '', $text); // nbsp

        return trim($text);
    }

    /**
     * Remove leading index column when header is dash/empty and body is mostly row numbers.
     */
    private static function removeIndexColumn(\DOMElement $table): void
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $row) {
            if ($row instanceof \DOMElement) {
                $rows[] = $row;
            }
        }
        if (count($rows) < 2) {
            return;
        }

        $rowCells = [];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($row->childNodes as $child) {
                if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                    $cells[] = $child;
                }
            }
            if ($cells !== []) {
                $rowCells[] = $cells;
            }
        }
        if (count($rowCells) < 2) {
            return;
        }

        // Need at least 3 columns so removing one still leaves a useful table
        $maxCols = 0;
        foreach ($rowCells as $cells) {
            $maxCols = max($maxCols, count($cells));
        }
        if ($maxCols < 3) {
            return;
        }

        $headerText = self::cellText($rowCells[0][0]);
        $headerIsIndex = $headerText === ''
            || in_array($headerText, ['-', 'ـ', '—', '#', 'ردیف', 'ر', 'ش'], true);

        if (! $headerIsIndex) {
            return;
        }

        $indexLike = 0;
        $bodyRows = 0;
        for ($i = 1, $n = count($rowCells); $i < $n; $i++) {
            if (! isset($rowCells[$i][0])) {
                continue;
            }
            $bodyRows++;
            $t = self::cellText($rowCells[$i][0]);
            // row number, dash, or total labels that sit in index col on some tables
            if (
                $t === ''
                || in_array($t, ['-', 'ـ', '—'], true)
                || preg_match('/^\d+$/u', $t)
                || in_array($t, ['جمع', 'مانده', 'مجموع', 'کل'], true)
            ) {
                $indexLike++;
            }
        }

        if ($bodyRows === 0 || ($indexLike / $bodyRows) < 0.7) {
            return;
        }

        // Remove first cell of each row; keep meaningful labels (جمع / مانده)
        foreach ($rowCells as $cells) {
            $first = $cells[0] ?? null;
            if (! $first) {
                continue;
            }
            $label = self::cellText($first);
            $keepLabel = in_array($label, ['جمع', 'مانده', 'مجموع', 'کل'], true);

            if ($keepLabel && isset($cells[1])) {
                $second = $cells[1];
                $doc = $second->ownerDocument;
                $secondText = self::cellText($second);
                if ($doc && $secondText !== '' && ! str_starts_with($secondText, $label)) {
                    $prefix = $doc->createElement('strong');
                    $prefix->appendChild($doc->createTextNode($label.': '));
                    $second->insertBefore($prefix, $second->firstChild);
                } elseif ($doc && $secondText === '') {
                    while ($second->firstChild) {
                        $second->removeChild($second->firstChild);
                    }
                    $second->appendChild($doc->createTextNode($label));
                }
            }

            $first->parentNode?->removeChild($first);
        }
    }
}
