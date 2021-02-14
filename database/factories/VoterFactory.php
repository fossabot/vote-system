<?php

namespace Database\Factories;

use App\VoteSystem\Helpers\TokenHelper;
use App\VoteSystem\Models\Voter;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoterFactory extends Factory
{
    protected $model = Voter::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            // 16 chars
            'token' => TokenHelper::generateToken(
                config('vote-system.token_length')
            ),
        ];
    }
}
