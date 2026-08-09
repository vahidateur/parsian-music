<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Enums\RoomResolutionEnum;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;

/** Resolves room facts from existing room records; it never creates or mutates a room. */
final readonly class RoomSuitabilityService
{
    public function __construct(private RoomResolver $resolver, private RoomOptionProvider $options, private ConflictFactsProvider $conflicts) {}

    public function normalizedRoom(?string $room): ?string { return $this->resolver->normalize($room); }

    /** @param list<ConflictFact> $intervalFacts @return array{conflicts:list<SchedulingConflict>,eligible_rooms:list<array{id:int|string,name:string}>} */
    public function facts(ScheduleProposal $proposal, EffectiveSchedulingRules $rules, array $intervalFacts): array
    {
        $selected = $this->normalizedRoom($proposal->room);
        $required = $rules->requiredRoomsFor($proposal->relationPath->instrumentId);
        $conflicts = [];
        if ($selected === null && $required !== []) { $conflicts[] = new SchedulingConflict('room', 'ROOM_REQUIRED', 'hard', true); }
        if ($selected !== null && ! $this->resolver->fitsLegacyCapacity($selected)) { $conflicts[] = new SchedulingConflict('room', 'ROOM_NAME_INVALID', 'hard', true); }
        if ($selected !== null && $this->resolver->resolve($selected) !== RoomResolutionEnum::ResolvedActive) { $conflicts[] = new SchedulingConflict('room', 'ROOM_UNAVAILABLE', 'hard', true); }
        if ($selected !== null && $required !== [] && ! in_array($selected, $required, true)) { $conflicts[] = new SchedulingConflict('room', 'ROOM_INCOMPATIBLE', 'hard', true); }

        $eligible = [];
        $selectedOccupied = array_filter($intervalFacts, static fn (ConflictFact $fact): bool => $fact->resource === 'room' && $fact->status?->value !== 'cancelled') !== [];
        foreach ($this->options->forSessionInput() as $option) {
            if ($required !== [] && ! in_array($option->name, $required, true)) { continue; }
            // ponytail: candidate availability is one established query per room; replace with a batched facts adapter when suggestions are added.
            if (($option->name === $selected && $selectedOccupied) || ($option->name !== $selected && $this->conflicts->roomIsOccupied($option->name, $proposal))) { continue; }
            $eligible[] = ['id' => $option->id, 'name' => $option->name];
        }

        return ['conflicts' => $conflicts, 'eligible_rooms' => $eligible];
    }
}
