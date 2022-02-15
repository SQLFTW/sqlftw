<?php declare(strict_types = 1);

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;

class Session
{
    use StrictBehaviorMixin;

    /** @var ReflectionProvider */
    private $provider;

    /** @var string|null */
    private $schema;

    /** @var VariablesReflection */
    private $variables;

    /** @var VariablesReflection */
    private $userVariables;

    public function __construct(ReflectionProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getProvider(): ReflectionProvider
    {
        return $this->provider;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function changeSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    public function getVariables(): VariablesReflection
    {
        if ($this->variables === null) {
            $this->variables = $this->provider->loadVariables();
        }

        return $this->variables;
    }

    public function getUserVariables(): VariablesReflection
    {
        if ($this->userVariables === null) {
            $this->userVariables = $this->provider->loadUserVariables();
        }

        return $this->userVariables;
    }

}
