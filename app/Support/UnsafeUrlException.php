<?php

namespace App\Support;

use RuntimeException;

/** Thrown when a user-supplied URL is rejected by the SSRF guard or fails to fetch. */
class UnsafeUrlException extends RuntimeException {}
