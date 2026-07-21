--
-- PostgreSQL database dump
--

\restrict FlOsFAl4aRiZv3vkBdShM8zOyYhYO2OoZqnAfThuIU3fJXsWFirmsKMgcHed8lw

-- Dumped from database version 17.10
-- Dumped by pg_dump version 17.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    email character varying(160) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role character varying(20) NOT NULL,
    employee_id uuid,
    patient_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    last_login timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT account_one_owner CHECK ((((employee_id IS NOT NULL) AND (patient_id IS NULL)) OR ((employee_id IS NULL) AND (patient_id IS NOT NULL)))),
    CONSTRAINT account_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'employee'::character varying, 'patient'::character varying])::text[])))
);


--
-- Name: TABLE account; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.account IS 'Single login table for all users. Passwords are stored hashed.';


--
-- Name: appointment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.appointment (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    doctor_id uuid NOT NULL,
    branch_id uuid NOT NULL,
    scheduled_at timestamp with time zone NOT NULL,
    status character varying(20) DEFAULT 'booked'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT appointment_status_check CHECK (((status)::text = ANY ((ARRAY['booked'::character varying, 'completed'::character varying, 'cancelled'::character varying, 'no_show'::character varying])::text[])))
);


--
-- Name: TABLE appointment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.appointment IS 'One scheduled visit; drives the calendar.';


--
-- Name: appointment_request; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.appointment_request (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    doctor_id uuid NOT NULL,
    branch_id uuid NOT NULL,
    preferred_date date,
    note text,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    appointment_id uuid,
    response_note text,
    processed_by uuid,
    processed_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT appointment_request_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'scheduled'::character varying, 'declined'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: branch; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.branch (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name character varying(120) NOT NULL,
    type character varying(20) DEFAULT 'clinic'::character varying NOT NULL,
    phone character varying(30),
    address character varying(255),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT branch_type_check CHECK (((type)::text = ANY ((ARRAY['clinic'::character varying, 'studio'::character varying])::text[])))
);


--
-- Name: TABLE branch; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.branch IS 'Each clinic location (and optional photo studio).';


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: employee; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employee (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    branch_id uuid NOT NULL,
    name character varying(120) NOT NULL,
    job_title character varying(30) NOT NULL,
    phone character varying(30),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT employee_job_title_check CHECK (((job_title)::text = ANY ((ARRAY['admin'::character varying, 'doctor'::character varying, 'reception'::character varying, 'lab_tech'::character varying])::text[])))
);


--
-- Name: TABLE employee; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.employee IS 'All staff. job_title describes the job; account.role controls access.';


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: invoice; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    total numeric(10,2) DEFAULT 0 NOT NULL,
    balance numeric(10,2) DEFAULT 0 NOT NULL,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT invoice_balance_check CHECK ((balance >= (0)::numeric)),
    CONSTRAINT invoice_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'partial'::character varying, 'paid'::character varying, 'void'::character varying])::text[]))),
    CONSTRAINT invoice_total_check CHECK ((total >= (0)::numeric))
);


--
-- Name: TABLE invoice; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.invoice IS 'A bill for a patient; total and balance update as treatments and payments post.';


--
-- Name: invoice_line; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_line (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    invoice_id uuid NOT NULL,
    treatment_id uuid NOT NULL,
    description character varying(200),
    amount numeric(10,2) DEFAULT 0 NOT NULL,
    CONSTRAINT invoice_line_amount_check CHECK ((amount >= (0)::numeric))
);


