<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Enum;

class LargeFileUploadConflictBehavior extends Enum {
    public const FAIL = 'fail';
    public const RENAME = 'rename';
    public const REPLACE = 'replace';
}
