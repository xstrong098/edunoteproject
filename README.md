# EduNote

EduNote è una piattaforma PHP progettata per facilitare la condivisione di appunti e la gestione di gruppi di studio.

## Requisiti

- PHP 7.4 o superiore
- MySQL o MariaDB
- Server Apache (es. tramite XAMPP o MAMP)

---

## Installazione Manuale (senza Git)

### 1. Scaricare il progetto

1. Vai al repository GitHub del progetto (ad es. `https://github.com/xstrong098/EduNoteKra`)
2. Clicca su **Code** > **Download ZIP**
3. Estrai l’archivio ZIP in una cartella

---

### 2. Spostare la cartella nel server web

- **Con XAMPP (Windows/Linux):**

  Copia la cartella del progetto in: C:\xampp\htdocs



- **Con MAMP (macOS):**

Copia la cartella in: /Application/MAMP/htdocs/




---

### 3. Avviare i servizi

- Apri **XAMPP Control Panel** o **MAMP**
- Avvia **Apache** e **MySQL**

---

### 4. Creare il database

1. Vai su [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Clicca su “Nuovo” e crea un database con nome edunote
3. Seleziona il database appena creato
4. Clicca su “Importa”
5. Seleziona il file `database-structure.sql` presente nella cartella del progetto
6. Avvia l'importazione

---

### 5. Configurare la connessione al database

Apri il file config/config.php


Modifica le seguenti righe secondo la tua configurazione locale:

```php
$host = 'localhost';
$db   = 'edunote_db';
$user = 'root';     // Utente di default su XAMPP/MAMP
$pass = '';         // Password vuota di default

Dal browser avvia il sito con: 
http://localhost/EduNoteKra/


### Note importanti
I file caricati vengono salvati nella cartella /uploads/
Evita di rinominare file/folder se non sai dove sono richiamati nel codice
Assicurati che la cartella uploads/ abbia i permessi di scrittura attivi (specialmente su)