--
-- Name: TABLE invoice_line; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.invoice_line IS 'Links a treatment to a bill; one invoice can hold many lines / visits.';


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: lab_case; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_case (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    doctor_id uuid NOT NULL,
    type character varying(60) NOT NULL,
    due_date date,
    status character varying(20) DEFAULT 'received'::character varying NOT NULL,
    cost numeric(10,2) DEFAULT 0,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT lab_case_cost_check CHECK ((cost >= (0)::numeric)),
    CONSTRAINT lab_case_status_check CHECK (((status)::text = ANY ((ARRAY['received'::character varying, 'in_progress'::character varying, 'ready'::character varying, 'delivered'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: TABLE lab_case; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.lab_case IS 'Lab work tracked from order to delivery; updated by the lab technician.';


--
-- Name: media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    branch_id uuid,
    type character varying(20) NOT NULL,
    category character varying(40),
    file_url character varying(500) NOT NULL,
    taken_at timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT media_type_check CHECK (((type)::text = ANY ((ARRAY['xray'::character varying, 'scan'::character varying, 'photo'::character varying])::text[])))
);


--
-- Name: TABLE media; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.media IS 'X-rays, scans and photos; the file is in storage, the link is here.';


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: patient; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name character varying(120) NOT NULL,
    dob date,
    phone character varying(30),
    email character varying(160),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp(0) without time zone
);


--
-- Name: TABLE patient; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.patient IS 'Patient records, shared across all branches.';


--
-- Name: payment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    patient_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    method character varying(20) DEFAULT 'cash'::character varying NOT NULL,
    paid_at timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT payment_amount_check CHECK ((amount > (0)::numeric)),
    CONSTRAINT payment_method_check CHECK (((method)::text = ANY ((ARRAY['cash'::character varying, 'card'::character varying, 'transfer'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: TABLE payment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.payment IS 'A single payment received; not locked to one bill.';


--
-- Name: payment_allocation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_allocation (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    payment_id uuid NOT NULL,
    invoice_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT payment_allocation_amount_check CHECK ((amount > (0)::numeric))
);


--
-- Name: TABLE payment_allocation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.payment_allocation IS 'One payment can cover many invoices; one invoice can be paid by many payments.';


--
-- Name: report; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    appointment_id uuid NOT NULL,
    patient_id uuid NOT NULL,
    doctor_id uuid NOT NULL,
    diagnosis text,
    notes text,
    next_visit date,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE report; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.report IS 'Clinical notes written after a visit (diagnosis, advice, next visit).';


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: treatment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.treatment (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    appointment_id uuid NOT NULL,
    patient_id uuid NOT NULL,
    procedure character varying(160) NOT NULL,
    cost numeric(10,2) DEFAULT 0 NOT NULL,
    status character varying(20) DEFAULT 'planned'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT treatment_cost_check CHECK ((cost >= (0)::numeric)),
    CONSTRAINT treatment_status_check CHECK (((status)::text = ANY ((ARRAY['planned'::character varying, 'done'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: TABLE treatment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE public.treatment IS 'A procedure done on a tooth; its cost becomes an invoice line.';


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: account; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.account (id, email, password_hash, role, employee_id, patient_id, is_active, last_login, created_at) FROM stdin;
019f855b-f180-7144-8ed7-1e2a1c04f9ee	dr.lina.fares@clinic.local	$2y$12$aIKmXj7a2WQh0m3nuAgTyu4cWZYvpFS.cZks4YZm/UtbPaB.Sc9xK	employee	019f855b-ef70-7167-94b3-13918ce441e0	\N	t	\N	2026-07-21 18:47:03.169631+03
019f855b-f280-7085-a755-326a5fc0bc6d	rana.reception@clinic.local	$2y$12$u04XXftuGin7DyEMAMGiu.MA.Jylbg/7RiPPpglVaDXX7GFLJuk9m	employee	019f855b-ef71-7374-b7ec-0020ea1df76c	\N	t	\N	2026-07-21 18:47:03.425435+03
019f855b-f380-7267-8125-25181f129225	sami.lab@clinic.local	$2y$12$ylbPTC3QdEpHIXb3ButfW.Vz4GymDKO/UQ25ZLogP19qby92aMGte	employee	019f855b-ef73-73cb-be04-56bfcd69ab21	\N	t	\N	2026-07-21 18:47:03.681339+03
019f855b-f484-72ab-a613-b87da5487ca9	patient1@example.com	$2y$12$aAa0a9GedCPel1H1JAev8Oz.4mKkWoMlZWWa06c9XBa02lboOV4ra	patient	\N	019f855b-ef7b-7145-b114-bb7e4db8bb41	t	\N	2026-07-21 18:47:03.941139+03
019f855b-f585-71e1-85b0-aaadb6f676ee	patient2@example.com	$2y$12$yRKtCIXp8dJ15CUjbwstye3fv9EopszoUweETR4R2yeBjNjrz0Qx2	patient	\N	019f855b-ef7d-719e-b68d-6a16c9f56427	t	\N	2026-07-21 18:47:04.197503+03
019f855b-f684-7061-a126-4fd1e04ecd77	patient3@example.com	$2y$12$2ukyh9266YmFraC3sm3T6Oo3CLV7pV9/bTDf1G3ZFB6x/CXgEWvJK	patient	\N	019f855b-ef7e-734c-b8cb-bbbf81a755fb	t	\N	2026-07-21 18:47:04.453437+03
019f855b-f785-734d-b41c-f0c065ae78cb	patient4@example.com	$2y$12$Sgs9dlc1Jl8ScwnFPiUbC.v0QWwevmYcMjVAE5rrUarqy.Hyl8J1C	patient	\N	019f855b-ef80-722d-9744-ccf0445357c7	t	\N	2026-07-21 18:47:04.71003+03
019f855b-f884-72be-a157-954c88b49b6f	patient5@example.com	$2y$12$edXTI2nkj1ARksyALU94HuOyV9l0ZC8Iazi0p..uvKginMo3a1qhy	patient	\N	019f855b-ef81-7206-a3a4-e1799aef242b	t	\N	2026-07-21 18:47:04.965355+03
019f855f-f378-7206-bc21-3acff4f94498	dr.adam.hart@clinic.local	$2y$12$thSdibxTEzcLyMIjDMcL4e2aT1bAvq7pVELS8flk99Wn1p76NJmGa	employee	019f855f-f23f-7240-a624-ae618e627e9b	\N	t	\N	2026-07-21 18:51:25.817162+03
019f855b-ecdc-715b-aaa4-1e78ebc8675a	admin@clinic.local	$2y$12$krhkibZZkteps8x1jSxHRu9X8m6ShiDAuoMNx6Oyc1JtNN6eKb5AW	admin	019f855b-ebd9-726b-a24f-794e7f8c8690	\N	t	2026-07-21 15:53:54+03	2026-07-21 18:47:01.69696+03
\.


--
-- Data for Name: appointment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.appointment (id, patient_id, doctor_id, branch_id, scheduled_at, status, created_at) FROM stdin;
019f855b-ef89-73fe-87e6-0bf233dfa4f1	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-22 09:00:00+03	booked	2026-07-21 18:47:02.666164+03
019f855b-ef8c-7301-b228-0ec394443fc2	019f855b-ef7d-719e-b68d-6a16c9f56427	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-23 10:00:00+03	completed	2026-07-21 18:47:02.668612+03
019f855b-ef8e-729a-8226-f1c69a78e7b5	019f855b-ef7e-734c-b8cb-bbbf81a755fb	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-24 11:00:00+03	booked	2026-07-21 18:47:02.670635+03
019f855b-ef90-734b-8147-e35b43033d14	019f855b-ef80-722d-9744-ccf0445357c7	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-25 12:00:00+03	booked	2026-07-21 18:47:02.672757+03
019f855b-ef92-708b-9aae-9d6264a1a58a	019f855b-ef81-7206-a3a4-e1799aef242b	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-26 13:00:00+03	completed	2026-07-21 18:47:02.674676+03
019f855b-ef94-7348-a424-1e0b3d8749d8	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-27 14:00:00+03	booked	2026-07-21 18:47:02.676753+03
\.


--
-- Data for Name: appointment_request; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.appointment_request (id, patient_id, doctor_id, branch_id, preferred_date, note, status, appointment_id, response_note, processed_by, processed_at, created_at) FROM stdin;
019f855b-f02c-72be-9b02-3c23580fd80a	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-28	Mornings suit me best.	pending	\N	\N	\N	\N	2026-07-21 18:47:02.828717+03
019f855b-f02f-7279-af9c-3becba8a80c7	019f855b-ef7d-719e-b68d-6a16c9f56427	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-29	Any afternoon is fine.	pending	\N	\N	\N	\N	2026-07-21 18:47:02.831753+03
019f855b-f030-7312-a3a8-f6014ea3ff55	019f855b-ef7e-734c-b8cb-bbbf81a755fb	019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	2026-07-30	As soon as possible please.	pending	\N	\N	\N	\N	2026-07-21 18:47:02.833156+03
\.


--
-- Data for Name: branch; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.branch (id, name, type, phone, address, created_at) FROM stdin;
019f855b-ebd3-7096-85ff-d1a505bc8553	Main Clinic	clinic	\N	\N	2026-07-21 18:47:01.69696+03
019f855b-ef62-7036-8b1e-39c15de922bf	North Branch	clinic	011-000-0002	North district	2026-07-21 18:47:02.627431+03
019f855b-ef68-7229-8a61-4c5b6c740526	Photo Studio	studio	011-000-0003	Next to Main Clinic	2026-07-21 18:47:02.633047+03
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: employee; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.employee (id, branch_id, name, job_title, phone, created_at) FROM stdin;
019f855b-ebd9-726b-a24f-794e7f8c8690	019f855b-ebd3-7096-85ff-d1a505bc8553	Clinic Admin	admin	\N	2026-07-21 18:47:01.69696+03
019f855b-ef70-7167-94b3-13918ce441e0	019f855b-ef62-7036-8b1e-39c15de922bf	Dr Lina Fares	doctor	011-111-0002	2026-07-21 18:47:02.640576+03
019f855b-ef71-7374-b7ec-0020ea1df76c	019f855b-ef68-7229-8a61-4c5b6c740526	Rana Reception	reception	011-111-0003	2026-07-21 18:47:02.642125+03
019f855b-ef73-73cb-be04-56bfcd69ab21	019f855b-ebd3-7096-85ff-d1a505bc8553	Sami Lab	lab_tech	011-111-0004	2026-07-21 18:47:02.643999+03
019f855f-f23f-7240-a624-ae618e627e9b	019f855b-ebd3-7096-85ff-d1a505bc8553	Dr Adam Hart	doctor	011-111-0001	2026-07-21 18:51:25.505775+03
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: invoice; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.invoice (id, patient_id, total, balance, status, created_at) FROM stdin;
019f855b-efb3-715f-bc64-c90721074ef2	019f855b-ef7d-719e-b68d-6a16c9f56427	270.00	0.00	paid	2026-07-21 18:47:02.700877+03
019f855b-efc3-7173-a015-520aa5945b7d	019f855b-ef7b-7145-b114-bb7e4db8bb41	1080.00	0.00	paid	2026-07-21 18:47:02.721066+03
019f855e-1cee-7375-89f4-17ec12ca11f6	019f855b-ef7b-7145-b114-bb7e4db8bb41	220.00	220.00	open	2026-07-21 18:49:25.335356+03
\.


--
-- Data for Name: invoice_line; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.invoice_line (id, invoice_id, treatment_id, description, amount) FROM stdin;
019f855b-efb5-71d2-a01c-33077012bd48	019f855b-efb3-715f-bc64-c90721074ef2	019f855b-efad-7115-8f5e-2f2a4a094261	Composite filling	150.00
019f855b-efc4-71ff-9677-e4fa42f80f76	019f855b-efc3-7173-a015-520aa5945b7d	019f855b-efc1-7155-a820-2c0fb4753b1a	Scaling and polishing	80.00
019f855b-efcb-7044-9f47-9a092895d20f	019f855b-efc3-7173-a015-520aa5945b7d	019f855b-efc8-70d4-8178-e4554e69905c	Root canal treatment	400.00
019f855b-efd2-702a-9e9c-2925a1ad3b03	019f855b-efb3-715f-bc64-c90721074ef2	019f855b-efd0-7349-9ce0-d886f3663a68	Tooth extraction	120.00
019f855b-efd9-7376-83e6-2e8216c0663f	019f855b-efc3-7173-a015-520aa5945b7d	019f855b-efd7-7157-bd49-a9ba11a3cc83	Crown fitting	600.00
019f855e-1cef-7352-90a8-8e1dc857c9cf	019f855e-1cee-7375-89f4-17ec12ca11f6	019f855e-1ce6-73b5-af40-1be98424d382	Teeth whitening	220.00
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: lab_case; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.lab_case (id, patient_id, doctor_id, type, due_date, status, cost, created_at) FROM stdin;
019f855b-f015-71af-9f3f-afde85484ccc	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ef70-7167-94b3-13918ce441e0	Crown	2026-08-04	in_progress	250.00	2026-07-21 18:47:02.805462+03
019f855b-f017-711e-b782-2f34e50f014e	019f855b-ef7d-719e-b68d-6a16c9f56427	019f855b-ef70-7167-94b3-13918ce441e0	Bridge	2026-07-28	ready	500.00	2026-07-21 18:47:02.807437+03
019f855b-f018-71d2-a90d-6b0d3b721568	019f855b-ef7e-734c-b8cb-bbbf81a755fb	019f855b-ef70-7167-94b3-13918ce441e0	Denture	2026-08-20	received	350.00	2026-07-21 18:47:02.808905+03
019f855b-f01a-702f-8363-6c547bc10896	019f855b-ef80-722d-9744-ccf0445357c7	019f855b-ef70-7167-94b3-13918ce441e0	Veneer	2026-07-24	delivered	300.00	2026-07-21 18:47:02.811009+03
\.


--
-- Data for Name: media; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.media (id, patient_id, branch_id, type, category, file_url, taken_at, created_at) FROM stdin;
019f855b-f021-71b2-b64e-1a7a39ea804e	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ebd3-7096-85ff-d1a505bc8553	xray	diagnostic	https://example.com/media/xray-001.jpg	2026-07-20 15:47:02+03	2026-07-21 18:47:02.817432+03
019f855b-f023-7171-91a5-fd91a92e60b0	019f855b-ef7d-719e-b68d-6a16c9f56427	019f855b-ef62-7036-8b1e-39c15de922bf	photo	before	https://example.com/media/before-002.jpg	2026-07-19 15:47:02+03	2026-07-21 18:47:02.819696+03
019f855b-f024-705c-90a8-48be182016ec	019f855b-ef7e-734c-b8cb-bbbf81a755fb	019f855b-ef68-7229-8a61-4c5b6c740526	scan	intraoral	https://example.com/media/scan-003.jpg	2026-07-18 15:47:02+03	2026-07-21 18:47:02.821299+03
019f855b-f027-7350-ac14-8b54cd55a67a	019f855b-ef80-722d-9744-ccf0445357c7	019f855b-ebd3-7096-85ff-d1a505bc8553	photo	after	https://example.com/media/after-004.jpg	2026-07-17 15:47:02+03	2026-07-21 18:47:02.823424+03
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_07_17_000001_create_dental_clinic_schema	1
5	2026_07_18_000001_add_soft_deletes_to_patient	1
6	2026_07_18_000002_create_appointment_request_table	1
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: patient; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.patient (id, name, dob, phone, email, created_at, deleted_at) FROM stdin;
019f855b-ef7b-7145-b114-bb7e4db8bb41	Sample Patient One	1990-04-12	0900-000-001	patient1@example.com	2026-07-21 18:47:02.652166+03	\N
019f855b-ef7d-719e-b68d-6a16c9f56427	Sample Patient Two	1985-11-03	0900-000-002	patient2@example.com	2026-07-21 18:47:02.653887+03	\N
019f855b-ef7e-734c-b8cb-bbbf81a755fb	Sample Patient Three	2000-07-21	0900-000-003	patient3@example.com	2026-07-21 18:47:02.654966+03	\N
019f855b-ef80-722d-9744-ccf0445357c7	Sample Patient Four	1978-01-30	0900-000-004	patient4@example.com	2026-07-21 18:47:02.656334+03	\N
019f855b-ef81-7206-a3a4-e1799aef242b	Sample Patient Five	1995-09-09	0900-000-005	patient5@example.com	2026-07-21 18:47:02.657754+03	\N
\.


--
-- Data for Name: payment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.payment (id, patient_id, amount, method, paid_at, created_at) FROM stdin;
019f855b-efe8-7217-a880-be1568961d0d	019f855b-ef7d-719e-b68d-6a16c9f56427	135.00	cash	2026-07-21 15:47:02+03	2026-07-21 18:47:02.760272+03
019f855b-eff7-70b3-ba23-ed381163f831	019f855b-ef7d-719e-b68d-6a16c9f56427	135.00	card	2026-07-20 15:47:02+03	2026-07-21 18:47:02.775641+03
019f855b-f006-7081-94d1-d79059e7de78	019f855b-ef7b-7145-b114-bb7e4db8bb41	1080.00	transfer	2026-07-19 15:47:02+03	2026-07-21 18:47:02.790309+03
\.


--
-- Data for Name: payment_allocation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.payment_allocation (id, payment_id, invoice_id, amount, created_at) FROM stdin;
019f855b-eff0-71ce-817b-80f749ea2156	019f855b-efe8-7217-a880-be1568961d0d	019f855b-efb3-715f-bc64-c90721074ef2	135.00	2026-07-21 18:47:02.760272+03
019f855b-efff-70be-a0e1-ce6798c415ce	019f855b-eff7-70b3-ba23-ed381163f831	019f855b-efb3-715f-bc64-c90721074ef2	135.00	2026-07-21 18:47:02.775641+03
019f855b-f00b-7005-a47c-5a9c2cfd8824	019f855b-f006-7081-94d1-d79059e7de78	019f855b-efc3-7173-a015-520aa5945b7d	1080.00	2026-07-21 18:47:02.790309+03
\.


--
-- Data for Name: report; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.report (id, appointment_id, patient_id, doctor_id, diagnosis, notes, next_visit, created_at) FROM stdin;
019f855b-ef9c-7230-9da3-61dd7eb4ebbd	019f855b-ef92-708b-9aae-9d6264a1a58a	019f855b-ef81-7206-a3a4-e1799aef242b	019f855b-ef70-7167-94b3-13918ce441e0	Caries in lower left first molar	Cavity cleaned and filled. Advised fluoride toothpaste.	2027-01-21	2026-07-21 18:47:02.684884+03
019f855b-efa0-71c1-a21f-01a19c811121	019f855b-ef8e-729a-8226-f1c69a78e7b5	019f855b-ef7e-734c-b8cb-bbbf81a755fb	019f855b-ef70-7167-94b3-13918ce441e0	Generalised plaque and mild gingivitis	Full scaling done. Recommended daily flossing.	2027-01-21	2026-07-21 18:47:02.688939+03
019f855b-efa3-70df-ac23-674d3c90d5ad	019f855b-ef90-734b-8147-e35b43033d14	019f855b-ef80-722d-9744-ccf0445357c7	019f855b-ef70-7167-94b3-13918ce441e0	Irreversible pulpitis, upper right premolar	Root canal planned over two visits.	2027-01-21	2026-07-21 18:47:02.691858+03
019f855b-efa6-7395-8b05-22f4ee94e0a4	019f855b-ef89-73fe-87e6-0bf233dfa4f1	019f855b-ef7b-7145-b114-bb7e4db8bb41	019f855b-ef70-7167-94b3-13918ce441e0	Impacted lower third molar	Referred for surgical extraction.	2027-01-21	2026-07-21 18:47:02.694458+03
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: treatment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.treatment (id, appointment_id, patient_id, procedure, cost, status, created_at) FROM stdin;
019f855b-efad-7115-8f5e-2f2a4a094261	019f855b-ef8c-7301-b228-0ec394443fc2	019f855b-ef7d-719e-b68d-6a16c9f56427	Composite filling	150.00	done	2026-07-21 18:47:02.700877+03
019f855b-efc1-7155-a820-2c0fb4753b1a	019f855b-ef94-7348-a424-1e0b3d8749d8	019f855b-ef7b-7145-b114-bb7e4db8bb41	Scaling and polishing	80.00	done	2026-07-21 18:47:02.721066+03
019f855b-efc8-70d4-8178-e4554e69905c	019f855b-ef89-73fe-87e6-0bf233dfa4f1	019f855b-ef7b-7145-b114-bb7e4db8bb41	Root canal treatment	400.00	planned	2026-07-21 18:47:02.728226+03
019f855b-efd0-7349-9ce0-d886f3663a68	019f855b-ef8c-7301-b228-0ec394443fc2	019f855b-ef7d-719e-b68d-6a16c9f56427	Tooth extraction	120.00	done	2026-07-21 18:47:02.736138+03
019f855b-efd7-7157-bd49-a9ba11a3cc83	019f855b-ef89-73fe-87e6-0bf233dfa4f1	019f855b-ef7b-7145-b114-bb7e4db8bb41	Crown fitting	600.00	planned	2026-07-21 18:47:02.743409+03
019f855e-1ce6-73b5-af40-1be98424d382	019f855b-ef89-73fe-87e6-0bf233dfa4f1	019f855b-ef7b-7145-b114-bb7e4db8bb41	Teeth whitening	220.00	planned	2026-07-21 18:49:25.335356+03
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at) FROM stdin;
\.


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 6, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: account account_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_email_key UNIQUE (email);


--
-- Name: account account_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);


--
-- Name: appointment appointment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_pkey PRIMARY KEY (id);


--
-- Name: appointment_request appointment_request_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_pkey PRIMARY KEY (id);


--
-- Name: branch branch_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branch
    ADD CONSTRAINT branch_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: employee employee_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee
    ADD CONSTRAINT employee_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: invoice_line invoice_line_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line
    ADD CONSTRAINT invoice_line_pkey PRIMARY KEY (id);


--
-- Name: invoice invoice_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice
    ADD CONSTRAINT invoice_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: lab_case lab_case_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_case
    ADD CONSTRAINT lab_case_pkey PRIMARY KEY (id);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: patient patient_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient
    ADD CONSTRAINT patient_pkey PRIMARY KEY (id);


--
-- Name: payment_allocation payment_allocation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_allocation
    ADD CONSTRAINT payment_allocation_pkey PRIMARY KEY (id);


--
-- Name: payment payment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment
    ADD CONSTRAINT payment_pkey PRIMARY KEY (id);


--
-- Name: report report_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report
    ADD CONSTRAINT report_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: treatment treatment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment
    ADD CONSTRAINT treatment_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: idx_account_employee; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_employee ON public.account USING btree (employee_id);


--
-- Name: idx_account_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_account_patient ON public.account USING btree (patient_id);


--
-- Name: idx_alloc_invoice; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_alloc_invoice ON public.payment_allocation USING btree (invoice_id);


--
-- Name: idx_alloc_payment; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_alloc_payment ON public.payment_allocation USING btree (payment_id);


--
-- Name: idx_appt_branch; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_appt_branch ON public.appointment USING btree (branch_id);


--
-- Name: idx_appt_doctor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_appt_doctor ON public.appointment USING btree (doctor_id);


--
-- Name: idx_appt_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_appt_patient ON public.appointment USING btree (patient_id);


--
-- Name: idx_appt_schedule; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_appt_schedule ON public.appointment USING btree (scheduled_at);


--
-- Name: idx_apptreq_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_apptreq_patient ON public.appointment_request USING btree (patient_id);


--
-- Name: idx_apptreq_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_apptreq_status ON public.appointment_request USING btree (status);


--
-- Name: idx_employee_branch; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_employee_branch ON public.employee USING btree (branch_id);


--
-- Name: idx_invoice_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_invoice_patient ON public.invoice USING btree (patient_id);


--
-- Name: idx_labcase_doctor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_labcase_doctor ON public.lab_case USING btree (doctor_id);


--
-- Name: idx_labcase_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_labcase_patient ON public.lab_case USING btree (patient_id);


--
-- Name: idx_line_invoice; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_line_invoice ON public.invoice_line USING btree (invoice_id);


--
-- Name: idx_line_treatment; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_line_treatment ON public.invoice_line USING btree (treatment_id);


--
-- Name: idx_media_branch; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_media_branch ON public.media USING btree (branch_id);


--
-- Name: idx_media_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_media_patient ON public.media USING btree (patient_id);


--
-- Name: idx_payment_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_payment_patient ON public.payment USING btree (patient_id);


--
-- Name: idx_report_appt; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_report_appt ON public.report USING btree (appointment_id);


--
-- Name: idx_report_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_report_patient ON public.report USING btree (patient_id);


--
-- Name: idx_treatment_appt; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_treatment_appt ON public.treatment USING btree (appointment_id);


--
-- Name: idx_treatment_patient; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_treatment_patient ON public.treatment USING btree (patient_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: account account_employee_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employee(id) ON DELETE CASCADE;


--
-- Name: account account_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE CASCADE;


--
-- Name: appointment appointment_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(id) ON DELETE RESTRICT;


--
-- Name: appointment appointment_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.employee(id) ON DELETE RESTRICT;


--
-- Name: appointment appointment_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: appointment_request appointment_request_appointment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointment(id) ON DELETE SET NULL;


--
-- Name: appointment_request appointment_request_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(id) ON DELETE RESTRICT;


--
-- Name: appointment_request appointment_request_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.employee(id) ON DELETE RESTRICT;


--
-- Name: appointment_request appointment_request_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE CASCADE;


--
-- Name: appointment_request appointment_request_processed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment_request
    ADD CONSTRAINT appointment_request_processed_by_fkey FOREIGN KEY (processed_by) REFERENCES public.employee(id) ON DELETE SET NULL;


--
-- Name: employee employee_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee
    ADD CONSTRAINT employee_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(id) ON DELETE RESTRICT;


--
-- Name: invoice_line invoice_line_invoice_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line
    ADD CONSTRAINT invoice_line_invoice_id_fkey FOREIGN KEY (invoice_id) REFERENCES public.invoice(id) ON DELETE CASCADE;


--
-- Name: invoice_line invoice_line_treatment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line
    ADD CONSTRAINT invoice_line_treatment_id_fkey FOREIGN KEY (treatment_id) REFERENCES public.treatment(id) ON DELETE RESTRICT;


--
-- Name: invoice invoice_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice
    ADD CONSTRAINT invoice_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: lab_case lab_case_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_case
    ADD CONSTRAINT lab_case_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.employee(id) ON DELETE RESTRICT;


--
-- Name: lab_case lab_case_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_case
    ADD CONSTRAINT lab_case_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: media media_branch_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_branch_id_fkey FOREIGN KEY (branch_id) REFERENCES public.branch(id) ON DELETE SET NULL;


--
-- Name: media media_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: payment_allocation payment_allocation_invoice_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_allocation
    ADD CONSTRAINT payment_allocation_invoice_id_fkey FOREIGN KEY (invoice_id) REFERENCES public.invoice(id) ON DELETE CASCADE;


--
-- Name: payment_allocation payment_allocation_payment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_allocation
    ADD CONSTRAINT payment_allocation_payment_id_fkey FOREIGN KEY (payment_id) REFERENCES public.payment(id) ON DELETE CASCADE;


--
-- Name: payment payment_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment
    ADD CONSTRAINT payment_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: report report_appointment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report
    ADD CONSTRAINT report_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointment(id) ON DELETE CASCADE;


--
-- Name: report report_doctor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report
    ADD CONSTRAINT report_doctor_id_fkey FOREIGN KEY (doctor_id) REFERENCES public.employee(id) ON DELETE RESTRICT;


--
-- Name: report report_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report
    ADD CONSTRAINT report_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- Name: treatment treatment_appointment_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment
    ADD CONSTRAINT treatment_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointment(id) ON DELETE CASCADE;


--
-- Name: treatment treatment_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment
    ADD CONSTRAINT treatment_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict FlOsFAl4aRiZv3vkBdShM8zOyYhYO2OoZqnAfThuIU3fJXsWFirmsKMgcHed8lw

