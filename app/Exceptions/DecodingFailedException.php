<?php

namespace App\Exceptions;

/**
 * Thrown when the document decoder (YandexGPT) fails
 * — API error, timeout, or unexpected response format.
 *
 * Caught by webhook controllers and shown to the user
 * as a friendly error message.
 */
class DecodingFailedException extends \RuntimeException {}
