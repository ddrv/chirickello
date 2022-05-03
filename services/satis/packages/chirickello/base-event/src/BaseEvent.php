<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\BaseEvent;

use JsonSerializable;

abstract class BaseEvent implements JsonSerializable
{

    /**
     * @return array
     */
    abstract public function jsonSerialize(): array;
}
