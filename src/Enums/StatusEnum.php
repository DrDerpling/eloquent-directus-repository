<?php

declare(strict_types=1);

namespace DrDerpling\DirectusRepository\Enums;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
}
