-- Student payment receipt tables for existing WSIT School installations.
-- In phpMyAdmin, select the application's database before running this file.

CREATE TABLE IF NOT EXISTS student_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    receipt_no VARCHAR(40) NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    paid_on DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL,
    reference_no VARCHAR(100) NULL,
    notes VARCHAR(255) NULL,
    received_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    branch_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY student_payments_receipt_unique (receipt_no),
    KEY student_payments_student_index (student_id),
    KEY student_payments_paid_on_index (paid_on),
    KEY student_payments_branch_index (branch_id),
    CONSTRAINT student_payments_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE RESTRICT,
    CONSTRAINT student_payments_user_fk FOREIGN KEY (received_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT student_payments_branch_fk FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_payment_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,
    fee_collection_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    branch_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY student_payment_invoice_unique (payment_id, fee_collection_id),
    KEY student_payment_allocations_invoice_index (fee_collection_id),
    KEY student_payment_allocations_branch_index (branch_id),
    CONSTRAINT student_payment_allocations_payment_fk FOREIGN KEY (payment_id) REFERENCES student_payments (id) ON DELETE CASCADE,
    CONSTRAINT student_payment_allocations_invoice_fk FOREIGN KEY (fee_collection_id) REFERENCES fee_collections (id) ON DELETE RESTRICT,
    CONSTRAINT student_payment_allocations_branch_fk FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
