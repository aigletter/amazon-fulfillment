<?php

namespace App\Data;

class TestBuyer implements BuyerInterface
{
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __get(string $name)
    {
        return $this->data[$name];
    }

    public function __set(string $name, $value): void
    {
        $this->offsetSet($name, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }
}