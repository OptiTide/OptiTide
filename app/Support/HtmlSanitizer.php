<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitiser for untrusted rich text (AI-authored blog bodies)
 * rendered on the public site. Regex-based sanitising is fundamentally
 * bypassable (entity-encoded schemes, slash-separated attributes, etc.), so
 * this parses the real DOM and keeps only an explicit tag + attribute
 * allow-list, validating URL schemes after the parser has entity-decoded them.
 */
class HtmlSanitizer
{
    /** @var array<string, array<int, string>> tag => allowed attributes */
    protected array $allowed = [
        'h2' => [], 'h3' => [], 'p' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'strong' => [], 'em' => [], 'a' => ['href'], 'blockquote' => [], 'br' => [],
    ];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML prolog forces UTF-8; the <body> wrapper gives a stable root.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_NOERROR | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return '';
        }

        $this->sanitizeChildren($body);

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    protected function sanitizeChildren(DOMNode $node): void
    {
        // Snapshot the list first — we mutate the tree as we go.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue; // text nodes are safe (serialised escaped)
            }

            $tag = strtolower($child->tagName);

            if (! array_key_exists($tag, $this->allowed)) {
                // Disallowed element (script/style/iframe/…): drop the subtree.
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes ?? []) as $attr) {
                $name = strtolower($attr->name);

                if (! in_array($name, $this->allowed[$tag], true)) {
                    $child->removeAttribute($attr->name);

                    continue;
                }

                if ($name === 'href' && ! $this->isSafeUrl($attr->value)) {
                    $child->removeAttribute($attr->name);
                }
            }

            $this->sanitizeChildren($child);
        }
    }

    /** Allow only http(s)/mailto and relative/anchor URLs; reject javascript:, data:, etc. */
    protected function isSafeUrl(string $url): bool
    {
        // The DOM already entity-decoded the value; strip the whitespace/control
        // characters a browser ignores inside a scheme (the classic bypass).
        $normalized = preg_replace('/[\x00-\x20]+/', '', trim($url));

        if ($normalized === '' || $normalized === null) {
            return false;
        }

        if (str_starts_with($normalized, '/') || str_starts_with($normalized, '#')) {
            return true; // relative path or in-page anchor
        }

        if (preg_match('#^(https?:|mailto:)#i', $normalized)) {
            return true;
        }

        // Any other explicit scheme (javascript:, data:, vbscript:, …) is unsafe.
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $normalized)) {
            return false;
        }

        return true; // schemeless relative reference
    }
}
