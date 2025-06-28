<?php
// Plik: db_config.php

// Konfiguracja bazy danych - UZUPEŁNIJ WŁASNYMI DANYMI!
$servername = "localhost";
$username = "root";      // Twój użytkownik bazy danych
$password = "";          // Twoje hasło
$dbname = "dziekanat";  // Nazwa Twojej bazy danych

// Nawiązywanie połączenia
$conn = new mysqli($servername, $username, $password, $dbname);

// Sprawdzanie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania
$conn->set_charset("utf8mb4");
?>