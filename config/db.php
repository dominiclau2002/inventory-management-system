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
                ('Adminisztrátor', 'admin', '$admin_password', 'admin')";
        mysqli_query($conn, $sql);
    }

    // Check if sample books exist
    $result = mysqli_query($conn, "SELECT id FROM books LIMIT 1");
    if(mysqli_num_rows($result) == 0){
        // Insert sample books
        $sql = "INSERT INTO books (title, author, isbn, publisher, year, language, description) VALUES
                ('A Gyűrűk Ura', 'J.R.R. Tolkien', '9789634197843', 'Európa Könyvkiadó', 2021, 'magyar', 'A fantasy irodalom klasszikusa, amely egy gyűrű köré szövődő kalandos történetet mesél el. Középfölde részletes világában játszódó epikus fantasy, tele mágiával, barátságokkal és hősi tettekkel.'),
                ('Harry Potter és a Bölcsek Köve', 'J.K. Rowling', '9789633244277', 'Animus Kiadó', 2020, 'magyar', 'A világhírű sorozat első kötete, amely bevezet minket a varázslók világába. Követjük Harry Potter első évét a Roxfort Boszorkány- és Varázslóképző Szakiskolában.'),
                ('Az alapítvány', 'Isaac Asimov', '9789634197123', 'Gabo Kiadó', 2019, 'magyar', 'A science fiction műfaj meghatározó alkotása, amely egy galaktikus birodalom bukását és egy új civilizáció születését meséli el. Az emberiség jövőjét meghatározó tudományos fejlődés és társadalmi változások krónikája.'),
                ('1984', 'George Orwell', '9789634197456', 'Európa Könyvkiadó', 2020, 'magyar', 'Disztópikus regény, amely egy totalitárius állam működését mutatja be. A megfigyelés, az elnyomás és az egyéni szabadság elvesztésének időtlen figyelmeztetése.'),
                ('Kis herceg', 'Antoine de Saint-Exupéry', '9789634197789', 'Móra Könyvkiadó', 2018, 'magyar', 'Filozofikus mese gyerekeknek és felnőtteknek egyaránt. Egy kis herceg utazásain keresztül fedezzük fel az élet nagy igazságait és a barátság fontosságát.'),
                ('A hobbit', 'J.R.R. Tolkien', '9789634197234', 'Európa Könyvkiadó', 2022, 'magyar', 'Bilbó Zsákos váratlan kalandja, amely elvezet A Gyűrűk Ura eseményeihez. Egy békés hobbit története, aki akaratán kívül egy nagy kaland részesévé válik.'),
                ('Fahrenheit 451', 'Ray Bradbury', '9789634197567', 'Agave Könyvek', 2021, 'magyar', 'Egy olyan jövőben játszódik, ahol a könyvek tiltottak és a tűzoltók feladata azok elégetése. A tudás és a kultúra megőrzésének fontosságáról szóló klasszikus.'),
                ('A Da Vinci-kód', 'Dan Brown', '9789634197890', 'Gabo Kiadó', 2017, 'magyar', 'Izgalmas thriller, amely művészettörténeti és vallási rejtélyek köré épül. Robert Langdon professzor egy gyilkosság nyomait követve tárul fel egy ősi titok.'),
                ('Az éhezők viadala', 'Suzanne Collins', '9789634197345', 'Agave Könyvek', 2019, 'magyar', 'Egy disztópikus világban játszódó történet, ahol fiatalok kényszerülnek halálos játékban részt venni. Katniss Everdeen története a túlélésről és az ellenállásról.'),
                ('A nagy Gatsby', 'F. Scott Fitzgerald', '9789634197678', 'Európa Könyvkiadó', 2020, 'magyar', 'Az amerikai álom sötét oldala, egy titokzatos milliomos történetén keresztül. A gazdagság, szerelem és illúziók klasszikus regénye az 1920-as évek Amerikájából.')";
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