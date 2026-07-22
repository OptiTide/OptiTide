<?php

namespace App\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * DOM allow-list sanitiser for admin-authored article HTML.
 *
 * Landing page and blog bodies are written in the admin and rendered raw into a
 * public page. "Only staff can write it" is not a security boundary — a
 * compromised or malicious staff account turns that into stored XSS on every
 * visitor, including admins, which is a session-theft chain.
 *
 * Allow-list, not block-list, and DOM-based, not regex. A regex sanitiser is
 * bypassable in ways that are genuinely hard to see: entity-encoded schemes
 * (`java&#115;cript:`), slash- or newline-separated handlers, nested broken tags
 * that a browser silently repairs into something executable. Parsing to a DOM and
 * keeping only known-good elements and attributes has no such gaps — anything not
 * explicitly permitted is dropped, including anything invented after this was
 * written.
 */
final class HtmlSanitizer
{
    /** tag => attributes permitted on it */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 'small' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => ['cite'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['scope'], 'td' => [],
        'code' => [], 'pre' => [],
        'span' => ['class'], 'div' => ['class'],
        'section' => ['class'], 'article' => ['class'],
    ];

    /** Only these URL schemes may appear in href/src. */
    private const SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument();

        // Parse as UTF-8 without letting libxml add <html>/<body> wrappers or choke
        // on HTML5 elements it doesn't know.
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="ot-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($doc);
        $root = $xpath->query('//*[@id="ot-root"]')->item(0);

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::scrub($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    /** Depth-first: drop disallowed elements, keep their text. */
    private static function scrub(DOMNode $node): void
    {
        // Snapshot: the live NodeList shifts as nodes are removed mid-iteration.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    // script/style carry no text worth keeping — remove entirely.
                    // Anything else: unwrap so the words survive, the markup doesn't.
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) {
                        $child->parentNode->removeChild($child);
                        continue;
                    }

                    self::scrub($child);
                    while ($child->firstChild) {
                        $child->parentNode->insertBefore($child->firstChild, $child);
                    }
                    $child->parentNode->removeChild($child);
                    continue;
                }

                self::scrubAttributes($child, self::ALLOWED[$tag]);
                self::scrub($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                // Conditional comments have historically been an execution vector.
                $child->parentNode->removeChild($child);
            }
        }
    }

    /** @param string[] $allowed */
    private static function scrubAttributes(DOMElement $el, array $allowed): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            /** @var DOMAttr $attr */
            $name = strtolower($attr->nodeName);

            // This drops every on* handler as a class, not one by one.
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if (($name === 'href' || $name === 'src') && ! self::safeUrl($attr->nodeValue)) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        // A link opening in a new tab without noopener hands the opener window to
        // the destination page.
        if (strtolower($el->nodeName) === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        // Relative and anchor links are fine and have no scheme to check.
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        // Decode entities BEFORE inspecting: "java&#115;cript:alert(1)" is exactly
        // how a naive check gets walked past.
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip characters a browser tolerates inside a scheme but a parser doesn't.
        $decoded = preg_replace('/[\s\x00-\x1F\x7F]/', '', $decoded) ?? '';

        $scheme = strtolower((string) parse_url($decoded, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, self::SCHEMES, true);
    }
}
