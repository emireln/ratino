<?php

namespace App\Events\Concerns;

/**
 * The SPA binds short names (".ChatMessage", ".BalanceModification"). Laravel would publish
 * the fully qualified class name, so no listener would ever fire.
 */
trait BroadcastsShortName {

    public function broadcastAs(): string {
        return class_basename($this);
    }

}
