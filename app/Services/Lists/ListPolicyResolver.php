<?php

namespace App\Services\Lists;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Resolves display-only ability flags for a list and its rows.
 *
 * The resolver never authorizes a mutation: it only tells the view which
 * controls to render. Every state-changing endpoint enforces the same named
 * ability server-side, so a stale or permissive flag can never mutate data.
 *
 * When no policy is registered for a model, the coarse `role` middleware remains
 * the boundary and the flag stays true so existing controls are not silently
 * removed.
 */
final class ListPolicyResolver
{
    public function __construct(private readonly ?Authenticatable $actor = null) {}

    /**
     * @param Model|class-string<Model> $target
     */
    public function allows(string $ability, Model|string $target): bool
    {
        if ($this->actor === null) {
            return false;
        }

        if (Gate::getPolicyFor($target) === null) {
            return true;
        }

        return Gate::forUser($this->actor)->allows(
            $ability,
            is_string($target) ? [$target] : $target
        );
    }

    /**
     * @param array<int, string> $abilities
     * @param Model|class-string<Model> $target
     * @return array<int, string>
     */
    public function allowedFrom(array $abilities, Model|string $target): array
    {
        return array_values(array_filter(
            $abilities,
            fn (string $ability): bool => $this->allows($ability, $target)
        ));
    }

    /**
     * @param array<int, string> $abilities
     * @param Model|class-string<Model> $target
     * @return array<string, bool>
     */
    public function flags(array $abilities, Model|string $target): array
    {
        $flags = [];

        foreach ($abilities as $ability) {
            $flags[$ability] = $this->allows($ability, $target);
        }

        return $flags;
    }
}
