<?php namespace App\Whisper;

use App\Services\VipService;

class VipWhisper extends Whisper {

    public function event(): string {
        return 'Vip';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        if (($data->action ?? 'status') === 'claim') {
            $amount = VipService::claim($this->user);
            if ($amount <= 0) return ['code' => 2, 'message' => 'No rakeback to claim'];
            return ['claimed' => $amount, 'status' => VipService::status($this->user)];
        }

        return VipService::status($this->user);
    }

}