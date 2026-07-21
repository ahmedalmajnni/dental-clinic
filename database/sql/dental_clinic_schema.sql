-- ============================================================================
--  Dental Clinic Management System — Database Schema (PostgreSQL)
--  Version 2.0  |  13 tables
--
--  Highlights:
--   * One ACCOUNT table for login (admin, employee, patient) with hashed passwords
--   * Multi-branch: every record ties to a BRANCH
--   * Integrated billing: each TREATMENT becomes an INVOICE_LINE
--   * Flexible payments: PAYMENT_ALLOCATION splits payments across invoices
--
--  Run order matters (foreign keys). This file is already in the correct order.
-- ============================================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";   -- provides gen_random_uuid()

-- ----------------------------------------------------------------------------
-- 1. BRANCH  — each clinic location, and optionally the photo studio
-- ----------------------------------------------------------------------------
CREATE TABLE branch (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(120) NOT NULL,
    type        VARCHAR(20)  NOT NULL DEFAULT 'clinic'
                    CHECK (type IN ('clinic', 'studio')),
    phone       VARCHAR(30),
    address     VARCHAR(255),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);
COMMENT ON TABLE branch IS 'Each clinic location (and optional photo studio).';

-- ----------------------------------------------------------------------------
-- 2. EMPLOYEE  — all staff: admin, doctor, reception, lab technician
-- ----------------------------------------------------------------------------
CREATE TABLE employee (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    branch_id   UUID NOT NULL REFERENCES branch(id) ON DELETE RESTRICT,
    name        VARCHAR(120) NOT NULL,
    job_title   VARCHAR(30)  NOT NULL
                    CHECK (job_title IN ('admin', 'doctor', 'reception', 'lab_tech')),
    phone       VARCHAR(30),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);
CREATE INDEX idx_employee_branch ON employee(branch_id);
COMMENT ON TABLE employee IS 'All staff. job_title describes the job; account.role controls access.';

-- ----------------------------------------------------------------------------
-- 3. PATIENT  — patient records
-- ----------------------------------------------------------------------------
CREATE TABLE patient (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(120) NOT NULL,
    dob         DATE,
    phone       VARCHAR(30),
    email       VARCHAR(160),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);
COMMENT ON TABLE patient IS 'Patient records, shared across all branches.';

-- ----------------------------------------------------------------------------
-- 4. ACCOUNT  — login for everyone (admin / employee / patient)
--    An account belongs to EITHER an employee OR a patient (never both).
-- ----------------------------------------------------------------------------
CREATE TABLE account (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email          VARCHAR(160) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,          -- store a bcrypt/argon2 hash, never plain text
    role           VARCHAR(20)  NOT NULL
                       CHECK (role IN ('admin', 'employee', 'patient')),
    employee_id    UUID REFERENCES employee(id) ON DELETE CASCADE,
    patient_id     UUID REFERENCES patient(id)  ON DELETE CASCADE,
    is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login     TIMESTAMPTZ,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    -- exactly one owner: staff account -> employee_id, patient account -> patient_id
    CONSTRAINT account_one_owner CHECK (
        (employee_id IS NOT NULL AND patient_id IS NULL)
        OR (employee_id IS NULL AND patient_id IS NOT NULL)
    )
);
CREATE INDEX idx_account_employee ON account(employee_id);
CREATE INDEX idx_account_patient  ON account(patient_id);
COMMENT ON TABLE account IS 'Single login table for all users. Passwords are stored hashed.';

