CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin','admin','teacher','accountant','receptionist','librarian','student','parent') NOT NULL DEFAULT 'admin',
    link_type ENUM('student','teacher','guardian') NULL,
    link_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NOT NULL,
    PRIMARY KEY (email_hash, ip_hash),
    KEY login_attempts_time_index (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    user_email VARCHAR(190) NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY audit_logs_user_time_index (user_id, created_at),
    KEY audit_logs_entity_index (entity, entity_id, created_at),
    CONSTRAINT audit_logs_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academic_years (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY academic_years_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    numeric_level TINYINT UNSIGNED NOT NULL,
    shift ENUM('Morning','Day','Both') NOT NULL DEFAULT 'Day',
    PRIMARY KEY (id),
    UNIQUE KEY classes_name_unique (name),
    UNIQUE KEY classes_numeric_level_unique (numeric_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(20) NOT NULL,
    room_no VARCHAR(30) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY sections_class_name_unique (class_id, name),
    CONSTRAINT sections_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(40) NOT NULL,
    name VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    roll_no SMALLINT UNSIGNED NOT NULL,
    gender VARCHAR(20) NULL,
    photo VARCHAR(255) NULL,
    date_of_birth DATE NULL,
    birth_registration_no VARCHAR(50) NULL,
    admission_date DATE NULL,
    previous_school VARCHAR(190) NULL,
    address TEXT NULL,
    status ENUM('active','inactive','graduated') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY students_student_id_unique (student_id),
    UNIQUE KEY students_roll_unique (class_id, section_id, academic_year_id, roll_no),
    KEY students_year_status_index (academic_year_id, status),
    CONSTRAINT students_class_fk FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT students_section_fk FOREIGN KEY (section_id) REFERENCES sections (id),
    CONSTRAINT students_academic_year_fk FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_enrollment_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    roll_no SMALLINT UNSIGNED NOT NULL,
    status ENUM('active','inactive','graduated') NOT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY student_enrollment_history_year_unique (student_id, academic_year_id),
    KEY student_enrollment_history_class_index (academic_year_id, class_id, section_id),
    CONSTRAINT enrollment_history_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT enrollment_history_year_fk FOREIGN KEY (academic_year_id) REFERENCES academic_years (id),
    CONSTRAINT enrollment_history_class_fk FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT enrollment_history_section_fk FOREIGN KEY (section_id) REFERENCES sections (id),
    CONSTRAINT enrollment_history_user_fk FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Communication
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS notices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'general',
    published_date DATE NOT NULL,
    expires_date DATE NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY notices_published_index (published_date),
    CONSTRAINT notices_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS holidays (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY holidays_date_index (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#00704F',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY events_start_index (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complainant_name VARCHAR(150) NOT NULL,
    complainant_phone VARCHAR(20) NULL,
    description TEXT NOT NULL,
    type ENUM('student','staff','other') NOT NULL DEFAULT 'other',
    status ENUM('pending','investigating','resolved','dismissed') NOT NULL DEFAULT 'pending',
    resolution_notes TEXT NULL,
    resolved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY complaints_status_index (status),
    CONSTRAINT complaints_resolved_by_fk FOREIGN KEY (resolved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Settings, permissions and branches
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS school_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    value TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY school_settings_key_unique (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY permissions_slug_unique (slug),
    KEY permissions_module_index (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role VARCHAR(30) NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY role_permissions_unique (role, permission_id),
    CONSTRAINT role_permissions_permission_fk FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(190) NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY branches_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Subjects, defined before the tables that reference them
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS subjects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_bn VARCHAR(150) NULL,
    code VARCHAR(20) NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'core',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY subjects_name_unique (name),
    UNIQUE KEY subjects_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_subject (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    full_marks SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    written_marks SMALLINT UNSIGNED NULL,
    mcq_marks SMALLINT UNSIGNED NULL,
    practical_marks SMALLINT UNSIGNED NULL,
    ca_marks SMALLINT UNSIGNED NULL,
    pass_marks TINYINT UNSIGNED NOT NULL DEFAULT 33,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY class_subject_unique (class_id, subject_id),
    CONSTRAINT class_subject_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT class_subject_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- People: teachers, guardians and student houses
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS teachers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    employee_id VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NULL,
    department VARCHAR(100) NULL,
    designation_id BIGINT UNSIGNED NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(190) NULL,
    district VARCHAR(100) NULL,
    joining_date DATE NULL,
    gender VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    blood_group VARCHAR(5) NULL,
    address VARCHAR(255) NULL,
    qualification TEXT NULL,
    experience_years INT UNSIGNED NOT NULL DEFAULT 0,
    photo VARCHAR(255) NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY teachers_employee_id_unique (employee_id),
    KEY teachers_name_index (name),
    KEY teachers_designation_index (designation_id),
    CONSTRAINT teachers_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS designations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY designations_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_subject (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    is_class_teacher TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY teacher_subject_unique (teacher_id, class_id, subject_id),
    CONSTRAINT teacher_subject_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE,
    CONSTRAINT teacher_subject_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT teacher_subject_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guardians (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(190) NULL,
    occupation VARCHAR(100) NULL,
    relation VARCHAR(50) NOT NULL,
    address TEXT NULL,
    district VARCHAR(100) NULL,
    nid VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY guardians_phone_index (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_guardian (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    guardian_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY student_guardian_unique (student_id, guardian_id),
    CONSTRAINT student_guardian_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT student_guardian_guardian_fk FOREIGN KEY (guardian_id) REFERENCES guardians (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_houses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_bn VARCHAR(150) NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#0ea5a4',
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY student_houses_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Finance: fee heads, structures and collections
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS fee_heads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    name_bn VARCHAR(150) NULL,
    frequency ENUM('monthly','yearly','one-time') NOT NULL DEFAULT 'monthly',
    is_optional TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY fee_heads_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_structures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    fee_head_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY fee_structures_unique (class_id, fee_head_id),
    CONSTRAINT fee_structures_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT fee_structures_head_fk FOREIGN KEY (fee_head_id) REFERENCES fee_heads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_collections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    fee_head_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NULL,
    invoice_no VARCHAR(50) NOT NULL,
    month VARCHAR(20) NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('paid','due','partial') NOT NULL DEFAULT 'due',
    due_date DATE NULL,
    paid_on DATE NULL,
    payment_method VARCHAR(50) NULL,
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY fee_collections_invoice_unique (invoice_no),
    KEY fee_collections_student_index (student_id),
    KEY fee_collections_status_index (status),
    CONSTRAINT fee_collections_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fee_collections_head_fk FOREIGN KEY (fee_head_id) REFERENCES fee_heads (id) ON DELETE CASCADE,
    CONSTRAINT fee_collections_year_fk FOREIGN KEY (academic_year_id) REFERENCES academic_years (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_discounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    fee_head_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason VARCHAR(255) NULL,
    approved_by BIGINT UNSIGNED NULL,
    date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY fee_discounts_student_index (student_id),
    CONSTRAINT fee_discounts_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fee_discounts_head_fk FOREIGN KEY (fee_head_id) REFERENCES fee_heads (id) ON DELETE SET NULL,
    CONSTRAINT fee_discounts_approved_fk FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_carry_forwards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason VARCHAR(255) NULL,
    date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY fee_carry_forwards_student_index (student_id),
    CONSTRAINT fee_carry_forwards_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT fee_carry_forwards_year_fk FOREIGN KEY (academic_year_id) REFERENCES academic_years (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Accounts
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type ENUM('income','expense') NOT NULL DEFAULT 'expense',
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    date DATE NOT NULL,
    payment_method VARCHAR(50) NULL,
    reference VARCHAR(100) NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY transactions_date_index (date),
    KEY transactions_type_index (type),
    CONSTRAINT transactions_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(50) NULL,
    statement_date DATE NOT NULL,
    ending_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
    cleared_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
    difference DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('pending','cleared','discrepancy') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY bank_reconciliations_date_index (statement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Payroll
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS salary_structures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    house_rent DECIMAL(12,2) NOT NULL DEFAULT 0,
    medical DECIMAL(12,2) NOT NULL DEFAULT 0,
    transport_allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
    other_allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
    deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    effective_from DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY salary_structures_unique (teacher_id, effective_from),
    CONSTRAINT salary_structures_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    month VARCHAR(20) NOT NULL,
    year VARCHAR(10) NOT NULL,
    gross_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_date DATE NOT NULL,
    payment_method VARCHAR(50) NULL,
    reference_no VARCHAR(50) NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY salary_payments_unique (teacher_id, month, year),
    CONSTRAINT salary_payments_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Attendance
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS attendances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late','half_day','leave') NOT NULL DEFAULT 'present',
    remarks VARCHAR(255) NULL,
    marked_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY attendances_unique (student_id, date),
    KEY attendances_date_index (date),
    CONSTRAINT attendances_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT attendances_marked_by_fk FOREIGN KEY (marked_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_attendances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late','half_day','leave') NOT NULL DEFAULT 'present',
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY teacher_attendances_unique (teacher_id, date),
    CONSTRAINT teacher_attendances_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_attendances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late','half_day','leave') NOT NULL DEFAULT 'present',
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY staff_attendances_unique (user_id, date),
    CONSTRAINT staff_attendances_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    type ENUM('sick','casual','annual','maternity','unpaid') NOT NULL DEFAULT 'casual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_remarks VARCHAR(255) NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY leave_requests_teacher_index (teacher_id),
    CONSTRAINT leave_requests_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE,
    CONSTRAINT leave_requests_approved_fk FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inventory
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS inventory_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY inventory_categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    minimum_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY inventory_items_category_index (category_id),
    CONSTRAINT inventory_items_category_fk FOREIGN KEY (category_id) REFERENCES inventory_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Library
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS books (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NULL,
    publisher VARCHAR(150) NULL,
    isbn VARCHAR(50) NULL,
    edition VARCHAR(50) NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    available INT UNSIGNED NOT NULL DEFAULT 1,
    shelf_location VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY books_title_index (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS book_issues (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    book_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    teacher_id BIGINT UNSIGNED NULL,
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    return_date DATE NULL,
    fine DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('issued','returned','overdue','lost') NOT NULL DEFAULT 'issued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY book_issues_book_index (book_id),
    KEY book_issues_student_index (student_id),
    CONSTRAINT book_issues_book_fk FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE,
    CONSTRAINT book_issues_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT book_issues_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Transport and hostel
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS transport_routes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    driver_name VARCHAR(150) NULL,
    driver_phone VARCHAR(20) NULL,
    vehicle_no VARCHAR(30) NULL,
    fare DECIMAL(10,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY transport_routes_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transport_stops (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transport_route_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    pickup_time TIME NULL,
    drop_time TIME NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY transport_stops_route_index (transport_route_id),
    CONSTRAINT transport_stops_route_fk FOREIGN KEY (transport_route_id) REFERENCES transport_routes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_transport (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    transport_route_id BIGINT UNSIGNED NOT NULL,
    transport_stop_id BIGINT UNSIGNED NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY student_transport_unique (student_id, transport_route_id),
    CONSTRAINT student_transport_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT student_transport_route_fk FOREIGN KEY (transport_route_id) REFERENCES transport_routes (id) ON DELETE CASCADE,
    CONSTRAINT student_transport_stop_fk FOREIGN KEY (transport_stop_id) REFERENCES transport_stops (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hostel_rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_no VARCHAR(30) NOT NULL,
    type ENUM('single','double','triple','dormitory') NOT NULL DEFAULT 'double',
    capacity INT UNSIGNED NOT NULL DEFAULT 2,
    rent_per_bed DECIMAL(10,2) NOT NULL DEFAULT 0,
    facilities VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hostel_rooms_room_unique (room_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hostel_allocations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    hostel_room_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hostel_allocations_unique (student_id, hostel_room_id),
    CONSTRAINT hostel_allocations_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT hostel_allocations_room_fk FOREIGN KEY (hostel_room_id) REFERENCES hostel_rooms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Timetable
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS class_routines (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,
    day_of_week ENUM('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY class_routines_class_index (class_id, day_of_week),
    CONSTRAINT class_routines_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT class_routines_section_fk FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE CASCADE,
    CONSTRAINT class_routines_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE,
    CONSTRAINT class_routines_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Exams
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS exams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    type ENUM('unit','mid-term','half-yearly','annual','model-test','other') NOT NULL DEFAULT 'other',
    academic_year_id BIGINT UNSIGNED NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY exams_year_index (academic_year_id),
    CONSTRAINT exams_year_fk FOREIGN KEY (academic_year_id) REFERENCES academic_years (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NULL,
    date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    hall VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY exam_schedules_unique (exam_id, subject_id, class_id),
    CONSTRAINT exam_schedules_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    CONSTRAINT exam_schedules_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE,
    CONSTRAINT exam_schedules_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attendances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    present TINYINT(1) NOT NULL DEFAULT 1,
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY exam_attendances_unique (exam_id, student_id, subject_id),
    CONSTRAINT exam_attendances_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    CONSTRAINT exam_attendances_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT exam_attendances_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_suggestions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    suggestion TEXT NULL,
    file_path VARCHAR(255) NULL,
    teacher_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY exam_suggestions_exam_index (exam_id),
    CONSTRAINT exam_suggestions_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    CONSTRAINT exam_suggestions_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL,
    CONSTRAINT exam_suggestions_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Reports and results
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS academic_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    exam_id BIGINT UNSIGNED NOT NULL,
    class_position INT UNSIGNED NULL,
    working_days INT UNSIGNED NOT NULL DEFAULT 0,
    present_days INT UNSIGNED NOT NULL DEFAULT 0,
    absent_days INT UNSIGNED NOT NULL DEFAULT 0,
    moral_evaluation VARCHAR(255) NULL,
    co_curricular TEXT NULL,
    comments VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY academic_reports_student_exam_unique (student_id, exam_id),
    KEY academic_reports_exam_index (exam_id),
    CONSTRAINT academic_reports_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT academic_reports_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academic_report_marks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_report_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    full_marks SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    highest_marks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    written_marks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    mcq_marks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    practical_marks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ca_marks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    obtained SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY academic_report_marks_report_index (academic_report_id),
    KEY academic_report_marks_subject_index (subject_id),
    CONSTRAINT academic_report_marks_report_fk FOREIGN KEY (academic_report_id) REFERENCES academic_reports (id) ON DELETE CASCADE,
    CONSTRAINT academic_report_marks_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_configs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type ENUM('report','fail-list','section-result','tabulation','merit-list','admit-card') NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(20) NOT NULL DEFAULT '',
    exam_name VARCHAR(150) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    config TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY report_configs_type_index (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Online examinations
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS online_exams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    class_id BIGINT UNSIGNED NULL,
    subject_id BIGINT UNSIGNED NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    total_marks SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status ENUM('draft','active','completed') NOT NULL DEFAULT 'draft',
    instructions TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY online_exams_status_index (status),
    CONSTRAINT online_exams_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE SET NULL,
    CONSTRAINT online_exams_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    online_exam_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    type ENUM('mcq','written') NOT NULL DEFAULT 'mcq',
    correct_answer VARCHAR(255) NULL,
    marks SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY exam_questions_exam_index (online_exam_id),
    CONSTRAINT exam_questions_exam_fk FOREIGN KEY (online_exam_id) REFERENCES online_exams (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    online_exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    answers TEXT NULL,
    score DECIMAL(8,2) NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    submitted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY exam_submissions_unique (online_exam_id, student_id),
    CONSTRAINT exam_submissions_exam_fk FOREIGN KEY (online_exam_id) REFERENCES online_exams (id) ON DELETE CASCADE,
    CONSTRAINT exam_submissions_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Certificates
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS certificates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'Merit',
    certificate_no VARCHAR(50) NOT NULL,
    issue_date DATE NOT NULL,
    content TEXT NULL,
    remarks VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY certificates_number_unique (certificate_no),
    CONSTRAINT certificates_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Integrations
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sms_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    sender_id VARCHAR(50) NULL,
    api_base_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_gateways (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    provider_key VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    merchant_id VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY payment_gateways_provider_unique (provider_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Examination seating
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS exam_seats (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    hall VARCHAR(50) NOT NULL DEFAULT 'Main hall',
    seat_no VARCHAR(20) NOT NULL,
    row_no SMALLINT UNSIGNED NULL,
    column_no SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY exam_seats_student_unique (exam_id, student_id),
    UNIQUE KEY exam_seats_seat_unique (exam_id, hall, seat_no),
    CONSTRAINT exam_seats_exam_fk FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE,
    CONSTRAINT exam_seats_student_fk FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Academic: subjects and class subject marks configuration
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS study_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NULL,
    file_type VARCHAR(100) NULL,
    class_id BIGINT UNSIGNED NULL,
    subject_id BIGINT UNSIGNED NULL,
    teacher_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY study_materials_title_index (title),
    CONSTRAINT study_materials_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE SET NULL,
    CONSTRAINT study_materials_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL,
    CONSTRAINT study_materials_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id BIGINT UNSIGNED NULL,
    subject_id BIGINT UNSIGNED NULL,
    teacher_id BIGINT UNSIGNED NULL,
    topic VARCHAR(255) NOT NULL,
    topic_overview TEXT NULL,
    lesson_breakdown TEXT NULL,
    lesson_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY lesson_plans_topic_index (topic),
    CONSTRAINT lesson_plans_class_fk FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE SET NULL,
    CONSTRAINT lesson_plans_subject_fk FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE SET NULL,
    CONSTRAINT lesson_plans_teacher_fk FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Notice audiences (normalized from the former notices.target_roles CSV)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS notice_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    notice_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY notice_roles_unique (notice_id, role),
    CONSTRAINT notice_roles_notice_fk FOREIGN KEY (notice_id) REFERENCES notices (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Online exam MCQ options (normalized from the former exam_questions.options)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS exam_question_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    exam_question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY exam_question_options_question_index (exam_question_id),
    CONSTRAINT exam_question_options_question_fk FOREIGN KEY (exam_question_id) REFERENCES exam_questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Shared database sessions (for horizontal scaling)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(191) NOT NULL,
    payload MEDIUMTEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
