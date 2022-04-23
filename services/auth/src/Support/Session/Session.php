<?php

declare(strict_types=1);

namespace Chirickello\Auth\Support\Session;

use ArrayObject;

class Session extends ArrayObject
{
    private array $flash = [
        'prev' => [],
        'curr' => [],
    ];

    public function offsetExists($key): bool
    {
        if (array_key_exists($key, $this->flash['prev'])) {
            return true;
        }
        if (array_key_exists($key, $this->flash['curr'])) {
            return true;
        }
        return parent::offsetExists($key);
    }

    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->flash['prev'])) {
            return $this->flash['prev'][$key];
        }
        if (array_key_exists($key, $this->flash['curr'])) {
            return $this->flash['curr'][$key];
        }
        return parent::offsetGet($key);
    }

    public function flash(string $key, $data): void
    {
        $this->flash['curr'][$key] = $data;
    }

    public function beforeSave()
    {
        $this->flash['prev'] = $this->flash['curr'];
        $this->flash['curr'] = [];
    }
}
