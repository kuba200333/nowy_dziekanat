import requests
import pandas as pd
from datetime import datetime

# Lista bazowych nazw przedmiotów do wyszukania
# POPRAWKA: Usunięto duplikat i dodano brakujący przecinek
subject_names = [
    'Język angielski 1',
    'Systemy informacji geograficznej',
    'Grafika i Wizualizacja',
    'Inżynieria oprogramowania',
    'Metody numeryczne 2',
    'Przetwarzanie i analiza danych',
    'Transmisja danych',
    'Zarządzanie informacją 1',
    'Metody obliczeniowe i jakościowe w inżynierii biznesu',
    'Teoria informacji i kodowania',
    'Wychowanie fizyczne 2'
]

# Stały dopisek do nazwy przedmiotu
SUBJECT_SUFFIX = " (WI, informatyka, SN, SPS)"

# Zakres dat
start_date = "2025-02-01"
end_date = "2025-05-10"

# Nagłówki kolumn w pliku Excel
excel_columns = [
    'Wykładowca','Forma Zajęć','Nazwa Grupy','Przedmiot'
    # 'Kryterium Wyszukiwania', 'Tytuł Zajęć', 'Opis', 'Rozpoczęcie', 'Zakończenie',
    # 'Wykładowca (Tytuł)', 'Wykładowca', 'Forma Zajęć',
    # 'Forma Zajęć (Skrót)', 'Nazwa Grupy', 'Nazwa TOK', 'Sala',
    # 'Status Zajęć', 'Status Zajęć (Skrót)', 'Status Przedmiotu',
    # 'Przedmiot', 'Godziny', 'Kolor', 'Kolor Ramki'
]

all_schedule_data = []
# Zbiór (set) do śledzenia już dodanych, unikalnych przypisańskich
unique_entries_tracker = set()

print("Rozpoczynanie wyszukiwania planów zajęć dla przedmiotów...")

for subject_base_name in subject_names:
    full_subject_query = f"{subject_base_name}{SUBJECT_SUFFIX}"
    url = "https://plan.zut.edu.pl/schedule_student.php"
    params = {
        'subject': full_subject_query,
        'start': f"{start_date}T00:00:00+02:00",
        'end': f"{end_date}T00:00:00+02:00"
    }

    print(f"Wysyłanie zapytania dla: '{full_subject_query}'")

    try:
        response = requests.get(url, params=params)
        response.raise_for_status()
        data = response.json()

        if isinstance(data, list) and len(data) > 1:
            schedule_entries = data[1:]
            for entry in schedule_entries:
                if not isinstance(entry, dict):
                    continue

                # Tworzymy unikalny identyfikator dla każdego przypisania
                unique_id = (
                    entry.get('worker'),
                    entry.get('lesson_form'),
                    entry.get('group_name'),
                    entry.get('subject')
                )

                # Jeśli ten identyfikator nie był jeszcze widziany, dodajemy wpis
                if unique_id not in unique_entries_tracker:
                    unique_entries_tracker.add(unique_id)  # Dodaj do śledzonych
                    
                    row = {
                        'Wykładowca': entry.get('worker'),
                        'Forma Zajęć': entry.get('lesson_form'),
                        'Nazwa Grupy': entry.get('group_name'),
                        'Przedmiot': entry.get('subject')
                    }
                    all_schedule_data.append(row)
        else:
            print(f"--- Brak wyników lub nieprawidłowa odpowiedź dla zapytania.")

    except requests.exceptions.RequestException as e:
        print(f"--- Błąd podczas pobierania danych: {e}")
    except ValueError:
        print(f"--- Błąd dekodowania JSON. Prawdopodobnie pusta odpowiedź.")
    except Exception as e:
        print(f"--- Wystąpił nieoczekiwany błąd: {e}")

# --- Zapisywanie danych do pliku CSV ---
if not all_schedule_data:
    print("\nBrak jakichkolwiek danych do zapisania w pliku.")
else:
    df = pd.DataFrame(all_schedule_data, columns=excel_columns)
    df.sort_values(by=['Przedmiot', 'Nazwa Grupy', 'Forma Zajęć'], inplace=True)

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    output_filename = f"zajecia_przypisania_{timestamp}.csv"

    try:
        # ZMIANA: Dodajemy parametr encoding='utf-8-sig', który poprawnie zapisuje polskie znaki
        df.to_csv(output_filename, index=False, sep=';', encoding='utf-8-sig')
        
        print(f"\n✅ Sukces! Dane zostały zapisane do pliku: {output_filename}")
        print(f"Znaleziono łącznie {len(all_schedule_data)} unikalnych przypisań.")
    except Exception as e:
        print(f"\n❌ Błąd podczas zapisywania do pliku CSV: {e}")
