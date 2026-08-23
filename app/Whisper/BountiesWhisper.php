<?php namespace App\Whisper;

use App\Services\BountyService;

class BountiesWhisper extends Whisper {

    public function event(): string {
        return 'Bounties';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];
        return BountyService::progress($this->user);
    }

}