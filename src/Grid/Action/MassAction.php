<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\Grid\Action;

class MassAction implements MassActionInterface
{
    private string $title;

    private ?string $callback;

    private bool $confirm;

    private string $confirmMessage;

    private array $parameters = [];

    private ?string $role;

    public function __construct(
        string $title,
        ?string $callback = null,
        bool $confirm = false,
        array $parameters = [],
        ?string $role = null
    ) {
        $this->title          = $title;
        $this->callback       = $callback;
        $this->confirm        = $confirm;
        $this->confirmMessage = 'Do you want to ' . strtolower($title) . ' the selected rows?';
        $this->parameters     = $parameters;
        $this->role           = $role;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCallback(string $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): ?string
    {
        return $this->callback;
    }

    public function setConfirm(bool $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirmMessage(string $confirmMessage): self
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    public function getConfirmMessage(): string
    {
        return $this->confirmMessage;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}
