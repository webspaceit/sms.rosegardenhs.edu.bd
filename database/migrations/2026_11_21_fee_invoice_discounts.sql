-- Approved invoice-level discounts used by the student payment receipt flow.
-- Run this once in phpMyAdmin after selecting the application database.

CREATE TABLE IF NOT EXISTS fee_invoice_discounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    fee_collection_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason VARCHAR(255) NULL,
    approved_by BIGINT UNSIGNED NOT NULL,
    approved_on DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    branch_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY fee_invoice_discounts_student_index (student_id),
    KEY fee_invoice_discounts_invoice_index (fee_collection_id),
    KEY fee_invoice_discounts_payment_index (payment_id),
    KEY fee_invoice_discounts_branch_index (branch_id),
    CONSTRAINT fee_invoice_discounts_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE RESTRICT,
    CONSTRAINT fee_invoice_discounts_invoice_fk FOREIGN KEY (fee_collection_id) REFERENCES fee_collections (id) ON DELETE RESTRICT,
    CONSTRAINT fee_invoice_discounts_payment_fk FOREIGN KEY (payment_id) REFERENCES student_payments (id) ON DELETE CASCADE,
    CONSTRAINT fee_invoice_discounts_approver_fk FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fee_invoice_discounts_branch_fk FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
