<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Classes\Room;

final class RoomTest extends TestCase
{
    public $room;
    public function setUp(): void
    {
        $charArray = ['id'=>'-235252325332'];
        $this->room = new Room($charArray);
    }

    public function testGetID()
    {
        $this->assertEquals('235252325332', $this->room->getID());
    }
    public function testGetID2()
    {
        $this->assertEquals('-235252325332', $this->room->getID());
    }
}