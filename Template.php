<?php namespace Ewll\MailerBundle;

class Template
{
    private $name;
    private $bundle;
    private $data;

    public function __construct(string $name, string $bundle, array $data)
    {
        $this->name = $name;
        $this->bundle = $bundle;
        $this->data = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBundle(): string
    {
        return $this->bundle;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
