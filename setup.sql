--
-- File: setup.sql
-- Descrizione: Script SQL per impostare il database per gli esempi di QueBu.
--

-- Elimina la tabella se esiste per garantire un'installazione pulita.
DROP TABLE IF EXISTS `users`;

-- Crea la tabella `users`.
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `registration_date` DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserisci alcuni dati di esempio nella tabella `users`.
INSERT INTO `users` (`name`, `email`, `registration_date`) VALUES
('Mario Rossi', 'mario.rossi@example.com', '2023-01-15'),
('Luigi Verdi', 'luigi.verdi@example.com', '2023-02-20'),
('Anna Bianchi', 'anna.bianchi@example.com', '2023-03-10'),
('Laura Neri', 'laura.neri@example.com', '2023-04-05');
