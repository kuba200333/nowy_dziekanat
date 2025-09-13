<?php
// Plik: index.php (Wersja Ostateczna z pełnym, poprawionym menu)
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$is_admin = $_SESSION['is_admin'] ?? false;

$page = $_GET['page'] ?? 'dashboard';

if ($user_role == 'student' && $page == 'dashboard') $page = 'karta_studenta';
if ($user_role == 'pracownik' && !$is_admin && $page == 'dashboard') $page = 'obecnosc_wybor_zajec';

$currentPage = $page;

// Kontrola dostępu (ACL)
$dozwolone_strony = [];
if ($user_role == 'pracownik') {
    if (!$is_admin) {
        // Zwykły nauczyciel
        $dozwolone_strony = [
            'dashboard', 'oceny_czastkowe_wybor', 'oceny_czastkowe_zarzadzaj',
            'oceny_wybor_krok1', 'oceny_wybor_krok2', 'oceny_wprowadz_form',
            'plan_wyszukiwarka', 'obecnosc_wybor_zajec', 'obecnosc_form'
        ];
    }
} elseif ($user_role == 'student') {
    // Student
    $dozwolone_strony = [
        'karta_studenta', 'plan_wyszukiwarka', 'wybory_student_lista',
        'wybory_student_glosuj', 'stypendia_student_lista', 'stypendia_student_form',
        'ankiety_student_lista', 'ankiety_student_form',
        'obecnosc_student_podglad'
    ];
}

