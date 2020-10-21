<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

use App\Models\User;
use App\Models\Quiz;
use App\Models\Roster;
use App\Models\Activity;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition()
    {
        $teacher_id = User::where('affiliation', 'not like', '%student%')->inRandomOrder()->limit(1)->get()[0]->id;
        $quiz_id = Quiz::inRandomOrder()->limit(1)->get()[0]->id;
        $roster = Roster::inRandomOrder()->limit(1)->get()[0];
        $roster_id = $roster->id;
        $teacher_id = $roster->teacher_id;

        return [
            'quiz_id' => $quiz_id,
            'roster_id' => $roster_id,
            'user_id' => $teacher_id,
            'duration' => Arr::random([20, 60, 600, 600, 600, 600, 600, 600, 1000 ]),
            'hidden' => false,
            'shuffle_questions' => Arr::random([0, 1]),
            'shuffle_propositions' => Arr::random([0, 1]),
        ];
    }
};
