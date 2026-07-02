<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// EXCEPTION HIERARCHY
// ============================================================================

class AppException        extends \Exception {}
class ValidationException extends AppException {}  // USER-FACING: Message shown verbatim.
class SecurityException   extends AppException {}  // INTERNAL: Generic message to client, full detail to error_log.
class StorageException    extends AppException {}  // INTERNAL: Generic message to client, full detail to error_log.
