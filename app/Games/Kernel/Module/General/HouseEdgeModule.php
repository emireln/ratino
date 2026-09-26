<?php namespace App\Games\Kernel\Module\General;

use App\Games\Kernel\Game;
use App\Games\Kernel\Module\Module;
use App\Games\Kernel\Module\ModuleConfigurationOption;
use App\Models\Modules;

class HouseEdgeModule extends Module {

    function id(): string {
        return 'house_edge';
    }

    function name(): string {
        return 'House Edge';
    }

    function description(): string {
        return 'Applied to payouts';
    }

    function settings(): array {
        return [
            new class extends ModuleConfigurationOption {
                function id(): string {
                    return "house_edge_option";
                }
                function name(): string {
                    return "House Edge";
                }
                function description(): string {
                    return "Percent (1-100)";
                }
                function defaultValue(): ?string {
                    return '1';
                }
                function type(): string {
                    return 'input';
                }
            }
        ];
    }

    function supports(): bool {
        return true;
    }

    function lose(bool $demo): bool {
        return false;
    }

    public static function apply(Game $game, float $mul): float {
        $houseEdgeModule = new HouseEdgeModule($game, null, null, null);
        if(Modules::get($game, false)->isEnabled($houseEdgeModule)) {
            $percent = max(0, min(100, floatval(Modules::get($game, false)->get($houseEdgeModule, 'house_edge_option'))));
            return $mul * (1 - $percent / 100);
        }
        return $mul;
    }

    public static function get(Game $game, float $default): float {
        $houseEdgeModule = new HouseEdgeModule($game, null, null, null);
        if(!Modules::get($game, false)->isEnabled($houseEdgeModule)) return $default;
        $percent = max(0, min(100, floatval(Modules::get($game, false)->get($houseEdgeModule, 'house_edge_option'))));
        return 1 - $percent / 100;
    }

}
