<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Enum;

class LargeFileUploadAttachmentType extends Enum {
    public const FILE = 'file';
    public const ITEM = 'item';
    public const REFERENCE = 'reference';
}
