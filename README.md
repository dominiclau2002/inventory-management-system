# BookHive

A simple library management system built with PHP and MySQL. Helps you keep track of books, users, and borrowings with an easy-to-use interface.

## Getting Started

You'll need:
- XAMPP
- A modern web browser

### Quick Setup
1. Drop the files into your XAMPP's htdocs folder
2. Fire up Apache and MySQL in XAMPP
3. Visit `http://localhost/index.php`
4. The database will set itself up with some sample books on start

Credentials for the admin account:
- Username: `admin`
- Password: `admin123`

## What Can You Do?

### Regular Users Can:
- Find books using the search bar
- See all the details about a book
- Borrow books for 30 days
- Keep track of when books are due
- Check their borrowing history

### Admins Can:
- Add, edit, or remove books
- See who's borrowed what
- Handle borrows and returns
- Manage user accounts
- Check the whole library's history

## Technologies used in the project

### Frontend:
- Built with HTML5 & CSS3
- Uses Bootstrap 5.3.0 for the layout
- Font Awesome 6.0.0 for icons

### Backend:
- PHP 7.4+ doing the heavy lifting
- MySQL keeping track of everything
- Sessions for keeping you logged in
- Safe from SQL injection

### Database Layout:
- `users` table: user informations (id, full name, username, hashed password, role and when was it created)
- `books` table: book informations (id, title, author, isbn number, publisher, year, language, description, status and when was it created)
- `borrows` table: borrow informations (borrow id, book id, user id, borrow and return date, the date when the book was actually returned and the current borrowing status)

## How to Use It

### If You're a User:
1. Sign up through "Regisztráció"
2. Find something to read
3. Hit "Kölcsönzés" to borrow it
4. Check "Kölcsönzéseim" to see your books

### If You're an Admin:
1. Books:
   - Add new ones
   - Fix details
   - Remove old ones
   - See who's borrowed them

2. Users:
   - Keep track of members
   - Make someone an admin
   - Remove accounts if needed

3. Borrowings:
   - Handle checkouts
   - Process returns
   - Check who's late

## Additional Information
- Keep XAMPP updated
- What does the system handle:
  - Checking if books are available
  - Setting due dates
  - Making sure you're logged in
  - Keeping track of who can do what

