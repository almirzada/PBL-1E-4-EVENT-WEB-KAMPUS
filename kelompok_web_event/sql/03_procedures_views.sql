DELIMITER $$

--
-- Prosedur: generate_registration_report
--
CREATE PROCEDURE `generate_registration_report` (IN `p_start_date` DATE, IN `p_end_date` DATE)  
BEGIN
    SELECT 
        e.judul AS event_name,
        e.tanggal AS event_date,
        e.biaya_pendaftaran AS fee,
        COUNT(DISTINCT CASE WHEN e.tipe_pendaftaran = 'tim' THEN p.tim_id ELSE p.id END) AS total_registrations,
        SUM(CASE WHEN p.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END) AS verified_payments,
        SUM(CASE WHEN p.status_pembayaran = 'menunggu_verifikasi' THEN 1 ELSE 0 END) AS pending_payments,
        SUM(e.biaya_pendaftaran * 
            CASE WHEN e.tipe_pendaftaran = 'tim' THEN 
                (CASE WHEN t.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END)
            ELSE
                (CASE WHEN p.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END)
            END) AS total_verified_revenue
    FROM events e
    LEFT JOIN peserta p ON e.id = p.event_id
    LEFT JOIN tim_event t ON p.tim_id = t.id
    WHERE DATE(p.created_at) BETWEEN p_start_date AND p_end_date
       OR DATE(t.created_at) BETWEEN p_start_date AND p_end_date
    GROUP BY e.id, e.judul, e.tanggal, e.biaya_pendaftaran
    ORDER BY e.tanggal DESC;
END$$

--
-- Prosedur: verifikasi_pembayaran
--
CREATE PROCEDURE `verifikasi_pembayaran` (
    IN `p_id` INT, 
    IN `p_tipe` VARCHAR(10), 
    IN `p_admin_id` INT, 
    IN `p_status_baru` VARCHAR(20), 
    IN `p_catatan` TEXT
)  
BEGIN
    DECLARE v_peserta_id INT;
    DECLARE v_tim_id INT;
    DECLARE v_status_sebelum VARCHAR(20);
    
    IF p_tipe = 'individu' THEN
        SELECT id, status_pembayaran INTO v_peserta_id, v_status_sebelum
        FROM peserta WHERE id = p_id;
        
        UPDATE peserta 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
        
        INSERT INTO log_pembayaran (peserta_id, admin_id, status_sebelum, status_sesudah, catatan)
        VALUES (v_peserta_id, p_admin_id, v_status_sebelum, p_status_baru, p_catatan);
        
    ELSEIF p_tipe = 'tim' THEN
        SELECT id, status_pembayaran INTO v_tim_id, v_status_sebelum
        FROM tim_event WHERE id = p_id;
        
        UPDATE tim_event 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
        
        UPDATE peserta 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE tim_id = p_id;
        
        INSERT INTO log_pembayaran (tim_id, admin_id, status_sebelum, status_sesudah, catatan)
        VALUES (v_tim_id, p_admin_id, v_status_sebelum, p_status_baru, p_catatan);
    END IF;
END$$

DELIMITER ;