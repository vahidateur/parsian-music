<?php

namespace Database\Factories\Concerns;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Parent-relationship factory states.
 *
 * Requirement 4.6: when a required parent is not supplied, it is created
 * through its own factory; when a parent is supplied by the test, it is reused
 * and no duplicate parent record is created.
 */
trait HasParentStates
{
    /**
     * Attribute values that create every required parent of this model.
     *
     * Keys are foreign-key column names; values are factory instances or
     * attribute closures.
     *
     * @return array<string, mixed>
     */
    abstract protected function parentAttributes(): array;

    /**
     * Create every required parent through its own factory.
     */
    public function withNewParents(): static
    {
        return $this->state($this->parentAttributes());
    }

    /**
     * Reuse the parents supplied by the test instead of creating new ones.
     *
     * @param  array<string, Model|int>  $parents  keyed by column (`teacher_id`) or alias (`teacher`)
     */
    public function withParents(array $parents): static
    {
        $required = $this->parentAttributes();
        $attributes = [];

        foreach ($parents as $key => $parent) {
            $column = array_key_exists($key, $required) ? $key : $key . '_id';

            if (! array_key_exists($column, $required)) {
                throw new InvalidArgumentException(
                    'Unknown parent [' . $key . '] for ' . static::class . '.'
                );
            }

            $attributes[$column] = $parent instanceof Model ? $parent->getKey() : $parent;
        }

        return $this->state($attributes);
    }
}
