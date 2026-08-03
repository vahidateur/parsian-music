<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * A record cannot be deleted because another record still depends on it.
 *
 * The message is already localized by the Action that raises it, so the
 * controller can hand it straight to the Feedback_Channel without exposing any
 * implementation detail.
 *
 * Requirements: 6.9, 7.7
 */
class RecordInUseException extends DomainException
{
}
