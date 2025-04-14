<?php

class Program
{
    private int $id;
    private string $name;
    private string $type;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $this->getTypeInit($name);
    }

    private function getTypeInit($name)
    {
        if (stripos(strtolower($name), 'maestría') !== false) {
            return 'Maestría';
        } else {
            return 'Doctorado';
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return ucwords($this->name);
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->type = $this->getTypeInit($name);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

}
