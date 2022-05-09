<?php

declare(strict_types=1);

namespace Chirickello\Package\Event;

use JsonSerializable;

abstract class BaseEvent implements JsonSerializable
{

    /**
     * @return object
     */
    abstract public function jsonSerialize(): object;
}
