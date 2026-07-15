<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCode
{
    /** Render data as an inline SVG string (no XML prolog, safe to embed). */
    public static function svg(string $data, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));
        $svg = $writer->writeString($data);

        // Strip the XML declaration so it embeds cleanly inside HTML.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }
}
