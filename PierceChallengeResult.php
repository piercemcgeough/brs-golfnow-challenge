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
    public $message = 'You are not allowed more than 4 players in a group';
}

class InvalidDateTimeBookedException extends Exception {}

class NoDateTimeBookedException extends InvalidDateTimeBookedException
{
    public $message = 'You need to pick a date and time to tee off';
}

class PastException extends InvalidDateTimeBookedException
{
    public $message = 'You cannot book in the past.';
}

class TooCloseException extends InvalidDateTimeBookedException
{
    public $message = 'You cannot book this close to tee time. Please book at least 30 minutes in advance.';
}

class Booking
{
    private $player;

    private $players = [];

    private $logger;

    private $bookedSlot;

    public function __construct()
    {
        $this->logger = new FileLogger('/var/log/bookings.log');
    }

    public function setBookedSlot(DateTime $bookedSlot)
    {
        $this->bookedSlot = $bookedSlot;

        $this->validateBookingSlot();
    }

    public function getBookedSlot(): DateTime
    {
        return $this->bookedSlot;
    }

    private function validateBookingSlot()
    {
        $now = new DateTime('now');

        if ($this->bookedSlot < $now) {
            throw new PastException();
        }

        $diff = date_diff($this->bookedSlot, $now);

        $minutes = $diff->days * 24 * 60;
        $minutes += $diff->h * 60;
        $minutes += $diff->i;

        if ($minutes < 30) {
            throw new TooCloseException();
        }
    }

    /**
     * @param Player $player
     * @throws MaximumGroupNumberException
     */
    public function addPlayer(Player $player): void
    {
        if (count($this->players) == 4) {
            throw new MaximumGroupNumberException();
        }

        $this->players[] = $player;
    }

    public function complete(): void
    {
        if (is_null($this->bookedSlot)) {
            throw new NoDateTimeBookedException();
        }

        foreach ($this->players as $player) {
            $this->createBooking($player);
        }
    }

    public function createBooking(Player $player): void
    {
        $this->player = $player;

        $this->addBooking();

        $this->logBooking();
    }

    private function addBooking(): void
    {
        (new MySqlBookingStore())->addBooking($this->player->getName(), $this->player->getChargeRate($this->bookedSlot));
    }

    private function logBooking(): void
    {
        $this->logger->addEntry(
            'A new ' . $this->player->getPlayerType()
            . ' booking has been created for ' . $this->player->getName() . ' on '
            . $this->bookedSlot->format('l jS F Y') . ' at ' . $this->bookedSlot->format('H:i')
        );
    }
}

interface PlayerType
{
    public function getPlayerType(): string;
}

interface ChargeRate
{
    public function getChargeRate(DateTime $date): int;
}

class Player
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

    public function getChargeRate(DateTime $bookedDate): int
    {
        $class = get_called_class();

        if ($bookedDate->format('N') > 5) {
            return $class::WEEKEND_CHARGE_RATE;
        }

        return $class::WEEKDAY_CHARGE_RATE;
    }
}

class Member extends Player implements PlayerType
{
    const WEEKDAY_CHARGE_RATE = 5;

    const WEEKEND_CHARGE_RATE = 10;

    public function getPlayerType(): string
    {
        return 'member';
    }
}

class Visitor extends Player implements PlayerType
{
    const WEEKDAY_CHARGE_RATE = 15;

    const WEEKEND_CHARGE_RATE = 30;

    public function getPlayerType(): string
    {
        return 'visitor';
    }
}

class Juvenile extends Player implements PlayerType
{
    const WEEKDAY_CHARGE_RATE = 5;

    const WEEKEND_CHARGE_RATE = 15;

    public function getPlayerType(): string
    {
        return 'juvenile';
    }
}



// Scenario: A valid weekday booking
try {
    $date = new DateTime('now');
    $date->modify('next monday');
    $date->setTime(12, 00);


    $booking = new Booking();
    $booking->setBookedSlot($date);
    $booking->addPlayer(new Member('Pierce'));
    $booking->addPlayer(new Visitor('Leia'));
    $booking->addPlayer(new Juvenile('Aodh'));
    $booking->complete();
} catch (MaximumGroupNumberException $e) {
    echo $e->getMessage();
}

// Scenario: A valid weekend booking
try {
    $date = new DateTime('now');
    $date->modify('next saturday');
    $date->setTime(12, 00);


    $booking = new Booking();
    $booking->setBookedSlot($date);
    $booking->addPlayer(new Member('Pierce'));
    $booking->addPlayer(new Visitor('Leia'));
    $booking->addPlayer(new Juvenile('Aodh'));
    $booking->complete();
} catch (MaximumGroupNumberException $e) {
    echo $e->getMessage();
}

// Scenario: Too close to tee time
try {
    $date = new DateTime('now');
    $date->modify('+25 minutes');

    $booking = new Booking();
    $booking->setBookedSlot($date);
    $booking->complete();
} catch (InvalidDateTimeBookedException $e) {
    echo $e->getMessage() . "\n";
}

// Scenario: Past booking
try {
    $date = new DateTime('now');
    $date->modify('-1 day');

    $booking = new Booking();
    $booking->setBookedSlot(new DateTime('2021-02-01'));
    $booking->complete();
} catch (InvalidDateTimeBookedException $e) {
    echo $e->getMessage() . "\n";
}

// Scenario: No date set
try {
    $booking = new Booking();
    $booking->addPlayer(new Member('Pierce'));
    $booking->complete();
} catch (InvalidDateTimeBookedException $e) {
    echo $e->getMessage() . "\n";
}

// Scenario: Too many in the group
try {
    $date = new DateTime('now');
    $date->modify('+1 day');

    $booking = new Booking();
    $booking->setBookedSlot($date);
    $booking->addPlayer(new Member('One'));
    $booking->addPlayer(new Visitor('Two'));
    $booking->addPlayer(new Juvenile('Three'));
    $booking->addPlayer(new Juvenile('Four'));
    $booking->addPlayer(new Juvenile('Five'));
    $booking->complete();
} catch (MaximumGroupNumberException $e) {
    echo $e->getMessage();
}
