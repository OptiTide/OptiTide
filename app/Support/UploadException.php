<?php

namespace App\Support;

/**
 * Thrown when an upload is rejected. The message is written to be shown to the
 * person uploading, so it must never leak paths, MIME internals or server state.
 */
final class UploadException extends \RuntimeException
{
}
