<?php
/*
 * GolfNow engineer challenge instructions.
 *
 * Please refactor 'Player', 'Member', 'Visitor' and 'BookingCreator' to make them more
 * efficient, maintainable, reusable and testable. You may also update the 'example usage'
 * code to suit your changes.
 *
 * Do not change 'BookingStore', 'Logger', 'MySqlBookingStore' or 'FileLogger'.
 *
 * You should not need to add any new comments - ideally your code should be self-documenting!
 *
 * Do not be concerned with the number of lines of code in your solution - smaller is not
 * necessarily better!
 *
 * Hint 1: Program to interface, not implementation
 * Hint 2: We may need different booking storage and logging mechanisms in the future
 * Hint 3: BookingCreator should not be concerned with the type of player making a booking
 */

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
}

class Member extends Player
{
}

class Visitor extends Player
{
}

class BookingCreator
{
    public function createMemberBooking(Member $member): void
    {
        $bookingStore = new MySqlBookingStore();
        $bookingStore->addBooking($member->getName(), 10); // Chargable member rate is £10
        $logger = new FileLogger('/var/log/bookings.log');
        $logger->addEntry('A new member booking has been created for ' . $member->getName());
    }

    public function createVisitorBooking(Visitor $visitor): void
    {
        $bookingStore = new MySqlBookingStore();
        $bookingStore->addBooking($visitor->getName(), 30); // Chargable visitor rate is £30
        $logger = new FileLogger('/var/log/bookings.log');
        $logger->addEntry('A new visitor booking has been created for ' . $visitor->getName());
    }
}

// Example usage...
$bookingCreator = new BookingCreator();
$bookingCreator->createMemberBooking(new Member('David'));
$bookingCreator->createVisitorBooking(new Visitor('Andy'));