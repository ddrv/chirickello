<?php

declare(strict_types=1);

namespace Chirickello\Package\EventPacker;

interface EventTransformerInterface
{
    public function transform(object $event): object;
}
