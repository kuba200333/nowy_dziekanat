import requests
import pandas as pd
from datetime import datetime

# Lista numerów albumów
album_numbers = [
53660, 53978, 54439, 55552, 55555, 55556, 55558, 55560, 55563, 55566, 55567, 55569, 55571, 55572, 55573, 55575, 55576, 55579, 55580, 55582, 55583, 55585, 55586, 55587, 55588, 55589, 55590, 55592, 55594, 55596, 55597, 55599, 55600, 55602, 55603, 55604, 55611, 55612, 55613, 55615, 55616, 55617, 55618, 55621, 55623, 55624, 55625, 55630, 55631, 55634, 55635, 55638, 55639, 55641, 55642, 55643, 55644, 55647, 55648, 55650, 55653, 55655, 55656, 55659, 55660, 55661, 55662, 55664, 55666, 55667, 55669, 55670, 55672, 55673, 55675, 55676, 55678, 55679, 55681, 55683, 55684, 55686, 55687, 55689, 55692, 55694, 55695, 55696, 55697, 55699, 55700, 55702, 55703, 55706, 55707, 55709, 55710, 55714, 55715, 55716, 55717, 55718, 55719, 55721, 55722, 55723, 55724, 55725, 55726, 55727, 55728, 55729, 55730, 55732, 55733, 55734, 55735, 55736, 55737, 55738, 55740, 55741, 55742, 55743, 55744, 55745, 55746, 55747, 55748, 55750, 55751, 55752, 55753, 55755, 55756, 55757, 55759, 55760, 55761, 55762, 55763, 55764, 55765, 55767, 55768, 55769, 55770, 55771, 55773, 55774, 55776, 55778, 55779, 55782, 55783, 55784, 55785, 55787, 55789, 55792, 55794, 55796, 55798, 55799, 55801, 55804, 55805, 55806, 55807, 55809, 55813, 55816, 55817, 55819, 55820, 55821, 55825, 55826, 55827, 55828, 55830, 55831, 55832, 55834, 55837, 55839, 55840, 55841, 55842, 55843, 55848, 55849, 55854, 55857, 55876

]

# Zakres dat dla planu zajęć
start_date = "2025-10-01"
end_date = "2026-03-01"

# Nagłówki kolumn w pliku Excel (usunięto 'Numer Albumu')
excel_columns = [
    #'Tytuł Zajęć', 'Opis', 
    'Rozpoczęcie', 'Zakończenie',
    #'Wykładowca (Tytuł)', 
    'Wykładowca', 'Forma Zajęć',
    #'Forma Zajęć (Skrót)', 
    'Nazwa Grupy', 
    #'Nazwa TOK', 
    'Sala',
    #'Status Zajęć', 'Status Zajęć (Skrót)', 'Status Przedmiotu',
    'Przedmiot'
    #, 'Godziny', 'Kolor', 'Kolor Ramki'
]

# Lista do przechowywania wszystkich unikalnych danych z planów zajęć
all_schedule_data = []
# Zbiór (set) do śledzenia już dodanych zajęć, aby uniknąć duplikatów
unique_entries_tracker = set()

print("Rozpoczynanie pobierania unikalnych planów zajęć...")

for album_number in album_numbers:
    url = f"https://plan.zut.edu.pl/schedule_student.php?number={album_number}&start={start_date}&end={end_date}"
    print(f"Pobieranie planu dla numeru albumu: {album_number}")

    try:
        response = requests.get(url)
        response.raise_for_status()
        data = response.json()

        if isinstance(data, list) and len(data) > 1:
            schedule_entries = data[1:]
            if not schedule_entries:
                print(f"Brak zajęć dla numeru albumu {album_number} w podanym zakresie dat.")
                continue

            for entry in schedule_entries:
                # Tworzymy unikalny identyfikator dla każdych zajęć
                # na podstawie kluczowych informacji (tytuł, start, koniec, prowadzący)
                unique_id = (
                    entry.get('title'),
                    entry.get('start'),
                    entry.get('end'),
                    entry.get('worker')
                )

                # Jeśli ten identyfikator nie był jeszcze widziany, dodajemy zajęcia
                if unique_id not in unique_entries_tracker:
                    unique_entries_tracker.add(unique_id) # Dodaj do śledzonych
                    
                    row = {
                        'Tytuł Zajęć': entry.get('title'),
                        'Opis': entry.get('description'),
                        'Rozpoczęcie': entry.get('start'),
                        'Zakończenie': entry.get('end'),
                        'Wykładowca (Tytuł)': entry.get('worker_title'),
                        'Wykładowca': entry.get('worker'),
                        'Forma Zajęć': entry.get('lesson_form'),
                        'Forma Zajęć (Skrót)': entry.get('lesson_form_short'),
                        'Nazwa Grupy': entry.get('group_name'),
                        'Nazwa TOK': entry.get('tok_name'),
                        'Sala': entry.get('room'),
                        'Status Zajęć': entry.get('lesson_status'),
                        'Status Zajęć (Skrót)': entry.get('lesson_status_short'),
                        'Status Przedmiotu': entry.get('status_item'),
                        'Przedmiot': entry.get('subject'),
                        'Godziny': entry.get('hours'),
                        'Kolor': entry.get('color'),
                        'Kolor Ramki': entry.get('borderColor')
                    }
                    all_schedule_data.append(row)
        else:
            print(f"Brak danych lub nieprawidłowa struktura dla numeru albumu {album_number}.")

    except requests.exceptions.RequestException as e:
        print(f"Błąd podczas pobierania danych dla numeru albumu {album_number}: {e}")
    except ValueError:
        print(f"Błąd dekodowania JSON dla numeru albumu {album_number}.")
    except Exception as e:
        print(f"Wystąpił nieoczekiwany błąd dla numeru albumu {album_number}: {e}")

# --- Zapisywanie danych do Excela ---
if not all_schedule_data:
    print("\nBrak danych do zapisania w pliku Excel.")
else:
    df = pd.DataFrame(all_schedule_data, columns=excel_columns)
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    output_filename = f"plan_zajec_ZUT_{timestamp}.xlsx"

    try:
        df.to_excel(output_filename, index=False)
        print(f"\nSukces! Dane zostały zapisane do pliku: {output_filename}")
        print(f"Znaleziono łącznie {len(df)} unikalnych wpisów zajęć.")
    except Exception as e:
        print(f"Błąd podczas zapisywania do pliku Excel: {e}")