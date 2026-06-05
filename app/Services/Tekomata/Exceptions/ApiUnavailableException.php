<?php

namespace App\Services\Tekomata\Exceptions;

/**
 * The Go API could not be reached, timed out, or returned 5xx after retries.
 * Rendered to users as a friendly "try again shortly" page — never a stack trace.
 */
class ApiUnavailableException extends TekomataApiException {}