// Admin ma dostęp do wszystkiego, a dla innych sprawdzamy listę
$ma_dostep = $is_admin ? true : in_array($page, $dozwolone_strony);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wirtualny Dziekanat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <h2>Dziekanat</h2>
            <nav>
                <ul>
                    <?php if ($user_role == 'pracownik'): ?>
                        <?php if ($is_admin): // ### MENU DLA ADMINISTRATORA (WIDZI WSZYSTKO) ### ?>
                            <li><a href="index.php?page=dashboard" class="<?= ($currentPage == 'dashboard') ? 'active' : '' ?>">Pulpit</a></li>
                            <hr>
                            <li><strong>Zarządzanie Dydaktyką</strong></li>
                            <li><a href="index.php?page=przedmioty_lista" class="<?= str_starts_with($currentPage, 'przedmioty') ? 'active' : '' ?>">Przedmioty</a></li>
                            <li><a href="index.php?page=konfiguracje_lista" class="<?= str_starts_with($currentPage, 'konfiguracje_lista') ? 'active' : '' ?>">Konfiguracje (ECTS)</a></li>
                            <li><a href="index.php?page=konfiguracje_komponentow_form" class="<?= str_starts_with($currentPage, 'konfiguracje_komponentow') ? 'active' : '' ?>">Konfiguracje (Wagi)</a></li>
                            <li><a href="index.php?page=grupy_lista" class="<?= str_starts_with($currentPage, 'grupy') ? 'active' : '' ?>">Grupy Zajęciowe</a></li>
                            <li><a href="index.php?page=zajecia_lista" class="<?= str_starts_with($currentPage, 'zajecia_lista') ? 'active' : '' ?>">Zajęcia</a></li>
                            <li><a href="index.php?page=zajecia_obsada_form" class="<?= str_starts_with($currentPage, 'zajecia_obsada') ? 'active' : '' ?>">Zarządzaj Obsadą</a></li>
                            <li><a href="index.php?page=zapisy_masowe_krok1" class="<?= str_starts_with($currentPage, 'zapisy_masowe') ? 'active' : '' ?>">Zapisy na Zajęcia</a></li>
                            <li><a href="index.php?page=student_zapisy_form" class="<?= str_starts_with($currentPage, 'student_zapisy_form') ? 'active' : '' ?>">Pogląd zajęć studenta</a></li>
                            <li><a href="index.php?page=dydaktyka_przeglad" class="<?= str_starts_with($currentPage, 'dydaktyka_przeglad') ? 'active' : '' ?>">▶ Przegląd Dydaktyki</a></li>
                            <li><a href="index.php?page=generator_grup" class="<?= str_starts_with($currentPage, 'generator_grup') ? 'active' : '' ?>">▶ Generator Grup</a></li>
                            <li><a href="index.php?page=preferencje_studenta_form" class="<?= str_starts_with($currentPage, 'preferencje_studenta_form') ? 'active' : '' ?>">▶ Preferencje</a></li>

                            <hr>
                            <li><strong>Wnioski i Ankiety</strong></li>
                            <li><a href="index.php?page=wybory_admin_form" class="<?= str_starts_with($currentPage, 'wybory_admin_form') ? 'active' : '' ?>">Utwórz Wybór Przedmiotów</a></li>
                            <li><a href="index.php?page=wybory_admin_wyniki" class="<?= str_starts_with($currentPage, 'wybory_admin_wyniki') ? 'active' : '' ?>">Wyniki Wyborów</a></li>
                            <li><a href="index.php?page=stypendia_admin_lista" class="<?= str_starts_with($currentPage, 'stypendia_admin') ? 'active' : '' ?>">Zarządzaj Stypendiami</a></li>
                            <li><a href="index.php?page=ankiety_admin_form" class="<?= str_starts_with($currentPage, 'ankiety_admin_form') ? 'active' : '' ?>">Utwórz Ankietę</a></li>
                            <li><a href="index.php?page=ankiety_admin_wyniki" class="<?= str_starts_with($currentPage, 'ankiety_admin_wyniki') ? 'active' : '' ?>">Wyniki Ankiet</a></li>
                            <hr>
                            <li><strong>Plan Zajęć</strong></li>
                            <li><a href="index.php?page=plan_wyszukiwarka" class="<?= str_starts_with($currentPage, 'plan_') ? 'active' : '' ?>">Wyszukiwarka Planu</a></li>
                            <li><a href="index.php?page=plan_dodaj_szablon" class="<?= str_starts_with($currentPage, 'plan_') ? 'active' : '' ?>">Zarządzaj Szablonami</a></li>
                            <li><a href="index.php?page=plan_modyfikuj_termin" class="<?= str_starts_with($currentPage, 'plan_') ? 'active' : '' ?>">Modyfikuj Terminy</a></li>
                            <hr>
                            <li><strong>Ocenianie</strong></li>
                            <li><a href="index.php?page=oceny_czastkowe_wybor" class="<?= str_starts_with($currentPage, 'oceny_czastkowe') ? 'active' : '' ?>">Oceny Cząstkowe</a></li>
                            <li><a href="index.php?page=oceny_wybor_krok1" class="<?= str_starts_with($currentPage, 'oceny_wybor') ? 'active' : '' ?>">Oceny Końcowe</a></li>
                            <hr>
                            <li><strong>Zarządzanie Użytkownikami</strong></li>
                            <li><a href="index.php?page=studenci_lista" class="<?= str_starts_with($currentPage, 'studenci') ? 'active' : '' ?>">Studenci i Profile</a></li>
                            <li><a href="index.php?page=prowadzacy_lista" class="<?= str_starts_with($currentPage, 'prowadzacy') ? 'active' : '' ?>">Prowadzący</a></li>
                            <hr>
                            <li><strong>Dane Podstawowe</strong></li>
                            <li><a href="index.php?page=wydzialy_lista" class="<?= str_starts_with($currentPage, 'wydzialy') ? 'active' : '' ?>">Wydziały</a></li>
                            <li><a href="index.php?page=roki_lista" class="<?= str_starts_with($currentPage, 'roki') ? 'active' : '' ?>">Roki Akademickie</a></li>
                            <li><a href="index.php?page=sale_lista" class="<?= str_starts_with($currentPage, 'sale') ? 'active' : '' ?>">Sale Zajęciowe</a></li>
                        
                        <?php else: // ### MENU DLA ZWYKŁEGO NAUCZYCIELA ### ?>
                            <li><a href="index.php?page=obecnosc_wybor_zajec" class="<?= str_starts_with($currentPage, 'obecnosc') ? 'active' : '' ?>">Sprawdzanie Obecności</a></li>
                            <li><a href="index.php?page=plan_wyszukiwarka" class="<?= $currentPage == 'plan_wyszukiwarka' ? 'active' : '' ?>">Mój Plan Zajęć</a></li>
                            <hr>
                            <li><strong>Ocenianie</strong></li>
                            <li><a href="index.php?page=oceny_czastkowe_wybor" class="<?= str_starts_with($currentPage, 'oceny_czastkowe') ? 'active' : '' ?>">Wystaw Oceny Cząstkowe</a></li>
                            <li><a href="index.php?page=oceny_wybor_krok1" class="<?= str_starts_with($currentPage, 'oceny_wybor') ? 'active' : '' ?>">Wystaw Oceny Końcowe</a></li>
                        <?php endif; ?>
                    
                    <?php elseif ($user_role == 'student'): // ### MENU DLA STUDENTA ### ?>
                        <li><a href="index.php?page=karta_studenta" class="<?= $currentPage == 'karta_studenta' ? 'active' : '' ?>">Moje Oceny</a></li>
                        <li><a href="index.php?page=plan_wyszukiwarka" class="<?= $currentPage == 'plan_wyszukiwarka' ? 'active' : '' ?>">Mój Plan Zajęć</a></li>
                        <li><a href="index.php?page=obecnosc_student_podglad" class="<?= str_starts_with($currentPage, 'obecnosc_') ? 'active' : '' ?>">Moja Obecność</a></li>
                        <li><a href="index.php?page=wybory_student_lista" class="<?= str_starts_with($currentPage, 'wybory_student') ? 'active' : '' ?>">Wybór Przedmiotów</a></li>
                        <li><a href="index.php?page=stypendia_student_lista" class="<?= str_starts_with($currentPage, 'stypendia_student') ? 'active' : '' ?>">Wnioskuj o stypendium</a></li>
                        <li><a href="index.php?page=ankiety_student_lista" class="<?= str_starts_with($currentPage, 'ankiety_student') ? 'active' : '' ?>">Ankietyzacja</a></li>
                    <?php endif; ?>
                    
                    <li><hr></li>
                    <li><a href="logout.php">Wyloguj się</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <?php
            if (isset($_GET['status'])) {
                $status_class = $_GET['status'] == 'success' ? 'success' : 'error';
                $message = htmlspecialchars($_GET['message'] ?? '');
                echo "<div class='status-message {$status_class}'>{$message}</div>";
            }
            
            $view_file = "views/{$page}.php";

            if ($ma_dostep && file_exists($view_file)) {
                include $view_file;
            } else {
                http_response_code(403);
                echo "<h1>Błąd 403 - Dostęp Zablokowany</h1><p>Nie masz uprawnień, aby wyświetlić tę stronę.</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>