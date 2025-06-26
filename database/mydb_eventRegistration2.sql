ALTER TABLE `mydb_eventregistration`.`event_registration` 
ADD COLUMN `total_tickets` INT NOT NULL AFTER `users_id`;

ALTER TABLE event_registration DROP COLUMN qr_code;

CREATE TABLE ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_registration_id INT NOT NULL,
    qr_code VARCHAR(255),
    participant_name VARCHAR(255),
    FOREIGN KEY (event_registration_id) REFERENCES event_registration(id)
);

ALTER TABLE ticket MODIFY qr_code LONGTEXT;

SELECT * FROM event_registration r
JOIN payments p ON r.payment_id = p.id
WHERE p.status = 'pending';

SELECT r.*, e.name, e.date, e.location, p.id as payment_id, p.proof_path, p.status as payment_status
FROM event_registration r
JOIN events e ON r.event_id = e.id
JOIN payments p ON r.payment_id = p.id
WHERE p.status = 'pending'
ORDER BY r.registered_at DESC

DROP TABLE IF EXISTS event_attendance;

ALTER TABLE ticket
  ADD COLUMN scanned_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN scanned_by INT(11) NULL DEFAULT NULL;
  
ALTER TABLE ticket ADD COLUMN qr_value VARCHAR(255);

ALTER TABLE ticket ADD COLUMN certificate_path VARCHAR(255) AFTER qr_value;