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
    
class MaximumGroupNumberException extends Exception
{
}

abstract class BookingCreator
{
    public $player;
    
    private $players = [];
    
    private $logger;
    
    public function __construct() 
    {
        $this->logger = new FileLogger('/var/log/bookings.log');
    }

    public function addMember(Player $player): void
    {
        if (count($this->players) == 4) {
            throw new MaximumGroupNumberException('You are not allowed more than 4 players in a group');
        }
        
        $this->player = $player;
        
        $this->player->setGreenFee($this->getChargeRate($player));

        $this->players[] = $this->player;
    }
    
    public function finalise()
    {
        foreach ($this->players as $player) {
            $this->create($player);
            $this->log($player);
        }
    }
    
    private function create(Player $player): void
    {
        $bookingStore = new MySqlBookingStore();
        $bookingStore->addBooking($player->getName(), $player->getGreenFee());
    }
    
    private function log(Player $player): void
    {
        $this->logger->addEntry('A new ' . $this->getWeekTime() . ' ' . $player->getType() . ' booking has been created for ' . $player->getName());
    }
    
    abstract public function getWeekTime();
    
    abstract public function getChargeRate(Player $player);
}
    
class WeekdayBooking extends BookingCreator
{
    public function getWeekTime(): string
    {
        return 'weekday';
    }
    
    public function getChargeRate(Player $player): int
    {
        return $player->getWeekdayChargeRate();
    }
}
    
class WeekendBooking extends BookingCreator
{
    public function getWeekTime(): string
    {
        return 'weekend';
    }
    
    public function getChargeRate(Player $player): int
    {
        return $player->getWeekendChargeRate();
    }
}



abstract class Player
{
    private $name;
    
    public $greenFee;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getType(): string
    {
        return $this->parseType((new ReflectionClass($this))->getShortName());
    }
    
    public function setGreenFee(int $greenFee)
    {
        $this->greenFee = $greenFee;
    }
    
    public function getGreenFee(): int
    {
        return $this->greenFee;
    }
    
    private function parseType(string $text): string
    {
        $pattern = '/(.*?[a-z]{1})([A-Z]{1}.*?)/';
        $replace = '${1} ${2}';
        
        $typeString = preg_replace($pattern, $replace, $text);
        
        return strtolower($typeString);
    }
    
    abstract function getWeekdayChargeRate();
    
    abstract function getWeekendChargeRate();
}
    
class JuniorMember extends Player
{
    public function getWeekdayChargeRate(): int
    {
        return GreenFees::juniorMemberWeekdayGreenFees();
    }
    
    public function getWeekendChargeRate(): int
    {
        return GreenFees::juniorMemberWeekendGreenFees();
    }
}
    
class SeniorMember extends Player
{
    public function getWeekdayChargeRate(): int
    {
        return GreenFees::seniorMemberWeekdayGreenFees();
    }
    
    public function getWeekendChargeRate(): int 
    {
        return GreenFees::seniorMemberWeekendGreenFees();
    }
}

class JuniorVisitor extends Player
{
    public function getWeekdayChargeRate(): int
    {
        return GreenFees::juniorVisitorWeekdayGreenFees();
    }
    
    public function getWeekendChargeRate(): int
    {
        return GreenFees::juniorVisitorWeekendGreenFees();
    }
}
    
class SeniorVisitor extends Player
{
    public function getWeekdayChargeRate(): int
    {
        return GreenFees::seniorVisitorWeekdayGreenFees();
    }
    
    public function getWeekendChargeRate(): int 
    {
        return GreenFees::seniorVisitorWeekEndGreenFees();
    }
}
    
class GreenFees
{
    public static function juniorMemberWeekdayGreenFees(): int {
        return 10;
    }
    
    public static function juniorMemberWeekendGreenFees(): int {
        return 15;
    }
    
    public static function juniorVisitorWeekdayGreenFees(): int {
        return 20;
    }
    
    public static function juniorVisitorWeekendGreenFees(): int {
        return 30;
    }
    
    public static function seniorMemberWeekdayGreenFees(): int {
        return 20;
    }
    
    public static function seniorMemberWeekendGreenFees(): int {
        return 25;
    }
    
    public static function seniorVisitorWeekdayGreenFees(): int {
        return 40;
    }
    
    public static function seniorVisitorWeekendGreenFees(): int {
        return 60;
    }
}

    
    
    
    
$booking = new WeekdayBooking();
$booking->addMember(new JuniorMember('Aodh'));
$booking->addMember(new JuniorVisitor('Martin'));
$booking->addMember(new SeniorMember('Bill'));
$booking->addMember(new SeniorVisitor('Glen'));
$booking->finalise();

    
//    
//$booking = new WeekendBooking();
//$booking->addMember(new JuniorMember('Aodh'));
//$booking->addMember(new JuniorVisitor('Martin'));
//$booking->addMember(new SeniorMember('Bill'));
//$booking->addMember(new SeniorVisitor('Glen'));
//
//try {
//    $booking = new WeekendBooking();
//    $booking->addMember(new JuniorMember('Aodh'));
//    $booking->addMember(new JuniorVisitor('Martin'));
//    $booking->addMember(new SeniorMember('Bill'));
//    $booking->addMember(new SeniorMember('Shenda'));
//    $booking->addMember(new SeniorMember('Michelle'));
//    $booking->addMember(new SeniorVisitor('Gillian'));
//} catch (MaximumGroupNumberException $e) {
//    echo $e->getMessage();
//}
//    