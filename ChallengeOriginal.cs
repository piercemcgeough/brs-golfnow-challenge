/*
 * GolfNow engineer challenge instructions.
 *
 * Please refactor 'Player', 'Member', 'Visitor' and 'BookingCreator' to make them more
 * efficient, maintainable, reusable and testable. You may also update the 'example usage'
 * code to suit your changes.
 *
 * Do not change 'IBookingStore', 'ILogger', 'MySqlBookingStore' or 'FileLogger'.
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

using System;

interface IBookingStore
{
    void AddBooking(string name, float price);
}

interface ILogger
{
    void AddEntry(string text);
}

class MySqlBookingStore : IBookingStore
{
    public void AddBooking(string name, float price)
    {
        // Note: actual MySQL storage routine is irrelevant for this challenge.
        Console.WriteLine("Storing booking for '{0}' in MySQL database with price {1:C2}", name, price);
    }
}

class FileLogger : ILogger
{
    public FileLogger(string filename)
    {
        Filename = filename;
    }

    public string Filename { get; set; }

    public void AddEntry(string text)
    {
        // Note: actual file logging routine is irrelevant for this challenge.
        Console.WriteLine("'{0}' >> {1}", text, Filename);
    }
}

// Anything under this line can be changed!

abstract class Player
{
    public Player(string name)
    {
        Name = name;
    }

    public string Name { get; set; }
}

class Member : Player
{
    public Member(string name): base(name)
    { }
}

class Visitor : Player
{
    public Visitor(string name): base(name)
    { }
}

class BookingCreator
{
    public void CreateMemberBooking(Member member)
    {
        MySqlBookingStore bookingStore = new MySqlBookingStore();
        bookingStore.AddBooking(member.Name, 10); // Chargable member rate is £10
        FileLogger logger = new FileLogger("/var/log/bookings.log");
        logger.AddEntry("A new member booking has been created for " + member.Name);
    }

    public void CreateVisitorBooking(Visitor visitor)
    {
        MySqlBookingStore bookingStore = new MySqlBookingStore();
        bookingStore.AddBooking(visitor.Name, 30); // Chargable visitor rate is £30
        FileLogger logger = new FileLogger("/var/log/bookings.log");
        logger.AddEntry("A new visitor booking has been created for " + visitor.Name);
    }
}

class Challenge
{
    static void Main() {
        // Example usage...
        BookingCreator bookingCreator = new BookingCreator();
        bookingCreator.CreateMemberBooking(new Member("David"));
        bookingCreator.CreateVisitorBooking(new Visitor("Andy"));
    }
}
