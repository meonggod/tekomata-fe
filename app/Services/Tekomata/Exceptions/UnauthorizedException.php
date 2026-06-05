<?php

namespace App\Services\Tekomata\Exceptions;

/**
 * The Go API rejected our credentials/token (HTTP 401). The caller should
 * attempt a token refresh once, then send the user back to login.
 */
class UnauthorizedException extends TekomataApiException {}
