<?php


namespace App\VoteSystem\Services;


use App\VoteSystem\Models\Proposition;
use App\VoteSystem\Models\PropositionOption;
use App\VoteSystem\Models\Voter;
use App\VoteSystem\Repositories\PropositionRepository;
use App\VoteSystem\Repositories\VoterPropositionOptionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PropositionService
{
    private PropositionRepository $propositionRepository;
    private VoterPropositionOptionRepository $voterPropositionOptionRepository;

    public function __construct(
        PropositionRepository $propositionRepository,
        VoterPropositionOptionRepository $voterPropositionOptionRepository
    ) {
        $this->propositionRepository = $propositionRepository;
        $this->voterPropositionOptionRepository = $voterPropositionOptionRepository;
    }

    public function getNextProposition(Voter $voter): ?Proposition
    {
        return $this
            ->propositionRepository
            ->findOpenWhereUnanswered($voter->id);
    }

    public function answerProposition(Voter $voter, Proposition $proposition, Collection $answers): void
    {
        // Grid option key => value is flipped so we can give an option per row
        if ($proposition->type === 'grid') {
            $answers = $answers->flip();
        }
        $voterPropositionOptions = $answers->map(
            fn(string $vertical, string $horizontal) => [
                'id' => Str::uuid(),
                'voter_id' => $voter->id,
                'proposition_id' => $proposition->id,
                'horizontal_option_id' => $horizontal,
                'vertical_option_id' => $vertical,
            ]
        );

        $this
            ->voterPropositionOptionRepository
            ->insert($voterPropositionOptions);
    }

    public function createProposition(array $validated)
    {
        $options = $this->mapOptions($validated['options']);

        $proposition = $this
            ->propositionRepository
            ->create(
                $validated['title'],
                $validated['order'],
                array_key_exists('is_open', $validated),
                $this->getPropositionType($options)
            );

        $this->syncPropositionOptions($proposition, $options);
    }

    private function mapOptions(array $options): Collection
    {
        // Create vertical and horizontal options based on given data
        $mapped = collect();
        foreach ($options as $axis => $items) {
            foreach ($items as $id => $option) {
                if (is_null($option)) {
                    continue;
                }
                $model = [
                    'axis' => $axis,
                    'option' => $option,
                ];
                if (Str::isUuid($id)) {
                    $model['id'] = $id;
                }
                $mapped->push($model);
            }
        }
        return $mapped;
    }

    private function getPropositionType(Collection $options): string
    {
        $horizontalOptions = $options->where('axis', 'horizontal')->count();
        return $horizontalOptions > 1 ? 'grid' : 'list';
    }

    private function syncPropositionOptions(Proposition $proposition, Collection $newOptions)
    {
        $existingOptions = $proposition->options->keyBy('id');
        $newOptions = $newOptions->keyBy('id');

        $deletedOptions = $existingOptions->whereNotIn('id', $newOptions->keys())->pluck('id');
        $createdOptions = $newOptions->whereNotIn('id', $existingOptions->keys());
        $updatedOptions = $existingOptions->whereIn('id', $newOptions->keys());

        $proposition
            ->options()
            ->whereIn('id', $deletedOptions)
            ->delete();
        $proposition
            ->options()
            ->createMany($createdOptions);
        $updatedOptions->each(fn(PropositionOption $option) => $option->update($newOptions->get($option->id)));
    }

    public function toggleProposition(Proposition $proposition, bool $newState)
    {
        return $this
            ->propositionRepository
            ->update($proposition, [
                'is_open' => $newState,
            ]);
    }

    public function updateProposition(Proposition $proposition, array $validated)
    {
        $options = $this->mapOptions($validated['options']);

        $this
            ->propositionRepository
            ->update($proposition, [
                'title' => $validated['title'],
                'order' => $validated['order'],
                'is_open' => array_key_exists('is_open', $validated),
                'type' => $this->getPropositionType($options),
            ]);

        $this->syncPropositionOptions($proposition, $options);
    }
}
