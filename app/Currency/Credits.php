<?php namespace App\Currency;

class Credits extends Currency {
    function id(): string { return 'credits'; }
    function walletId(): string { return 'credits'; }
    function name(): string { return 'CHIPS'; }
    function displayName(): string { return 'CHIPS'; }
    function icon(): string { return 'fas fa-coins'; }
    function style(): string { return '#df344f'; }
}
