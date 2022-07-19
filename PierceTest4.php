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
class PastException extends Exception
{
    public $message = 'You cannot book in the past.';
}
class TooCloseException extends Exception
{
    public $message = 'You cannot book this close to tee time. Please book at least 30 minutes in advance.';
}
class AgeException extends Exception
{
    public $message = 'There is something not right here!';
}
class TooManyPlayersException extends Exception
{
    public $message = 'You are not allowed more than 4 players in a group';
}

class Booking 
{
    private $logger;
    
    private $bookedDate;
    
    private $players = [];
    
    public function __construct(DateTime $bookedDate)
    {
        $this->bookedDate = $bookedDate;
        
        $this->validateBookingTime();
        
        $this->logger = new FileLogger('/var/log/bookings.log');
    }
    
    public function addPlayer(Player $player)
    {
        if (count($this->players) == 4) {
            throw new TooManyPlayersException();
        }        

        $greenFees = GreenFees::get($this->bookedDate, $player);
        
        $player->setGreenFees($greenFees);
        
        $this->players[] = $player;
    }
    
    public function completeBooking()
    {
        foreach ($this->players as $player) {
            $this->create($player);
            $this->log($player);
        }
    }
    
    private function create(Player $player): void
    {
        $bookingStore = new MySqlBookingStore();
        $bookingStore->addBooking($player->getName(), $player->getGreenFees());
    }
    
    private function log(Player $player): void
    {
        $this->logger->addEntry(
            'A new ' . $player->getType() 
            . ' booking has been created for ' . $player->getName() . ' on ' 
            . $this->bookedDate->format('l jS F Y') . ' at ' . $this->bookedDate->format('H:i')
        );
    }
    
    private function validateBookingTime()
    {
        $now = new DateTime('now');
        
        if ($this->bookedDate < $now) {
            throw new PastException();
        }
        
        $diff = date_diff($this->bookedDate, $now);
        
        $minutes = $diff->days * 24 * 60;
        $minutes += $diff->h * 60;
        $minutes += $diff->i;
        
        if ($minutes < 30) {
            throw new TooCloseException();
        }
    }
}

class GreenFees
{
    public static function get(DateTime $bookedDate, Player $player): int
    {
        $player = get_class($player);
        
        $day = $bookedDate->format('N');
        
        if ($day > 5) {
            return self::getWeekendFees($player);
        } 
        
        return self::getWeekdayFees($player);
    }
    
    public static function getWeekdayFees(string $player)
    {
        switch ($player) {
            case 'JuniorMember':
                return GreenFeePrices::juniorMemberWeekdayFee();
                break;
            case 'JuniorVisiter':
                return GreenFeePrices::juniorVisiterWeekdayFee();
                break;
            case 'SeniorMember':
                return GreenFeePrices::seniorMemberWeekdayFee();
                break;
            case 'SeniorVisitor':
            default:
                return GreenFeePrices::seniorMemberWeekendFee();
                break;
        }
    }
    
    public static function getWeekendFees(string $player)
    {
        switch ($player) {
            case 'JuniorMember':
                break;
            case 'JuniorVisiter':
                break;
            case 'SeniorMember':
                break;
            case 'SeniorVisitor':
            default:
                break;
        }
    }
}

class GreenFeePrices
{
    public static function juniorMemberWeekdayFee(): int {
        return 10;
    }
    
    public static function juniorMemberWeekendFee(): int {
        return 15;
    }
    
    public static function juniorVisitorWeekdayFee(): int {
        return 20;
    }
    
    public static function juniorVisitorWeekendFee(): int {
        return 30;
    }
    
    public static function seniorMemberWeekdayFee(): int {
        return 20;
    }
    
    public static function seniorMemberWeekendFee(): int {
        return 25;
    }
    
    public static function seniorVisitorWeekdayFee(): int {
        return 40;
    }
    
    public static function seniorVisitorWeekendFee(): int {
        return 60;
    }
}

class Age
{
    private $age;
    
    public function __construct(int $age) 
    {
        if ($age < 0 || $age > 200) {
            throw new AgeException();
        }
        
        $this->age = $age;
    }
    
    public function getAge(): int
    {
        return $this->age;
    }
}

abstract class Player
{
    public $name;
    private $greenFees;
    public $isMember;
    
    public function __construct(string $name, bool $isMember = false)
    {
        $this->name = $name;

        $this->isMember = $isMember;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setGreenFees(int $greenFees)
    {
        $this->greenFees = $greenFees;
    }
    
    public function getGreenFees(): int
    {
        return $this->greenFees;
    }
    
    abstract public function getType(): string;
}



class JuniorMember extends Player
{
    public function getType(): string
    {
        return 'junior member';
    }
}

class JuniorVisitor extends Player
{
    public function getType(): string
    {
        return 'junior visitor';
    }
}

class SeniorMember extends Player
{
    public function getType(): string
    {
        return 'senior member';
    }
}

class SeniorVisitor extends Player
{
    public function getType(): string
    {
        return 'senior visitor';
    }
}

class PlayerFactory
{
    public static function create(string $name, Age $age, $isMember = false): Player
    {
        if ($age->getAge() < 18 && $isMember) {
            return new JuniorMember($name);
        }
        
        if ($age->getAge() < 18) {
            return new JuniorVisitor($name);
        }
        
        if ($isMember) {
            return new SeniorMember($name);
        }
        
        return new SeniorVisitor($name);
    }
}

try {
    $booking = new Booking(new DateTime('2021-02-03 12:00'));    
    $booking->addPlayer(new JuniorMember('Aodh'));
//    $booking->addPlayer(new SeniorVisitor('Brian'), true);
    $booking->completeBooking();
} catch (PastException $e) {
    echo $e->getMessage();
} catch (TooCloseException $e) {
    echo $e->getMessage();
} catch (AgeException $e) {
    echo $e->getMessage();
} catch (TooManyPlayersException $e) {
    echo $e->getMessage();
}


////$booking->addPlayer();
//
//// jm, jv, sm, sv