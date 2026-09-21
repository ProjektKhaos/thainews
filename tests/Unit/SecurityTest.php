<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase; use ThaiNews\Security\Tokens;
final class SecurityTest extends TestCase{public function testSignedUnsubscribe():void{$sig=Tokens::unsubscribeSignature(7,2,'01234567890123456789012345678901');$this->assertTrue(Tokens::verifyUnsubscribe(7,2,$sig,'01234567890123456789012345678901'));$this->assertFalse(Tokens::verifyUnsubscribe(7,3,$sig,'01234567890123456789012345678901'));}}
