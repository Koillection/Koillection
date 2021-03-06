<?php

declare(strict_types=1);

namespace App\Service;

class FeatureChecker
{
    private ContextHandler $contextHandler;

    public function __construct(ContextHandler $contextHandler)
    {
        $this->contextHandler = $contextHandler;
    }

    public function isFeatureEnabled(string $feature): bool
    {
        $getter = 'is' . ucfirst($feature) . 'FeatureEnabled';

        return $this->contextHandler->getContextUser()->$getter();
    }
}
