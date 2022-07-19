<?php

interface BookingStore
{
    public function addBooking(string $name, float $price): void;
}

interface Logger
{
    public function addEntry(string $text): void;
}

class MySqlBookingStore implements BookingStore
{
    public function addBooking(string $name, float $price): void
    {
        // Note: actual MySQL storage routine is irrelevant for this challenge.
        echo sprintf("Storing booking for '%s' in MySQL database with price £%01.2f\n", $name, $price);
    }
}
	
class FileLogger implements Logger
{
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function addEntry(string $text): void
    {
        // Note: actual file logging routine is irrelevant for this challenge.
        echo sprintf("'%s' >> %s\n", $text, $this->filename);
    }
}

// Anything under this line can be changed!

class Booking
{
    private $player;
    
    private $logger;
    
    public function __construct(Player $player) 
    {
        $this->player = $player;
        
        $this->logger = new FileLogger('/var/log/bookings.log');
    }

    public function createBooking(): void
    {
        $this->addBooking();
        
        $this->logBooking();
    }
    
    private function addBooking(): void
    {
        (new MySqlBookingStore())->addBooking($this->player->getName(), $this->player->getChargeRate());
    }
    
    private function logBooking(): void
    {
        $this->logger->addEntry('A new ' . $this->player->getPlayerType() . ' booking has been created for ' . $this->player->getName());
    }
}
    
    
abstract class Player
{
    private $name;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    abstract public function getChargeRate(): int;
    
    abstract public function getPlayerType(): string;
}
    
class Member extends Player
{
    public function getChargeRate(): int 
    {
        return 10;
    }
        
    public function getPlayerType(): string
    {
        return 'member';
    }
}
    
class Visitor extends Player
{
    public function getChargeRate(): int
    {
        return 30;
    }
    
    public function getPlayerType(): string
    {
        return 'visitor';
    }
}
    
class Juvenile extends Player
{
    public function getChargeRate(): int
    {
        return 5;
    }
    
    public function getPlayerType(): string
    {
        return 'juvenile';
    }
}

$booking = new Booking(new Member('Pierce'));
$booking->createBooking();

$booking = new Booking(new Visitor('Rene'));
$booking->createBooking();

$booking = new Booking(new Juvenile('Aodh'));
$booking->createBooking();
