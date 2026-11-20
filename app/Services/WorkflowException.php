<?php

namespace App\Services;

use RuntimeException;

/** Action refusée par les règles de délivrance (message affiché à l'utilisateur). */
class WorkflowException extends RuntimeException
{
}
