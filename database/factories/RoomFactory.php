<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function create($attributes = [], ?Model $parent = null)
    {
        return Model::unguarded(fn () => parent::create($attributes, $parent));
    }

    public function make($attributes = [], ?Model $parent = null)
    {
        return Model::unguarded(fn () => parent::make($attributes, $parent));
    }

    public function definition(): array
    {
        return [
            'call_id' => Call::factory(),
            'room_id' => 'room-'.$this->faker->uuid(),
            'sfu_node' => 'sfu-node-1',
            'media_ws_url' => 'wss://sfu.test/ws',
        ];
    }
}
