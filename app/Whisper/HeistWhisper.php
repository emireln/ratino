<?php namespace App\Whisper;

use App\Services\HeistService;

class HeistWhisper extends Whisper {

    public function event(): string {
        return 'Heist';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        if (($data->action ?? 'status') === 'spin') {
            $result = HeistService::spin($this->user);
            if (isset($result['code'])) return $result;
            return array_merge($result, ['canSpin' => false, 'secondsUntil' => 86400]);
        }

        return [
            'segments' => array_map(function($s) { return $s['reward']; }, HeistService::SEGMENTS),
            'canSpin' => HeistService::canSpin($this->user),
            'secondsUntil' => HeistService::secondsUntil($this->user)
        ];
    }

}