-- ----------------------------------------------------------------------------
-- 5. APPOINTMENT  — each scheduled visit
-- ----------------------------------------------------------------------------
CREATE TABLE appointment (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id    UUID NOT NULL REFERENCES patient(id)  ON DELETE RESTRICT,
    doctor_id     UUID NOT NULL REFERENCES employee(id) ON DELETE RESTRICT,
    branch_id     UUID NOT NULL REFERENCES branch(id)   ON DELETE RESTRICT,
    scheduled_at  TIMESTAMPTZ NOT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'booked'
                      CHECK (status IN ('booked', 'completed', 'cancelled', 'no_show')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_appt_patient  ON appointment(patient_id);
CREATE INDEX idx_appt_doctor   ON appointment(doctor_id);
CREATE INDEX idx_appt_branch   ON appointment(branch_id);
CREATE INDEX idx_appt_schedule ON appointment(scheduled_at);
COMMENT ON TABLE appointment IS 'One scheduled visit; drives the calendar.';

-- ----------------------------------------------------------------------------
-- 6. REPORT  — the doctor's written notes after a visit
-- ----------------------------------------------------------------------------
CREATE TABLE report (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    appointment_id  UUID NOT NULL REFERENCES appointment(id) ON DELETE CASCADE,
    patient_id      UUID NOT NULL REFERENCES patient(id)     ON DELETE RESTRICT,
    doctor_id       UUID NOT NULL REFERENCES employee(id)    ON DELETE RESTRICT,
    diagnosis       TEXT,
    notes           TEXT,
    next_visit      DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_report_appt    ON report(appointment_id);
CREATE INDEX idx_report_patient ON report(patient_id);
COMMENT ON TABLE report IS 'Clinical notes written after a visit (diagnosis, advice, next visit).';

-- ----------------------------------------------------------------------------
-- 7. TREATMENT  — procedures performed and their cost (feeds billing)
-- ----------------------------------------------------------------------------
CREATE TABLE treatment (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    appointment_id  UUID NOT NULL REFERENCES appointment(id) ON DELETE CASCADE,
    patient_id      UUID NOT NULL REFERENCES patient(id)     ON DELETE RESTRICT,
    procedure       VARCHAR(160) NOT NULL,
    cost            NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (cost >= 0),
    status          VARCHAR(20) NOT NULL DEFAULT 'planned'
                        CHECK (status IN ('planned', 'done', 'cancelled')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_treatment_appt    ON treatment(appointment_id);
CREATE INDEX idx_treatment_patient ON treatment(patient_id);
COMMENT ON TABLE treatment IS 'A procedure done on a tooth; its cost becomes an invoice line.';

-- ----------------------------------------------------------------------------
-- 8. INVOICE  — a patient bill
-- ----------------------------------------------------------------------------
CREATE TABLE invoice (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id  UUID NOT NULL REFERENCES patient(id) ON DELETE RESTRICT,
    total       NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    balance     NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (balance >= 0),
    status      VARCHAR(20) NOT NULL DEFAULT 'open'
                    CHECK (status IN ('open', 'partial', 'paid', 'void')),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_invoice_patient ON invoice(patient_id);
COMMENT ON TABLE invoice IS 'A bill for a patient; total and balance update as treatments and payments post.';

-- ----------------------------------------------------------------------------
-- 9. INVOICE_LINE  — one charge line, created automatically from a treatment
-- ----------------------------------------------------------------------------
CREATE TABLE invoice_line (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id   UUID NOT NULL REFERENCES invoice(id)   ON DELETE CASCADE,
    treatment_id UUID NOT NULL REFERENCES treatment(id) ON DELETE RESTRICT,
    description  VARCHAR(200),
    amount       NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (amount >= 0)
);
CREATE INDEX idx_line_invoice   ON invoice_line(invoice_id);
CREATE INDEX idx_line_treatment ON invoice_line(treatment_id);
COMMENT ON TABLE invoice_line IS 'Links a treatment to a bill; one invoice can hold many lines / visits.';

-- ----------------------------------------------------------------------------
-- 10. PAYMENT  — money received from a patient
-- ----------------------------------------------------------------------------
CREATE TABLE payment (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id  UUID NOT NULL REFERENCES patient(id) ON DELETE RESTRICT,
    amount      NUMERIC(10,2) NOT NULL CHECK (amount > 0),
    method      VARCHAR(20) NOT NULL DEFAULT 'cash'
                    CHECK (method IN ('cash', 'card', 'transfer', 'other')),
    paid_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_payment_patient ON payment(patient_id);
COMMENT ON TABLE payment IS 'A single payment received; not locked to one bill.';

-- ----------------------------------------------------------------------------
-- 11. PAYMENT_ALLOCATION  — splits a payment across one or more invoices
-- ----------------------------------------------------------------------------
CREATE TABLE payment_allocation (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id  UUID NOT NULL REFERENCES payment(id) ON DELETE CASCADE,
    invoice_id  UUID NOT NULL REFERENCES invoice(id) ON DELETE CASCADE,
    amount      NUMERIC(10,2) NOT NULL CHECK (amount > 0),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_alloc_payment ON payment_allocation(payment_id);
CREATE INDEX idx_alloc_invoice ON payment_allocation(invoice_id);
COMMENT ON TABLE payment_allocation IS 'One payment can cover many invoices; one invoice can be paid by many payments.';

-- ----------------------------------------------------------------------------
-- 12. LAB_CASE  — dental-lab work (crown, bridge, denture)
-- ----------------------------------------------------------------------------
CREATE TABLE lab_case (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id  UUID NOT NULL REFERENCES patient(id)  ON DELETE RESTRICT,
    doctor_id   UUID NOT NULL REFERENCES employee(id) ON DELETE RESTRICT,
    type        VARCHAR(60) NOT NULL,
    due_date    DATE,
    status      VARCHAR(20) NOT NULL DEFAULT 'received'
                    CHECK (status IN ('received', 'in_progress', 'ready', 'delivered', 'cancelled')),
    cost        NUMERIC(10,2) DEFAULT 0 CHECK (cost >= 0),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_labcase_patient ON lab_case(patient_id);
CREATE INDEX idx_labcase_doctor  ON lab_case(doctor_id);
COMMENT ON TABLE lab_case IS 'Lab work tracked from order to delivery; updated by the lab technician.';

-- ----------------------------------------------------------------------------
-- 13. MEDIA  — x-rays, scans, and photos linked to a patient
-- ----------------------------------------------------------------------------
CREATE TABLE media (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    patient_id  UUID NOT NULL REFERENCES patient(id) ON DELETE RESTRICT,
    branch_id   UUID REFERENCES branch(id) ON DELETE SET NULL,   -- where it was taken (clinic/studio)
    type        VARCHAR(20) NOT NULL
                    CHECK (type IN ('xray', 'scan', 'photo')),
    category    VARCHAR(40),                                     -- e.g. before / after / intraoral
    file_url    VARCHAR(500) NOT NULL,                           -- link to the stored file
    taken_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_media_patient ON media(patient_id);
CREATE INDEX idx_media_branch  ON media(branch_id);
COMMENT ON TABLE media IS 'X-rays, scans and photos; the file is in storage, the link is here.';

-- ============================================================================
--  End of schema. 13 tables created.
-- ============================================================================
