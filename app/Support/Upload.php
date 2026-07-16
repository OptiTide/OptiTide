<?php

namespace App\Support;

/**
 * Safe handling of user-uploaded files (CVs on the careers form).
 *
 * An upload endpoint is the highest-risk surface on a public site — get it wrong
 * and it's remote code execution. The guarantees here, in order of importance:
 *
 *  1. Files are written UNDER storage/, which is outside the webroot (docroot is
 *     public/), so an uploaded file is never reachable by URL and never executed.
 *  2. The stored basename is server-generated randomness plus an extension taken
 *     from our own allow-list — never any part of the client's filename. This
 *     kills path traversal ("../../x"), null bytes, and double extensions
 *     ("cv.pdf.php") at the source rather than by sanitising them.
 *  3. Both the extension AND the sniffed MIME must be allow-listed, and the MIME
 *     must be one the extension is allowed to have.
 *  4. Size is capped before anything touches disk.
 *  5. Retrieval goes through path() which pins the file inside the base dir, so a
 *     tampered DB value still can't escape.
 *
 * The client's original filename is kept only as a display label, and is escaped
 * at render time like any other untrusted string.
 */
final class Upload
{
    /** Our policy limit. The effective limit is maxBytes() — see below. */
    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    /**
     * The limit we can ACTUALLY honour: the smaller of our policy and PHP's own
     * ini caps. PHP rejects an oversized upload before any of our code runs, so
     * if the ini is lower than MAX_BYTES the form would promise a size it then
     * refuses. Advertising and enforcing this value keeps the message truthful
     * on any deployment (the Dockerfile raises the ini to match).
     */
    public static function maxBytes(): int
    {
        $limits = [self::MAX_BYTES];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes((string) ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return min($limits);
    }

    /** Parse PHP's ini shorthand ("2M", "8K", "1G") into bytes. */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $n = (int) $value;

        return match ($unit) {
            'g'     => $n * 1024 * 1024 * 1024,
            'm'     => $n * 1024 * 1024,
            'k'     => $n * 1024,
            default => $n,
        };
    }

    /**
     * extension => MIME types that extension may legitimately sniff as.
     * doc/docx/odt are container formats, so finfo commonly reports the
     * container (OLE2 / zip) rather than the office type — both are accepted for
     * those, which is safe because the file is never executed or served inline.
     */
    public const RESUME_TYPES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'odt'  => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'rtf'  => ['application/rtf', 'text/rtf', 'text/plain'],
        'txt'  => ['text/plain'],
    ];

    /**
     * Validate + store an upload. Returns [path, name, size] on success.
     *
     * @param  array<string,mixed>              $file  a single $_FILES entry
     * @param  array<string,array<int,string>>  $allowed  extension => MIME allow-list
     * @return array{path:string,name:string,size:int}
     *
     * @throws UploadException with a message safe to show the user
     */
    public static function store(array $file, string $subdir, array $allowed, ?int $maxBytes = null): array
    {
        $maxBytes ??= self::maxBytes();
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large — please keep it under ' . self::maxLabel($maxBytes) . '.',
                UPLOAD_ERR_PARTIAL                        => 'The upload didn\'t finish — please try again.',
                UPLOAD_ERR_NO_FILE                        => 'No file was uploaded.',
                default                                   => 'We couldn\'t accept that file — please try again.',
            });
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        // The one check that proves this really came through PHP's upload
        // handler and isn't an arbitrary server path smuggled in.
        if ($tmp === '' || ! is_uploaded_file($tmp)) {
            throw new UploadException('We couldn\'t accept that file — please try again.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new UploadException('That file appears to be empty.');
        }
        if ($size > $maxBytes) {
            throw new UploadException('That file is too large — please keep it under ' . self::maxLabel($maxBytes) . '.');
        }

        // Extension comes from the client, so it is only ever used to LOOK UP an
        // entry in our allow-list — its value is never written to disk.
        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '' || ! isset($allowed[$ext])) {
            throw new UploadException('Please upload one of: ' . strtoupper(implode(', ', array_keys($allowed))) . '.');
        }

        $mime = self::sniff($tmp);
        if (! in_array($mime, $allowed[$ext], true)) {
            throw new UploadException('That file doesn\'t look like a real ' . strtoupper($ext) . ' — please re-save it and try again.');
        }

        $dir = self::baseDir($subdir);
        if (! is_dir($dir) && ! @mkdir($dir, 0770, true) && ! is_dir($dir)) {
            throw new UploadException('We couldn\'t save that file right now — please try again shortly.');
        }

        // Server-generated name: no client input reaches the filesystem path.
        $basename = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $basename;

        if (! move_uploaded_file($tmp, $target)) {
            throw new UploadException('We couldn\'t save that file right now — please try again shortly.');
        }
        @chmod($target, 0640);

        return [
            'path' => $subdir . '/' . $basename,
            'name' => self::displayName((string) ($file['name'] ?? ''), $ext),
            'size' => $size,
        ];
    }

    /**
     * Absolute path for a stored file, or null if it escapes the base directory
     * or no longer exists. Defence in depth: even a tampered/corrupted DB value
     * cannot be used to read an arbitrary file off the server.
     */
    public static function path(?string $storedPath): ?string
    {
        $storedPath = trim((string) $storedPath);
        if ($storedPath === '') {
            return null;
        }

        $root = realpath(storage_path());
        if ($root === false) {
            return null;
        }

        // Rebuild from a sanitised subdir + basename rather than trusting the
        // stored string wholesale.
        $subdir = str_replace('\\', '/', dirname($storedPath));
        $subdir = preg_replace('/[^a-z0-9_\-\/]/i', '', $subdir) ?? '';
        if ($subdir === '' || $subdir === '.' || str_contains($subdir, '..')) {
            return null;
        }
        $full = storage_path($subdir . '/' . basename(str_replace('\\', '/', $storedPath)));

        $real = realpath($full);
        if ($real === false || ! is_file($real)) {
            return null;
        }

        // Must resolve to something genuinely inside storage/.
        if (! str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $real;
    }

    /** Remove a stored file. Safe to call with a missing/blank path. */
    public static function delete(?string $storedPath): void
    {
        $real = self::path($storedPath);
        if ($real !== null) {
            @unlink($real);
        }
    }

    /** Best-guess MIME for a download response; never trusted from the client. */
    public static function mimeFor(string $ext): string
    {
        return self::RESUME_TYPES[strtolower($ext)][0] ?? 'application/octet-stream';
    }

    public static function sizeLabel(?int $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '';
        }

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : max(1, (int) round($bytes / 1024)) . ' KB';
    }

    private static function baseDir(string $subdir): string
    {
        // Callers pass a literal, but never build a path from unvalidated input.
        $subdir = preg_replace('/[^a-z0-9_\-]/i', '', $subdir) ?: 'uploads';

        return storage_path($subdir);
    }

    private static function sniff(string $tmp): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return '';
        }
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        return is_string($mime) ? $mime : '';
    }

    /** A tidy label for the UI. Display only — never a filesystem path. */
    private static function displayName(string $clientName, string $ext): string
    {
        $base = pathinfo(str_replace(["\0", '/', '\\'], '', $clientName), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[^\w \-\.]/u', '', $base) ?? '');
        $base = $base === '' ? 'resume' : mb_substr($base, 0, 80);

        return $base . '.' . $ext;
    }

    private static function maxLabel(int $bytes): string
    {
        return (int) round($bytes / 1048576) . ' MB';
    }
}
