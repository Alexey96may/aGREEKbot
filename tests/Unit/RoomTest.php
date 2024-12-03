<?php declare(strict_types=1);

namespace Tests\Unit;
use PHPUnit\Framework\TestCase;

final class RoomTest extends TestCase
{
    public $id = '-test';
    public function testIsChatRoom(): bool
    {

        $firstIDLetter = mb_substr($this->id, 0, 1);
        $this->assertSame('-', $firstIDLetter);

        if ($firstIDLetter === '-') {
            return true;
        }
        return false;
    }
}