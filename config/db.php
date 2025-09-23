<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'library_db');

// Create connection for initial setup (without database)
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)){
    // Select the database
    mysqli_select_db($conn, DB_NAME);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create books table
    $sql = "CREATE TABLE IF NOT EXISTS books (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        isbn VARCHAR(13),
        publisher VARCHAR(100),
        year INT,
        language VARCHAR(50),
        description TEXT,
        status ENUM('available', 'borrowed') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Create borrows table
    $sql = "CREATE TABLE IF NOT EXISTS borrows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        book_id INT NOT NULL,
        user_id INT NOT NULL,
        borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        return_date TIMESTAMP NULL,
        actual_return_date TIMESTAMP NULL,
        status ENUM('active', 'returned') DEFAULT 'active',
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $sql);

    // Check if admin user exists
    $result = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
    if(mysqli_num_rows($result) == 0){
        // Insert default admin user (password: admin123)
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, username, password, role) VALUES
                ('Administrator', 'admin', '$admin_password', 'admin')";
        mysqli_query($conn, $sql);
    }

    // Check if sample books exist
    $result = mysqli_query($conn, "SELECT id FROM books LIMIT 1");
    if(mysqli_num_rows($result) == 0){
        // Insert sample books
        $sql = "INSERT INTO books (title, author, isbn, publisher, year, language, description) VALUES
                ('The Lord of the Rings', 'J.R.R. Tolkien', '9789634197843', 'Europa Publishing', 2021, 'english', 'A classic of fantasy literature that tells an adventurous story revolving around a ring. An epic fantasy set in the detailed world of Middle-earth, full of magic, friendships, and heroic deeds.'),
                ('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', '9789633244277', 'Animus Publishing', 2020, 'english', 'The first volume of the world-famous series that introduces us to the world of wizards. We follow Harry Potter\'s first year at Hogwarts School of Witchcraft and Wizardry.'),
                ('Foundation', 'Isaac Asimov', '9789634197123', 'Gabo Publishing', 2019, 'english', 'A defining work of the science fiction genre that tells the story of the fall of a galactic empire and the birth of a new civilization. A chronicle of scientific development and social changes that determine humanity\'s future.'),
                ('1984', 'George Orwell', '9789634197456', 'Europa Publishing', 2020, 'english', 'A dystopian novel that presents the functioning of a totalitarian state. A timeless warning about surveillance, oppression, and the loss of individual freedom.'),
                ('The Little Prince', 'Antoine de Saint-Exupéry', '9789634197789', 'Mora Publishing', 2018, 'english', 'A philosophical tale for both children and adults. Through a little prince\'s journeys, we discover life\'s great truths and the importance of friendship.'),
                ('The Hobbit', 'J.R.R. Tolkien', '9789634197234', 'Europa Publishing', 2022, 'english', 'Bilbo Baggins\' unexpected adventure that leads to the events of The Lord of the Rings. The story of a peaceful hobbit who unwillingly becomes part of a great adventure.'),
                ('Fahrenheit 451', 'Ray Bradbury', '9789634197567', 'Agave Books', 2021, 'english', 'Set in a future where books are forbidden and firefighters\' job is to burn them. A classic about the importance of preserving knowledge and culture.'),
                ('The Da Vinci Code', 'Dan Brown', '9789634197890', 'Gabo Publishing', 2017, 'english', 'An exciting thriller built around art historical and religious mysteries. Following Professor Robert Langdon as he traces the clues of a murder, an ancient secret is revealed.'),
                ('The Hunger Games', 'Suzanne Collins', '9789634197345', 'Agave Books', 2019, 'english', 'A story set in a dystopian world where young people are forced to participate in deadly games. Katniss Everdeen\'s story about survival and resistance.'),
                ('The Great Gatsby', 'F. Scott Fitzgerald', '9789634197678', 'Europa Publishing', 2020, 'english', 'The dark side of the American dream, through the story of a mysterious millionaire. A classic novel about wealth, love, and illusions from 1920s America.')";
        mysqli_query($conn, $sql);
    }
} else {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Close and reopen connection with database selected
mysqli_close($conn);
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}
?> 