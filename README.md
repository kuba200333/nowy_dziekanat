# Wirtualny Dziekanat

![Podgląd Ocen](https://github.com/kuba200333/nowy_dziekanat/blob/main/image/oceny.png?raw=true)

**Wirtualny Dziekanat** to kompleksowa aplikacja webowa napisana w PHP, symulująca system do zarządzania uczelnią. Umożliwia obsługę kluczowych procesów akademickich z perspektywy studenta, prowadzącego oraz administratora.

---

## O Projekcie

Aplikacja została zaprojektowana jako w pełni funkcjonalny system informatyczny dla uczelni wyższej. Umożliwia zarządzanie danymi studentów, kadrą dydaktyczną, przedmiotami, planami zajęć, ocenami oraz procesami administracyjnymi, takimi jak nabory na stypendia czy wybór przedmiotów obieralnych. System posiada trzy główne role użytkowników, z których każda ma dedykowany panel i zestaw uprawnień.

## Główne Funkcje

System podzielony jest na moduły dostępne dla różnych typów użytkowników:

### Panel Studenta
* **Moje Oceny:** Podgląd karty ocen z poszczególnych semestrów wraz ze statystykami (średnia ważona, zdobyte punkty ECTS).
* **Mój Plan Zajęć:** Interaktywny plan zajęć z wyszukiwarką i możliwością nawigacji między tygodniami.
* **Moja Obecność:** Wgląd w historię obecności na zajęciach.
* **Wnioski o Stypendium:** Możliwość składania wniosków w otwartych naborach.
* **Wybór Przedmiotów:** Panel do głosowania na przedmioty obieralne.
* **Ankietyzacja:** Wypełnianie ankiet oceniających zajęcia i prowadzących.

### Panel Prowadzącego
* **Wystawianie Ocen:** Formularze do wystawiania ocen cząstkowych i końcowych dla studentów w prowadzonych grupach.
* **Sprawdzanie Obecności:** Panel do zarządzania listą obecności na poszczególnych terminach zajęć.
* **Podgląd Planu:** Wgląd we własny plan zajęć.

### Panel Administratora (Dziekanat)
* **Zarządzanie Dydaktyką:** Pełna kontrola nad strukturą akademicką – wydziały, przedmioty, konfiguracja ECTS i wag ocen.
* **Zarządzanie Użytkownikami:** Dodawanie i edycja kont studentów oraz prowadzących.
* **Zarządzanie Zapisami:** Masowe zapisywanie studentów na zajęcia, zarządzanie grupami.
* **Zarządzanie Planem Zajęć:** Tworzenie szablonów planu i generowanie terminów.
* **Obsługa Stypendiów:** Tworzenie naborów i zarządzanie wnioskami studentów.
* **Przegląd Danych:** Dostęp do kartotek studentów, przeglądów dydaktyki i wyników ankiet.

## Galeria

![Plan Lekcji](https://github.com/kuba200333/nowy_dziekanat/blob/main/image/plan_lekcji.png?raw=true)
*Podgląd planu zajęć studenta.*

![Wystawianie Ocen](https://github.com/kuba200333/nowy_dziekanat/blob/main/image/wystaw_oceny.png?raw=true)
*Formularz wystawiania ocen przez prowadzącego.*

![Zarządzanie Stypendiami](https://github.com/kuba200333/nowy_dziekanat/blob/main/image/zarzadzaj_stypendiami.png?raw=true)
*Panel administratora do zarządzania wnioskiem stypendialnym.*

![Konfiguracja ECTS](https://github.com/kuba200333/nowy_dziekanat/blob/main/image/konfiguracja_ECTS.png?raw=true)
*Konfiguracja punktów ECTS dla przedmiotów w danym roku akademickim.*


## Uruchomienie Projektu

Do uruchomienia aplikacji potrzebne jest lokalne środowisko serwerowe (np. XAMPP).

### Wymagania
* Serwer WWW (np. Apache)
* PHP (w wersji 8.0 lub nowszej)
* Baza danych MariaDB lub MySQL

### Kroki Instalacji
1.  **Pobierz repozytorium** i umieść pliki w folderze serwera WWW (np. `C:\xampp\htdocs\dziekanat`).
2.  **Utwórz bazę danych** za pomocą narzędzia phpMyAdmin. Nazwa bazy danych powinna być zgodna z tą w pliku konfiguracyjnym (domyślnie `wirtualny_dziekanat`).
3.  **Zaimportuj strukturę bazy danych.** Wybierz utworzoną bazę, przejdź do zakładki "Import" i wskaż plik `.sql` zawierający strukturę tabel i dane.
4.  **Skonfiguruj połączenie.** Upewnij się, że dane w pliku `db_config.php` (nazwa użytkownika, hasło, nazwa bazy) zgadzają się z Twoją konfiguracją serwera.
5.  **Uruchom aplikację,** wchodząc w przeglądarce na adres `http://localhost/nazwa_folderu_projektu` (np. `http://localhost/dziekanat`).

## Użyte Technologie
* **Backend:** PHP
* **Baza Danych:** MariaDB / MySQL
* **Frontend:** HTML, CSS