<?php

namespace App\enums;

use JsonSerializable;

enum ImportTypesEnums implements JsonSerializable
{
    case UNKNOWN;
    case AHA;
    case JIRA;
    case AZURE;

    public function jsonSerialize(): string
    {
        return $this->getType();
    }

    public function getType(): string
    {
        return match ($this) {
            self::UNKNOWN => 'UNKNOWN',
            self::AHA => 'AHA',
            self::JIRA => 'JIRA',
            self::AZURE => 'AZURE',
        };
    }
}
