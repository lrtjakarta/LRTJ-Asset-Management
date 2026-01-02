--
-- PostgreSQL database dump
--

\restrict yJb39FbWM7VbGZVSC0Tcpr6pYLldBWsTqijF77O5fo6b9voJha4sFHmmBzp9BAb

-- Dumped from database version 13.22
-- Dumped by pg_dump version 13.22

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: asset_group_counters; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.asset_group_counters (
    group_code character varying(50) NOT NULL,
    last_parent_seq bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);



--
-- Name: asset_parent_counters; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.asset_parent_counters (
    parent_code character varying(100) NOT NULL,
    last_child_seq bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);



--
-- Name: assets; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode_group_category character varying(50) NOT NULL,
    asset_code character varying(120) NOT NULL,
    asset_number_parent character varying(50) NOT NULL,
    asset_number_child character varying(2) NOT NULL,
    description text NOT NULL,
    kode_asset_class character varying(50),
    kode_status character varying(50),
    kode_location character varying(50),
    kode_sumber character varying(50),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone,
    upload_code character varying,
    notes text
);



--
-- Name: assets_assignment; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_assignment (
    asset_uuid uuid NOT NULL,
    asset_owner character varying(50),
    asset_user character varying(50),
    asset_maintenance character varying(50),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone
);



--
-- Name: assets_classification; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_classification (
    asset_uuid uuid NOT NULL,
    kode_asset_transaction character varying(50) NOT NULL,
    kode_asset_type character varying(50),
    kode_category character varying(50),
    kode_category_2 character varying(50),
    kode_sub_category character varying(50),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone
);



--
-- Name: assets_depr_ledger_monthly; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_depr_ledger_monthly (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    period date NOT NULL,
    opening_balance numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    additions numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    transfers_in numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    transfers_out numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    disposals numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    adjustment_value numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    adjustment_depreciation numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    depr_expense numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    accumulated_depr_end numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    ending_balance numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    depr_code character varying
);



--
-- Name: assets_depr_movements; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_depr_movements (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    period date NOT NULL,
    category character varying(255) NOT NULL,
    amount numeric(18,2) NOT NULL,
    depr_start_period date,
    group_uuid uuid,
    source_type character varying(64),
    source_uuid uuid,
    note character varying(300),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT assets_depr_movements_category_check CHECK (((category)::text = ANY (ARRAY[('ADDITION'::character varying)::text, ('TRANSFER_IN'::character varying)::text, ('TRANSFER_OUT'::character varying)::text, ('DISPOSAL'::character varying)::text, ('ADJUSTMENT_VALUE'::character varying)::text, ('ADJUSTMENT_DEPRECIATION'::character varying)::text])))
);



--
-- Name: assets_depr_policy; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_depr_policy (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    method character varying(255) NOT NULL,
    useful_life_months integer NOT NULL,
    salvage_value numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    depr_start_date date NOT NULL,
    convention character varying(255) DEFAULT 'PRORATA_MONTH'::character varying NOT NULL,
    cutoff_day smallint DEFAULT '15'::smallint NOT NULL,
    start_rule character varying(255) DEFAULT 'CUT_OFF_NEXT_OR_NEXT2'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT assets_depr_policy_convention_check CHECK (((convention)::text = ANY (ARRAY[('PRORATA_MONTH'::character varying)::text, ('FULL_MONTH'::character varying)::text, ('HALF_MONTH'::character varying)::text, ('PRORATA_DAILY'::character varying)::text]))),
    CONSTRAINT assets_depr_policy_method_check CHECK (((method)::text = 'SL'::text)),
    CONSTRAINT assets_depr_policy_start_rule_check CHECK (((start_rule)::text = 'CUT_OFF_NEXT_OR_NEXT2'::text))
);



--
-- Name: assets_depr_transfer_requests; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_depr_transfer_requests (
    uuid uuid NOT NULL,
    from_asset_uuid uuid NOT NULL,
    to_asset_uuid uuid NOT NULL,
    transfer_type character varying(64) DEFAULT 'tf-val'::character varying NOT NULL,
    amount numeric(18,2) NOT NULL,
    actual_date date NOT NULL,
    note text,
    attachment_path character varying(255),
    kode_status character varying(10) NOT NULL,
    requested_by character varying(100),
    approved_by character varying(100),
    approved_at timestamp(0) without time zone,
    group_uuid uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    transfer_code character varying
);



--
-- Name: assets_depr_yearly; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_depr_yearly (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    fiscal_year integer NOT NULL,
    opening_balance numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    total_additions numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    depr_expense_year numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    adjustment_depreciation_year numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    accumulated_depr_end numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    ending_balance_year numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);



--
-- Name: assets_disposals; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_disposals (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    disposal_code character varying(64) NOT NULL,
    target_status character varying(32) NOT NULL,
    kode_status character varying(32) NOT NULL,
    note text,
    file_path character varying(255),
    pic_request_uid character varying(64) NOT NULL,
    pic_approve_uid character varying(64),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    file_name character varying(255),
    file_mime character varying(127),
    file_size bigint,
    before_status character varying(10),
    flow jsonb,
    flow_file_path character varying(255),
    flow_file_name character varying(255),
    flow_file_mime character varying(100),
    flow_file_size bigint,
    ba_file_path character varying(255),
    ba_file_name character varying(255),
    ba_file_mime character varying(255),
    ba_file_size bigint,
    reason character varying(20)
);



--
-- Name: assets_document; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_document (
    asset_uuid uuid NOT NULL,
    no_po_perjanjian_spk character varying(120),
    nota_referensi character varying(120),
    no_document character varying(120),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone
);



--
-- Name: assets_identifiers; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_identifiers (
    asset_uuid uuid NOT NULL,
    asset_number_maximo character varying(120),
    asset_number_dynamic_365 character varying(120),
    asset_number_internal character varying(120),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone,
    alias text
);



--
-- Name: assets_qr; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_qr (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    asset_uuid uuid NOT NULL,
    qr_data text NOT NULL,
    image_path text,
    is_active boolean DEFAULT true NOT NULL,
    generated_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone
);



--
-- Name: assets_rfid; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_rfid (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    asset_uuid uuid NOT NULL,
    epc character varying(128) NOT NULL,
    tag_type character varying(16) DEFAULT 'NFC'::character varying NOT NULL,
    encoded_at timestamp(0) with time zone,
    is_active boolean DEFAULT true NOT NULL,
    deactivated_at timestamp(0) with time zone,
    note text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone
);



--
-- Name: assets_transfers; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_transfers (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    transfer_code character varying(32) NOT NULL,
    type character varying(24) NOT NULL,
    before jsonb NOT NULL,
    after jsonb NOT NULL,
    kode_status character varying(64),
    note text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone,
    pic_request_uid character varying(190) NOT NULL,
    pic_approve_uid character varying(190),
    file_path character varying(255),
    file_name character varying(255),
    file_mime character varying(127),
    file_size bigint,
    flow json,
    flow_file_path character varying(255),
    flow_file_name character varying(255),
    flow_file_mime character varying(100),
    flow_file_size bigint
);



--
-- Name: assets_value; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_value (
    asset_uuid uuid NOT NULL,
    price numeric(18,2),
    quantity numeric(18,3),
    is_pajak boolean DEFAULT true NOT NULL,
    vat_in numeric(18,2),
    kode_uom character varying(50),
    total numeric(18,2),
    useful_life_month integer,
    useful_life_year numeric(6,2),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    deleted_at timestamp(0) with time zone,
    actual_date date,
    capitalization_date date
);



--
-- Name: assets_value_history; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.assets_value_history (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    before_payload json,
    after_payload json NOT NULL,
    pic_request_uid character varying(100),
    note character varying(1000),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    acq_code character varying(64)
);



--
-- Name: cache; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);



--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);



--
-- Name: master_action; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_action (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_asset_class; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_asset_class (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    kode_transaction character varying(32)
);



--
-- Name: master_asset_type; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_asset_type (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_category; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_category (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    kode_asset_type character varying(50) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_category_2; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_category_2 (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    kode_category character varying(50) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_division; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_division (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_group_category; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_group_category (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_location; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_location (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_menu; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_menu (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    actions json
);



--
-- Name: master_role; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_role (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_role_menu; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_role_menu (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    role_kode character varying(50) NOT NULL,
    menu_kode character varying(50) NOT NULL,
    actions jsonb DEFAULT '[]'::jsonb NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_status; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_status (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    type character varying(50) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_sub_category; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_sub_category (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_sumber; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_sumber (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    deleted_at timestamp with time zone,
    kode character varying(50) NOT NULL
);



--
-- Name: master_transaction; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_transaction (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_uom; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_uom (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    name character varying(191) NOT NULL,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);



--
-- Name: master_user_code; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.master_user_code (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    kode character varying(50) NOT NULL,
    department character varying(191) NOT NULL,
    description text,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    kode_division character varying(50)
);



--
-- Name: migrations; Type: TABLE; Schema: public; Owner: easymain_u_lrtj
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO easymain_u_lrtj;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: easymain_u_lrtj
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO easymain_u_lrtj;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: easymain_u_lrtj
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);



--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: easymain
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: easymain
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: return_history; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.return_history (
    uuid uuid NOT NULL,
    asset_uuid uuid NOT NULL,
    source_type character varying(16) NOT NULL,
    source_id uuid NOT NULL,
    source_code character varying(64),
    note text,
    pic_request_uid character varying(64),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    return_code character varying(64),
    CONSTRAINT return_history_source_type_chk CHECK (((source_type)::text = ANY (ARRAY[('transfer'::character varying)::text, ('disposal'::character varying)::text])))
);



--
-- Name: sessions; Type: TABLE; Schema: public; Owner: easymain
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
-- Name: user_role; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.user_role (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id bigint NOT NULL,
    role_kode character varying(50) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);



--
-- Name: users; Type: TABLE; Schema: public; Owner: easymain
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    username character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    ou character varying(100),
    role_kode character varying(50),
    kode_department character varying(50)
);



--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: easymain
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: easymain
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: easymain_u_lrtj
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: asset_group_counters; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.asset_group_counters (group_code, last_parent_seq, created_at, updated_at) FROM stdin;
A1102	1	2025-10-27 12:36:32+08	2025-10-27 12:36:32+08
A1210	1	2025-11-14 10:18:46+08	2025-11-14 10:18:45+08
J1203	1	2025-11-17 15:30:36+08	2025-11-17 15:30:36+08
J3100	1	2025-11-18 12:37:41+08	2025-11-18 12:37:41+08
J3101	2	2025-11-17 15:34:59+08	2025-11-18 12:38:33+08
A1221	1	2025-11-18 14:56:13+08	2025-11-18 14:56:12+08
A1501	1	2025-11-20 13:26:15+08	2025-11-20 13:26:15+08
J1323	1	2025-12-09 14:51:15+08	2025-12-09 14:51:15+08
J1300	1	2025-12-11 14:54:31+08	2025-12-11 14:54:31+08
A1500	1	2025-12-20 17:39:24+08	2025-12-20 17:39:23+08
\.


--
-- Data for Name: asset_parent_counters; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.asset_parent_counters (parent_code, last_child_seq, created_at, updated_at) FROM stdin;
A1102000001	1	2025-10-27 12:36:32+08	2025-10-27 12:36:32+08
A1210000001	1	2025-11-14 10:18:46+08	2025-11-14 10:18:45+08
J1203000001	1	2025-11-17 15:30:36+08	2025-11-17 15:30:36+08
J3101000001	1	2025-11-17 15:34:59+08	2025-11-17 15:34:58+08
J3100000001	1	2025-11-18 12:37:41+08	2025-11-18 12:37:41+08
J3101000002	1	2025-11-18 12:38:34+08	2025-11-18 12:38:33+08
A1221000001	0	2025-11-18 14:56:13+08	2025-11-18 14:56:13+08
A1501000001	0	2025-11-20 13:26:15+08	2025-11-20 13:26:15+08
J1323000001	0	2025-12-09 14:51:15+08	2025-12-09 14:51:15+08
J1300000001	0	2025-12-11 14:54:31+08	2025-12-11 14:54:31+08
A1300000001	1	2025-12-11 16:06:58+08	2025-12-11 16:06:57+08
A1500000001	0	2025-12-20 17:39:24+08	2025-12-20 17:39:24+08
\.


--
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets (uuid, kode_group_category, asset_code, asset_number_parent, asset_number_child, description, kode_asset_class, kode_status, kode_location, kode_sumber, created_at, updated_at, deleted_at, upload_code, notes) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	A1500	A1500000001-00	A1500000001	00	Cascade 2 SA 27,5, M. BO, Cascade 3 RA 27,5, A8A, BE, Cascade 3 SA 27,5, M. HR	1500	OPE	DEPO-00	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-1909-00148 : FAACQ
c9edde02-2af4-43a8-8e4c-6a02c17357b9	A1460	A1460000030-00	A1460000030	00	Alat Ukur Diameter Roda LRV Riftek Wheel Diameter Measuring Gauge IDK Series	1460	OPE	DEPO-25	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-2007-00277 : FAACQ
42b0073a-07f3-4dcc-b82c-e2851b626433	A1460	A1460000031-00	A1460000031	00	Alat Ukur Flens Roda Riftek Railway Wheel Profile Gauge IKP - 5 + PDA	1460	OPE	DEPO-25	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-2007-00277 : FAACQ
9beb94c2-f47d-4b48-9281-54ec00cf0758	A1440	A1440000002-00	A1440000002	00	Genset Hyundai type HDG 5800	1440	OPE	DEPO-11	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-2009-00237 : FAACQ
c88e2c69-914f-403e-ab36-0a9322d6591f	A1470	A1470000036-00	A1470000036	00	Wheel Flange System Long	1470	CON	DEPO-11	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
9580ea1b-0f93-4c89-b167-a089131d5761	A1470	A1470000037-00	A1470000037	00	Wheel Flange System Long	1470	CON	DEPO-11	EXC	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	A1470	A1470000038-00	A1470000038	00	Wheel Flange System Long	1470	CON	DEPO-11	EXC	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	A1300	A1300000001-00	A1300000001	00	Kereta LRV 1 Set (2 Gerbong)	1300	OPE	DEPO-00	EXC	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
1c4a40c1-aeb5-4287-a4b1-383d158920e5	A1300	A1300000002-00	A1300000002	00	Kereta LRV 1 Set (2 Gerbong)	1300	OPE	DEPO-00	EXC	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
f875c2ca-1800-433b-b0a4-2d4d31ba308e	A1300	A1300000003-00	A1300000003	00	Kereta LRV 1 Set (2 Gerbong)	1300	OPE	DEPO-00	EXC	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N	JB-2010-00220 : FAACQ, JB-2010-00224 : FAACQ
54ec2fba-0b2b-4783-ab74-464ba53d2e07	A1450	A1450000007-00	A1450000007	00	Tanda Keluar Masuk	1450	CON	STSN-01	EXC	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N	JB-2012-00459 : FAACQ
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	A1450	A1450000008-00	A1450000008	00	Parkir Sepeda Stasiun	1450	OPE	STSN-00	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-2012-00459 : FAACQ
52d9a146-b1cf-4110-b89d-be03c22a6e0e	A1400	A1400000047-00	A1400000047	00	Akrilik Stasiun	1400	CON	STSN-01	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-2012-00459 : FAACQ
99970f15-9c4a-4d4f-b550-a7ef488054d0	A1450	A1450000009-00	A1450000009	00	Tempat Bersandar dan Duduk Stasiun	1450	OPE	STSN-01	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-2012-00460 : FAACQ
e971913d-0f93-4a70-85eb-c0ed12a172d8	A1450	A1450000011-00	A1450000011	00	Wayfinding Stasiun Boulevard Utara LRT Jakarta	1450	OPE	BVU0-00	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-2301-00254 : FAACQ
101fda0f-877a-4290-9df5-00a84859c3e9	A1460	A1460000001-00	A1460000001	00	Track Gauge Vogel Ploetscher RCAD-BT 1435	1460	OPE	DEPO-06	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-1909-00039 : FAACQ
6504929e-7f0b-47a6-b6d6-25032344b55f	A1460	A1460000002-00	A1460000002	00	Track Gauge Vogel Ploetscher RCAD-BT 1435	1460	OPE	DEPO-06	EXC	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N	JB-1909-00039 : FAACQ
19c63207-1947-4bb3-9193-554042ba6da7	A1460	A1460000003-00	A1460000003	00	Vogel & Plotscher Type SKM-2	1460	OPE	DEPO-22	EXC	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N	JB-1909-00039 : FAACQ
03e94a29-9883-46a5-9294-21d22f2fba7f	A1460	A1460000004-00	A1460000004	00	Vogel & Plotscher Type SKM-2	1460	OPE	DEPO-22	EXC	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N	JB-1909-00039 : FAACQ
49fe0c73-3650-4c46-b8b2-28b11191c8fb	A1400	A1400000023-00	A1400000023	00	North Bayou Bracket Standing LED TV 32-65 Inch AVA1500-60-1P, LG Smart TV 60 Inch, Belden UTP Cable, TP Link 16 Port Gigabit Desktop Rackmount	1400	OPE	DEPO-22	EXC	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N	JB-1909-00305 : FAACQ
36e92940-a131-4ac0-b45b-b8500ff4b040	A1210	A1210000004-00	A1210000004	00	Jasa Penambahan Rolling Door pada Stasiun Boulevard Utara	1210	OPE	BVU2-06	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
a11862d4-69a5-4d2b-a426-57a89de1b13c	A1340	A1340000006-00	A1340000006	00	Penambahan Access Management System (AMS) di Gedung MCC dan Stasiun LRT Jakarta	1340	OPE	DEPO-00	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
e47f3b62-82ae-4322-8660-bf104df108a5	A1401	A1401000041-00	A1401000041	00	HP SAMSUNG GALAXY S SERIES	1401	OPE	PGD3-08	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	A1401	A1401000042-00	A1401000042	00	HP SAMSUNG GALAXY S SERIES	1401	OPE	PGD3-08	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
80acf346-539e-4c9a-aed0-9ff88df294f5	A1401	A1401000044-00	A1401000044	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	MCC4-13	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
4517635a-b083-4bba-bbba-22c060cff5b6	A1401	A1401000045-00	A1401000045	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N	\N
bc1fdef0-b3ba-4655-867f-8038f2a0c04f	A1401	A1401000046-00	A1401000046	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
5450ed79-c9ee-45ac-abd3-d657d1a8897c	A1401	A1401000047-00	A1401000047	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
50a845bf-b203-4b10-b292-fda3c7b5ac6e	A1401	A1401000048-00	A1401000048	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
de6479e8-c9c2-41c1-9ad6-c74439bc986f	A1401	A1401000049-00	A1401000049	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
c7f80482-89d8-4f80-975d-34a752e992aa	A1401	A1401000050-00	A1401000050	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	A1401	A1401000051-00	A1401000051	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
8e4323ee-5954-4946-b50e-252f098ee44e	A1401	A1401000052-00	A1401000052	00	SAMSUNG GALAXY TAB S10 FE 5G 8/128	1401	OPE	PGD3-08	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
0023be09-5f8c-4f86-9a6f-78cdd74e63a7	A1411	A1411000101-00	A1411000101	00	LADDER ROLLING 6 STEP KRISBOW	1411	OPE	DEPO-30	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	A1412	A1412000011-00	A1412000011	00	SAFE BOX ICHIKO BRANGKAS ICS-3 F 52X75X120	1412	OPE	MCC5-02	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
9dbcc529-de27-4753-a772-90aa5f8c7894	A1412	A1412000012-00	A1412000012	00	PALLET RACKING;HEAVY DUTY;2 LEVEL;ALL BRAND	1412	OPE	DEPO-35	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
47665328-ff67-40a5-aac0-24572afbdcf8	A1412	A1412000013-00	A1412000013	00	PALLET RACKING;HEAVY DUTY;2 LEVEL;ALL BRAND	1412	OPE	DEPO-35	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
747d2923-ba5d-475d-a784-e41bc58e5561	A1412	A1412000014-00	A1412000014	00	PALLET RACKING;HEAVY DUTY;2 LEVEL;ALL BRAND	1412	OPE	DEPO-35	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
2f8f647c-1936-4b32-93f7-9ebbcda6d039	A1412	A1412000015-00	A1412000015	00	PALLET RACKING;HEAVY DUTY;2 LEVEL;ALL BRAND	1412	OPE	DEPO-35	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
f743b734-490e-470d-bc30-19e730a855b2	A1420	A1420000169-00	A1420000169	00	Jasa Penyediaan dan Pemasangan CCTV ; QNAP NAS RACKMOUNT 12 BAY TS1237AU-RP-8G	1420	OPE	MCC5-15	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
ac204bbb-af9f-4e3a-9734-082c29c9641f	A1420	A1420000169-01	A1420000169	01	CCTV CAMERA DOME BOSCH NDE-5702	1420	OPE	PGD0-01	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	A1420	A1420000169-02	A1420000169	02	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PGD0-00	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
31a57d16-cb30-4e53-8e7d-3ee074f5770b	A1420	A1420000169-03	A1420000169	03	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PGD0-00	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
30a9ed88-3599-4d7f-8456-cce980762f96	A1420	A1420000169-04	A1420000169	04	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVU1-04	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
4ee48863-fa9b-4ff3-9c00-2304ada83c29	A1420	A1420000169-05	A1420000169	05	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVU1-02	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
34453391-14df-41b0-8475-2d31c5371f29	A1420	A1420000169-06	A1420000169	06	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVU1-01	EXC	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N	\N
edddbe54-8ed9-496c-88d9-1a96279445c6	A1420	A1420000169-07	A1420000169	07	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVU1-03	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	A1420	A1420000169-08	A1420000169	08	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PUM1-04	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
e8925ef1-66f5-432d-92c3-c37b79062eef	A1420	A1420000169-09	A1420000169	09	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PUM1-02	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
a1906f9d-e1c1-4072-99c4-51cba2577d90	A1420	A1420000169-10	A1420000169	10	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PUM1-01	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
5e69818f-651f-4e8b-8a69-513fa0a773db	A1420	A1420000169-11	A1420000169	11	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	PUM1-03	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
ba105ad8-72ad-40f6-8634-03d1e712b9af	A1420	A1420000169-12	A1420000169	12	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	EQS1-04	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
fc890cda-3a6a-436b-8aee-2b1e22131cfd	A1420	A1420000169-13	A1420000169	13	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	EQS1-02	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
62fcc371-d6de-4ef0-88ef-413b40c6783d	A1420	A1420000169-14	A1420000169	14	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	EQS1-01	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
3bd5cf1d-ae87-4735-b753-1f810b177052	A1420	A1420000169-15	A1420000169	15	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	EQS1-03	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	A1420	A1420000169-16	A1420000169	16	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	VEL1-04	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
cddab2cb-f430-4819-b9cf-c35a54b156cd	A1420	A1420000169-17	A1420000169	17	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	VEL1-02	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
4852ac97-baee-4c1e-8b48-7e0fd276ec48	A1420	A1420000169-18	A1420000169	18	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	VEL1-03	EXC	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N	\N
3b16435d-b93f-4811-bf25-6d03a45cc6dc	A1420	A1420000169-19	A1420000169	19	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	VEL1-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	A1420	A1420000169-20	A1420000169	20	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVS1-04	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
c521f578-f2c7-446d-b351-9b47fdb59913	A1420	A1420000169-21	A1420000169	21	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVS1-02	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
836f58bc-d2d9-4543-bc82-7859db2da9be	A1420	A1420000169-22	A1420000169	22	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVS1-03	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
6630f300-223a-4694-a3b5-28193c508cba	A1420	A1420000169-23	A1420000169	23	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	BVS1-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
75c6a8ad-7be8-47cb-9165-89d42bb233c7	A1420	A1420000169-24	A1420000169	24	CCTV CAMERA BULLET BOSCH NBE-5702	1420	OPE	MCC0-05	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
6d3c3c19-3b28-4cab-9aa1-e700bdcef883	A1423	A1423000129-00	A1423000129	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC5-13	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
896e640c-3b59-4bc8-aba1-5ac076e99c49	A1423	A1423000130-00	A1423000130	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC5-02	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	A1423	A1423000131-00	A1423000131	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC5-02	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
bb12563d-78e3-4121-84df-edae5df20c63	A1423	A1423000132-00	A1423000132	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC5-02	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
da02d35f-1531-49f7-89f3-9c9fed5f9553	A1423	A1423000133-00	A1423000133	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-06	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
d30045d9-6179-4162-b8dc-e8d16ce29802	A1423	A1423000134-00	A1423000134	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-06	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
46930604-8016-42a6-9329-ffdac3236bc1	A1423	A1423000135-00	A1423000135	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
fe41bf26-c9b0-406f-8000-7f9469e1fe7d	A1423	A1423000136-00	A1423000136	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
538a6d2a-ec13-4d7c-87e7-f2e56d089780	A1423	A1423000137-00	A1423000137	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
fdcd74dc-bb14-44bb-8ee0-c12839b31f44	A1423	A1423000138-00	A1423000138	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N	\N
1a2dda94-1f32-444a-a4dd-310edef0d76d	A1423	A1423000139-00	A1423000139	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
e3e63659-175c-4748-b571-d2224a256534	A1423	A1423000140-00	A1423000140	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
80be2e71-1ead-4023-bd82-148c11e82d2f	A1423	A1423000141-00	A1423000141	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
60ab9154-7025-4b9a-93f7-d8c7f276cbc3	A1423	A1423000142-00	A1423000142	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
0192e4a7-0901-4db9-aa00-c192d6adaa37	A1423	A1423000143-00	A1423000143	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
9398fd93-f9b2-4639-8c65-51086cf62165	A1423	A1423000144-00	A1423000144	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
db95bb38-c227-48ec-ac5a-69d642ba910e	A1423	A1423000145-00	A1423000145	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
e3222e82-d284-45f4-87c5-6ca46ea72fac	A1423	A1423000146-00	A1423000146	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
221f2223-7885-4f5d-9d6c-c3ac40c50f9e	A1423	A1423000147-00	A1423000147	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
3c8eab4b-ba11-42c3-bc67-9290d52a36f9	A1423	A1423000148-00	A1423000148	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
f2f26243-a41c-42c5-b593-2fe4e12bc4aa	A1423	A1423000149-00	A1423000149	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
5bd44432-06b9-41e2-a0c4-cd8e616f52c9	A1423	A1423000150-00	A1423000150	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
abbaf21e-07b0-4097-889a-094bfeda26ef	A1423	A1423000151-00	A1423000151	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	A1423	A1423000152-00	A1423000152	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
66df45b6-5011-45a5-be1d-f140cc3e4b7d	A1423	A1423000153-00	A1423000153	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
0c5094e1-9380-4a00-aef4-46048c2ec697	A1423	A1423000154-00	A1423000154	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	A1423	A1423000155-00	A1423000155	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
d403907f-306d-4dfb-8ca4-a950b548394d	A1423	A1423000156-00	A1423000156	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
3998992b-b5bf-4d03-9cd7-526c45df750c	A1423	A1423000157-00	A1423000157	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-36	EXC	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	\N	\N
b6368e98-7f87-42db-8b28-4084b11a0972	A1423	A1423000158-00	A1423000158	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
82ef3eb4-8b79-47e0-915e-7276ea7bd578	A1423	A1423000159-00	A1423000159	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
899b064e-2a20-489f-b713-56a3a1bcaf20	A1423	A1423000160-00	A1423000160	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
3f0dafbf-7fd9-407b-b1a9-4141e6326797	A1423	A1423000161-00	A1423000161	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
659ca9a0-f2de-4890-86ed-ef404f8d93fd	A1423	A1423000162-00	A1423000162	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
67bd771b-1a68-403b-b081-4727a5b09bbe	A1423	A1423000163-00	A1423000163	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
45925fe4-a66c-4e4c-92e4-81f818fd71c8	A1423	A1423000164-00	A1423000164	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	A1423	A1423000165-00	A1423000165	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
9bb4b946-4d3b-427a-914c-accbaf7c362d	A1423	A1423000166-00	A1423000166	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
e74afc6c-038f-4875-a703-89b52e09ee91	A1423	A1423000167-00	A1423000167	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
43f964a1-fc8a-42fe-85b7-af80de5688a7	A1423	A1423000168-00	A1423000168	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	A1423	A1423000169-00	A1423000169	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-13	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
a267ecca-f8a6-4fde-8bfb-eaba58162ba2	A1423	A1423000170-00	A1423000170	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-13	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
06b7d765-707f-4860-a0e7-3e520d4c1578	A1423	A1423000171-00	A1423000171	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-13	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
d81dabec-1c10-4269-9548-808f65039d63	A1423	A1423000172-00	A1423000172	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-14	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
df697add-1313-4c55-957d-e53f28e5b499	A1423	A1423000173-00	A1423000173	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-14	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
68147a4a-8037-42ff-862a-64cc61cad395	A1423	A1423000174-00	A1423000174	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
3387ade2-a790-4808-9294-4308ebe93867	A1423	A1423000175-00	A1423000175	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
620120f2-1730-4c93-b033-954f79d02e56	A1423	A1423000176-00	A1423000176	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
c55b5d93-f4c2-4588-9bc0-e3051f907091	A1423	A1423000177-00	A1423000177	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
a0a355f7-a358-4e92-bdbd-9b31808a868e	A1423	A1423000178-00	A1423000178	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	\N	\N
091d5401-cef6-40f8-8778-87389d39e51f	A1423	A1423000179-00	A1423000179	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
3189250e-a44f-4b07-9d24-4b9b128485f9	A1423	A1423000180-00	A1423000180	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
55042527-68f7-43ac-9e1a-b2d1872b8b82	A1423	A1423000181-00	A1423000181	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
2e1343ff-6d31-4246-9667-83ecf97a93ba	A1423	A1423000182-00	A1423000182	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
c54762d7-e7d2-499f-a4db-fb340f1e740d	A1423	A1423000183-00	A1423000183	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
27ae456f-8d57-4820-a7e1-e478df363acf	A1423	A1423000184-00	A1423000184	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
a48bed46-6d76-4a7e-ad06-77a533df7482	A1423	A1423000185-00	A1423000185	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC6-04	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
361667bd-377a-46f0-83ea-bdce1a20b6ad	A1423	A1423000186-00	A1423000186	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC6-04	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	A1423	A1423000187-00	A1423000187	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
ebd82a70-8c97-4dad-80e0-7ece07478479	A1423	A1423000188-00	A1423000188	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
d2dac9ea-3c11-4698-8628-9c3412693fe6	A1423	A1423000189-00	A1423000189	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-05	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
0b19b75b-02ea-429d-b638-696f626d1384	A1423	A1423000190-00	A1423000190	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
b11ed488-c372-47f4-bf13-3a27148b98f0	A1423	A1423000191-00	A1423000191	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
76c9fc95-c035-4d55-a192-88b22c907aaf	A1423	A1423000192-00	A1423000192	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
e83c6db7-1c8d-44d3-818d-5fabf4127734	A1423	A1423000193-00	A1423000193	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
993c08e7-1142-433b-93c1-61aad85798f2	A1423	A1423000194-00	A1423000194	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
1bb9f4ba-525d-4390-800d-140404e63991	A1423	A1423000195-00	A1423000195	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	\N	\N
593b6e25-ee8c-4702-b04e-b8675711696b	A1423	A1423000196-00	A1423000196	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
34cb37c8-43d8-42bf-8be8-3622219b1fd2	A1423	A1423000197-00	A1423000197	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-01	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
a7072c26-3165-47e4-81bc-5a88a2d43ab1	A1423	A1423000198-00	A1423000198	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	MCC4-10	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
f99028fd-3f94-4fc4-8635-13369d98711f	A1423	A1423000199-00	A1423000199	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-25	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
c0677d15-296c-4d34-98e1-cc940baa7a99	A1423	A1423000200-00	A1423000200	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-25	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	A1423	A1423000201-00	A1423000201	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-25	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
e00e59a1-0f30-4ea7-8094-3956711ff682	A1423	A1423000202-00	A1423000202	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-25	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
5373b97d-245d-4889-9018-20958b798c17	A1423	A1423000203-00	A1423000203	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-25	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
e1b3ed82-00ea-485e-b741-070c71fe1d2c	A1423	A1423000204-00	A1423000204	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-26	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
627738fb-548f-49a7-ade4-0f7ae516c3c3	A1423	A1423000205-00	A1423000205	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	DEPO-24	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
d71ea253-d0e4-42f4-861d-a743fd7a8900	A1423	A1423000206-00	A1423000206	00	HP ELITEBOOK 630 13,3" G11	1423	OPE	PGD3-08	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	A1430	A1430000007-00	A1430000007	00	PERANGKAT JARINGAN CCTV MCC WITH SOFTWARE	1430	OPE	MCC0-00	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
de204b49-049f-4e74-9fad-76680c0ec640	A1431	A1431000008-00	A1431000008	00	CCTV - Honeywell HC35WZ2R25	1431	OPE	EQS0-00	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
d00fd50d-fdfa-440b-8698-8ba7c354386a	A1441	A1441000014-00	A1441000014	00	DEWALT DCF900P2T HIGH TORQUE CORDLESS IMPACT WRENCH	1441	OPE	DEPO-12	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
d88cc5d9-f493-4156-821e-29602853c857	A1461	A1461000018-00	A1461000018	00	Earth Clamp Tester	1461	OPE	DEPO-37	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	A1471	A1471000017-00	A1471000017	00	HT HYTERA PNC380 PRO HT POC 4G	1471	OPE	PGD3-08	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
f613464f-be5b-4c3d-9ff5-8ff2793f9d05	A1471	A1471000018-00	A1471000018	00	HT HYTERA PNC380 PRO HT POC 4G	1471	OPE	PGD3-08	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
e31d30be-ccad-45b8-a337-70e5c00155e2	A2102	A2102000003-00	A2102000003	00	DARWINBOX HRIS &PAYROLL SYSTEM	2102	OPE	SFWR-00	EXC	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N	\N
a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	A1401	A1401000043-00	A1401000043	00	SAMSUNG GALAXY TAB S10	1401	DIS	MCC4-13	EXC	2025-12-22 18:23:59+08	2025-12-22 21:15:35+08	\N	\N	\N
\.


--
-- Data for Name: assets_assignment; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_assignment (asset_uuid, asset_owner, asset_user, asset_maintenance, created_at, updated_at, deleted_at) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	SAR	SAR	BUM	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c9edde02-2af4-43a8-8e4c-6a02c17357b9	SAR	SAR	SAR	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
42b0073a-07f3-4dcc-b82c-e2851b626433	SAR	SAR	SAR	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9beb94c2-f47d-4b48-9281-54ec00cf0758	SAR	SAR	SAR	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c88e2c69-914f-403e-ab36-0a9322d6591f	SAR	SAR	SAR	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9580ea1b-0f93-4c89-b167-a089131d5761	SAR	SAR	SAR	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	SAR	SAR	SAR	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
1c4a40c1-aeb5-4287-a4b1-383d158920e5	SAR	SAR	SAR	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
f875c2ca-1800-433b-b0a4-2d4d31ba308e	SAR	SAR	SAR	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
54ec2fba-0b2b-4783-ab74-464ba53d2e07	PEL	PEL	PEL	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	PEL	PEL	PEL	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
52d9a146-b1cf-4110-b89d-be03c22a6e0e	PEL	PEL	PEL	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
99970f15-9c4a-4d4f-b550-a7ef488054d0	PEL	PEL	PEL	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
e971913d-0f93-4a70-85eb-c0ed12a172d8	PEL	PEL	PEL	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
101fda0f-877a-4290-9df5-00a84859c3e9	JLB	JLB	JLB	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
6504929e-7f0b-47a6-b6d6-25032344b55f	JLB	JLB	JLB	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
19c63207-1947-4bb3-9193-554042ba6da7	JLB	JLB	JLB	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
03e94a29-9883-46a5-9294-21d22f2fba7f	JLB	JLB	JLB	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
49fe0c73-3650-4c46-b8b2-28b11191c8fb	OIT	KIT	KIT	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
36e92940-a131-4ac0-b45b-b8500ff4b040	JLB	JLB	JLB	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
a11862d4-69a5-4d2b-a426-57a89de1b13c	FOP	KAM	FOP	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
e47f3b62-82ae-4322-8660-bf104df108a5	OIT	ASP	OIT	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	OIT	ASP	OIT	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	WRH	PGD	OIT	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
80acf346-539e-4c9a-aed0-9ff88df294f5	OIT	PGD	OIT	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
4517635a-b083-4bba-bbba-22c060cff5b6	OIT	ASP	OIT	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
bc1fdef0-b3ba-4655-867f-8038f2a0c04f	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
5450ed79-c9ee-45ac-abd3-d657d1a8897c	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
50a845bf-b203-4b10-b292-fda3c7b5ac6e	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
de6479e8-c9c2-41c1-9ad6-c74439bc986f	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
c7f80482-89d8-4f80-975d-34a752e992aa	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
8e4323ee-5954-4946-b50e-252f098ee44e	OIT	ASP	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
0023be09-5f8c-4f86-9a6f-78cdd74e63a7	WRH	WRH	WRH	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	BUM	SPR	BUM	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
9dbcc529-de27-4753-a772-90aa5f8c7894	WRH	WRH	WRH	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
47665328-ff67-40a5-aac0-24572afbdcf8	WRH	WRH	WRH	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
747d2923-ba5d-475d-a784-e41bc58e5561	WRH	WRH	WRH	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
2f8f647c-1936-4b32-93f7-9ebbcda6d039	WRH	WRH	WRH	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
f743b734-490e-470d-bc30-19e730a855b2	OIT	OIT	KIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ac204bbb-af9f-4e3a-9734-082c29c9641f	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
31a57d16-cb30-4e53-8e7d-3ee074f5770b	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
30a9ed88-3599-4d7f-8456-cce980762f96	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
4ee48863-fa9b-4ff3-9c00-2304ada83c29	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
34453391-14df-41b0-8475-2d31c5371f29	OIT	KAM	OIT	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
edddbe54-8ed9-496c-88d9-1a96279445c6	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
e8925ef1-66f5-432d-92c3-c37b79062eef	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
a1906f9d-e1c1-4072-99c4-51cba2577d90	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
5e69818f-651f-4e8b-8a69-513fa0a773db	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
ba105ad8-72ad-40f6-8634-03d1e712b9af	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
fc890cda-3a6a-436b-8aee-2b1e22131cfd	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
62fcc371-d6de-4ef0-88ef-413b40c6783d	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3bd5cf1d-ae87-4735-b753-1f810b177052	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
cddab2cb-f430-4819-b9cf-c35a54b156cd	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
4852ac97-baee-4c1e-8b48-7e0fd276ec48	OIT	KAM	OIT	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3b16435d-b93f-4811-bf25-6d03a45cc6dc	OIT	KAM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	OIT	KAM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
c521f578-f2c7-446d-b351-9b47fdb59913	OIT	KAM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
836f58bc-d2d9-4543-bc82-7859db2da9be	OIT	KAM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
6630f300-223a-4694-a3b5-28193c508cba	OIT	KAM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
75c6a8ad-7be8-47cb-9165-89d42bb233c7	OIT	OIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
6d3c3c19-3b28-4cab-9aa1-e700bdcef883	OIT	KAP	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
896e640c-3b59-4bc8-aba1-5ac076e99c49	OIT	KOM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	OIT	KOM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
bb12563d-78e3-4121-84df-edae5df20c63	OIT	KOM	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
da02d35f-1531-49f7-89f3-9c9fed5f9553	OIT	MIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
d30045d9-6179-4162-b8dc-e8d16ce29802	OIT	KIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
46930604-8016-42a6-9329-ffdac3236bc1	OIT	OIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
fe41bf26-c9b0-406f-8000-7f9469e1fe7d	OIT	OIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
538a6d2a-ec13-4d7c-87e7-f2e56d089780	OIT	OIT	OIT	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
fdcd74dc-bb14-44bb-8ee0-c12839b31f44	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
1a2dda94-1f32-444a-a4dd-310edef0d76d	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
e3e63659-175c-4748-b571-d2224a256534	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
80be2e71-1ead-4023-bd82-148c11e82d2f	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
60ab9154-7025-4b9a-93f7-d8c7f276cbc3	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0192e4a7-0901-4db9-aa00-c192d6adaa37	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
9398fd93-f9b2-4639-8c65-51086cf62165	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
db95bb38-c227-48ec-ac5a-69d642ba910e	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
e3222e82-d284-45f4-87c5-6ca46ea72fac	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
221f2223-7885-4f5d-9d6c-c3ac40c50f9e	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
3c8eab4b-ba11-42c3-bc67-9290d52a36f9	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
f2f26243-a41c-42c5-b593-2fe4e12bc4aa	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
5bd44432-06b9-41e2-a0c4-cd8e616f52c9	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
abbaf21e-07b0-4097-889a-094bfeda26ef	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
66df45b6-5011-45a5-be1d-f140cc3e4b7d	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0c5094e1-9380-4a00-aef4-46048c2ec697	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	OIT	SBP	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
d403907f-306d-4dfb-8ca4-a950b548394d	OIT	OIT	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
3998992b-b5bf-4d03-9cd7-526c45df750c	OIT	WRH	OIT	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
b6368e98-7f87-42db-8b28-4084b11a0972	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
82ef3eb4-8b79-47e0-915e-7276ea7bd578	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
899b064e-2a20-489f-b713-56a3a1bcaf20	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
3f0dafbf-7fd9-407b-b1a9-4141e6326797	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
659ca9a0-f2de-4890-86ed-ef404f8d93fd	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
67bd771b-1a68-403b-b081-4727a5b09bbe	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
45925fe4-a66c-4e4c-92e4-81f818fd71c8	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
9bb4b946-4d3b-427a-914c-accbaf7c362d	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
e74afc6c-038f-4875-a703-89b52e09ee91	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
43f964a1-fc8a-42fe-85b7-af80de5688a7	OIT	OIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	OIT	PGD	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a267ecca-f8a6-4fde-8bfb-eaba58162ba2	OIT	PGD	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
06b7d765-707f-4860-a0e7-3e520d4c1578	OIT	PGD	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
d81dabec-1c10-4269-9548-808f65039d63	OIT	BUM	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
df697add-1313-4c55-957d-e53f28e5b499	OIT	LDM	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
68147a4a-8037-42ff-862a-64cc61cad395	OIT	KDA	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
3387ade2-a790-4808-9294-4308ebe93867	OIT	MRK	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
620120f2-1730-4c93-b033-954f79d02e56	OIT	SBP	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
c55b5d93-f4c2-4588-9bc0-e3051f907091	OIT	MIT	OIT	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a0a355f7-a358-4e92-bdbd-9b31808a868e	OIT	RMP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
091d5401-cef6-40f8-8778-87389d39e51f	OIT	JLB	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
3189250e-a44f-4b07-9d24-4b9b128485f9	OIT	RMP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
55042527-68f7-43ac-9e1a-b2d1872b8b82	OIT	PDM	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
2e1343ff-6d31-4246-9667-83ecf97a93ba	OIT	PDM	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
c54762d7-e7d2-499f-a4db-fb340f1e740d	OIT	MKL	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
27ae456f-8d57-4820-a7e1-e478df363acf	OIT	MKL	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
a48bed46-6d76-4a7e-ad06-77a533df7482	OIT	POP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
361667bd-377a-46f0-83ea-bdce1a20b6ad	OIT	POP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	OIT	AKP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
ebd82a70-8c97-4dad-80e0-7ece07478479	OIT	MRK	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
d2dac9ea-3c11-4698-8628-9c3412693fe6	OIT	AKP	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
0b19b75b-02ea-429d-b638-696f626d1384	OIT	OIT	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
b11ed488-c372-47f4-bf13-3a27148b98f0	OIT	OIT	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
76c9fc95-c035-4d55-a192-88b22c907aaf	OIT	OIT	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
e83c6db7-1c8d-44d3-818d-5fabf4127734	OIT	OIT	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
993c08e7-1142-433b-93c1-61aad85798f2	OIT	JLB	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
1bb9f4ba-525d-4390-800d-140404e63991	OIT	JLB	OIT	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
593b6e25-ee8c-4702-b04e-b8675711696b	OIT	OPL	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
34cb37c8-43d8-42bf-8be8-3622219b1fd2	OIT	OPL	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
a7072c26-3165-47e4-81bc-5a88a2d43ab1	OIT	JLB	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f99028fd-3f94-4fc4-8635-13369d98711f	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
c0677d15-296c-4d34-98e1-cc940baa7a99	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e00e59a1-0f30-4ea7-8094-3956711ff682	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
5373b97d-245d-4889-9018-20958b798c17	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e1b3ed82-00ea-485e-b741-070c71fe1d2c	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
627738fb-548f-49a7-ade4-0f7ae516c3c3	OIT	SAR	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d71ea253-d0e4-42f4-861d-a743fd7a8900	OIT	ASP	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	OIT	OIT	KIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
de204b49-049f-4e74-9fad-76680c0ec640	OIT	OIT	OIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d00fd50d-fdfa-440b-8698-8ba7c354386a	SAR	SAR	SAR	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d88cc5d9-f493-4156-821e-29602853c857	FOP	FOP	FOP	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	ASP	ASP	FOP	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f613464f-be5b-4c3d-9ff5-8ff2793f9d05	ASP	ASP	FOP	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e31d30be-ccad-45b8-a337-70e5c00155e2	OIT	LDM	DIT	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	ASP	SAR	SAR	2025-12-22 13:17:05+08	2025-12-22 21:12:25+08	\N
\.


--
-- Data for Name: assets_classification; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_classification (asset_uuid, kode_asset_transaction, kode_asset_type, kode_category, kode_category_2, kode_sub_category, created_at, updated_at, deleted_at) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c9edde02-2af4-43a8-8e4c-6a02c17357b9	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
42b0073a-07f3-4dcc-b82c-e2851b626433	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9beb94c2-f47d-4b48-9281-54ec00cf0758	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c88e2c69-914f-403e-ab36-0a9322d6591f	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9580ea1b-0f93-4c89-b167-a089131d5761	A	\N	\N	\N	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	A	\N	\N	\N	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	A	\N	\N	\N	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
1c4a40c1-aeb5-4287-a4b1-383d158920e5	A	\N	\N	\N	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
f875c2ca-1800-433b-b0a4-2d4d31ba308e	A	\N	\N	\N	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
54ec2fba-0b2b-4783-ab74-464ba53d2e07	A	\N	\N	\N	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
52d9a146-b1cf-4110-b89d-be03c22a6e0e	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
99970f15-9c4a-4d4f-b550-a7ef488054d0	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
e971913d-0f93-4a70-85eb-c0ed12a172d8	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
101fda0f-877a-4290-9df5-00a84859c3e9	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
6504929e-7f0b-47a6-b6d6-25032344b55f	A	\N	\N	\N	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
19c63207-1947-4bb3-9193-554042ba6da7	A	\N	\N	\N	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
03e94a29-9883-46a5-9294-21d22f2fba7f	A	\N	\N	\N	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
49fe0c73-3650-4c46-b8b2-28b11191c8fb	A	\N	\N	\N	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
36e92940-a131-4ac0-b45b-b8500ff4b040	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
a11862d4-69a5-4d2b-a426-57a89de1b13c	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
e47f3b62-82ae-4322-8660-bf104df108a5	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
80acf346-539e-4c9a-aed0-9ff88df294f5	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
4517635a-b083-4bba-bbba-22c060cff5b6	A	\N	\N	\N	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
bc1fdef0-b3ba-4655-867f-8038f2a0c04f	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
5450ed79-c9ee-45ac-abd3-d657d1a8897c	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
50a845bf-b203-4b10-b292-fda3c7b5ac6e	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
de6479e8-c9c2-41c1-9ad6-c74439bc986f	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
c7f80482-89d8-4f80-975d-34a752e992aa	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
8e4323ee-5954-4946-b50e-252f098ee44e	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
0023be09-5f8c-4f86-9a6f-78cdd74e63a7	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
9dbcc529-de27-4753-a772-90aa5f8c7894	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
47665328-ff67-40a5-aac0-24572afbdcf8	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
747d2923-ba5d-475d-a784-e41bc58e5561	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
2f8f647c-1936-4b32-93f7-9ebbcda6d039	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
f743b734-490e-470d-bc30-19e730a855b2	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ac204bbb-af9f-4e3a-9734-082c29c9641f	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
31a57d16-cb30-4e53-8e7d-3ee074f5770b	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
30a9ed88-3599-4d7f-8456-cce980762f96	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
4ee48863-fa9b-4ff3-9c00-2304ada83c29	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
34453391-14df-41b0-8475-2d31c5371f29	A	\N	\N	\N	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
edddbe54-8ed9-496c-88d9-1a96279445c6	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
e8925ef1-66f5-432d-92c3-c37b79062eef	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
a1906f9d-e1c1-4072-99c4-51cba2577d90	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
5e69818f-651f-4e8b-8a69-513fa0a773db	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
ba105ad8-72ad-40f6-8634-03d1e712b9af	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
fc890cda-3a6a-436b-8aee-2b1e22131cfd	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
62fcc371-d6de-4ef0-88ef-413b40c6783d	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3bd5cf1d-ae87-4735-b753-1f810b177052	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
cddab2cb-f430-4819-b9cf-c35a54b156cd	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
4852ac97-baee-4c1e-8b48-7e0fd276ec48	A	\N	\N	\N	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3b16435d-b93f-4811-bf25-6d03a45cc6dc	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
c521f578-f2c7-446d-b351-9b47fdb59913	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
836f58bc-d2d9-4543-bc82-7859db2da9be	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
6630f300-223a-4694-a3b5-28193c508cba	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
75c6a8ad-7be8-47cb-9165-89d42bb233c7	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
6d3c3c19-3b28-4cab-9aa1-e700bdcef883	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
896e640c-3b59-4bc8-aba1-5ac076e99c49	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
bb12563d-78e3-4121-84df-edae5df20c63	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
da02d35f-1531-49f7-89f3-9c9fed5f9553	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
d30045d9-6179-4162-b8dc-e8d16ce29802	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
46930604-8016-42a6-9329-ffdac3236bc1	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
fe41bf26-c9b0-406f-8000-7f9469e1fe7d	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
538a6d2a-ec13-4d7c-87e7-f2e56d089780	A	\N	\N	\N	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
fdcd74dc-bb14-44bb-8ee0-c12839b31f44	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
1a2dda94-1f32-444a-a4dd-310edef0d76d	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
e3e63659-175c-4748-b571-d2224a256534	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
80be2e71-1ead-4023-bd82-148c11e82d2f	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
60ab9154-7025-4b9a-93f7-d8c7f276cbc3	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0192e4a7-0901-4db9-aa00-c192d6adaa37	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
9398fd93-f9b2-4639-8c65-51086cf62165	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
db95bb38-c227-48ec-ac5a-69d642ba910e	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
e3222e82-d284-45f4-87c5-6ca46ea72fac	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
221f2223-7885-4f5d-9d6c-c3ac40c50f9e	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
3c8eab4b-ba11-42c3-bc67-9290d52a36f9	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
f2f26243-a41c-42c5-b593-2fe4e12bc4aa	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
5bd44432-06b9-41e2-a0c4-cd8e616f52c9	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
abbaf21e-07b0-4097-889a-094bfeda26ef	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
66df45b6-5011-45a5-be1d-f140cc3e4b7d	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0c5094e1-9380-4a00-aef4-46048c2ec697	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
d403907f-306d-4dfb-8ca4-a950b548394d	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
3998992b-b5bf-4d03-9cd7-526c45df750c	A	\N	\N	\N	\N	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
b6368e98-7f87-42db-8b28-4084b11a0972	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
82ef3eb4-8b79-47e0-915e-7276ea7bd578	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
899b064e-2a20-489f-b713-56a3a1bcaf20	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
3f0dafbf-7fd9-407b-b1a9-4141e6326797	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
659ca9a0-f2de-4890-86ed-ef404f8d93fd	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
67bd771b-1a68-403b-b081-4727a5b09bbe	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
45925fe4-a66c-4e4c-92e4-81f818fd71c8	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
9bb4b946-4d3b-427a-914c-accbaf7c362d	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
e74afc6c-038f-4875-a703-89b52e09ee91	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
43f964a1-fc8a-42fe-85b7-af80de5688a7	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a267ecca-f8a6-4fde-8bfb-eaba58162ba2	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
06b7d765-707f-4860-a0e7-3e520d4c1578	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
d81dabec-1c10-4269-9548-808f65039d63	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
df697add-1313-4c55-957d-e53f28e5b499	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
68147a4a-8037-42ff-862a-64cc61cad395	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
3387ade2-a790-4808-9294-4308ebe93867	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
620120f2-1730-4c93-b033-954f79d02e56	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
c55b5d93-f4c2-4588-9bc0-e3051f907091	A	\N	\N	\N	\N	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a0a355f7-a358-4e92-bdbd-9b31808a868e	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
091d5401-cef6-40f8-8778-87389d39e51f	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
3189250e-a44f-4b07-9d24-4b9b128485f9	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
55042527-68f7-43ac-9e1a-b2d1872b8b82	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
2e1343ff-6d31-4246-9667-83ecf97a93ba	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
c54762d7-e7d2-499f-a4db-fb340f1e740d	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
27ae456f-8d57-4820-a7e1-e478df363acf	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
a48bed46-6d76-4a7e-ad06-77a533df7482	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
361667bd-377a-46f0-83ea-bdce1a20b6ad	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
ebd82a70-8c97-4dad-80e0-7ece07478479	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
d2dac9ea-3c11-4698-8628-9c3412693fe6	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
0b19b75b-02ea-429d-b638-696f626d1384	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
b11ed488-c372-47f4-bf13-3a27148b98f0	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
76c9fc95-c035-4d55-a192-88b22c907aaf	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
e83c6db7-1c8d-44d3-818d-5fabf4127734	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
993c08e7-1142-433b-93c1-61aad85798f2	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
1bb9f4ba-525d-4390-800d-140404e63991	A	\N	\N	\N	\N	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
593b6e25-ee8c-4702-b04e-b8675711696b	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
34cb37c8-43d8-42bf-8be8-3622219b1fd2	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
a7072c26-3165-47e4-81bc-5a88a2d43ab1	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f99028fd-3f94-4fc4-8635-13369d98711f	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
c0677d15-296c-4d34-98e1-cc940baa7a99	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e00e59a1-0f30-4ea7-8094-3956711ff682	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
5373b97d-245d-4889-9018-20958b798c17	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e1b3ed82-00ea-485e-b741-070c71fe1d2c	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
627738fb-548f-49a7-ade4-0f7ae516c3c3	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d71ea253-d0e4-42f4-861d-a743fd7a8900	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
de204b49-049f-4e74-9fad-76680c0ec640	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d00fd50d-fdfa-440b-8698-8ba7c354386a	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d88cc5d9-f493-4156-821e-29602853c857	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f613464f-be5b-4c3d-9ff5-8ff2793f9d05	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e31d30be-ccad-45b8-a337-70e5c00155e2	A	\N	\N	\N	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
\.


--
-- Data for Name: assets_depr_ledger_monthly; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_depr_ledger_monthly (uuid, asset_uuid, period, opening_balance, additions, transfers_in, transfers_out, disposals, adjustment_value, adjustment_depreciation, depr_expense, accumulated_depr_end, ending_balance, created_at, updated_at, depr_code) FROM stdin;
a0a6c0f6-be52-43da-9f60-36fb5165ddb1	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2019-10-01	28240000.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	588333.00	27651667.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP19100001
a0a6c0f6-bfed-4dfd-89cc-526d4c88c720	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2019-11-01	27651667.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	1176666.00	27063334.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP19110001
a0a6c0f6-c114-4adf-91f9-7308408a148a	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2019-12-01	27063334.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	1764999.00	26475001.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP19120001
a0a6c0f6-c21d-454c-8f72-f4007aae8983	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-01-01	26475001.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	2353332.00	25886668.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20010001
a0a6c0f6-c37e-422c-8813-26ef856954b4	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-02-01	25886668.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	2941665.00	25298335.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20020001
a0a6c0f6-c539-4365-a033-a512a24a650c	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-03-01	25298335.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	3529998.00	24710002.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20030001
a0a6c0f6-c689-43a5-9335-e67aa2c2ecb1	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-04-01	24710002.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	4118331.00	24121669.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20040001
a0a6c0f6-c7a6-4ed5-a44d-435ee2e1071d	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-05-01	24121669.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	4706664.00	23533336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20050001
a0a6c0f6-c8bb-493b-8110-bbe0d0101db0	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-06-01	23533336.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	5294997.00	22945003.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20060001
a0a6c0f6-c9c2-4590-ad2e-f1c31437ce92	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-07-01	22945003.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	5883330.00	22356670.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20070001
a0a6c0f6-cb1a-4ddb-b0bc-6c40367c5efe	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-08-01	22356670.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	6471663.00	21768337.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20080001
a0a6c0f6-cbff-471f-818f-225ad21568c5	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-09-01	21768337.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	7059996.00	21180004.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20090001
a0a6c0f6-ccdf-492f-8930-10ca05b0662c	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-10-01	21180004.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	7648329.00	20591671.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20100001
a0a6c0f6-cded-4e51-9252-b86cb8bdaa36	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-11-01	20591671.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	8236662.00	20003338.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110001
a0a6c0f6-cf1e-4a95-a2fd-07893157af7f	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2020-12-01	20003338.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	8824995.00	19415005.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120001
a0a6c0f6-d067-44a5-a24c-11a6e6843ed5	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-01-01	19415005.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	9413328.00	18826672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010001
a0a6c0f6-d1af-45e5-8e84-9ab70c4a6b44	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-02-01	18826672.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	10001661.00	18238339.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020001
a0a6c0f6-d2f3-4e7b-80b0-6cc751510c5d	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-03-01	18238339.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	10589994.00	17650006.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030001
a0a6c0f6-d41f-4893-bd03-88425c19efb3	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-04-01	17650006.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	11178327.00	17061673.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040001
a0a6c0f6-d54f-4edd-9b93-59bb841f040d	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-05-01	17061673.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	11766660.00	16473340.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050001
a0a6c0f6-d6d2-4cf1-98f6-aff6962b8ca5	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-06-01	16473340.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	12354993.00	15885007.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060001
a0a6c0f6-d7d7-4c77-9d0d-2d624a2c93a6	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-07-01	15885007.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	12943326.00	15296674.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070001
a0a6c0f6-d8cd-47e8-ac45-893ad9285179	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-08-01	15296674.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	13531659.00	14708341.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080001
a0a6c0f6-d99f-459b-b150-9f644f4868fa	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-09-01	14708341.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	14119992.00	14120008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090001
a0a6c0f6-dc67-43f6-98a5-e81cf78d6b0e	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-10-01	14120008.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	14708325.00	13531675.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100001
a0a6c0f6-ddd1-4b92-b34d-ab07bb795a8d	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-11-01	13531675.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	15296658.00	12943342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110001
a0a6c0f6-e06e-490e-a980-5388ca0b7ce0	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2021-12-01	12943342.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	15884991.00	12355009.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120001
a0a6c0f6-e19e-4327-8401-98b175fa2d9e	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-01-01	12355009.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	16473324.00	11766676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010001
a0a6c0f6-e354-4501-a6f1-7436dac239dc	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-02-01	11766676.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	17061657.00	11178343.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020001
a0a6c0f6-e687-4811-8675-27c47d2b674a	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-03-01	11178343.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	17649990.00	10590010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030001
a0a6c0f6-e7ad-4f21-9527-38940697d6f6	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-04-01	10590010.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	18238323.00	10001677.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040001
a0a6c0f6-e8d1-4ba8-974e-35c4c2a2b01b	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-05-01	10001677.00	0.00	0.00	0.00	0.00	0.00	0.00	588333.00	18826656.00	9413344.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050001
a0a6c0f6-e9da-4029-a1bf-c12348fcd88e	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-06-01	9413344.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	19414990.00	8825010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060001
a0a6c0f6-ebc1-4472-8424-ee5abb77646a	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-07-01	8825010.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	20003324.00	8236676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070001
a0a6c0f6-ee7a-495b-a5fb-41ad105a0582	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-08-01	8236676.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	20591658.00	7648342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080001
a0a6c0f6-efc7-44dd-8445-b635266d3c9e	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-09-01	7648342.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	21179992.00	7060008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22090001
a0a6c0f6-f14e-4269-a66f-d20b96915540	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-10-01	7060008.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	21768326.00	6471674.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22100001
a0a6c0f6-f2e9-4602-917b-0ba3370fbb04	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-11-01	6471674.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	22356660.00	5883340.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22110001
a0a6c0f6-f3d1-4767-9c57-14360df26eaa	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2022-12-01	5883340.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	22944994.00	5295006.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22120001
a0a6c0f6-f51b-4654-8b36-1045b7788f19	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-01-01	5295006.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	23533328.00	4706672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23010001
a0a6c0f6-f7d4-4cb9-80e8-d00adc84091f	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-02-01	4706672.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	24121662.00	4118338.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23020001
a0a6c0f6-f97f-49ac-bbac-7340f5cd8d35	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-03-01	4118338.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	24709996.00	3530004.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23030001
a0a6c0f6-fae1-42f2-b219-1cc5efc84f73	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-04-01	3530004.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	25298330.00	2941670.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23040001
a0a6c0f6-fc2a-423f-b24f-862014edc738	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-05-01	2941670.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	25886664.00	2353336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23050001
a0a6c0f6-fd39-47a9-9262-8d9d93745a8f	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-06-01	2353336.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	26474998.00	1765002.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23060001
a0a6c0f6-fe4f-4104-b52a-ff6aeacf6bed	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-07-01	1765002.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	27063332.00	1176668.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23070001
a0a6c0f6-ff1f-4495-990c-40ca6f8cd5b9	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-08-01	1176668.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	27651666.00	588334.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23080001
a0a6c0f7-0037-4bc1-97c8-4a6b47d62a1a	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2023-09-01	588334.00	0.00	0.00	0.00	0.00	0.00	0.00	588334.00	28240000.00	0.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23090001
a0a6c0f7-1202-42ca-8f0a-7984e199999a	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2020-08-01	82500000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	1718750.00	80781250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20080002
a0a6c0f7-1329-4f45-9dfd-6f57628090d4	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2020-09-01	80781250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	3437500.00	79062500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20090002
a0a6c0f7-145d-408d-b917-bfe659b395c3	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2020-10-01	79062500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	5156250.00	77343750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20100002
a0a6c0f7-1515-4fc5-b2ac-d73610ed9e20	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2020-11-01	77343750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	6875000.00	75625000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110002
a0a6c0f7-165d-456e-b9ab-915c990e3556	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2020-12-01	75625000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	8593750.00	73906250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120002
a0a6c0f7-171f-4d7c-a13b-5d81f4e96b96	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-01-01	73906250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	10312500.00	72187500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010002
a0a6c0f7-17b5-4c2c-85cf-af3077b6734e	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-02-01	72187500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	12031250.00	70468750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020002
a0a6c0f7-1857-4743-9033-c01c89731df9	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-03-01	70468750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	13750000.00	68750000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030002
a0a6c0f7-18fc-4abf-a678-02716bef38a3	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-04-01	68750000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	15468750.00	67031250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040002
a0a6c0f7-1987-40dc-9c2d-cfc0d6f8f76e	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-05-01	67031250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	17187500.00	65312500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050002
a0a6c0f7-1a24-46ad-9361-80133e14c071	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-06-01	65312500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	18906250.00	63593750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060002
a0a6c0f7-1ab7-4c6a-9902-d1c61412c758	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-07-01	63593750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	20625000.00	61875000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070002
a0a6c0f7-1b67-45ef-b44c-4e12da1170fc	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-08-01	61875000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	22343750.00	60156250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080002
a0a6c0f7-1c21-454c-93be-1a924e9a6823	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-09-01	60156250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	24062500.00	58437500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090002
a0a6c0f7-1ce6-44e7-8fa2-dbbdf08fd848	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-10-01	58437500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	25781250.00	56718750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100002
a0a6c0f7-1d84-4137-a6a7-f05269fd6a92	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-11-01	56718750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	27500000.00	55000000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110002
a0a6c0f7-1e29-4792-8a1f-07b9011adf16	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2021-12-01	55000000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	29218750.00	53281250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120002
a0a6c0f7-1ee3-4f68-816b-4010ea99b786	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-01-01	53281250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	30937500.00	51562500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010002
a0a6c0f7-1fb7-456a-bb36-dbe3e327bae3	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-02-01	51562500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	32656250.00	49843750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020002
a0a6c0f7-2312-4347-8115-db0a2254d41c	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-03-01	49843750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	34375000.00	48125000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030002
a0a6c0f7-23f2-402c-82c8-013d2646dce6	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-04-01	48125000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	36093750.00	46406250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040002
a0a6c0f7-2508-46c2-aae4-3f5a4f4de7a8	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-05-01	46406250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	37812500.00	44687500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050002
a0a6c0f7-25c6-44d7-9a12-5911d6b7552e	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-06-01	44687500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	39531250.00	42968750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060002
a0a6c0f7-266c-4b03-9bce-fd8281b77360	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-07-01	42968750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	41250000.00	41250000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070002
a0a6c0f7-2723-47ec-9fe7-4b66b7ed758f	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-08-01	41250000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	42968750.00	39531250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080002
a0a6c0f7-27c1-48f6-876f-7e5a419acd04	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-09-01	39531250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	44687500.00	37812500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22090002
a0a6c0f7-2878-4a94-85bf-8c4327162b97	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-10-01	37812500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	46406250.00	36093750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22100002
a0a6c0f7-2910-46ff-845f-d1a32eae93d7	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-11-01	36093750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	48125000.00	34375000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22110002
a0a6c0f7-29a1-4c01-b4fb-c57338466b94	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2022-12-01	34375000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	49843750.00	32656250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22120002
a0a6c0f7-2a1f-4a9e-900b-a49d17a9facf	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-01-01	32656250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	51562500.00	30937500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23010002
a0a6c0f7-2ad6-4815-b752-d5602372b7c9	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-02-01	30937500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	53281250.00	29218750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23020002
a0a6c0f7-2b95-4300-ad61-dfd08a734da9	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-03-01	29218750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	55000000.00	27500000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23030002
a0a6c0f7-2c4b-48f3-9f36-d17d0ccf2502	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-04-01	27500000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	56718750.00	25781250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23040002
a0a6c0f7-2e24-433d-8623-f10589c5bb11	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-05-01	25781250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	58437500.00	24062500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23050002
a0a6c0f7-2ffc-40e9-9004-a6c1cd2a485e	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-06-01	24062500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	60156250.00	22343750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23060002
a0a6c0f7-30a2-4520-82d0-fc9b63cb2fa5	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-07-01	22343750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	61875000.00	20625000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23070002
a0a6c0f7-313a-4e70-b4ea-c0577aa8c56a	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-08-01	20625000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	63593750.00	18906250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23080002
a0a6c0f7-31c6-4067-aaa4-69ab905b5470	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-09-01	18906250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	65312500.00	17187500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23090002
a0a6c0f7-3292-45cc-9596-e936748542a1	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-10-01	17187500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	67031250.00	15468750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23100001
a0a6c0f7-336a-4354-bbe8-3c27def2251b	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-11-01	15468750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	68750000.00	13750000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23110001
a0a6c0f7-3443-4a87-b45e-d0a6ece55be1	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2023-12-01	13750000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	70468750.00	12031250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23120001
a0a6c0f7-3585-4b00-a2d4-b52b79140175	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-01-01	12031250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	72187500.00	10312500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24010001
a0a6c0f7-36b0-45d2-b597-cd8523e6d5fe	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-02-01	10312500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	73906250.00	8593750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24020001
a0a6c0f7-37b2-46a5-a42b-ec32d8027d75	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-03-01	8593750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	75625000.00	6875000.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24030001
a0a6c0f7-3875-4200-b649-60637e6b8a37	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-04-01	6875000.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	77343750.00	5156250.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24040001
a0a6c0f7-3964-40a3-9bc3-9422d28900ce	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-05-01	5156250.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	79062500.00	3437500.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24050001
a0a6c0f7-3aa7-494d-83ae-ffa25550ed6f	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-06-01	3437500.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	80781250.00	1718750.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24060001
a0a6c0f7-3bd6-4a9d-b0fd-80cc96b3fae1	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2024-07-01	1718750.00	0.00	0.00	0.00	0.00	0.00	0.00	1718750.00	82500000.00	0.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24070001
a0a6c0f7-4e57-4d1f-8b5c-442bbc75c98b	42b0073a-07f3-4dcc-b82c-e2851b626433	2020-08-01	154550000.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	3219791.00	151330209.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20080003
a0a6c0f7-4f27-4a1a-a9fb-b22cf5e668d0	42b0073a-07f3-4dcc-b82c-e2851b626433	2020-09-01	151330209.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	6439582.00	148110418.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20090003
a0a6c0f7-4ff1-4b45-8e52-8d6ae16ead14	42b0073a-07f3-4dcc-b82c-e2851b626433	2020-10-01	148110418.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	9659373.00	144890627.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20100003
a0a6c0f7-50b2-4a8c-9e8f-8397dc7b61a8	42b0073a-07f3-4dcc-b82c-e2851b626433	2020-11-01	144890627.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	12879164.00	141670836.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110003
a0a6c0f7-516a-43ee-a63d-1da067d35bcc	42b0073a-07f3-4dcc-b82c-e2851b626433	2020-12-01	141670836.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	16098955.00	138451045.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120003
a0a6c0f7-5228-492c-a97f-bd439b8cb95b	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-01-01	138451045.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	19318746.00	135231254.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010003
a0a6c0f7-52f2-4618-b30b-34a6fe40aaaf	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-02-01	135231254.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	22538537.00	132011463.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020003
a0a6c0f7-539b-4f78-a0fc-0a22e29fbe20	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-03-01	132011463.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	25758328.00	128791672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030003
a0a6c0f7-543d-4266-a3ed-81b3192a4ca8	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-04-01	128791672.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	28978119.00	125571881.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040003
a0a6c0f7-54d1-4a76-bc0f-4002a7234ab1	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-05-01	125571881.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	32197910.00	122352090.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050003
a0a6c0f7-557a-47f8-8b96-378890cd2192	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-06-01	122352090.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	35417701.00	119132299.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060003
a0a6c0f7-562b-4114-842f-b3ddb93e5418	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-07-01	119132299.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	38637492.00	115912508.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070003
a0a6c0f7-56f9-4566-ba35-ac818c5b07d3	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-08-01	115912508.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	41857283.00	112692717.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080003
a0a6c0f7-5792-4709-aded-da75e3218511	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-09-01	112692717.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	45077074.00	109472926.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090003
a0a6c0f7-5913-4a2c-b577-ebe771013cfc	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-10-01	109472926.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	48296865.00	106253135.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100003
a0a6c0f7-59e5-4375-a633-7fa901f98be9	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-11-01	106253135.00	0.00	0.00	0.00	0.00	0.00	0.00	3219791.00	51516656.00	103033344.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110003
a0a6c0f7-5a88-490b-805d-465c84342744	42b0073a-07f3-4dcc-b82c-e2851b626433	2021-12-01	103033344.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	54736448.00	99813552.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120003
a0a6c0f7-5b36-4467-baf8-95fc4f91f390	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-01-01	99813552.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	57956240.00	96593760.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010003
a0a6c0f7-5dc8-48c4-8662-1a298554e33f	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-02-01	96593760.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	61176032.00	93373968.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020003
a0a6c0f7-5eb5-41e7-900e-00d796944612	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-03-01	93373968.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	64395824.00	90154176.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030003
a0a6c0f7-5f63-403e-8b5f-b5e4532ffa32	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-04-01	90154176.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	67615616.00	86934384.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040003
a0a6c0f7-600c-4a0e-8334-44946cd2cbcc	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-05-01	86934384.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	70835408.00	83714592.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050003
a0a6c0f7-60e3-4f13-bea5-fbc6695f1ca1	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-06-01	83714592.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	74055200.00	80494800.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060003
a0a6c0f7-6170-48bf-b9c0-282e491d5575	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-07-01	80494800.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	77274992.00	77275008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070003
a0a6c0f7-6282-4b85-92f2-7f7f31ebb56f	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-08-01	77275008.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	80494784.00	74055216.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080003
a0a6c0f7-63cc-425a-9b0f-1cb508f1211b	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-09-01	74055216.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	83714576.00	70835424.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22090003
a0a6c0f7-647b-4b3e-aa28-b3107169f39e	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-10-01	70835424.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	86934368.00	67615632.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22100003
a0a6c0f7-6577-49a1-a85c-75434c604739	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-11-01	67615632.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	90154160.00	64395840.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22110003
a0a6c0f7-66ad-4afc-9788-a5ba80720a5b	42b0073a-07f3-4dcc-b82c-e2851b626433	2022-12-01	64395840.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	93373952.00	61176048.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22120003
a0a6c0f7-6878-47b8-9e01-45691b7680d0	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-01-01	61176048.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	96593744.00	57956256.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23010003
a0a6c0f7-6985-4934-ab4d-a376e1f8e028	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-02-01	57956256.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	99813536.00	54736464.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23020003
a0a6c0f7-6ab2-4b75-8eb8-ca00c85986fd	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-03-01	54736464.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	103033328.00	51516672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23030003
a0a6c0f7-6bb3-4e5e-bb9d-de95cca90424	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-04-01	51516672.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	106253120.00	48296880.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23040003
a0a6c0f7-6c99-4389-beb5-8a250bd237c7	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-05-01	48296880.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	109472912.00	45077088.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23050003
a0a6c0f7-6de5-423c-adb0-75731d8984fe	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-06-01	45077088.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	112692704.00	41857296.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23060003
a0a6c0f7-6f21-4622-8c0f-c06a16a9dbd1	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-07-01	41857296.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	115912496.00	38637504.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23070003
a0a6c0f7-7009-4957-946f-df6ef4baa16e	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-08-01	38637504.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	119132288.00	35417712.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23080003
a0a6c0f7-70bc-4d8c-9c9e-1321beeae3d8	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-09-01	35417712.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	122352080.00	32197920.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23090003
a0a6c0f7-718f-49cf-89ff-ca4a71724d36	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-10-01	32197920.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	125571872.00	28978128.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23100002
a0a6c0f7-723d-4629-b035-05247a3ae4cc	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-11-01	28978128.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	128791664.00	25758336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23110002
a0a6c0f7-72d7-4a47-b4ea-8b2ddca3d64a	42b0073a-07f3-4dcc-b82c-e2851b626433	2023-12-01	25758336.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	132011456.00	22538544.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23120002
a0a6c0f7-737f-4b6f-9a77-920f1f8b6d10	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-01-01	22538544.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	135231248.00	19318752.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24010002
a0a6c0f7-740e-44a1-8c51-51676ce56819	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-02-01	19318752.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	138451040.00	16098960.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24020002
a0a6c0f7-7563-43e1-b258-d3acf2742ebe	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-03-01	16098960.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	141670832.00	12879168.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24030002
a0a6c0f7-76cb-4fa2-8216-d6d5879db156	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-04-01	12879168.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	144890624.00	9659376.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24040002
a0a6c0f7-785b-419d-b3eb-66cf5e89fed2	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-05-01	9659376.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	148110416.00	6439584.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24050002
a0a6c0f7-7b37-451b-9eb2-8d27bda0c438	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-06-01	6439584.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	151330208.00	3219792.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24060002
a0a6c0f7-7c7c-436e-9403-c29339d9575e	42b0073a-07f3-4dcc-b82c-e2851b626433	2024-07-01	3219792.00	0.00	0.00	0.00	0.00	0.00	0.00	3219792.00	154550000.00	0.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24070002
a0a6c0f7-8bdf-4f12-936e-dbd2590cf928	9beb94c2-f47d-4b48-9281-54ec00cf0758	2020-10-01	24145000.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	503020.00	23641980.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20100004
a0a6c0f7-8ca7-4a37-9c91-5b1a39c43656	9beb94c2-f47d-4b48-9281-54ec00cf0758	2020-11-01	23641980.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	1006040.00	23138960.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110004
a0a6c0f7-8dbd-447b-b779-9ca2a9a05443	9beb94c2-f47d-4b48-9281-54ec00cf0758	2020-12-01	23138960.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	1509060.00	22635940.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120004
a0a6c0f7-8e48-4f64-a2ff-075dbf22ea9b	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-01-01	22635940.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	2012080.00	22132920.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010004
a0a6c0f7-8ee5-44a8-8976-2f47f693a1c0	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-02-01	22132920.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	2515100.00	21629900.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020004
a0a6c0f7-8fac-4a64-a416-0fd17f6c8ff9	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-03-01	21629900.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	3018120.00	21126880.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030004
a0a6c0f7-909c-4500-9790-c8e72d1f0724	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-04-01	21126880.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	3521140.00	20623860.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040004
a0a6c0f7-915a-416a-bd7c-2ded4a76af4d	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-05-01	20623860.00	0.00	0.00	0.00	0.00	0.00	0.00	503020.00	4024160.00	20120840.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050004
a0a6c0f7-91f9-4c2e-8767-1e81277b8ca5	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-06-01	20120840.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	4527181.00	19617819.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060004
a0a6c0f7-9292-44d0-a446-a9fd05fa44f3	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-07-01	19617819.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	5030202.00	19114798.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070004
a0a6c0f7-932c-4b6d-9ded-e5abb80d7051	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-08-01	19114798.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	5533223.00	18611777.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080004
a0a6c0f7-9452-40b6-b092-a9ab9e83ac56	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-09-01	18611777.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	6036244.00	18108756.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090004
a0a6c0f7-94f2-47ed-8714-dc6082b1d2fa	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-10-01	18108756.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	6539265.00	17605735.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100004
a0a6c0f7-95cf-4d5e-b159-9648049e0861	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-11-01	17605735.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	7042286.00	17102714.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110004
a0a6c0f7-9664-4dfa-b2cb-38c539422ded	9beb94c2-f47d-4b48-9281-54ec00cf0758	2021-12-01	17102714.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	7545307.00	16599693.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120004
a0a6c0f7-96ff-4174-8cb8-607f61bb52ae	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-01-01	16599693.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	8048328.00	16096672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010004
a0a6c0f7-9777-4f64-8f2c-a63a7b0e9bc7	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-02-01	16096672.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	8551349.00	15593651.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020004
a0a6c0f7-9813-4cae-a829-6917373f8471	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-03-01	15593651.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	9054370.00	15090630.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030004
a0a6c0f7-98d2-4707-9450-ed620d7e4014	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-04-01	15090630.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	9557391.00	14587609.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040004
a0a6c0f7-996f-415c-bb20-233e1e5466db	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-05-01	14587609.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	10060412.00	14084588.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050004
a0a6c0f7-9c2f-4891-8cef-3bc313f03e40	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-06-01	14084588.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	10563433.00	13581567.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060004
a0a6c0f7-9d56-4d99-a33d-b522715fe883	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-07-01	13581567.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	11066454.00	13078546.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070004
a0a6c0f7-9e24-4085-92c6-6ae932f0279d	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-08-01	13078546.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	11569475.00	12575525.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080004
a0a6c0f7-9ee0-4430-b0b1-723cd9a4f8d8	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-09-01	12575525.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	12072496.00	12072504.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22090004
a0a6c0f7-9f6e-4100-80ee-38625dbece97	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-10-01	12072504.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	12575517.00	11569483.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22100004
a0a6c0f7-a03f-497d-96ec-7c261e1f9dac	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-11-01	11569483.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	13078538.00	11066462.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22110004
a0a6c0f7-a0fc-41cf-8ffd-69c2e2b27f0a	9beb94c2-f47d-4b48-9281-54ec00cf0758	2022-12-01	11066462.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	13581559.00	10563441.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22120004
a0a6c0f7-a1b7-4f2b-81b9-785a9e976706	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-01-01	10563441.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	14084580.00	10060420.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23010004
a0a6c0f7-a24c-467b-a9c6-39079b7235f2	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-02-01	10060420.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	14587601.00	9557399.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23020004
a0a6c0f7-a2d3-4042-914e-058ba77d663d	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-03-01	9557399.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	15090622.00	9054378.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23030004
a0a6c0f7-a43d-43ed-8481-0fd1cef83700	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-04-01	9054378.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	15593643.00	8551357.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23040004
a0a6c0f7-a570-4113-a354-67a26a141182	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-05-01	8551357.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	16096664.00	8048336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23050004
a0a6c0f7-a642-4b03-8af9-901247807bf8	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-06-01	8048336.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	16599685.00	7545315.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23060004
a0a6c0f7-a6ed-4aac-b945-7239ab86c25b	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-07-01	7545315.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	17102706.00	7042294.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23070004
a0a6c0f7-a7e3-464b-9904-0c446cbca922	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-08-01	7042294.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	17605727.00	6539273.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23080004
a0a6c0f7-a8fe-435b-9fd5-bc695da761b0	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-09-01	6539273.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	18108748.00	6036252.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23090004
a0a6c0f7-a9e3-429f-9832-695bf5efcfee	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-10-01	6036252.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	18611769.00	5533231.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23100003
a0a6c0f7-aada-40b4-b853-49cee7058b43	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-11-01	5533231.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	19114790.00	5030210.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23110003
a0a6c0f7-abac-48ea-8dae-c82ba5891dc5	9beb94c2-f47d-4b48-9281-54ec00cf0758	2023-12-01	5030210.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	19617811.00	4527189.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23120003
a0a6c0f7-ac62-408d-9591-ca0a4aa05319	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-01-01	4527189.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	20120832.00	4024168.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24010003
a0a6c0f7-ad3e-47f8-90bc-5db2dee6b1f4	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-02-01	4024168.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	20623853.00	3521147.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24020003
a0a6c0f7-adde-4e14-a0ad-428f52acdb28	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-03-01	3521147.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	21126874.00	3018126.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24030003
a0a6c0f7-ae83-4767-85b4-635efc2a461b	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-04-01	3018126.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	21629895.00	2515105.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24040003
a0a6c0f7-af1a-4916-abf2-da6932b04795	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-05-01	2515105.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	22132916.00	2012084.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24050003
a0a6c0f7-afb6-4f77-b84f-e36b6f21184a	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-06-01	2012084.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	22635937.00	1509063.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24060003
a0a6c0f7-b059-425b-a678-b807cbc46aac	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-07-01	1509063.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	23138958.00	1006042.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24070003
a0a6c0f7-b130-48c7-85dd-b08007c267db	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-08-01	1006042.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	23641979.00	503021.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24080001
a0a6c0f7-b1f6-43bb-b560-f08cdacb8edc	9beb94c2-f47d-4b48-9281-54ec00cf0758	2024-09-01	503021.00	0.00	0.00	0.00	0.00	0.00	0.00	503021.00	24145000.00	0.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24090001
a0a6c0f7-c25b-4896-b910-478cd8df6379	c88e2c69-914f-403e-ab36-0a9322d6591f	2020-11-01	25520000.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	531666.00	24988334.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110005
a0a6c0f7-c317-4b9c-98b0-af4d27cec2a4	c88e2c69-914f-403e-ab36-0a9322d6591f	2020-12-01	24988334.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1063332.00	24456668.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120005
a0a6c0f7-c3aa-40ed-8d59-689170a2b0bd	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-01-01	24456668.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1594998.00	23925002.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010005
a0a6c0f7-c448-443a-9bdd-21c9e2c92e5a	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-02-01	23925002.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2126664.00	23393336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020005
a0a6c0f7-c4cd-408b-a7c7-67f367b91188	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-03-01	23393336.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2658330.00	22861670.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030005
a0a6c0f7-c56e-40a9-a37f-ad4570aeb9e0	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-04-01	22861670.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3189996.00	22330004.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040005
a0a6c0f7-c700-42ad-a5b8-bd1f29b1256d	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-05-01	22330004.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3721662.00	21798338.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050005
a0a6c0f7-c7ae-408a-9489-60eac532a312	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-06-01	21798338.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4253328.00	21266672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060005
a0a6c0f7-c84c-486d-85bc-492957e16400	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-07-01	21266672.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4784994.00	20735006.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070005
a0a6c0f7-c976-416e-9f2c-85674bcf0a75	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-08-01	20735006.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5316660.00	20203340.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080005
a0a6c0f7-cb57-4b05-82e0-cea2220d9f3b	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-09-01	20203340.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5848326.00	19671674.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090005
a0a6c0f7-cc83-4a00-a865-2e648d2ad7ad	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-10-01	19671674.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6379992.00	19140008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100005
a0a6c0f7-cd77-4b82-b715-ffa7ba830174	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-11-01	19140008.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6911658.00	18608342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110005
a0a6c0f7-ce1f-41a4-877e-bebca85cd724	c88e2c69-914f-403e-ab36-0a9322d6591f	2021-12-01	18608342.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7443324.00	18076676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120005
a0a6c0f7-cee5-48e6-bbed-ac7240de7b5e	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-01-01	18076676.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7974990.00	17545010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010005
a0a6c0f7-d00c-4942-bd0a-5c9ffd2fff3c	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-02-01	17545010.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	8506656.00	17013344.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020005
a0a6c0f7-d102-4957-b0f2-8ef47ba74179	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-03-01	17013344.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9038323.00	16481677.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030005
a0a6c0f7-d1ef-4acb-bd09-4e5c7cd1fcce	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-04-01	16481677.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9569990.00	15950010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040005
a0a6c0f7-d30f-4e01-834e-f26ff3245cbf	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-05-01	15950010.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10101657.00	15418343.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050005
a0a6c0f7-d409-47bd-83cb-743c711b4e66	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-06-01	15418343.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10633324.00	14886676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060005
a0a6c0f7-d4ee-41ed-a9ad-f2c5e1b22035	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-07-01	14886676.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11164991.00	14355009.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070005
a0a6c0f7-d6a0-42c6-a203-4d7f24cea7f5	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-08-01	14355009.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11696658.00	13823342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080005
a0a6c0f7-d7be-465a-b13a-87a50cb6111f	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-09-01	13823342.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12228325.00	13291675.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22090005
a0a6c0f7-d8ab-47e4-b4fe-fb9869d66873	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-10-01	13291675.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12759992.00	12760008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22100005
a0a6c0f7-d9ae-4e1a-8471-8d6df3b7c439	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-11-01	12760008.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13291659.00	12228341.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22110005
a0a6c0f7-daa9-4544-a254-34ef88250945	c88e2c69-914f-403e-ab36-0a9322d6591f	2022-12-01	12228341.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13823326.00	11696674.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22120005
a0a6c0f7-db70-4df1-bdb3-54000f949a73	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-01-01	11696674.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14354993.00	11165007.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23010005
a0a6c0f7-dc49-44ca-8153-837ec56f8693	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-02-01	11165007.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14886660.00	10633340.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23020005
a0a6c0f7-dd1d-4eea-8815-314809cbdcec	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-03-01	10633340.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15418327.00	10101673.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23030005
a0a6c0f7-dded-459b-a58b-bf52f3648e17	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-04-01	10101673.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15949994.00	9570006.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23040005
a0a6c0f7-debb-4d5b-a720-ba78c3cbb10f	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-05-01	9570006.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	16481661.00	9038339.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23050005
a0a6c0f7-dfcd-4ec9-8425-0372fd3847ce	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-06-01	9038339.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17013328.00	8506672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23060005
a0a6c0f7-e0ad-4c14-bdc5-aa9638c40122	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-07-01	8506672.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17544995.00	7975005.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23070005
a0a6c0f7-e186-42cf-be39-20bdcfd3356e	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-08-01	7975005.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18076662.00	7443338.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23080005
a0a6c0f7-e235-4148-94ef-e7d717117270	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-09-01	7443338.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18608329.00	6911671.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23090005
a0a6c0f7-e334-4783-9e37-9f2027fc1795	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-10-01	6911671.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19139996.00	6380004.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23100004
a0a6c0f7-e40b-481b-8311-5140a1dba63a	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-11-01	6380004.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19671663.00	5848337.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23110004
a0a6c0f7-e5f4-47db-a415-3b519228927e	c88e2c69-914f-403e-ab36-0a9322d6591f	2023-12-01	5848337.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20203330.00	5316670.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP23120004
a0a6c0f7-e718-4b91-b3e8-9831d01fa67a	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-01-01	5316670.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20734997.00	4785003.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24010004
a0a6c0f7-e80e-4966-9122-07e5a58b619a	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-02-01	4785003.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21266664.00	4253336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24020004
a0a6c0f7-e942-4b42-83db-5e62d41ade1d	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-03-01	4253336.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21798331.00	3721669.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24030004
a0a6c0f7-ec65-4696-a725-eda9a232a3c0	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-04-01	3721669.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22329998.00	3190002.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24040004
a0a6c0f7-ed1a-44f6-bd1c-e7cbefae49f3	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-05-01	3190002.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22861665.00	2658335.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24050004
a0a6c0f7-edf0-4d45-bff4-17814ce5021e	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-06-01	2658335.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23393332.00	2126668.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24060004
a0a6c0f7-eeb1-4031-bf1b-1c91c0e6044f	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-07-01	2126668.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23924999.00	1595001.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24070004
a0a6c0f7-ef50-4908-b8c8-3c5819daa451	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-08-01	1595001.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24456666.00	1063334.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24080002
a0a6c0f7-f022-4f52-8628-107b4618872e	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-09-01	1063334.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24988333.00	531667.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24090002
a0a6c0f7-f1ac-4771-a7e6-0e0f7095c19c	c88e2c69-914f-403e-ab36-0a9322d6591f	2024-10-01	531667.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	25520000.00	0.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP24100001
a0a6c0f7-ff1f-456b-9979-e38d8099921d	9580ea1b-0f93-4c89-b167-a089131d5761	2020-11-01	25520000.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	531666.00	24988334.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20110006
a0a6c0f7-ffe7-4b8c-890b-b4f777330f76	9580ea1b-0f93-4c89-b167-a089131d5761	2020-12-01	24988334.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1063332.00	24456668.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP20120006
a0a6c0f8-011f-4bfa-81c9-4ff990cbf626	9580ea1b-0f93-4c89-b167-a089131d5761	2021-01-01	24456668.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1594998.00	23925002.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21010006
a0a6c0f8-01f3-4c10-b060-d7063d51e76b	9580ea1b-0f93-4c89-b167-a089131d5761	2021-02-01	23925002.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2126664.00	23393336.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21020006
a0a6c0f8-02a9-446c-9d2e-345edea09238	9580ea1b-0f93-4c89-b167-a089131d5761	2021-03-01	23393336.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2658330.00	22861670.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21030006
a0a6c0f8-0345-4684-8f82-2b75e38a2da7	9580ea1b-0f93-4c89-b167-a089131d5761	2021-04-01	22861670.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3189996.00	22330004.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21040006
a0a6c0f8-045a-4c09-873b-c45f7ff38ea4	9580ea1b-0f93-4c89-b167-a089131d5761	2021-05-01	22330004.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3721662.00	21798338.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21050006
a0a6c0f8-05e2-4fec-9e15-a5339af3739d	9580ea1b-0f93-4c89-b167-a089131d5761	2021-06-01	21798338.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4253328.00	21266672.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21060006
a0a6c0f8-06d2-4a01-952c-5fa51c320570	9580ea1b-0f93-4c89-b167-a089131d5761	2021-07-01	21266672.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4784994.00	20735006.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21070006
a0a6c0f8-08f5-4305-a6e3-6148cb684101	9580ea1b-0f93-4c89-b167-a089131d5761	2021-08-01	20735006.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5316660.00	20203340.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21080006
a0a6c0f8-0a37-4419-8951-3fb2a0631858	9580ea1b-0f93-4c89-b167-a089131d5761	2021-09-01	20203340.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5848326.00	19671674.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21090006
a0a6c0f8-0b47-4e97-b63e-bc5e82bc8283	9580ea1b-0f93-4c89-b167-a089131d5761	2021-10-01	19671674.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6379992.00	19140008.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21100006
a0a6c0f8-0c09-4694-9c14-9703524a45ed	9580ea1b-0f93-4c89-b167-a089131d5761	2021-11-01	19140008.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6911658.00	18608342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21110006
a0a6c0f8-0cbf-4fd0-b0d3-7b7800046027	9580ea1b-0f93-4c89-b167-a089131d5761	2021-12-01	18608342.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7443324.00	18076676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP21120006
a0a6c0f8-0e25-440b-bf84-e8321bf1f628	9580ea1b-0f93-4c89-b167-a089131d5761	2022-01-01	18076676.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7974990.00	17545010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22010006
a0a6c0f8-0f1c-4a9e-bcba-fc02a2724556	9580ea1b-0f93-4c89-b167-a089131d5761	2022-02-01	17545010.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	8506656.00	17013344.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22020006
a0a6c0f8-0fe9-48df-88c5-9fd33d6d679c	9580ea1b-0f93-4c89-b167-a089131d5761	2022-03-01	17013344.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9038323.00	16481677.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22030006
a0a6c0f8-10a4-4364-9d9a-c9e30759d67f	9580ea1b-0f93-4c89-b167-a089131d5761	2022-04-01	16481677.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9569990.00	15950010.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22040006
a0a6c0f8-1222-4799-b40a-a6cd7584baa4	9580ea1b-0f93-4c89-b167-a089131d5761	2022-05-01	15950010.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10101657.00	15418343.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22050006
a0a6c0f8-13b1-4353-8c19-7a9922d1c4bf	9580ea1b-0f93-4c89-b167-a089131d5761	2022-06-01	15418343.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10633324.00	14886676.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22060006
a0a6c0f8-14d5-4e4e-8064-6514dfdf7f70	9580ea1b-0f93-4c89-b167-a089131d5761	2022-07-01	14886676.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11164991.00	14355009.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22070006
a0a6c0f8-17e8-42c5-9699-c2713f02e40e	9580ea1b-0f93-4c89-b167-a089131d5761	2022-08-01	14355009.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11696658.00	13823342.00	2025-12-22 12:17:04	2025-12-22 12:17:04	DEP22080006
a0a6c0f8-1900-4c6c-8d32-f64b822b084f	9580ea1b-0f93-4c89-b167-a089131d5761	2022-09-01	13823342.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12228325.00	13291675.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090006
a0a6c0f8-19de-4c42-9b75-375ba079b597	9580ea1b-0f93-4c89-b167-a089131d5761	2022-10-01	13291675.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12759992.00	12760008.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100006
a0a6c0f8-1ab6-4bf8-8453-5ad6ab643aac	9580ea1b-0f93-4c89-b167-a089131d5761	2022-11-01	12760008.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13291659.00	12228341.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110006
a0a6c0f8-1b84-4f6c-ace5-ec5795368d2b	9580ea1b-0f93-4c89-b167-a089131d5761	2022-12-01	12228341.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13823326.00	11696674.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120006
a0a6c0f8-1c49-40ba-9fcf-dd34ccc83381	9580ea1b-0f93-4c89-b167-a089131d5761	2023-01-01	11696674.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14354993.00	11165007.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010006
a0a6c0f8-1d12-4b58-b074-c706935c0cde	9580ea1b-0f93-4c89-b167-a089131d5761	2023-02-01	11165007.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14886660.00	10633340.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020006
a0a6c0f8-1dc6-4bc0-81e2-38e11fd88be0	9580ea1b-0f93-4c89-b167-a089131d5761	2023-03-01	10633340.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15418327.00	10101673.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030006
a0a6c0f8-1e6e-413c-9a25-0f725071a91a	9580ea1b-0f93-4c89-b167-a089131d5761	2023-04-01	10101673.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15949994.00	9570006.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040006
a0a6c0f8-1f0b-4312-8424-a3d349137d47	9580ea1b-0f93-4c89-b167-a089131d5761	2023-05-01	9570006.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	16481661.00	9038339.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050006
a0a6c0f8-1f9e-46c9-99d6-3dc24acfef75	9580ea1b-0f93-4c89-b167-a089131d5761	2023-06-01	9038339.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17013328.00	8506672.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060006
a0a6c0f8-2033-4b7d-b56d-ca2479ed07fd	9580ea1b-0f93-4c89-b167-a089131d5761	2023-07-01	8506672.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17544995.00	7975005.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070006
a0a6c0f8-20d8-4e6a-828d-242b1dd1f696	9580ea1b-0f93-4c89-b167-a089131d5761	2023-08-01	7975005.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18076662.00	7443338.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080006
a0a6c0f8-21a2-40f5-8eb5-202f5ffff179	9580ea1b-0f93-4c89-b167-a089131d5761	2023-09-01	7443338.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18608329.00	6911671.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090006
a0a6c0f8-22f4-4e1c-b0a4-c394a1fef6de	9580ea1b-0f93-4c89-b167-a089131d5761	2023-10-01	6911671.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19139996.00	6380004.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100005
a0a6c0f8-2554-4f88-b822-a8f73880fc46	9580ea1b-0f93-4c89-b167-a089131d5761	2023-11-01	6380004.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19671663.00	5848337.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110005
a0a6c0f8-2638-48de-a36e-ce64f534acc6	9580ea1b-0f93-4c89-b167-a089131d5761	2023-12-01	5848337.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20203330.00	5316670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120005
a0a6c0f8-2b47-4374-b2b5-925baacbc653	9580ea1b-0f93-4c89-b167-a089131d5761	2024-01-01	5316670.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20734997.00	4785003.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010005
a0a6c0f8-2c19-419b-a02c-a4aa333101c4	9580ea1b-0f93-4c89-b167-a089131d5761	2024-02-01	4785003.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21266664.00	4253336.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020005
a0a6c0f8-2ce5-4c89-884c-040a72d02e6f	9580ea1b-0f93-4c89-b167-a089131d5761	2024-03-01	4253336.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21798331.00	3721669.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030005
a0a6c0f8-2ea8-43ab-ba0e-7c8cd9fd5d22	9580ea1b-0f93-4c89-b167-a089131d5761	2024-04-01	3721669.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22329998.00	3190002.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040005
a0a6c0f8-2f77-4ec1-a48d-64aa40bf1044	9580ea1b-0f93-4c89-b167-a089131d5761	2024-05-01	3190002.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22861665.00	2658335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050005
a0a6c0f8-3041-4802-9854-d37fd0c087dd	9580ea1b-0f93-4c89-b167-a089131d5761	2024-06-01	2658335.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23393332.00	2126668.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060005
a0a6c0f8-310c-4230-8fab-11b5e0c3c91f	9580ea1b-0f93-4c89-b167-a089131d5761	2024-07-01	2126668.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23924999.00	1595001.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070005
a0a6c0f8-31e5-4920-8829-8e9729ea6654	9580ea1b-0f93-4c89-b167-a089131d5761	2024-08-01	1595001.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24456666.00	1063334.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080003
a0a6c0f8-329f-4a45-9af2-32bf000fc4b0	9580ea1b-0f93-4c89-b167-a089131d5761	2024-09-01	1063334.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24988333.00	531667.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090003
a0a6c0f8-3349-4a04-b28f-8c15d1579fea	9580ea1b-0f93-4c89-b167-a089131d5761	2024-10-01	531667.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	25520000.00	0.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100002
a0a6c0f8-4566-44c1-b82a-e89313c09acb	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2020-11-01	25520000.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	531666.00	24988334.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP20110007
a0a6c0f8-4789-4a7f-8ba2-b427de8bfe6c	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2020-12-01	24988334.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1063332.00	24456668.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP20120007
a0a6c0f8-489b-4d10-950c-a1e2c1aef26c	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-01-01	24456668.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	1594998.00	23925002.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21010007
a0a6c0f8-4975-4942-a5a5-f2e0ab62d4f0	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-02-01	23925002.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2126664.00	23393336.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21020007
a0a6c0f8-4a4b-49c1-abd4-29ae9564b563	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-03-01	23393336.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	2658330.00	22861670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21030007
a0a6c0f8-4b8b-494b-b7df-5d88f9d29418	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-04-01	22861670.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3189996.00	22330004.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21040007
a0a6c0f8-4c71-4d55-b888-e2f3bbfb787c	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-05-01	22330004.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	3721662.00	21798338.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21050007
a0a6c0f8-4d5d-4d38-b491-d0108ca27efe	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-06-01	21798338.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4253328.00	21266672.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21060007
a0a6c0f8-4e5a-4322-967b-907631e116e8	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-07-01	21266672.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	4784994.00	20735006.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21070007
a0a6c0f8-4f04-4c79-982b-46db05e52952	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-08-01	20735006.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5316660.00	20203340.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21080007
a0a6c0f8-4fea-47a9-b22d-0dffb53ebe72	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-09-01	20203340.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	5848326.00	19671674.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21090007
a0a6c0f8-50bb-4bbe-8eb3-bc1e3b646103	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-10-01	19671674.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6379992.00	19140008.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21100007
a0a6c0f8-5186-409b-aa1d-d959d5a859bc	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-11-01	19140008.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	6911658.00	18608342.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21110007
a0a6c0f8-523b-4a28-a8c5-08b3c39fea71	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2021-12-01	18608342.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7443324.00	18076676.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21120007
a0a6c0f8-52f5-494a-8b18-fd1410c4d62f	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-01-01	18076676.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	7974990.00	17545010.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22010007
a0a6c0f8-53e0-4831-bd36-227ea533710e	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-02-01	17545010.00	0.00	0.00	0.00	0.00	0.00	0.00	531666.00	8506656.00	17013344.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22020007
a0a6c0f8-5538-472a-a034-007e890a0a1b	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-03-01	17013344.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9038323.00	16481677.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22030007
a0a6c0f8-56aa-446e-a307-1e6b08f5f66f	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-04-01	16481677.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	9569990.00	15950010.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22040007
a0a6c0f8-57d9-429c-bcca-88636289f78a	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-05-01	15950010.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10101657.00	15418343.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22050007
a0a6c0f8-58e1-41fa-8ab9-42b03cb6034a	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-06-01	15418343.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	10633324.00	14886676.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22060007
a0a6c0f8-59d4-4a81-b642-66a3b8714e66	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-07-01	14886676.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11164991.00	14355009.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22070007
a0a6c0f8-5aac-4c5b-8543-267101ea77c9	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-08-01	14355009.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	11696658.00	13823342.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22080007
a0a6c0f8-5b89-43a7-9b23-6d091ab1c60f	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-09-01	13823342.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12228325.00	13291675.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090007
a0a6c0f8-5c89-4b44-a6fd-4fc2b645dfaf	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-10-01	13291675.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	12759992.00	12760008.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100007
a0a6c0f8-5de8-4cfb-a9cf-c5d8c5c52a68	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-11-01	12760008.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13291659.00	12228341.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110007
a0a6c0f8-5ea4-4f4e-acba-630f5ad75698	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2022-12-01	12228341.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	13823326.00	11696674.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120007
a0a6c0f8-5f7e-4194-b4cb-d5e5d7340fa0	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-01-01	11696674.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14354993.00	11165007.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010007
a0a6c0f8-6035-4dc0-87ff-9e87c66987bb	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-02-01	11165007.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	14886660.00	10633340.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020007
a0a6c0f8-60ea-43aa-b7e2-1dc2392cdcf1	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-03-01	10633340.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15418327.00	10101673.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030007
a0a6c0f8-6181-40d5-aab1-93978b730183	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-04-01	10101673.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	15949994.00	9570006.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040007
a0a6c0f8-62ff-4a58-a404-a86550522c8a	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-05-01	9570006.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	16481661.00	9038339.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050007
a0a6c0f8-6406-44b5-81a3-56731f25d6c5	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-06-01	9038339.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17013328.00	8506672.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060007
a0a6c0f8-64c3-46aa-988a-4267ae88247f	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-07-01	8506672.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	17544995.00	7975005.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070007
a0a6c0f8-659b-4ed3-acf7-268815092f5f	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-08-01	7975005.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18076662.00	7443338.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080007
a0a6c0f8-6745-4cc0-92d8-ccdb8037486d	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-09-01	7443338.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	18608329.00	6911671.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090007
a0a6c0f8-67f4-489b-a4a8-1e62e00e8107	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-10-01	6911671.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19139996.00	6380004.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100006
a0a6c0f8-68c1-4ca3-bd41-61ad68469c71	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-11-01	6380004.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	19671663.00	5848337.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110006
a0a6c0f8-696a-4869-857f-ffeaabcb4bd1	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2023-12-01	5848337.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20203330.00	5316670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120006
a0a6c0f8-6a25-4e01-adca-773c6ad383fb	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-01-01	5316670.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	20734997.00	4785003.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010006
a0a6c0f8-6ad2-4297-bd8a-3243dac690e0	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-02-01	4785003.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21266664.00	4253336.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020006
a0a6c0f8-6b9c-4c88-bacf-ebc3819a90f1	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-03-01	4253336.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	21798331.00	3721669.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030006
a0a6c0f8-6c68-4452-8799-5bb4a0c68460	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-04-01	3721669.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22329998.00	3190002.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040006
a0a6c0f8-6d2f-4820-8d7b-4206307b6beb	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-05-01	3190002.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	22861665.00	2658335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050006
a0a6c0f8-6f04-44f0-8839-491e28dfb8ea	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-06-01	2658335.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23393332.00	2126668.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060006
a0a6c0f8-6fe5-4d08-8ed9-933709eb6740	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-07-01	2126668.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	23924999.00	1595001.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070006
a0a6c0f8-70a1-4745-917f-b9e4ec5c8004	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-08-01	1595001.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24456666.00	1063334.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080004
a0a6c0f8-7149-41a9-99fc-92f3e185dde8	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-09-01	1063334.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	24988333.00	531667.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090004
a0a6c0f8-71fa-4e07-bd4a-4e6f788806df	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2024-10-01	531667.00	0.00	0.00	0.00	0.00	0.00	0.00	531667.00	25520000.00	0.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100003
a0a6c0f8-7f9f-442e-a40c-967384a4a668	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2020-12-01	61297800000.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	175638395.00	61122161605.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP20120008
a0a6c0f8-8054-4c64-82ce-f70bb84ffe42	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-01-01	61122161605.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	351276790.00	60946523210.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21010008
a0a6c0f8-8105-49fc-8e22-077c51dce6c9	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-02-01	60946523210.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	526915185.00	60770884815.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21020008
a0a6c0f8-818d-4c1a-ba0f-575643a9e856	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-03-01	60770884815.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	702553580.00	60595246420.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21030008
a0a6c0f8-8220-485f-af70-8436aa880e00	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-04-01	60595246420.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	878191975.00	60419608025.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21040008
a0a6c0f8-8328-498d-acee-57426943b916	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-05-01	60419608025.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1053830370.00	60243969630.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21050008
a0a6c0f8-8432-4ee9-9c6b-4d2078423d32	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-06-01	60243969630.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1229468765.00	60068331235.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21060008
a0a6c0f8-84dc-49dc-b43c-59926fb81c99	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-07-01	60068331235.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1405107160.00	59892692840.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21070008
a0a6c0f8-859b-4612-b2ef-e125ccf757ac	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-08-01	59892692840.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1580745555.00	59717054445.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21080008
a0a6c0f8-8646-4b91-9928-a3ead91a46b0	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-09-01	59717054445.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1756383950.00	59541416050.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21090008
a0a6c0f8-86f2-4415-bff2-2f66785ffdd7	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-10-01	59541416050.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1932022345.00	59365777655.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21100008
a0a6c0f8-87ae-458b-8c0b-74258d4a3045	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-11-01	59365777655.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2107660740.00	59190139260.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21110008
a0a6c0f8-8878-47da-9a6f-2aa467e52358	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2021-12-01	59190139260.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2283299135.00	59014500865.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21120008
a0a6c0f8-8a48-488a-8303-61f812e90987	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-01-01	59014500865.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2458937530.00	58838862470.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22010008
a0a6c0f8-8b10-474f-a4a3-3c1144277b4d	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-02-01	58838862470.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2634575925.00	58663224075.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22020008
a0a6c0f8-8f9b-42f0-9b41-59ba6b616755	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-03-01	58663224075.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2810214320.00	58487585680.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22030008
a0a6c0f8-9086-4e72-b7e4-121d432f4809	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-04-01	58487585680.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2985852715.00	58311947285.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22040008
a0a6c0f8-9234-412e-8e2b-b893647394ea	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-05-01	58311947285.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3161491110.00	58136308890.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22050008
a0a6c0f8-9444-48e5-9e67-043a73137698	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-06-01	58136308890.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3337129505.00	57960670495.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22060008
a0a6c0f8-9526-4d1a-81d0-6996702d8b0e	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-07-01	57960670495.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3512767900.00	57785032100.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22070008
a0a6c0f8-9624-47a2-a505-1d0a76c57d04	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-08-01	57785032100.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3688406295.00	57609393705.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22080008
a0a6c0f8-9717-457f-a210-542bc84d7ddd	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-09-01	57609393705.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3864044690.00	57433755310.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090008
a0a6c0f8-97de-4b6d-b01d-44a57193aeed	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-10-01	57433755310.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4039683085.00	57258116915.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100008
a0a6c0f8-98cf-433e-9eaf-40bf37dc0d6f	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-11-01	57258116915.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4215321480.00	57082478520.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110008
a0a6c0f8-9978-4547-b399-300727f9dab7	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2022-12-01	57082478520.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4390959875.00	56906840125.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120008
a0a6c0f8-9a54-4148-8d2e-3bd439ff2d18	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-01-01	56906840125.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4566598270.00	56731201730.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010008
a0a6c0f8-9b0e-4190-a52e-865d6255aaa9	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-02-01	56731201730.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4742236665.00	56555563335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020008
a0a6c0f8-9bc5-43e5-8091-2f0b1b347d42	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-03-01	56555563335.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4917875060.00	56379924940.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030008
a0a6c0f8-9cce-4083-8562-03d489ab0327	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-04-01	56379924940.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5093513455.00	56204286545.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040008
a0a6c0f8-9e7d-409e-bb7a-0ffb2e778b47	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-05-01	56204286545.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5269151850.00	56028648150.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050008
a0a6c0f8-a23c-4efd-8268-1cbd7807371a	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-06-01	56028648150.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5444790245.00	55853009755.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060008
a0a6c0f8-a455-4ea8-a89f-1d7cbcda18e9	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-07-01	55853009755.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5620428640.00	55677371360.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070008
a0a6c0f8-a52d-4a21-86f8-775aa09e13bc	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-08-01	55677371360.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5796067035.00	55501732965.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080008
a0a6c0f8-a5e0-4866-a49b-10f2e5374d82	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-09-01	55501732965.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5971705430.00	55326094570.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090008
a0a6c0f8-a684-4814-80b4-6b1d9d434c47	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-10-01	55326094570.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6147343825.00	55150456175.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100007
a0a6c0f8-a7b3-4302-b2a6-04f80f58ee46	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-11-01	55150456175.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6322982220.00	54974817780.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110007
a0a6c0f8-a8f0-4230-9adb-b70d5c4fadb6	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2023-12-01	54974817780.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6498620615.00	54799179385.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120007
a0a6c0f8-ac5e-4a75-8084-93e83fe087ea	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-01-01	54799179385.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6674259010.00	54623540990.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010007
a0a6c0f8-ad70-4ff1-96a6-534b764c9370	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-02-01	54623540990.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6849897405.00	54447902595.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020007
a0a6c0f8-b08f-41d2-8a97-c2efb04128f1	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-03-01	54447902595.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7025535800.00	54272264200.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030007
a0a6c0f8-b1f4-41ef-85ac-f77ffc6512c7	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-04-01	54272264200.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7201174195.00	54096625805.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040007
a0a6c0f8-b345-48be-b28a-f6450db12bbf	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-05-01	54096625805.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7376812590.00	53920987410.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050007
a0a6c0f8-b463-438b-86c9-34658412ad18	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-06-01	53920987410.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7552450985.00	53745349015.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060007
a0a6c0f8-b874-4904-854b-310c76f242b3	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-07-01	53745349015.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7728089380.00	53569710620.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070007
a0a6c0f8-b970-4c9a-9472-e70bf17e942a	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-08-01	53569710620.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7903727775.00	53394072225.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080005
a0a6c0f8-ba2d-4cb2-b120-21eb554bd37e	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-09-01	53394072225.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8079366170.00	53218433830.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090005
a0a6c0f8-bad2-4e08-aa77-a1966ae56535	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-10-01	53218433830.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8255004565.00	53042795435.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100004
a0a6c0f8-bbf7-477e-b499-34fce3ff671f	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-11-01	53042795435.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8430642960.00	52867157040.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24110001
a0a6c0f8-bcc9-45d2-abe0-d292c0441e62	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2024-12-01	52867157040.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8606281355.00	52691518645.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24120001
a0a6c0f8-bd9a-4661-a5c3-202f1292ac4c	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-01-01	52691518645.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8781919750.00	52515880250.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25010001
a0a6c0f8-be7e-417c-b918-14f965616ac8	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-02-01	52515880250.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8957558145.00	52340241855.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25020001
a0a6c0f8-bf59-416d-955e-69224d0bc939	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-03-01	52340241855.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9133196540.00	52164603460.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25030001
a0a6c0f8-c025-4532-826a-19283de69d3a	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-04-01	52164603460.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9308834935.00	51988965065.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25040001
a0a6c0f8-c18e-403d-a366-6d044bbf101e	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-05-01	51988965065.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9484473330.00	51813326670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25050001
a0a6c0f8-c283-4c55-b1f8-ebf283718bdf	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-06-01	51813326670.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9660111725.00	51637688275.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25060001
a0a6c0f8-c378-4b4b-b042-7e42f27e90d7	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-07-01	51637688275.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9835750120.00	51462049880.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25070001
a0a6c0f8-c4b4-4ad6-b24c-bec273b400ee	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-08-01	51462049880.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10011388515.00	51286411485.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25080001
a0a6c0f8-c5cb-454f-92b6-540ecb329824	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-09-01	51286411485.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10187026910.00	51110773090.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25090001
a0a7306c-4a83-4df4-8849-644f8bcd08f2	9beb94c2-f47d-4b48-9281-54ec00cf0758	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	24145000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100404
a0a6c0f8-d7d4-4bd2-a222-c2917043f997	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2020-12-01	61297800000.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	175638395.00	61122161605.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP20120009
a0a6c0f8-d8ee-41f8-b60b-27e629251a9f	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-01-01	61122161605.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	351276790.00	60946523210.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21010009
a0a6c0f8-d9b7-49c1-bb0f-362ccaaef7b7	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-02-01	60946523210.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	526915185.00	60770884815.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21020009
a0a6c0f8-da7c-4166-b7b9-12df075d19a2	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-03-01	60770884815.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	702553580.00	60595246420.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21030009
a0a6c0f8-dc9b-4fca-bc50-6bf465d28f68	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-04-01	60595246420.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	878191975.00	60419608025.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21040009
a0a6c0f8-dd61-49c4-93d8-770989d902ed	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-05-01	60419608025.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1053830370.00	60243969630.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21050009
a0a6c0f8-de1d-4500-830b-eca793f088c7	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-06-01	60243969630.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1229468765.00	60068331235.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21060009
a0a6c0f8-dec1-4999-8362-f87d6ffb9d2e	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-07-01	60068331235.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1405107160.00	59892692840.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21070009
a0a6c0f8-df7c-4c8b-a2ce-b759cb06b2d1	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-08-01	59892692840.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1580745555.00	59717054445.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21080009
a0a6c0f8-e01e-403b-be68-09281f9c81f0	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-09-01	59717054445.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1756383950.00	59541416050.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21090009
a0a6c0f8-e0be-4688-bbe8-44c1841faea1	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-10-01	59541416050.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1932022345.00	59365777655.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21100009
a0a6c0f8-e16a-4b2c-b2b4-e22a821f51f1	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-11-01	59365777655.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2107660740.00	59190139260.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21110009
a0a6c0f8-e210-41dd-9755-c33f9974c516	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2021-12-01	59190139260.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2283299135.00	59014500865.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21120009
a0a6c0f8-e410-49b1-b654-0b491ad991a4	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-01-01	59014500865.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2458937530.00	58838862470.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22010009
a0a6c0f8-e4f4-4e24-939c-3b73d85499b3	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-02-01	58838862470.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2634575925.00	58663224075.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22020009
a0a6c0f8-e5f1-49b5-929d-dc2d9dfe3a29	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-03-01	58663224075.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2810214320.00	58487585680.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22030009
a0a6c0f8-e6bb-4084-be32-218834d7a350	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-04-01	58487585680.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2985852715.00	58311947285.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22040009
a0a6c0f8-e757-488d-90b6-d82c6463cc4c	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-05-01	58311947285.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3161491110.00	58136308890.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22050009
a0a6c0f8-e892-448c-a808-f5cccee3290e	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-06-01	58136308890.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3337129505.00	57960670495.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22060009
a0a6c0f8-e958-45d0-acec-d9f2fe8bce3a	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-07-01	57960670495.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3512767900.00	57785032100.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22070009
a0a6c0f8-ea06-417a-95ff-586f1269fbf4	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-08-01	57785032100.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3688406295.00	57609393705.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22080009
a0a6c0f8-eab8-4e4f-ba7f-695f03b7d4c8	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-09-01	57609393705.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3864044690.00	57433755310.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090009
a0a6c0f8-eb55-416b-8003-dbb7e5eb93b8	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-10-01	57433755310.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4039683085.00	57258116915.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100009
a0a6c0f8-ebf4-43ac-91cd-f1a3d9d4ad73	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-11-01	57258116915.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4215321480.00	57082478520.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110009
a0a6c0f8-ec86-41fb-a4bb-cdb382ede82a	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2022-12-01	57082478520.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4390959875.00	56906840125.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120009
a0a6c0f8-ed52-40c5-a6a7-fe86827ed6bf	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-01-01	56906840125.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4566598270.00	56731201730.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010009
a0a6c0f8-edea-4160-a2ce-0d40f2dd3aa7	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-02-01	56731201730.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4742236665.00	56555563335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020009
a0a6c0f8-eef3-4c22-84c3-0ef714fa0c77	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-03-01	56555563335.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4917875060.00	56379924940.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030009
a0a6c0f8-ef95-4a6e-b9e0-fa85acea2a22	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-04-01	56379924940.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5093513455.00	56204286545.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040009
a0a6c0f8-f04f-49b5-9531-efadd19a8e33	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-05-01	56204286545.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5269151850.00	56028648150.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050009
a0a6c0f8-f140-4884-bf9c-95b48e88b79c	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-06-01	56028648150.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5444790245.00	55853009755.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060009
a0a6c0f8-f246-4f3a-87a4-de4b97818bc5	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-07-01	55853009755.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5620428640.00	55677371360.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070009
a0a6c0f8-f2e3-41c5-a2e2-708e5b160b92	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-08-01	55677371360.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5796067035.00	55501732965.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080009
a0a6c0f8-f384-463a-81f3-eef0be8a256c	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-09-01	55501732965.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5971705430.00	55326094570.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090009
a0a6c0f8-f494-4671-a423-65c9b0e2de2e	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-10-01	55326094570.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6147343825.00	55150456175.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100008
a0a6c0f8-f6c1-4606-9d59-37889f576fcf	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-11-01	55150456175.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6322982220.00	54974817780.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110008
a0a6c0f8-f8ed-41a3-b56b-01fb1866ada7	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2023-12-01	54974817780.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6498620615.00	54799179385.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120008
a0a6c0f8-fa0b-4541-baa3-611a8afe388f	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-01-01	54799179385.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6674259010.00	54623540990.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010008
a0a6c0f8-fca1-43ba-8e2d-35a0865d0601	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-02-01	54623540990.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6849897405.00	54447902595.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020008
a0a6c0f8-fdec-4c0c-b133-ed722fd7c27c	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-03-01	54447902595.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7025535800.00	54272264200.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030008
a0a6c0f8-fefe-4de5-89e5-5e3fb365c1bf	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-04-01	54272264200.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7201174195.00	54096625805.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040008
a0a6c0f8-ffec-4d15-88ec-d81fd26252ef	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-05-01	54096625805.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7376812590.00	53920987410.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050008
a0a6c0f9-0196-4430-ba9b-450db4c11722	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-06-01	53920987410.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7552450985.00	53745349015.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060008
a0a6c0f9-02c8-4dc6-827d-829544f1c559	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-07-01	53745349015.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7728089380.00	53569710620.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070008
a0a6c0f9-0662-4bdc-a23e-ffff3a7a7b4b	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-08-01	53569710620.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7903727775.00	53394072225.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080006
a0a6c0f9-0878-4fa2-84ac-36e6e0bc149b	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-09-01	53394072225.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8079366170.00	53218433830.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090006
a0a6c0f9-099c-4282-8f5c-55ad0a0209cd	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-10-01	53218433830.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8255004565.00	53042795435.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100005
a0a6c0f9-0a61-468b-b64c-c3f204ab4414	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-11-01	53042795435.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8430642960.00	52867157040.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24110002
a0a6c0f9-0b18-4fb6-a8fe-262a30b9a8d4	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2024-12-01	52867157040.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8606281355.00	52691518645.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24120002
a0a6c0f9-0bc3-4b08-9d0d-8e537646df26	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-01-01	52691518645.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8781919750.00	52515880250.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25010002
a0a6c0f9-0ca9-44f1-a6f3-dc806062ad60	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-02-01	52515880250.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8957558145.00	52340241855.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25020002
a0a6c0f9-0d78-408f-9699-726015c0c997	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-03-01	52340241855.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9133196540.00	52164603460.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25030002
a0a6c0f9-0efd-4cd2-aca7-8a5ecfb82cdc	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-04-01	52164603460.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9308834935.00	51988965065.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25040002
a0a6c0f9-0fed-4102-ae69-241f39b12909	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-05-01	51988965065.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9484473330.00	51813326670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25050002
a0a6c0f9-109a-40d4-b25c-5f5e664d0e55	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-06-01	51813326670.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9660111725.00	51637688275.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25060002
a0a6c0f9-1160-4379-a7d0-e5a17f7f1a62	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-07-01	51637688275.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9835750120.00	51462049880.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25070002
a0a6c0f9-1212-4b5e-bc9b-2b08bf2725d1	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-08-01	51462049880.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10011388515.00	51286411485.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25080002
a0a6c0f9-12cb-4c8f-b138-0270aaf9b08b	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-09-01	51286411485.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10187026910.00	51110773090.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25090002
a0a7306c-4fa5-4955-ad49-b7ccb8cf9512	c88e2c69-914f-403e-ab36-0a9322d6591f	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100405
a0a6c0f9-26a8-445b-9609-742c3ed43abd	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2020-12-01	61297800000.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	175638395.00	61122161605.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP20120010
a0a6c0f9-2788-46ad-a75d-c386e4b04478	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-01-01	61122161605.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	351276790.00	60946523210.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21010010
a0a6c0f9-289f-4e6b-bc75-0df5c82c8409	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-02-01	60946523210.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	526915185.00	60770884815.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21020010
a0a6c0f9-2a26-4e84-b0d4-1e67c504d6be	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-03-01	60770884815.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	702553580.00	60595246420.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21030010
a0a6c0f9-2af9-41dd-94b9-33f9e1ecd0f7	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-04-01	60595246420.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	878191975.00	60419608025.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21040010
a0a6c0f9-2bdb-4de3-8b7f-093c59ca4c4f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-05-01	60419608025.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1053830370.00	60243969630.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21050010
a0a6c0f9-2c88-4e23-8055-0184f2231f61	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-06-01	60243969630.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1229468765.00	60068331235.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21060010
a0a6c0f9-2ddb-4d9c-80d2-d436a5fd14eb	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-07-01	60068331235.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1405107160.00	59892692840.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21070010
a0a6c0f9-2ea4-444c-a8b2-6ead269f730b	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-08-01	59892692840.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1580745555.00	59717054445.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21080010
a0a6c0f9-2f50-42eb-aea0-a809ba096570	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-09-01	59717054445.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1756383950.00	59541416050.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21090010
a0a6c0f9-2ff6-4cc0-a3f0-8244c1afc742	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-10-01	59541416050.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	1932022345.00	59365777655.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21100010
a0a6c0f9-30f4-46bf-8c7f-f9d1207d2391	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-11-01	59365777655.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2107660740.00	59190139260.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21110010
a0a6c0f9-31b0-4d68-9ad0-a9473bd3b4ee	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2021-12-01	59190139260.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2283299135.00	59014500865.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21120010
a0a6c0f9-326e-4cbf-890c-4dac6fe85c6c	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-01-01	59014500865.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2458937530.00	58838862470.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22010010
a0a6c0f9-3320-4ec3-9c27-bd73c1665b46	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-02-01	58838862470.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2634575925.00	58663224075.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22020010
a0a6c0f9-33d2-4d73-b7de-be44f91a3715	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-03-01	58663224075.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2810214320.00	58487585680.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22030010
a0a6c0f9-3480-44f6-8d5b-203847ce5e27	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-04-01	58487585680.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	2985852715.00	58311947285.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22040010
a0a6c0f9-352b-407b-be87-4afcd7a8fbcb	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-05-01	58311947285.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3161491110.00	58136308890.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22050010
a0a6c0f9-35d7-4e25-97c6-057009817068	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-06-01	58136308890.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3337129505.00	57960670495.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22060010
a0a6c0f9-3751-4ff5-ab9d-3bc509e09580	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-07-01	57960670495.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3512767900.00	57785032100.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22070010
a0a6c0f9-3936-4a63-8eb4-b2a639db4b41	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-08-01	57785032100.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3688406295.00	57609393705.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22080010
a0a6c0f9-39ec-480a-a473-393af5e5939d	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-09-01	57609393705.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	3864044690.00	57433755310.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090010
a0a6c0f9-3a9e-4b01-818d-7501cd5562ad	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-10-01	57433755310.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4039683085.00	57258116915.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100010
a0a6c0f9-3b40-48b0-a53d-b64ce8229160	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-11-01	57258116915.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4215321480.00	57082478520.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110010
a0a6c0f9-3cb3-470c-a12b-821d081e5725	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2022-12-01	57082478520.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4390959875.00	56906840125.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120010
a0a6c0f9-3e5c-4433-af8d-f787abd78b56	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-01-01	56906840125.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4566598270.00	56731201730.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010010
a0a6c0f9-3efe-4b0d-bcbd-e2996ee277f9	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-02-01	56731201730.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4742236665.00	56555563335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020010
a0a6c0f9-3fac-4eac-9fd8-3e3bc7d9643f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-03-01	56555563335.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	4917875060.00	56379924940.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030010
a0a6c0f9-4076-4c7e-a4ce-0c5640738257	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-04-01	56379924940.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5093513455.00	56204286545.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040010
a0a6c0f9-4139-4709-8530-4597a88c1a4f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-05-01	56204286545.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5269151850.00	56028648150.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050010
a0a6c0f9-41fe-40b8-857b-1ef22145bca2	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-06-01	56028648150.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5444790245.00	55853009755.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060010
a0a6c0f9-4322-48ed-928f-ad1954610a08	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-07-01	55853009755.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5620428640.00	55677371360.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070010
a0a6c0f9-43c8-48d4-8874-347998612117	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-08-01	55677371360.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5796067035.00	55501732965.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080010
a0a6c0f9-4467-4585-974a-4e818675798c	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-09-01	55501732965.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	5971705430.00	55326094570.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090010
a0a6c0f9-45ee-4741-86ff-5adf082ebd27	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-10-01	55326094570.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6147343825.00	55150456175.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100009
a0a6c0f9-46c8-4a4d-8cb6-dfa21e711aa0	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-11-01	55150456175.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6322982220.00	54974817780.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110009
a0a6c0f9-4789-4e67-9da7-6d01a1fc8eba	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2023-12-01	54974817780.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6498620615.00	54799179385.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120009
a0a6c0f9-4828-4478-90aa-1877b3f52c7a	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-01-01	54799179385.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6674259010.00	54623540990.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010009
a0a6c0f9-48bf-4a3e-ae86-be2cb97bb866	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-02-01	54623540990.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	6849897405.00	54447902595.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020009
a0a6c0f9-4984-4d1f-85f8-8a2da8002977	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-03-01	54447902595.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7025535800.00	54272264200.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030009
a0a6c0f9-4a03-44cf-8fa1-f771113f2da2	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-04-01	54272264200.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7201174195.00	54096625805.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040009
a0a6c0f9-4aa4-48fd-ab39-a38129440fb1	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-05-01	54096625805.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7376812590.00	53920987410.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050009
a0a6c0f9-4d9b-4c95-908b-192a432d35b3	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-06-01	53920987410.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7552450985.00	53745349015.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060009
a0a6c0f9-4e8c-4b43-af52-28d48cdd1a4b	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-07-01	53745349015.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7728089380.00	53569710620.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070009
a0a6c0f9-4f52-4f0e-8076-07060f7acb0a	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-08-01	53569710620.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	7903727775.00	53394072225.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080007
a0a6c0f9-500f-49c6-bd90-e846ef69dd4f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-09-01	53394072225.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8079366170.00	53218433830.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090007
a0a6c0f9-5155-40ec-b7f3-08686d2f4b28	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-10-01	53218433830.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8255004565.00	53042795435.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100006
a0a6c0f9-523a-4b4f-9174-f70e68b70ddf	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-11-01	53042795435.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8430642960.00	52867157040.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24110003
a0a6c0f9-5337-4e16-b3cb-af07ae70a33f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2024-12-01	52867157040.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8606281355.00	52691518645.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24120003
a0a6c0f9-540f-429e-b075-273dedca9a78	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-01-01	52691518645.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8781919750.00	52515880250.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25010003
a0a6c0f9-54d7-4610-b7fc-910ef01af9bf	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-02-01	52515880250.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	8957558145.00	52340241855.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25020003
a0a6c0f9-55b6-4680-b628-94ad0d27d95c	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-03-01	52340241855.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9133196540.00	52164603460.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25030003
a0a6c0f9-5694-4eb8-b6fb-78f96f0f0416	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-04-01	52164603460.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9308834935.00	51988965065.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25040003
a0a6c0f9-5777-4a2b-85bf-606b2cca7e62	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-05-01	51988965065.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9484473330.00	51813326670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25050003
a0a6c0f9-5870-484c-96b3-7382f718262d	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-06-01	51813326670.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9660111725.00	51637688275.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25060003
a0a6c0f9-5a2b-4cf5-be3a-64305348f67f	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-07-01	51637688275.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	9835750120.00	51462049880.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25070003
a0a6c0f9-5b4d-4254-9d22-8520e0b38269	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-08-01	51462049880.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10011388515.00	51286411485.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25080003
a0a6c0f9-5c1f-4a57-9469-206930780727	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-09-01	51286411485.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10187026910.00	51110773090.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP25090003
a0a7306c-53c5-4933-bc71-4e87280ab19c	9580ea1b-0f93-4c89-b167-a089131d5761	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100406
a0a6c0f9-6cb5-49b6-ace6-c276305406fd	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-01-01	209440000.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	4363333.00	205076667.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21010011
a0a6c0f9-6d92-4c27-9222-2eb29cea5c0f	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-02-01	205076667.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	8726666.00	200713334.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21020011
a0a6c0f9-6e4b-4ccb-92e7-f6e16c031768	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-03-01	200713334.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	13089999.00	196350001.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21030011
a0a6c0f9-707f-4a56-95da-8ed096216ea1	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-04-01	196350001.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	17453332.00	191986668.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21040011
a0a6c0f9-7146-41c1-a36d-91b8d256e240	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-05-01	191986668.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	21816665.00	187623335.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21050011
a0a6c0f9-7217-408c-aab1-7d1604c10f12	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-06-01	187623335.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	26179998.00	183260002.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21060011
a0a6c0f9-72d5-4016-809c-ebb683953b8a	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-07-01	183260002.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	30543331.00	178896669.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21070011
a0a6c0f9-7385-42a4-ac3c-2b6b7d37fd3e	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-08-01	178896669.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	34906664.00	174533336.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21080011
a0a6c0f9-7450-4bc1-abf0-c77e7c4d645f	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-09-01	174533336.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	39269997.00	170170003.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21090011
a0a6c0f9-7508-4e31-b4c5-04ae9f39a9e7	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-10-01	170170003.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	43633330.00	165806670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21100011
a0a6c0f9-75b5-419d-83cc-52e60b36cbd5	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-11-01	165806670.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	47996663.00	161443337.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21110011
a0a6c0f9-7655-4502-9f92-33bf8f8b3423	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2021-12-01	161443337.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	52359996.00	157080004.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP21120011
a0a6c0f9-78d3-418a-999b-8568a116d498	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-01-01	157080004.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	56723329.00	152716671.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22010011
a0a6c0f9-79d1-43ae-a8ae-91c835330f53	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-02-01	152716671.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	61086662.00	148353338.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22020011
a0a6c0f9-7ac0-4ab0-a212-f8eff5a5ac66	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-03-01	148353338.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	65449995.00	143990005.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22030011
a0a6c0f9-7b8a-429a-b5c1-c4f84e5f6376	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-04-01	143990005.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	69813328.00	139626672.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22040011
a0a6c0f9-7d0f-441f-8815-023e070e98d9	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-05-01	139626672.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	74176661.00	135263339.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22050011
a0a6c0f9-7db5-4bf5-8dce-67a19b48eb0b	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-06-01	135263339.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	78539994.00	130900006.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22060011
a0a6c0f9-7e58-4aae-87e7-5c5bc44ccea3	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-07-01	130900006.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	82903327.00	126536673.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22070011
a0a6c0f9-7ef7-4e70-bfcc-b362e568b190	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-08-01	126536673.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	87266660.00	122173340.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22080011
a0a6c0f9-7fcc-4f65-bdd7-ad23bf3ab34b	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-09-01	122173340.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	91629993.00	117810007.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22090011
a0a6c0f9-80dd-4af2-9de1-ea3c3b5ca21b	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-10-01	117810007.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	95993326.00	113446674.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22100011
a0a6c0f9-81f3-433d-9f7b-f7155223f4a7	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-11-01	113446674.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	100356659.00	109083341.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22110011
a0a6c0f9-82ad-4c12-9d73-0b9acc78883d	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2022-12-01	109083341.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	104719992.00	104720008.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP22120011
a0a6c0f9-836f-4946-83fd-7b7ece027799	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-01-01	104720008.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	109083325.00	100356675.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23010011
a0a6c0f9-841d-4656-93cd-bf7eb76a2d91	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-02-01	100356675.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	113446658.00	95993342.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23020011
a0a6c0f9-84dd-495e-86ce-1d0e300b9901	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-03-01	95993342.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	117809991.00	91630009.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23030011
a0a6c0f9-8583-4f1b-9146-1a51421bf9f9	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-04-01	91630009.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	122173324.00	87266676.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23040011
a0a6c0f9-8627-4925-9615-64cd5d582803	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-05-01	87266676.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	126536657.00	82903343.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23050011
a0a6c0f9-8a40-4dc7-ad2f-ffb5be567118	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-06-01	82903343.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	130899990.00	78540010.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23060011
a0a6c0f9-8c85-4168-8eb2-df5543dbc719	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-07-01	78540010.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	135263323.00	74176677.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23070011
a0a6c0f9-8dc7-4d25-9b03-9a6879b05e63	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-08-01	74176677.00	0.00	0.00	0.00	0.00	0.00	0.00	4363333.00	139626656.00	69813344.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23080011
a0a6c0f9-8e97-4e89-9264-d4debeb361d9	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-09-01	69813344.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	143989990.00	65450010.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23090011
a0a6c0f9-8f49-4403-a942-aa2038c58672	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-10-01	65450010.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	148353324.00	61086676.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23100010
a0a6c0f9-900f-4f29-9434-4976b248cd3f	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-11-01	61086676.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	152716658.00	56723342.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23110010
a0a6c0f9-90a4-4448-a847-542c7cb20e34	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2023-12-01	56723342.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	157079992.00	52360008.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP23120010
a0a6c0f9-9150-4eaf-a311-64781b09f035	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-01-01	52360008.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	161443326.00	47996674.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24010010
a0a6c0f9-9268-4a42-b440-b60015c6b828	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-02-01	47996674.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	165806660.00	43633340.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24020010
a0a6c0f9-9361-43d1-8b73-4b50a7436326	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-03-01	43633340.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	170169994.00	39270006.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24030010
a0a6c0f9-9457-4e1a-a01d-36ddbc6cae09	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-04-01	39270006.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	174533328.00	34906672.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24040010
a0a6c0f9-9539-4a51-addb-758e3fb03890	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-05-01	34906672.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	178896662.00	30543338.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24050010
a0a6c0f9-96b7-42d7-ba01-e5dbbacea795	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-06-01	30543338.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	183259996.00	26180004.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24060010
a0a6c0f9-9951-4957-8506-705e8649b106	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-07-01	26180004.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	187623330.00	21816670.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24070010
a0a6c0f9-9a36-440e-b5e1-c13805b6d79a	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-08-01	21816670.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	191986664.00	17453336.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24080008
a0a6c0f9-9b0d-4f73-af7c-d80e00445f73	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-09-01	17453336.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	196349998.00	13090002.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24090008
a0a6c0f9-9c48-439a-9d4d-ce57d08d2512	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-10-01	13090002.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	200713332.00	8726668.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24100007
a0a6c0f9-9d32-448a-b113-9ca1d3b583bc	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-11-01	8726668.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	205076666.00	4363334.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24110004
a0a6c0f9-9df5-4b97-8f26-12ab98c138fe	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2024-12-01	4363334.00	0.00	0.00	0.00	0.00	0.00	0.00	4363334.00	209440000.00	0.00	2025-12-22 12:17:05	2025-12-22 12:17:05	DEP24120004
a0a6c0f9-ac48-4ca0-ba1a-80032f0d44ad	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-01-01	58822500.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	1225468.00	57597032.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21010012
a0a6c0f9-acf7-497b-8533-ac12071c2b2d	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-02-01	57597032.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	2450936.00	56371564.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21020012
a0a6c0f9-ad9f-43c1-9c81-4cf14e508842	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-03-01	56371564.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	3676404.00	55146096.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21030012
a0a6c0f9-af81-498e-9588-314f347b2116	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-04-01	55146096.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	4901872.00	53920628.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21040012
a0a6c0f9-b07f-4988-bad0-7dbd9f3278cc	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-05-01	53920628.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	6127340.00	52695160.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21050012
a0a6c0f9-b1ec-4e31-9165-de19a5ada397	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-06-01	52695160.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	7352808.00	51469692.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21060012
a0a6c0f9-b2db-4a66-a97a-72a7a297ef8a	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-07-01	51469692.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	8578276.00	50244224.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21070012
a0a6c0f9-b3f7-4dcc-90d8-64d42f1d3284	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-08-01	50244224.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	9803744.00	49018756.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21080012
a0a6c0f9-b537-4c6c-a2c2-e222aa3e24b7	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-09-01	49018756.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	11029212.00	47793288.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21090012
a0a6c0f9-b698-48d1-970b-37092d5e9f6e	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-10-01	47793288.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	12254680.00	46567820.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21100012
a0a6c0f9-b79a-4e0e-8a24-bcef7bfa0d3f	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-11-01	46567820.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	13480148.00	45342352.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21110012
a0a6c0f9-b888-4062-939f-7e2c26c7107a	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2021-12-01	45342352.00	0.00	0.00	0.00	0.00	0.00	0.00	1225468.00	14705616.00	44116884.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21120012
a0a6c0f9-b92b-40bf-9c3b-4758caaf0b40	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-01-01	44116884.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	15931085.00	42891415.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22010012
a0a6c0f9-b9e1-492a-b208-29b1429b3995	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-02-01	42891415.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	17156554.00	41665946.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22020012
a0a6c0f9-ba8e-435c-806e-526f11906038	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-03-01	41665946.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	18382023.00	40440477.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22030012
a0a6c0f9-bb3a-4f65-8524-752b08c3bc4b	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-04-01	40440477.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	19607492.00	39215008.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22040012
a0a6c0f9-bc16-400c-9e3f-46579d646e46	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-05-01	39215008.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	20832961.00	37989539.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22050012
a0a6c0f9-bcfe-46e0-b2eb-2618fa6afbad	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-06-01	37989539.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	22058430.00	36764070.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22060012
a0a6c0f9-bdc2-49ab-854b-d9f7d0cc2ce2	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-07-01	36764070.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	23283899.00	35538601.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22070012
a0a6c0f9-bf2a-40da-80d2-21799ff56153	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-08-01	35538601.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	24509368.00	34313132.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22080012
a0a6c0f9-bff4-4143-81d9-a58e85da5185	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-09-01	34313132.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	25734837.00	33087663.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22090012
a0a6c0f9-c0ca-49b8-879c-88178d64b0b2	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-10-01	33087663.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	26960306.00	31862194.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22100012
a0a6c0f9-c183-4366-8844-7bbbb9755b54	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-11-01	31862194.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	28185775.00	30636725.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22110012
a0a6c0f9-c233-4895-b585-d6a4af5b1de4	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2022-12-01	30636725.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	29411244.00	29411256.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22120012
a0a6c0f9-c34c-4cff-bf31-8b428c364ca2	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-01-01	29411256.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	30636713.00	28185787.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23010012
a0a6c0f9-c492-4dff-b48d-85e7837ef96f	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-02-01	28185787.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	31862182.00	26960318.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020012
a0a6c0f9-c5d0-4a41-88f5-d6d2d74abfde	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-03-01	26960318.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	33087651.00	25734849.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030012
a0a6c0f9-c6f6-4c8d-8362-8cc9010bc44e	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-04-01	25734849.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	34313120.00	24509380.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040012
a0a6c0f9-c8ce-4007-8704-0723727be2ac	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-05-01	24509380.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	35538589.00	23283911.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050012
a0a6c0f9-c9e5-490f-90e4-d85998137b79	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-06-01	23283911.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	36764058.00	22058442.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060012
a0a6c0f9-caf6-40cd-bb7e-9f8ecac7f56a	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-07-01	22058442.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	37989527.00	20832973.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23070012
a0a6c0f9-cbd2-4f59-bdd5-0d96cff71106	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-08-01	20832973.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	39214996.00	19607504.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23080012
a0a6c0f9-cd1c-4c56-a6ce-15bbf611f7b8	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-09-01	19607504.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	40440465.00	18382035.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23090012
a0a6c0f9-ceb7-4fa9-9fa9-cdc06a6e8753	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-10-01	18382035.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	41665934.00	17156566.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23100011
a0a6c0f9-cfe5-4847-af9f-4e9671f3a172	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-11-01	17156566.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	42891403.00	15931097.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23110011
a0a6c0f9-d0c3-4c29-b1f6-0d9200dba876	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2023-12-01	15931097.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	44116872.00	14705628.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23120011
a0a6c0f9-d167-4cc5-be7b-68968d7315a6	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-01-01	14705628.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	45342341.00	13480159.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24010011
a0a6c0f9-d1f0-40f4-b743-fb6aad58ac45	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-02-01	13480159.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	46567810.00	12254690.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24020011
a0a6c0f9-d292-44ab-8978-8a8a40cba3fc	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-03-01	12254690.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	47793279.00	11029221.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24030011
a0a6c0f9-d343-4c76-9653-9f0c6ee55aaa	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-04-01	11029221.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	49018748.00	9803752.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24040011
a0a6c0f9-d3e5-4aca-a822-f5870c079e42	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-05-01	9803752.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	50244217.00	8578283.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24050011
a0a6c0f9-d48c-4910-b672-8c6fd4a7f1c7	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-06-01	8578283.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	51469686.00	7352814.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24060011
a0a6c0f9-d52d-4e6a-b11f-372a0b230b6e	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-07-01	7352814.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	52695155.00	6127345.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24070011
a0a6c0f9-d5d7-433c-ab44-8dfa623ac733	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-08-01	6127345.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	53920624.00	4901876.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24080009
a0a6c0f9-d685-4928-a5ce-d3218b292c8d	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-09-01	4901876.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	55146093.00	3676407.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24090009
a0a6c0f9-d749-4ee7-be65-c3485cf73bb1	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-10-01	3676407.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	56371562.00	2450938.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24100008
a0a6c0f9-d819-41f5-935e-b492f2a9863b	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-11-01	2450938.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	57597031.00	1225469.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24110005
a0a6c0f9-db93-468c-a4ee-0f87879349e6	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2024-12-01	1225469.00	0.00	0.00	0.00	0.00	0.00	0.00	1225469.00	58822500.00	0.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24120005
a0a6c0f9-eaf1-44c9-af52-c59ce8c71508	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-01-01	46750000.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	973958.00	45776042.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21010013
a0a6c0f9-ec1b-4a80-9be7-e75ad7236e1b	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-02-01	45776042.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	1947916.00	44802084.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21020013
a0a6c0f9-ed6a-487b-91f9-03433773d3d4	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-03-01	44802084.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	2921874.00	43828126.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21030013
a0a6c0f9-ee71-48b6-9040-60eb8c1bbb36	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-04-01	43828126.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	3895832.00	42854168.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21040013
a0a6c0f9-ef5f-439b-9896-be9c0324e3e8	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-05-01	42854168.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	4869790.00	41880210.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21050013
a0a6c0f9-f03a-43b0-84b3-d724782cfa3f	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-06-01	41880210.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	5843748.00	40906252.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21060013
a0a6c0f9-f145-4e61-80e1-460675f99ac0	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-07-01	40906252.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	6817706.00	39932294.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21070013
a0a6c0f9-f24e-4ce4-8711-d5eb301da8e7	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-08-01	39932294.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	7791664.00	38958336.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21080013
a0a6c0f9-f2fb-4e90-aa25-65cd56756a13	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-09-01	38958336.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	8765622.00	37984378.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21090013
a0a6c0f9-f3c0-4b9f-8510-8770a31594be	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-10-01	37984378.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	9739580.00	37010420.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21100013
a0a6c0f9-f457-4368-af61-49dce097887c	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-11-01	37010420.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	10713538.00	36036462.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21110013
a0a6c0f9-f4fe-464a-ac91-e055daa04b2e	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2021-12-01	36036462.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	11687496.00	35062504.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21120013
a0a6c0f9-f5a2-4888-be06-7a93d71fe2d1	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-01-01	35062504.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	12661454.00	34088546.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22010013
a0a6c0f9-f64a-4b0a-a737-8285fdf17c54	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-02-01	34088546.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	13635412.00	33114588.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22020013
a0a6c0f9-f985-46a9-8a23-10738ebc1808	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-03-01	33114588.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	14609370.00	32140630.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22030013
a0a6c0f9-fa73-4b1a-a977-51bc1d168db4	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-04-01	32140630.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	15583328.00	31166672.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22040013
a0a6c0f9-fb3e-46bc-87e1-682a411214ce	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-05-01	31166672.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	16557286.00	30192714.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22050013
a0a6c0f9-fc0b-4ba9-aeed-40e7f7ee5bdf	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-06-01	30192714.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	17531244.00	29218756.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22060013
a0a6c0f9-fe43-4033-9f69-9198af26cdaf	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-07-01	29218756.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	18505202.00	28244798.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22070013
a0a6c0f9-ffc5-4f74-bf0e-a756bba2f0f5	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-08-01	28244798.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	19479160.00	27270840.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22080013
a0a6c0fa-022a-4ec6-8250-169d60e329de	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-09-01	27270840.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	20453118.00	26296882.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22090013
a0a6c0fa-0310-4bce-acef-d4a734e775b3	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-10-01	26296882.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	21427076.00	25322924.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22100013
a0a6c0fa-03c1-4e03-bb40-6992f6a24d13	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-11-01	25322924.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	22401034.00	24348966.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22110013
a0a6c0fa-0461-4b9c-89ff-f89cdfb8ffca	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2022-12-01	24348966.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	23374992.00	23375008.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22120013
a0a6c0fa-04fd-49bc-8019-c92f211d4632	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-01-01	23375008.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	24348950.00	22401050.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23010013
a0a6c0fa-05b3-4e29-ad1e-34a23d91cdee	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-02-01	22401050.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	25322908.00	21427092.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020013
a0a6c0fa-070d-4fe5-840a-ae64182f26c1	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-03-01	21427092.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	26296866.00	20453134.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030013
a0a6c0fa-080d-4911-8e66-7317f0884bbe	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-04-01	20453134.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	27270824.00	19479176.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040013
a0a6c0fa-08bb-4234-bc96-df7b80ff3d93	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-05-01	19479176.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	28244782.00	18505218.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050013
a0a6c0fa-0967-432f-b45a-aa1f8fe6e596	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-06-01	18505218.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	29218740.00	17531260.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060013
a0a6c0fa-0a13-4de7-8459-256cb757f9ae	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-07-01	17531260.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	30192698.00	16557302.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23070013
a0a6c0fa-0aee-4bb0-bb5e-81c92ca10049	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-08-01	16557302.00	0.00	0.00	0.00	0.00	0.00	0.00	973958.00	31166656.00	15583344.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23080013
a0a6c0fa-0c1d-4786-9cec-5dc09fda295f	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-09-01	15583344.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	32140615.00	14609385.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23090013
a0a6c0fa-0ce0-4588-938a-40f56bed2214	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-10-01	14609385.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	33114574.00	13635426.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23100012
a0a6c0fa-0da5-432b-95f1-a21063ac43f1	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-11-01	13635426.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	34088533.00	12661467.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23110012
a0a6c0fa-0edd-4ffe-bf34-36338b7e9662	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2023-12-01	12661467.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	35062492.00	11687508.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23120012
a0a6c0fa-0fdc-4574-bfe2-6f9689de26f6	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-01-01	11687508.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	36036451.00	10713549.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24010012
a0a6c0fa-1137-446c-af40-cc63fa490ae5	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-02-01	10713549.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	37010410.00	9739590.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24020012
a0a6c0fa-1209-4c01-8e4a-803313786922	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-03-01	9739590.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	37984369.00	8765631.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24030012
a0a6c0fa-1319-4728-a176-da61bf67198c	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-04-01	8765631.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	38958328.00	7791672.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24040012
a0a6c0fa-1408-4eb9-bd1e-5b545ba42d3b	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-05-01	7791672.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	39932287.00	6817713.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24050012
a0a6c0fa-14e2-4297-a828-82f6148f700e	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-06-01	6817713.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	40906246.00	5843754.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24060012
a0a6c0fa-1613-4aaa-ab3e-08b11f4c48dd	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-07-01	5843754.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	41880205.00	4869795.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24070012
a0a6c0fa-16f7-434b-bfde-b6b7b41faa34	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-08-01	4869795.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	42854164.00	3895836.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24080010
a0a6c0fa-17c4-4c33-8537-4463c88e26fc	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-09-01	3895836.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	43828123.00	2921877.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24090010
a0a6c0fa-1887-483b-b72e-e6046fe1a2a3	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-10-01	2921877.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	44802082.00	1947918.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24100009
a0a6c0fa-1952-4685-810f-914061cd3b01	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-11-01	1947918.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	45776041.00	973959.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24110006
a0a6c0fa-1a03-46f9-b00b-1efb612429e1	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2024-12-01	973959.00	0.00	0.00	0.00	0.00	0.00	0.00	973959.00	46750000.00	0.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24120006
a0a6c0fa-29c7-4688-bf0d-64dba96d6ce8	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-02-01	398612500.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	8304427.00	390308073.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21020014
a0a6c0fa-2b61-4257-b080-91c32fed4763	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-03-01	390308073.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	16608854.00	382003646.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21030014
a0a6c0fa-2ceb-4eed-9bd7-5ac74b595df4	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-04-01	382003646.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	24913281.00	373699219.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21040014
a0a6c0fa-2df3-4119-a2e1-bd48daad2cdc	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-05-01	373699219.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	33217708.00	365394792.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21050014
a0a6c0fa-2f07-42cc-9537-a6f9f0f14fd1	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-06-01	365394792.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	41522135.00	357090365.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21060014
a0a6c0fa-2ff7-45ac-8240-9bafd0e49555	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-07-01	357090365.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	49826562.00	348785938.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21070014
a0a6c0fa-30f7-420c-bbcf-a0e850b1bc7b	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-08-01	348785938.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	58130989.00	340481511.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21080014
a0a6c0fa-31a1-41cd-a38a-7f95edee729f	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-09-01	340481511.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	66435416.00	332177084.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21090014
a0a6c0fa-325e-4e15-85ff-d136b510449f	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-10-01	332177084.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	74739843.00	323872657.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21100014
a0a6c0fa-330b-45d7-9480-d61b8c151d12	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-11-01	323872657.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	83044270.00	315568230.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21110014
a0a6c0fa-33b1-4aed-8f16-0cb661dd8349	99970f15-9c4a-4d4f-b550-a7ef488054d0	2021-12-01	315568230.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	91348697.00	307263803.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21120014
a0a6c0fa-345a-4396-a7c6-1e9b1ca98cd6	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-01-01	307263803.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	99653124.00	298959376.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22010014
a0a6c0fa-3501-4b29-a027-aaf59448afc1	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-02-01	298959376.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	107957551.00	290654949.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22020014
a0a6c0fa-35d5-436c-904d-d2089e368402	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-03-01	290654949.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	116261978.00	282350522.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22030014
a0a6c0fa-3678-4f44-98e2-ff2699e7b743	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-04-01	282350522.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	124566405.00	274046095.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22040014
a0a6c0fa-37fc-4e82-8a4d-11259dbf829e	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-05-01	274046095.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	132870832.00	265741668.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22050014
a0a6c0fa-38bc-40ed-800e-346644c2f328	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-06-01	265741668.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	141175259.00	257437241.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22060014
a0a6c0fa-39be-43cc-a86c-fbac1645ef46	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-07-01	257437241.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	149479686.00	249132814.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22070014
a0a6c0fa-3a9f-4d42-98d3-54cecda0e850	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-08-01	249132814.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	157784113.00	240828387.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22080014
a0a6c0fa-3b7e-475d-b66c-c0371bb7918d	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-09-01	240828387.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	166088540.00	232523960.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22090014
a0a6c0fa-3d23-4653-97d1-656c035e0282	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-10-01	232523960.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	174392967.00	224219533.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22100014
a0a6c0fa-3e3b-4320-9441-119a9abcaba6	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-11-01	224219533.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	182697394.00	215915106.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22110014
a0a6c0fa-418c-48c0-ac3a-270d01bcb5a2	99970f15-9c4a-4d4f-b550-a7ef488054d0	2022-12-01	215915106.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	191001821.00	207610679.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22120014
a0a6c0fa-4322-451b-a045-e84fdb16b5d2	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-01-01	207610679.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	199306248.00	199306252.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23010014
a0a6c0fa-44e0-49f2-8666-dfa8149ad5f3	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-02-01	199306252.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	207610675.00	191001825.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020014
a0a6c0fa-462f-4458-9b1e-5115f1ca9f74	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-03-01	191001825.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	215915102.00	182697398.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030014
a0a6c0fa-46ee-49c3-be74-09aef74684a1	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-04-01	182697398.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	224219529.00	174392971.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040014
a0a6c0fa-4787-49ab-9e81-1706403f9fd4	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-05-01	174392971.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	232523956.00	166088544.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050014
a0a6c0fa-4843-4da0-a9d4-55f9a4165fb5	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-06-01	166088544.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	240828383.00	157784117.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060014
a0a6c0fa-48fe-49a6-aa75-bbc2eba44580	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-07-01	157784117.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	249132810.00	149479690.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23070014
a0a6c0fa-49c5-4431-b96d-28425f4bedb7	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-08-01	149479690.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	257437237.00	141175263.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23080014
a0a6c0fa-4a77-4522-a5dd-d96c39cb78d8	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-09-01	141175263.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	265741664.00	132870836.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23090014
a0a6c0fa-4b4a-4f60-86ae-c43dad734d8d	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-10-01	132870836.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	274046091.00	124566409.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23100013
a0a6c0fa-4c07-40a9-beb3-48278a364038	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-11-01	124566409.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	282350518.00	116261982.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23110013
a0a6c0fa-4cd5-42b6-bc79-821ad6781b0f	99970f15-9c4a-4d4f-b550-a7ef488054d0	2023-12-01	116261982.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	290654945.00	107957555.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23120013
a0a6c0fa-4d75-42f9-ba7c-487f1c0c361d	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-01-01	107957555.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	298959372.00	99653128.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24010013
a0a6c0fa-4e02-4c30-9700-0dd688d92f48	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-02-01	99653128.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	307263799.00	91348701.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24020013
a0a6c0fa-4f87-4f40-978e-4ae1d9abbea3	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-03-01	91348701.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	315568226.00	83044274.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24030013
a0a6c0fa-5044-49ab-b909-17e89df1a976	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-04-01	83044274.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	323872653.00	74739847.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24040013
a0a6c0fa-50d7-45dc-8fd3-6ab8df04dba1	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-05-01	74739847.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	332177080.00	66435420.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24050013
a0a6c0fa-52dc-4d18-b591-49872b9d3d67	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-06-01	66435420.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	340481507.00	58130993.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24060013
a0a6c0fa-5400-4db9-8402-9ef2b7e71e36	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-07-01	58130993.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	348785934.00	49826566.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24070013
a0a6c0fa-54d4-45e5-b628-332e1f048aba	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-08-01	49826566.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	357090361.00	41522139.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24080011
a0a6c0fa-5594-4362-9fa2-67fc6ca6d700	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-09-01	41522139.00	0.00	0.00	0.00	0.00	0.00	0.00	8304427.00	365394788.00	33217712.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24090011
a0a6c0fa-5665-4dc1-bfa9-13a2af31dd9b	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-10-01	33217712.00	0.00	0.00	0.00	0.00	0.00	0.00	8304428.00	373699216.00	24913284.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24100010
a0a6c0fa-5728-4d02-9f87-3173b518fc58	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-11-01	24913284.00	0.00	0.00	0.00	0.00	0.00	0.00	8304428.00	382003644.00	16608856.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24110007
a0a6c0fa-58db-44b4-bce0-ccce696040b1	99970f15-9c4a-4d4f-b550-a7ef488054d0	2024-12-01	16608856.00	0.00	0.00	0.00	0.00	0.00	0.00	8304428.00	390308072.00	8304428.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24120007
a0a6c0fa-5a29-4566-b33e-42220cf2339d	99970f15-9c4a-4d4f-b550-a7ef488054d0	2025-01-01	8304428.00	0.00	0.00	0.00	0.00	0.00	0.00	8304428.00	398612500.00	0.00	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25010004
a0a6c0fa-689b-4323-b3d1-36b86ef6c584	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-02-01	297940647.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	6207096.00	291733551.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020015
a0a6c0fa-694b-4ecb-861a-422674a0a017	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-03-01	291733551.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	12414192.00	285526455.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030015
a0a6c0fa-69fb-4343-974b-ef080409db4a	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-04-01	285526455.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	18621288.00	279319359.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040015
a0a6c0fa-6ae7-4c0b-a6b9-d6143d34f568	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-05-01	279319359.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	24828384.00	273112263.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050015
a0a6c0fa-6b79-47fb-a0c8-725c16c05956	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-06-01	273112263.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	31035480.00	266905167.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060015
a0a6c0fa-6c3f-42fa-8152-1518cba4a3bf	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-07-01	266905167.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	37242576.00	260698071.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23070015
a0a6c0fa-6ceb-4f05-80cd-786e862d8642	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-08-01	260698071.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	43449672.00	254490975.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23080015
a0a6c0fa-6e83-4da2-be1a-38b26b155e3f	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-09-01	254490975.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	49656768.00	248283879.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23090015
a0a6c0fa-6f6d-470d-8bc4-b79f00f1ad88	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-10-01	248283879.12	0.00	0.00	0.00	0.00	0.00	0.00	6207096.00	55863864.00	242076783.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23100014
a0a6c0fa-70bb-47f4-9887-da28aa86762a	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-11-01	242076783.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	62070961.00	235869686.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23110014
a0a6c0fa-7185-4fb6-816a-f8f0f1b721d6	e971913d-0f93-4a70-85eb-c0ed12a172d8	2023-12-01	235869686.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	68278058.00	229662589.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23120014
a0a6c0fa-72f2-4fa4-a013-3e9c0d6f399c	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-01-01	229662589.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	74485155.00	223455492.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24010014
a0a6c0fa-73da-4007-a99b-fa0f2018a8dc	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-02-01	223455492.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	80692252.00	217248395.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24020014
a0a6c0fa-7494-4c2f-b19f-a970c29aa910	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-03-01	217248395.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	86899349.00	211041298.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24030014
a0a6c0fa-7532-4017-9b26-57db309cbca4	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-04-01	211041298.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	93106446.00	204834201.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24040014
a0a6c0fa-75cf-4c44-94db-c6872228c582	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-05-01	204834201.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	99313543.00	198627104.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24050014
a0a6c0fa-7673-4a34-95d5-367281256bdd	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-06-01	198627104.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	105520640.00	192420007.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24060014
a0a6c0fa-7713-4fa3-ac90-f3e92815d478	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-07-01	192420007.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	111727737.00	186212910.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24070014
a0a6c0fa-77b6-498d-ae7a-887b61bf945b	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-08-01	186212910.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	117934834.00	180005813.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24080012
a0a6c0fa-78a2-432b-8dd7-94c17d1b5c39	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-09-01	180005813.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	124141931.00	173798716.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24090012
a0a6c0fa-79ee-4850-adc1-3cace849a1f5	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-10-01	173798716.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	130349028.00	167591619.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24100011
a0a6c0fa-7dda-496b-ad62-173bac34f922	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-11-01	167591619.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	136556125.00	161384522.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24110008
a0a6c0fa-812b-427b-818c-12b70ae57373	e971913d-0f93-4a70-85eb-c0ed12a172d8	2024-12-01	161384522.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	142763222.00	155177425.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24120008
a0a6c0fa-824b-4771-b88d-2284170e6014	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-01-01	155177425.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	148970319.00	148970328.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25010005
a0a6c0fa-840e-40d6-b27c-788566d0cd9c	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-02-01	148970328.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	155177416.00	142763231.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25020004
a0a6c0fa-899f-4939-862f-45f78af2b6e1	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-03-01	142763231.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	161384513.00	136556134.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25030004
a0a6c0fa-8ae7-44e6-82eb-983b73377d5c	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-04-01	136556134.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	167591610.00	130349037.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25040004
a0a6c0fa-8c38-49d3-8ff9-b397beaf4945	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-05-01	130349037.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	173798707.00	124141940.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25050004
a0a6c0fa-8d34-497b-bdef-5fb6b71f3161	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-06-01	124141940.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	180005804.00	117934843.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25060004
a0a6c0fa-8dff-4e69-80fc-9eb98474e922	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-07-01	117934843.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	186212901.00	111727746.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25070004
a0a6c0fa-8ea9-40c1-84ee-8a14fce03d1e	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-08-01	111727746.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	192419998.00	105520649.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25080004
a0a6c0fa-8f2d-4851-9785-c95d3f01c608	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-09-01	105520649.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	198627095.00	99313552.12	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25090004
a0a7306c-59a8-48f4-a0a8-cbf30d533af7	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100407
a0a6c0fa-a1d5-48ba-9b12-c1da0e45a090	101fda0f-877a-4290-9df5-00a84859c3e9	2019-08-01	36508127.52	0.00	0.00	0.00	0.00	0.00	0.00	380292.00	380292.00	36127835.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19080001
a0a6c0fa-a38a-4fc4-b79b-373e3d4fa35f	101fda0f-877a-4290-9df5-00a84859c3e9	2019-09-01	36127835.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	760585.00	35747542.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19090001
a0a6c0fa-a44b-4719-bf74-65d5032dce6f	101fda0f-877a-4290-9df5-00a84859c3e9	2019-10-01	35747542.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1140878.00	35367249.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19100002
a0a6c0fa-a4dd-4358-aef8-fedad104de2a	101fda0f-877a-4290-9df5-00a84859c3e9	2019-11-01	35367249.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1521171.00	34986956.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19110002
a0a6c0fa-a5d6-4815-927f-60eaa5c65308	101fda0f-877a-4290-9df5-00a84859c3e9	2019-12-01	34986956.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1901464.00	34606663.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19120002
a0a6c0fa-a680-4148-a9a1-301ab145627a	101fda0f-877a-4290-9df5-00a84859c3e9	2020-01-01	34606663.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	2281757.00	34226370.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20010002
a0a6c0fa-a740-4e9c-b165-433fa52ee20f	101fda0f-877a-4290-9df5-00a84859c3e9	2020-02-01	34226370.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	2662050.00	33846077.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20020002
a0a6c0fa-a82b-4374-a843-a4dacde67d64	101fda0f-877a-4290-9df5-00a84859c3e9	2020-03-01	33846077.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3042343.00	33465784.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20030002
a0a6c0fa-a94e-44d6-a5ed-94b974c35a7b	101fda0f-877a-4290-9df5-00a84859c3e9	2020-04-01	33465784.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3422636.00	33085491.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20040002
a0a6c0fa-a9f3-433a-81db-f8814a5606fe	101fda0f-877a-4290-9df5-00a84859c3e9	2020-05-01	33085491.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3802929.00	32705198.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20050002
a0a6c0fa-aad8-4644-9a84-e9345ce259ac	101fda0f-877a-4290-9df5-00a84859c3e9	2020-06-01	32705198.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4183222.00	32324905.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20060002
a0a6c0fa-ab75-4a09-b071-38f01fb8539c	101fda0f-877a-4290-9df5-00a84859c3e9	2020-07-01	32324905.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4563515.00	31944612.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20070002
a0a6c0fa-ac32-4f96-a0a7-b5d135e87862	101fda0f-877a-4290-9df5-00a84859c3e9	2020-08-01	31944612.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4943808.00	31564319.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20080004
a0a6c0fa-acdf-4cda-bebe-8a107041d20f	101fda0f-877a-4290-9df5-00a84859c3e9	2020-09-01	31564319.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	5324101.00	31184026.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20090004
a0a6c0fa-ad9c-451d-9063-c63f6601a2aa	101fda0f-877a-4290-9df5-00a84859c3e9	2020-10-01	31184026.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	5704394.00	30803733.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20100005
a0a6c0fa-ae72-4bab-b221-b2227fa94c89	101fda0f-877a-4290-9df5-00a84859c3e9	2020-11-01	30803733.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6084687.00	30423440.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20110008
a0a6c0fa-af37-4d91-8159-6a3b06662252	101fda0f-877a-4290-9df5-00a84859c3e9	2020-12-01	30423440.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6464980.00	30043147.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20120011
a0a6c0fa-afc6-442d-9acc-971b0d3043aa	101fda0f-877a-4290-9df5-00a84859c3e9	2021-01-01	30043147.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6845273.00	29662854.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21010014
a0a6c0fa-b04d-427c-acd1-03d607d9c921	101fda0f-877a-4290-9df5-00a84859c3e9	2021-02-01	29662854.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7225566.00	29282561.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21020015
a0a6c0fa-b0e2-40cb-bfda-7f4efc17f528	101fda0f-877a-4290-9df5-00a84859c3e9	2021-03-01	29282561.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7605859.00	28902268.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21030015
a0a6c0fa-b172-49c5-955f-e4394332e0fd	101fda0f-877a-4290-9df5-00a84859c3e9	2021-04-01	28902268.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7986152.00	28521975.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21040015
a0a6c0fa-b290-43a0-95f5-f4c690b4a734	101fda0f-877a-4290-9df5-00a84859c3e9	2021-05-01	28521975.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	8366445.00	28141682.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21050015
a0a6c0fa-b325-4f86-9453-893cafe13be1	101fda0f-877a-4290-9df5-00a84859c3e9	2021-06-01	28141682.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	8746738.00	27761389.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21060015
a0a6c0fa-b3d7-4515-9dad-585c0f977da0	101fda0f-877a-4290-9df5-00a84859c3e9	2021-07-01	27761389.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9127031.00	27381096.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21070015
a0a6c0fa-b509-42cf-9916-361308cc8ac8	101fda0f-877a-4290-9df5-00a84859c3e9	2021-08-01	27381096.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9507324.00	27000803.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21080015
a0a6c0fa-b5e1-4024-8c39-a6c5354e5bc8	101fda0f-877a-4290-9df5-00a84859c3e9	2021-09-01	27000803.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9887617.00	26620510.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21090015
a0a6c0fa-b677-49e2-9ae7-4099543bbafb	101fda0f-877a-4290-9df5-00a84859c3e9	2021-10-01	26620510.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	10267910.00	26240217.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21100015
a0a6c0fa-b70f-4fc1-93a1-193734cbd6f4	101fda0f-877a-4290-9df5-00a84859c3e9	2021-11-01	26240217.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	10648203.00	25859924.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21110015
a0a6c0fa-b791-45e4-91b6-3c734b12fcaa	101fda0f-877a-4290-9df5-00a84859c3e9	2021-12-01	25859924.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11028496.00	25479631.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21120015
a0a6c0fa-b8b9-4976-90b6-851575a764c7	101fda0f-877a-4290-9df5-00a84859c3e9	2022-01-01	25479631.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11408789.00	25099338.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22010015
a0a6c0fa-b9ae-4848-952b-10bcf8a2388e	101fda0f-877a-4290-9df5-00a84859c3e9	2022-02-01	25099338.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11789082.00	24719045.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22020015
a0a6c0fa-ba91-441d-a369-acd7333ed158	101fda0f-877a-4290-9df5-00a84859c3e9	2022-03-01	24719045.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12169375.00	24338752.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22030015
a0a6c0fa-bdc1-410e-827d-02c501caf845	101fda0f-877a-4290-9df5-00a84859c3e9	2022-04-01	24338752.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12549668.00	23958459.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22040015
a0a6c0fa-bf57-4cfd-aa5e-6e5a6d1eded3	101fda0f-877a-4290-9df5-00a84859c3e9	2022-05-01	23958459.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12929961.00	23578166.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22050015
a0a6c0fa-c0c3-43cd-bfed-b3ffe2bfbe65	101fda0f-877a-4290-9df5-00a84859c3e9	2022-06-01	23578166.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	13310254.00	23197873.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22060015
a0a6c0fa-c1ef-4eef-b284-d2cfac72b5ea	101fda0f-877a-4290-9df5-00a84859c3e9	2022-07-01	23197873.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	13690547.00	22817580.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22070015
a0a6c0fa-c328-4581-9621-1fcf7e5502ec	101fda0f-877a-4290-9df5-00a84859c3e9	2022-08-01	22817580.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14070840.00	22437287.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22080015
a0a6c0fa-c45b-48eb-9237-7e692c0a0e7c	101fda0f-877a-4290-9df5-00a84859c3e9	2022-09-01	22437287.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14451133.00	22056994.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22090015
a0a6c0fa-c567-4447-86ec-aa62fa8644bf	101fda0f-877a-4290-9df5-00a84859c3e9	2022-10-01	22056994.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14831426.00	21676701.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22100015
a0a6c0fa-c694-49c7-b25f-4ddb40ab9ffd	101fda0f-877a-4290-9df5-00a84859c3e9	2022-11-01	21676701.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15211719.00	21296408.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22110015
a0a6c0fa-c7ae-4704-8acf-34e821147f6f	101fda0f-877a-4290-9df5-00a84859c3e9	2022-12-01	21296408.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15592012.00	20916115.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22120015
a0a6c0fa-c97b-4fce-855e-55de293f4a02	101fda0f-877a-4290-9df5-00a84859c3e9	2023-01-01	20916115.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15972305.00	20535822.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23010015
a0a6c0fa-ca70-4ace-a55a-85a6f651a06a	101fda0f-877a-4290-9df5-00a84859c3e9	2023-02-01	20535822.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	16352598.00	20155529.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020016
a0a6c0fa-cb4f-42d0-8527-2b2c187b1107	101fda0f-877a-4290-9df5-00a84859c3e9	2023-03-01	20155529.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	16732891.00	19775236.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030016
a0a6c0fa-cbfe-4d26-b0b0-20aa75c1f430	101fda0f-877a-4290-9df5-00a84859c3e9	2023-04-01	19775236.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17113184.00	19394943.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040016
a0a6c0fa-ccd9-4948-aff6-b98dd5c9ca42	101fda0f-877a-4290-9df5-00a84859c3e9	2023-05-01	19394943.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17493477.00	19014650.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050016
a0a6c0fa-cd9f-4c65-8ab8-5ff2854599fb	101fda0f-877a-4290-9df5-00a84859c3e9	2023-06-01	19014650.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17873770.00	18634357.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060016
a0a6c0fa-ce7f-4d8e-9396-85fa71a16f41	101fda0f-877a-4290-9df5-00a84859c3e9	2023-07-01	18634357.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	18254063.00	18254064.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23070016
a0a6c0fa-cf7b-4d4f-b79b-6a297e071ea1	101fda0f-877a-4290-9df5-00a84859c3e9	2023-08-01	18254064.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	18634356.00	17873771.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23080016
a0a6c0fa-d06a-4276-985a-99e4ae162cb8	101fda0f-877a-4290-9df5-00a84859c3e9	2023-09-01	17873771.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19014649.00	17493478.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23090016
a0a6c0fa-d136-44ec-84cc-066a32e6afb0	101fda0f-877a-4290-9df5-00a84859c3e9	2023-10-01	17493478.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19394942.00	17113185.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23100015
a0a6c0fa-d1fc-42f9-8e70-0b396f12f583	101fda0f-877a-4290-9df5-00a84859c3e9	2023-11-01	17113185.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19775235.00	16732892.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23110015
a0a6c0fa-d293-4312-850f-f437058ebe7d	101fda0f-877a-4290-9df5-00a84859c3e9	2023-12-01	16732892.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20155528.00	16352599.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23120015
a0a6c0fa-d33d-4436-89b7-573ddfb448ff	101fda0f-877a-4290-9df5-00a84859c3e9	2024-01-01	16352599.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20535821.00	15972306.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24010015
a0a6c0fa-d3e1-4cbf-b21b-e231d6425581	101fda0f-877a-4290-9df5-00a84859c3e9	2024-02-01	15972306.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20916114.00	15592013.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24020015
a0a6c0fa-d4ba-40dc-9b91-34364ee180c7	101fda0f-877a-4290-9df5-00a84859c3e9	2024-03-01	15592013.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	21296407.00	15211720.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24030015
a0a6c0fa-d5ac-4b0c-8ab0-4ee726214c2e	101fda0f-877a-4290-9df5-00a84859c3e9	2024-04-01	15211720.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	21676700.00	14831427.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24040015
a0a6c0fa-d6a6-4ca4-a6c9-8a664fefdfc6	101fda0f-877a-4290-9df5-00a84859c3e9	2024-05-01	14831427.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22056993.00	14451134.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24050015
a0a6c0fa-d77d-480f-96d3-3bf16cd95b44	101fda0f-877a-4290-9df5-00a84859c3e9	2024-06-01	14451134.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22437286.00	14070841.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24060015
a0a6c0fa-d844-46c2-90cf-380bdfc303fa	101fda0f-877a-4290-9df5-00a84859c3e9	2024-07-01	14070841.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22817579.00	13690548.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24070015
a0a6c0fa-d90e-4ee2-8974-ff992d326811	101fda0f-877a-4290-9df5-00a84859c3e9	2024-08-01	13690548.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23197872.00	13310255.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24080013
a0a6c0fa-d9f3-411e-a84c-3606b3a837b8	101fda0f-877a-4290-9df5-00a84859c3e9	2024-09-01	13310255.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23578165.00	12929962.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24090013
a0a6c0fa-db34-45f0-b76c-4885a4cc8667	101fda0f-877a-4290-9df5-00a84859c3e9	2024-10-01	12929962.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23958458.00	12549669.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24100012
a0a6c0fa-dc1b-4386-8ddb-6745bca41a47	101fda0f-877a-4290-9df5-00a84859c3e9	2024-11-01	12549669.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	24338751.00	12169376.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24110009
a0a6c0fa-dd53-4396-825f-b700e6c45368	101fda0f-877a-4290-9df5-00a84859c3e9	2024-12-01	12169376.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	24719044.00	11789083.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP24120009
a0a6c0fa-df93-4d2d-b8fb-904e155ade2a	101fda0f-877a-4290-9df5-00a84859c3e9	2025-01-01	11789083.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25099337.00	11408790.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25010006
a0a6c0fa-e0d1-4ffd-9713-6ce18638cec9	101fda0f-877a-4290-9df5-00a84859c3e9	2025-02-01	11408790.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25479630.00	11028497.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25020005
a0a6c0fa-e1b2-4b3e-acf6-9066d03773ef	101fda0f-877a-4290-9df5-00a84859c3e9	2025-03-01	11028497.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25859923.00	10648204.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25030005
a0a6c0fa-e33c-4b7b-be21-448e5f2eb928	101fda0f-877a-4290-9df5-00a84859c3e9	2025-04-01	10648204.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	26240216.00	10267911.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25040005
a0a6c0fa-e6ab-41ab-9214-0e11fc50a729	101fda0f-877a-4290-9df5-00a84859c3e9	2025-05-01	10267911.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	26620509.00	9887618.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25050005
a0a6c0fa-e91f-41c8-b0cf-871cc6fbe42f	101fda0f-877a-4290-9df5-00a84859c3e9	2025-06-01	9887618.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27000802.00	9507325.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25060005
a0a6c0fa-ea76-4698-9780-815ab3f3f2b1	101fda0f-877a-4290-9df5-00a84859c3e9	2025-07-01	9507325.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27381095.00	9127032.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25070005
a0a6c0fa-ecf4-4fa3-99d4-3314f4461d4f	101fda0f-877a-4290-9df5-00a84859c3e9	2025-08-01	9127032.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27761388.00	8746739.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25080005
a0a6c0fa-edcd-44bf-946d-a69b274dd377	101fda0f-877a-4290-9df5-00a84859c3e9	2025-09-01	8746739.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28141681.00	8366446.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP25090005
a0a6c0f8-c769-4ab0-9bbf-18ecf8aeba2e	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-10-01	51110773090.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10362665305.00	50935134695.00	2025-12-22 12:17:05	2025-12-24 11:50:27	DEP25100408
a0a6c0fa-fb08-4436-9518-7c75085e0c6f	6504929e-7f0b-47a6-b6d6-25032344b55f	2019-08-01	36508127.52	0.00	0.00	0.00	0.00	0.00	0.00	380292.00	380292.00	36127835.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19080002
a0a6c0fa-fbc6-4ace-902d-b590f1c4cbf7	6504929e-7f0b-47a6-b6d6-25032344b55f	2019-09-01	36127835.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	760585.00	35747542.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19090002
a0a6c0fa-fc58-47b7-925f-1747d3791cb3	6504929e-7f0b-47a6-b6d6-25032344b55f	2019-10-01	35747542.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1140878.00	35367249.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19100003
a0a6c0fa-fce0-4e85-9c44-4a89024bc3c0	6504929e-7f0b-47a6-b6d6-25032344b55f	2019-11-01	35367249.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1521171.00	34986956.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19110003
a0a6c0fa-ff7a-4c6a-ab5a-31876a781aa1	6504929e-7f0b-47a6-b6d6-25032344b55f	2019-12-01	34986956.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	1901464.00	34606663.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP19120003
a0a6c0fb-00a5-4fdb-ad4b-24a95c81b97a	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-01-01	34606663.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	2281757.00	34226370.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20010003
a0a6c0fb-0183-4877-a301-3b5afceb5074	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-02-01	34226370.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	2662050.00	33846077.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20020003
a0a6c0fb-0254-48b1-826d-bccf065afd70	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-03-01	33846077.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3042343.00	33465784.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20030003
a0a6c0fb-030c-4413-a6e6-53af1c41e3af	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-04-01	33465784.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3422636.00	33085491.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20040003
a0a6c0fb-03d6-4bc3-8202-bfbeb9ac7eb3	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-05-01	33085491.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	3802929.00	32705198.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20050003
a0a6c0fb-0490-4ef9-be5f-f7d045fab3a2	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-06-01	32705198.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4183222.00	32324905.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20060003
a0a6c0fb-05bd-4ce2-a82f-9d3f555897fc	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-07-01	32324905.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4563515.00	31944612.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20070003
a0a6c0fb-08d6-4834-954b-016a13db37b1	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-08-01	31944612.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	4943808.00	31564319.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20080005
a0a6c0fb-09b9-427e-8603-eafe04351392	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-09-01	31564319.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	5324101.00	31184026.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20090005
a0a6c0fb-0a60-499b-aacd-617e01d9c95e	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-10-01	31184026.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	5704394.00	30803733.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20100006
a0a6c0fb-0dda-499a-9124-8b69e954b383	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-11-01	30803733.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6084687.00	30423440.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20110009
a0a6c0fb-0eaa-4729-951a-c5b059aa47ba	6504929e-7f0b-47a6-b6d6-25032344b55f	2020-12-01	30423440.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6464980.00	30043147.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP20120012
a0a6c0fb-0f6a-4ca4-ba79-832cf49982ed	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-01-01	30043147.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	6845273.00	29662854.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21010015
a0a6c0fb-1026-43fe-bdaf-f226d9328afd	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-02-01	29662854.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7225566.00	29282561.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21020016
a0a6c0fb-10c5-430a-9373-571d6f6b89f6	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-03-01	29282561.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7605859.00	28902268.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21030016
a0a6c0fb-116a-414e-bec0-7b23fd36b08e	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-04-01	28902268.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	7986152.00	28521975.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21040016
a0a6c0fb-12e9-4911-92f2-b695ad7a4eb3	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-05-01	28521975.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	8366445.00	28141682.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21050016
a0a6c0fb-1385-4d1a-b026-2f3c926c2f87	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-06-01	28141682.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	8746738.00	27761389.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21060016
a0a6c0fb-1406-4dd4-aea4-2d2f2eed559e	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-07-01	27761389.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9127031.00	27381096.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21070016
a0a6c0fb-1498-42f8-8927-8d04e1b9ae9e	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-08-01	27381096.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9507324.00	27000803.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21080016
a0a6c0fb-152f-44a0-9ac3-e3bb266dbef5	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-09-01	27000803.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	9887617.00	26620510.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21090016
a0a6c0fb-15ad-4647-b475-c2d6db47deaa	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-10-01	26620510.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	10267910.00	26240217.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21100016
a0a6c0fb-1649-4822-a4a2-02a7fd98f92b	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-11-01	26240217.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	10648203.00	25859924.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21110016
a0a6c0fb-16db-40fb-bc74-cd865b923dbd	6504929e-7f0b-47a6-b6d6-25032344b55f	2021-12-01	25859924.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11028496.00	25479631.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP21120016
a0a6c0fb-1758-46e1-9f7d-e970e8fcec5e	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-01-01	25479631.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11408789.00	25099338.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22010016
a0a6c0fb-17f7-4893-82ed-e5588db8d5e3	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-02-01	25099338.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	11789082.00	24719045.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22020016
a0a6c0fb-1875-4b1b-affc-cda214931512	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-03-01	24719045.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12169375.00	24338752.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22030016
a0a6c0fb-18f3-4b75-b974-3ac435656097	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-04-01	24338752.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12549668.00	23958459.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22040016
a0a6c0fb-1a5a-455d-a0ae-56a19d2979d8	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-05-01	23958459.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	12929961.00	23578166.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22050016
a0a6c0fb-1b25-4cf7-aabe-8915ebbe7b43	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-06-01	23578166.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	13310254.00	23197873.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22060016
a0a6c0fb-1bc2-4d11-afbf-33ce2d576a18	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-07-01	23197873.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	13690547.00	22817580.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22070016
a0a6c0fb-1c55-4308-8076-3a56f29861e5	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-08-01	22817580.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14070840.00	22437287.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22080016
a0a6c0fb-1d12-4d07-9ac0-2e3ddcb523ab	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-09-01	22437287.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14451133.00	22056994.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22090016
a0a6c0fb-1d9f-4af8-a5b6-bceb2303f65b	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-10-01	22056994.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	14831426.00	21676701.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22100016
a0a6c0fb-1e2e-4a23-8279-1186d19776ea	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-11-01	21676701.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15211719.00	21296408.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22110016
a0a6c0fb-1ec9-4ad4-8d44-d45e06deba85	6504929e-7f0b-47a6-b6d6-25032344b55f	2022-12-01	21296408.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15592012.00	20916115.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP22120016
a0a6c0fb-1f52-412f-ab31-a107f60035ba	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-01-01	20916115.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	15972305.00	20535822.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23010016
a0a6c0fb-1fff-4cf1-984b-98b9e5429f1b	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-02-01	20535822.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	16352598.00	20155529.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23020017
a0a6c0fb-2081-4c11-9179-32b882bc74a8	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-03-01	20155529.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	16732891.00	19775236.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23030017
a0a6c0fb-2249-4f2f-a687-6470f8d496dd	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-04-01	19775236.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17113184.00	19394943.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23040017
a0a6c0fb-22fd-40c7-b953-0b5dc90e3e24	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-05-01	19394943.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17493477.00	19014650.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23050017
a0a6c0fb-238e-4da8-814a-e551ec8a05a1	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-06-01	19014650.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	17873770.00	18634357.52	2025-12-22 12:17:06	2025-12-22 12:17:06	DEP23060017
a0a6c0fb-2427-48da-b025-5f70b391d174	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-07-01	18634357.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	18254063.00	18254064.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23070017
a0a6c0fb-2711-4f2c-bc82-02905c25f9ef	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-08-01	18254064.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	18634356.00	17873771.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23080017
a0a6c0fb-27ee-4d21-9362-041bbc6b4064	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-09-01	17873771.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19014649.00	17493478.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23090017
a0a6c0fb-28a9-441c-91f6-e4d8e6c344a1	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-10-01	17493478.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19394942.00	17113185.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23100016
a0a6c0fb-2a36-4e26-9106-be9374f9bf3b	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-11-01	17113185.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	19775235.00	16732892.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23110016
a0a6c0fb-2ae4-4452-b09c-caeaa5dc4702	6504929e-7f0b-47a6-b6d6-25032344b55f	2023-12-01	16732892.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20155528.00	16352599.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23120016
a0a6c0fb-2b7c-41e5-929d-0272fef9c140	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-01-01	16352599.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20535821.00	15972306.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24010016
a0a6c0fb-2c17-48ea-a5fc-74a653360c83	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-02-01	15972306.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	20916114.00	15592013.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24020016
a0a6c0fb-2c9f-41c6-a007-60e08080175c	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-03-01	15592013.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	21296407.00	15211720.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24030016
a0a6c0fb-2d2e-4ac2-ba08-cc744a4da0c7	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-04-01	15211720.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	21676700.00	14831427.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24040016
a0a6c0fb-2de3-4486-a078-7a88a5a08f1b	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-05-01	14831427.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22056993.00	14451134.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24050016
a0a6c0fb-2e76-46ae-97be-f70d10266eb7	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-06-01	14451134.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22437286.00	14070841.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24060016
a0a6c0fb-2f17-4e94-92fc-5b92ce698d40	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-07-01	14070841.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	22817579.00	13690548.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24070016
a0a6c0fb-2fca-4bce-8f32-54f921ca5600	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-08-01	13690548.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23197872.00	13310255.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24080014
a0a6c0fb-304f-4a4a-b5ad-36e140bf50e6	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-09-01	13310255.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23578165.00	12929962.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24090014
a0a6c0fb-31b2-4dc1-b897-f4221899bb01	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-10-01	12929962.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	23958458.00	12549669.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24100013
a0a6c0fb-3252-47ec-8bcc-aac5cc20dba4	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-11-01	12549669.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	24338751.00	12169376.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24110010
a0a6c0fb-32dd-4b58-a59f-6cb1daf9f06d	6504929e-7f0b-47a6-b6d6-25032344b55f	2024-12-01	12169376.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	24719044.00	11789083.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24120010
a0a6c0fb-3363-4732-8001-9137f9b9a774	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-01-01	11789083.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25099337.00	11408790.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25010007
a0a6c0fb-359c-4cf9-8a5e-84d3abe9c006	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-02-01	11408790.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25479630.00	11028497.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25020006
a0a6c0fb-3875-4d35-a3f4-392e3a9b3038	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-03-01	11028497.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	25859923.00	10648204.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25030006
a0a6c0fb-392b-4dd4-86a6-0df1f55521bb	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-04-01	10648204.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	26240216.00	10267911.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25040006
a0a6c0fb-39ed-414e-b830-84ebded40435	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-05-01	10267911.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	26620509.00	9887618.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25050006
a0a6c0fb-3abb-4f1d-a192-1e298bff3ab4	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-06-01	9887618.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27000802.00	9507325.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25060006
a0a6c0fb-3b72-4c52-b576-0b65807e1c3f	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-07-01	9507325.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27381095.00	9127032.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25070006
a0a6c0fb-3c1c-4c28-8653-1f19c863734a	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-08-01	9127032.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	27761388.00	8746739.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25080006
a0a6c0fb-3cab-44b0-ba59-378aa9eb2587	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-09-01	8746739.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28141681.00	8366446.52	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25090006
a0a6c0f9-136b-441f-8131-f70afd850d79	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-10-01	51110773090.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10362665305.00	50935134695.00	2025-12-22 12:17:05	2025-12-24 11:50:27	DEP25100409
a0a6c0fb-4d05-40aa-a9fe-a3a8074f0cfe	19c63207-1947-4bb3-9193-554042ba6da7	2019-08-01	21673690.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	225767.00	21447923.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19080003
a0a6c0fb-4d9e-4fb5-87b6-23fb0a947672	19c63207-1947-4bb3-9193-554042ba6da7	2019-09-01	21447923.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	451534.00	21222156.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19090003
a0a6c0fb-50b8-4a4b-b08b-1db388282ae6	19c63207-1947-4bb3-9193-554042ba6da7	2019-10-01	21222156.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	677301.00	20996389.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19100004
a0a6c0fb-5236-44a1-a4b8-1a6ace4fb86d	19c63207-1947-4bb3-9193-554042ba6da7	2019-11-01	20996389.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	903068.00	20770622.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19110004
a0a6c0fb-52f7-4976-a588-54b3984525b9	19c63207-1947-4bb3-9193-554042ba6da7	2019-12-01	20770622.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1128835.00	20544855.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19120004
a0a6c0fb-53a9-4e42-89db-e5f8925843b2	19c63207-1947-4bb3-9193-554042ba6da7	2020-01-01	20544855.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1354602.00	20319088.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20010004
a0a6c0fb-5454-41f4-91d7-d41f302e923b	19c63207-1947-4bb3-9193-554042ba6da7	2020-02-01	20319088.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1580369.00	20093321.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20020004
a0a6c0fb-54fc-4d45-825b-f859bf1b254c	19c63207-1947-4bb3-9193-554042ba6da7	2020-03-01	20093321.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1806136.00	19867554.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20030004
a0a6c0fb-559d-4fdc-9e63-79915f3280c7	19c63207-1947-4bb3-9193-554042ba6da7	2020-04-01	19867554.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2031903.00	19641787.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20040004
a0a6c0fb-563b-43c1-bb9d-0ebf4186927f	19c63207-1947-4bb3-9193-554042ba6da7	2020-05-01	19641787.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2257670.00	19416020.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20050004
a0a6c0fb-56e0-43f7-b4bb-b9de6041d90e	19c63207-1947-4bb3-9193-554042ba6da7	2020-06-01	19416020.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2483437.00	19190253.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20060004
a0a6c0fb-5780-4da7-b9a1-3df2d82ac05c	19c63207-1947-4bb3-9193-554042ba6da7	2020-07-01	19190253.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2709204.00	18964486.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20070004
a0a6c0fb-593e-43b9-859d-62217ed57d35	19c63207-1947-4bb3-9193-554042ba6da7	2020-08-01	18964486.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2934971.00	18738719.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20080006
a0a6c0fb-5cbf-4d4a-a56b-68b6f348dffd	19c63207-1947-4bb3-9193-554042ba6da7	2020-09-01	18738719.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3160738.00	18512952.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20090006
a0a6c0fb-5da2-4473-88c3-1cfd55b1daa4	19c63207-1947-4bb3-9193-554042ba6da7	2020-10-01	18512952.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3386505.00	18287185.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20100007
a0a6c0fb-5e7c-450b-b773-4903a171d282	19c63207-1947-4bb3-9193-554042ba6da7	2020-11-01	18287185.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3612272.00	18061418.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20110010
a0a6c0fb-5f71-4027-8839-c4ba3d23d54a	19c63207-1947-4bb3-9193-554042ba6da7	2020-12-01	18061418.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3838039.00	17835651.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20120013
a0a6c0fb-610f-4c64-b921-659e456b65d1	19c63207-1947-4bb3-9193-554042ba6da7	2021-01-01	17835651.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4063806.00	17609884.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21010016
a0a6c0fb-61db-4b81-b806-04a2e68a8891	19c63207-1947-4bb3-9193-554042ba6da7	2021-02-01	17609884.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4289573.00	17384117.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21020017
a0a6c0fb-6298-48aa-88df-aca0c3a4c368	19c63207-1947-4bb3-9193-554042ba6da7	2021-03-01	17384117.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4515340.00	17158350.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21030017
a0a6c0fb-635e-42b6-b9fd-497093d272b5	19c63207-1947-4bb3-9193-554042ba6da7	2021-04-01	17158350.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4741107.00	16932583.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21040017
a0a6c0fb-6418-4ca3-9235-fc341ac10020	19c63207-1947-4bb3-9193-554042ba6da7	2021-05-01	16932583.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4966874.00	16706816.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21050017
a0a6c0fb-64d0-4be1-8d88-6cc16f47f835	19c63207-1947-4bb3-9193-554042ba6da7	2021-06-01	16706816.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5192641.00	16481049.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21060017
a0a6c0fb-6585-4991-b4ac-74b1b1bd7d15	19c63207-1947-4bb3-9193-554042ba6da7	2021-07-01	16481049.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5418408.00	16255282.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21070017
a0a6c0fb-663d-4339-a72f-4b5cb557ac31	19c63207-1947-4bb3-9193-554042ba6da7	2021-08-01	16255282.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5644175.00	16029515.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21080017
a0a6c0fb-66f2-403f-82c8-b41919d4034a	19c63207-1947-4bb3-9193-554042ba6da7	2021-09-01	16029515.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5869942.00	15803748.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21090017
a0a6c0fb-6a87-4408-8edf-7e55a911d572	19c63207-1947-4bb3-9193-554042ba6da7	2021-10-01	15803748.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6095709.00	15577981.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21100017
a0a6c0fb-6b23-4f5b-a857-80024b74e59e	19c63207-1947-4bb3-9193-554042ba6da7	2021-11-01	15577981.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6321476.00	15352214.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21110017
a0a6c0fb-6bb1-40e5-b08c-cb1545713417	19c63207-1947-4bb3-9193-554042ba6da7	2021-12-01	15352214.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6547243.00	15126447.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21120017
a0a6c0fb-6c55-4aba-acf0-6f9a17540a59	19c63207-1947-4bb3-9193-554042ba6da7	2022-01-01	15126447.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6773010.00	14900680.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22010017
a0a6c0fb-6d0a-49e9-bbe9-211fcb1d0b2e	19c63207-1947-4bb3-9193-554042ba6da7	2022-02-01	14900680.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6998777.00	14674913.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22020017
a0a6c0fb-6dc1-452a-a0ad-17025e720607	19c63207-1947-4bb3-9193-554042ba6da7	2022-03-01	14674913.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7224544.00	14449146.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22030017
a0a6c0fb-70df-45de-b654-793958900da3	19c63207-1947-4bb3-9193-554042ba6da7	2022-04-01	14449146.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7450311.00	14223379.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22040017
a0a6c0fb-71a0-4201-8327-54a342b8f99b	19c63207-1947-4bb3-9193-554042ba6da7	2022-05-01	14223379.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7676078.00	13997612.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22050017
a0a6c0fb-7239-4b2f-ab0e-d9e098ce4795	19c63207-1947-4bb3-9193-554042ba6da7	2022-06-01	13997612.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7901845.00	13771845.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22060017
a0a6c0fb-72d6-41dd-9145-b31c4343a5e0	19c63207-1947-4bb3-9193-554042ba6da7	2022-07-01	13771845.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8127612.00	13546078.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22070017
a0a6c0fb-7362-446c-a07e-1a1610cd3562	19c63207-1947-4bb3-9193-554042ba6da7	2022-08-01	13546078.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8353379.00	13320311.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22080017
a0a6c0fb-73e8-4e6d-9849-b6253b2c5f18	19c63207-1947-4bb3-9193-554042ba6da7	2022-09-01	13320311.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8579146.00	13094544.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22090017
a0a6c0fb-747b-4f30-ba21-ec0649077eb0	19c63207-1947-4bb3-9193-554042ba6da7	2022-10-01	13094544.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	8804914.00	12868776.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22100017
a0a6c0fb-7501-4b3a-8e82-720d741337d7	19c63207-1947-4bb3-9193-554042ba6da7	2022-11-01	12868776.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9030682.00	12643008.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22110017
a0a6c0fb-7597-4dc0-a42a-06b6e1807927	19c63207-1947-4bb3-9193-554042ba6da7	2022-12-01	12643008.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9256450.00	12417240.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22120017
a0a6c0fb-7633-4913-bfb6-971ca8fdd63a	19c63207-1947-4bb3-9193-554042ba6da7	2023-01-01	12417240.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9482218.00	12191472.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23010017
a0a6c0fb-76d5-49d4-8a8e-9fac8bb518ea	19c63207-1947-4bb3-9193-554042ba6da7	2023-02-01	12191472.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9707986.00	11965704.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23020018
a0a6c0fb-7838-49c0-a003-b68aee4ab807	19c63207-1947-4bb3-9193-554042ba6da7	2023-03-01	11965704.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9933754.00	11739936.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23030018
a0a6c0fb-78de-47c6-9895-9c4aa37ff671	19c63207-1947-4bb3-9193-554042ba6da7	2023-04-01	11739936.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10159522.00	11514168.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23040018
a0a6c0fb-7969-487e-b963-f4c7e887ed8f	19c63207-1947-4bb3-9193-554042ba6da7	2023-05-01	11514168.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10385290.00	11288400.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23050018
a0a6c0fb-7a03-49d9-9053-881c0c8db910	19c63207-1947-4bb3-9193-554042ba6da7	2023-06-01	11288400.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10611058.00	11062632.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23060018
a0a6c0fb-7ad0-4260-83a2-67e533c8cbb7	19c63207-1947-4bb3-9193-554042ba6da7	2023-07-01	11062632.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10836826.00	10836864.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23070018
a0a6c0fb-7bf8-4ad8-aabd-e63a00be091a	19c63207-1947-4bb3-9193-554042ba6da7	2023-08-01	10836864.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11062594.00	10611096.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23080018
a0a6c0fb-7cb0-467f-b0f5-9a8d8e5796c6	19c63207-1947-4bb3-9193-554042ba6da7	2023-09-01	10611096.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11288362.00	10385328.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23090018
a0a6c0fb-7d4f-4c04-8d55-5fa5809db590	19c63207-1947-4bb3-9193-554042ba6da7	2023-10-01	10385328.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11514130.00	10159560.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23100017
a0a6c0fb-7e44-4656-97b3-b7789c221c78	19c63207-1947-4bb3-9193-554042ba6da7	2023-11-01	10159560.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11739898.00	9933792.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23110017
a0a6c0fb-7f2e-4260-aa7f-8df6c15cc982	19c63207-1947-4bb3-9193-554042ba6da7	2023-12-01	9933792.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11965666.00	9708024.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23120017
a0a6c0fb-802b-405f-9cde-e1dcb9418872	19c63207-1947-4bb3-9193-554042ba6da7	2024-01-01	9708024.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12191434.00	9482256.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24010017
a0a6c0fb-80b7-4c9e-9a55-cf245e2f1267	19c63207-1947-4bb3-9193-554042ba6da7	2024-02-01	9482256.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12417202.00	9256488.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24020017
a0a6c0fb-8152-478b-b068-b0ab1fdb1862	19c63207-1947-4bb3-9193-554042ba6da7	2024-03-01	9256488.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12642970.00	9030720.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24030017
a0a6c0fb-81e5-430e-b778-c1446b0227f9	19c63207-1947-4bb3-9193-554042ba6da7	2024-04-01	9030720.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12868738.00	8804952.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24040017
a0a6c0fb-827c-4be3-8664-f92834d9d533	19c63207-1947-4bb3-9193-554042ba6da7	2024-05-01	8804952.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13094506.00	8579184.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24050017
a0a6c0fb-830b-4af2-860d-688c78e54802	19c63207-1947-4bb3-9193-554042ba6da7	2024-06-01	8579184.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13320274.00	8353416.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24060017
a0a6c0fb-838a-4d3a-a1f2-b50dbda38aad	19c63207-1947-4bb3-9193-554042ba6da7	2024-07-01	8353416.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13546042.00	8127648.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24070017
a0a6c0fb-8457-43c1-b022-5694fba88854	19c63207-1947-4bb3-9193-554042ba6da7	2024-08-01	8127648.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13771810.00	7901880.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24080015
a0a6c0fb-852d-469d-a4be-398a6199a2aa	19c63207-1947-4bb3-9193-554042ba6da7	2024-09-01	7901880.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13997578.00	7676112.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24090015
a0a6c0fb-85ff-42ef-9e8a-e961b2f1510b	19c63207-1947-4bb3-9193-554042ba6da7	2024-10-01	7676112.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14223346.00	7450344.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24100014
a0a6c0fb-8808-466f-a160-314060e3c815	19c63207-1947-4bb3-9193-554042ba6da7	2024-11-01	7450344.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14449114.00	7224576.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24110011
a0a6c0fb-891d-4f49-9434-c1c6c2d5af4e	19c63207-1947-4bb3-9193-554042ba6da7	2024-12-01	7224576.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14674882.00	6998808.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24120011
a0a6c0fb-8a41-4a0e-b291-cedfefcc4ea2	19c63207-1947-4bb3-9193-554042ba6da7	2025-01-01	6998808.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14900650.00	6773040.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25010008
a0a6c0fb-8b21-48ba-b5a7-b5c1de192381	19c63207-1947-4bb3-9193-554042ba6da7	2025-02-01	6773040.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15126418.00	6547272.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25020007
a0a6c0fb-8c10-43b0-977f-67daf99ad9cf	19c63207-1947-4bb3-9193-554042ba6da7	2025-03-01	6547272.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15352186.00	6321504.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25030007
a0a6c0fb-8cc2-49e1-b8b9-898186670bd7	19c63207-1947-4bb3-9193-554042ba6da7	2025-04-01	6321504.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15577954.00	6095736.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25040007
a0a6c0fb-8d76-4afb-b4fa-0178dc4cde5b	19c63207-1947-4bb3-9193-554042ba6da7	2025-05-01	6095736.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15803722.00	5869968.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25050007
a0a6c0fb-8e19-4021-8672-fd10c16e36d0	19c63207-1947-4bb3-9193-554042ba6da7	2025-06-01	5869968.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16029490.00	5644200.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25060007
a0a6c0fb-8fe8-49cc-ac79-e4ebeea475e5	19c63207-1947-4bb3-9193-554042ba6da7	2025-07-01	5644200.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16255258.00	5418432.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25070007
a0a6c0fb-90f2-4f14-b73e-6989686372ae	19c63207-1947-4bb3-9193-554042ba6da7	2025-08-01	5418432.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16481026.00	5192664.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25080007
a0a6c0fb-941c-4e8f-9721-068246427dc3	19c63207-1947-4bb3-9193-554042ba6da7	2025-09-01	5192664.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16706794.00	4966896.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25090007
a0a6c0f9-5cc5-43ba-888c-ad4049ffbf7e	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-10-01	51110773090.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10362665305.00	50935134695.00	2025-12-22 12:17:05	2025-12-24 11:50:27	DEP25100410
a0a6c0fb-a889-410c-9411-dce504e9b218	03e94a29-9883-46a5-9294-21d22f2fba7f	2019-08-01	21673690.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	225767.00	21447923.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19080004
a0a6c0fb-a9a2-4fd4-81cf-adf10319f84c	03e94a29-9883-46a5-9294-21d22f2fba7f	2019-09-01	21447923.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	451534.00	21222156.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19090004
a0a6c0fb-aa80-4aa7-8c5a-7450de1cdf84	03e94a29-9883-46a5-9294-21d22f2fba7f	2019-10-01	21222156.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	677301.00	20996389.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19100005
a0a6c0fb-ab82-470d-8a27-d01968921bec	03e94a29-9883-46a5-9294-21d22f2fba7f	2019-11-01	20996389.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	903068.00	20770622.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19110005
a0a6c0fb-ac8b-4506-b2fe-da1e4c4b96ed	03e94a29-9883-46a5-9294-21d22f2fba7f	2019-12-01	20770622.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1128835.00	20544855.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19120005
a0a6c0fb-ad58-48e6-8df7-9e36ae48bf0b	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-01-01	20544855.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1354602.00	20319088.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20010005
a0a6c0fb-ae18-4742-ba8d-ad38dcacb6a1	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-02-01	20319088.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1580369.00	20093321.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20020005
a0a6c0fb-aee9-4a2f-a721-5e224c158016	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-03-01	20093321.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	1806136.00	19867554.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20030005
a0a6c0fb-afdd-445b-b94b-7dcd02ad7b71	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-04-01	19867554.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2031903.00	19641787.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20040005
a0a6c0fb-b0db-4f29-9b53-cb53a814e475	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-05-01	19641787.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2257670.00	19416020.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20050005
a0a6c0fb-b187-4cd8-a206-78a4a40b7a9a	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-06-01	19416020.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2483437.00	19190253.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20060005
a0a6c0fb-b221-48b1-b942-d126331b5ef2	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-07-01	19190253.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2709204.00	18964486.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20070005
a0a6c0fb-b2b3-4e18-a8f0-717b3c760d5f	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-08-01	18964486.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	2934971.00	18738719.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20080007
a0a6c0fb-b389-4a8c-a5e7-f5baea75110e	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-09-01	18738719.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3160738.00	18512952.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20090007
a0a6c0fb-b43f-4e23-8d5c-f08d5b8e2ac3	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-10-01	18512952.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3386505.00	18287185.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20100008
a0a6c0fb-b6ea-44ec-9ff6-8b23649d81db	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-11-01	18287185.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3612272.00	18061418.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20110011
a0a6c0fb-b7e8-4873-9bc2-2ebd35c07e78	03e94a29-9883-46a5-9294-21d22f2fba7f	2020-12-01	18061418.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	3838039.00	17835651.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20120014
a0a6c0fb-b910-420e-acd8-b6fb08e3d90a	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-01-01	17835651.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4063806.00	17609884.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21010017
a0a6c0fb-b9d4-4f38-921e-f695af82b4b1	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-02-01	17609884.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4289573.00	17384117.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21020018
a0a6c0fb-bd4b-46d2-8494-690081511181	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-03-01	17384117.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4515340.00	17158350.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21030018
a0a6c0fb-be5a-47c3-a681-cf96c296fb9d	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-04-01	17158350.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4741107.00	16932583.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21040018
a0a6c0fb-c01e-4bfd-ac26-5a7f9f5f5386	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-05-01	16932583.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	4966874.00	16706816.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21050018
a0a6c0fb-c0fd-4cb4-88bb-4277d9e966f6	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-06-01	16706816.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5192641.00	16481049.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21060018
a0a6c0fb-c1ac-487c-8b3a-00ea653dfe9a	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-07-01	16481049.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5418408.00	16255282.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21070018
a0a6c0fb-c2e3-4194-b45d-2cd62e7b066b	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-08-01	16255282.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5644175.00	16029515.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21080018
a0a6c0fb-c47e-4659-809c-1ee0d8dd67e0	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-09-01	16029515.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	5869942.00	15803748.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21090018
a0a6c0fb-c55a-4077-9839-1b52e339b657	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-10-01	15803748.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6095709.00	15577981.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21100018
a0a6c0fb-c627-4568-be7d-d3e29f0913d5	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-11-01	15577981.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6321476.00	15352214.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21110018
a0a6c0fb-c74a-44cf-a3dc-ce026b2ff691	03e94a29-9883-46a5-9294-21d22f2fba7f	2021-12-01	15352214.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6547243.00	15126447.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21120018
a0a6c0fb-c805-4f31-8f3d-e614ad433e57	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-01-01	15126447.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6773010.00	14900680.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22010018
a0a6c0fb-c8a7-4b3e-a049-077cefc6797d	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-02-01	14900680.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	6998777.00	14674913.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22020018
a0a6c0fb-c931-4d0d-9cc8-178c711a3cf6	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-03-01	14674913.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7224544.00	14449146.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22030018
a0a6c0fb-c9d3-46d1-a809-efca09d93d07	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-04-01	14449146.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7450311.00	14223379.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22040018
a0a6c0fb-cb18-4a13-9e70-c308f9359fd6	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-05-01	14223379.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7676078.00	13997612.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22050018
a0a6c0fb-cc2b-4f5a-ba5d-0c927c579e0e	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-06-01	13997612.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	7901845.00	13771845.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22060018
a0a6c0fb-ce58-4b50-b407-f621bbac6710	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-07-01	13771845.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8127612.00	13546078.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22070018
a0a6c0fb-cf12-4b94-b1a9-4aac512fd9a1	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-08-01	13546078.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8353379.00	13320311.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22080018
a0a6c0fb-cfa2-4925-b9e4-a97d4ff5ef89	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-09-01	13320311.48	0.00	0.00	0.00	0.00	0.00	0.00	225767.00	8579146.00	13094544.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22090018
a0a6c0fb-d068-4084-87c2-1b4d5f063cb9	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-10-01	13094544.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	8804914.00	12868776.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22100018
a0a6c0fb-d5f5-4f24-90ec-cefbd6f5dc87	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-11-01	12868776.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9030682.00	12643008.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22110018
a0a6c0fb-d903-4670-99ca-5ac07a5548d8	03e94a29-9883-46a5-9294-21d22f2fba7f	2022-12-01	12643008.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9256450.00	12417240.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22120018
a0a6c0fb-d9d1-4829-8f57-e712dbfa0caa	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-01-01	12417240.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9482218.00	12191472.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23010018
a0a6c0fb-da85-42f6-8665-d6a6d2ea20f3	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-02-01	12191472.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9707986.00	11965704.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23020019
a0a6c0fb-db4b-49f4-adcc-0772a3fb3add	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-03-01	11965704.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	9933754.00	11739936.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23030019
a0a6c0fb-dc01-4230-ba9a-23f771647f55	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-04-01	11739936.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10159522.00	11514168.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23040019
a0a6c0fb-dcb5-40a3-b360-ddd9d79e8c9a	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-05-01	11514168.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10385290.00	11288400.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23050019
a0a6c0fb-dd4d-4bd1-a678-76cf01bdb09f	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-06-01	11288400.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10611058.00	11062632.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23060019
a0a6c0fb-de07-4654-ae12-1338218ab9d7	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-07-01	11062632.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	10836826.00	10836864.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23070019
a0a6c0fb-dec6-4a67-8ed3-a61668a3c981	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-08-01	10836864.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11062594.00	10611096.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23080019
a0a6c0fb-df78-42d9-ae3a-26c79440f6ca	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-09-01	10611096.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11288362.00	10385328.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23090019
a0a6c0fb-e02b-47dc-8ffa-409f10d207b2	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-10-01	10385328.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11514130.00	10159560.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23100018
a0a6c0fb-e0e7-424b-9784-adc26b07c744	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-11-01	10159560.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11739898.00	9933792.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23110018
a0a6c0fb-e18e-4a44-92bc-fdf7ecf35ab6	03e94a29-9883-46a5-9294-21d22f2fba7f	2023-12-01	9933792.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	11965666.00	9708024.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23120018
a0a6c0fb-e246-439a-ab9a-2709170b57ed	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-01-01	9708024.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12191434.00	9482256.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24010018
a0a6c0fb-e2fc-4ded-9013-700be885b902	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-02-01	9482256.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12417202.00	9256488.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24020018
a0a6c0fb-e3b3-47e7-85ba-d42568be73a7	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-03-01	9256488.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12642970.00	9030720.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24030018
a0a6c0fb-e542-4ee2-aafa-d9a41e1763d2	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-04-01	9030720.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	12868738.00	8804952.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24040018
a0a6c0fb-e636-41bb-b44a-efa3a190e31b	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-05-01	8804952.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13094506.00	8579184.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24050018
a0a6c0fb-e708-4f36-9f70-46d0efcd6ed6	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-06-01	8579184.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13320274.00	8353416.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24060018
a0a6c0fb-e7bd-4c00-a437-dcaa73791b9e	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-07-01	8353416.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13546042.00	8127648.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24070018
a0a6c0fb-e869-4aa5-ab1b-f4ee1182812f	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-08-01	8127648.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13771810.00	7901880.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24080016
a0a6c0fb-e91a-488a-837a-3b64a13aa7ff	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-09-01	7901880.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	13997578.00	7676112.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24090016
a0a6c0fb-e9c9-4b01-8671-fd24d2bb06c4	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-10-01	7676112.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14223346.00	7450344.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24100015
a0a6c0fb-eab2-4d1c-ac62-7b41934a2d2b	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-11-01	7450344.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14449114.00	7224576.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24110012
a0a6c0fb-eb92-4de4-b1b6-07f1b2dd358e	03e94a29-9883-46a5-9294-21d22f2fba7f	2024-12-01	7224576.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14674882.00	6998808.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24120012
a0a6c0fb-ed21-4031-ada6-142ffdc3dcf0	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-01-01	6998808.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	14900650.00	6773040.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25010009
a0a6c0fb-edb8-4bc3-beae-caab7f5fd190	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-02-01	6773040.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15126418.00	6547272.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25020008
a0a6c0fb-ee75-4cf9-8e5f-b16be45a7988	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-03-01	6547272.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15352186.00	6321504.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25030008
a0a6c0fb-ef07-474b-b617-03204c82f7a0	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-04-01	6321504.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15577954.00	6095736.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25040008
a0a6c0fb-f159-488c-8a12-2da68a7da8c7	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-05-01	6095736.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	15803722.00	5869968.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25050008
a0a6c0fb-f202-4810-b847-b00701e83278	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-06-01	5869968.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16029490.00	5644200.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25060008
a0a6c0fb-f2b4-415a-93e0-a63d2936dec3	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-07-01	5644200.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16255258.00	5418432.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25070008
a0a6c0fb-f35f-4970-8194-086a0392e7f4	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-08-01	5418432.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16481026.00	5192664.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25080008
a0a6c0fb-f3f8-48d8-821a-a3c8ea9e73d5	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-09-01	5192664.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16706794.00	4966896.48	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25090008
a0a7306c-7317-441a-851b-40abb8b0004a	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	209440000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:28	DEP25100411
a0a6c0fc-094f-4127-b396-e2f89a57512c	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2019-11-01	18275000.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	190364.00	18084636.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19110006
a0a6c0fc-0a11-4f35-9034-162b04b17e2a	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2019-12-01	18084636.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	380728.00	17894272.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP19120006
a0a6c0fc-0ad0-41aa-98fc-ee9dab5b1326	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-01-01	17894272.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	571092.00	17703908.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20010006
a0a6c0fc-0dd2-4c46-bccb-b0dccce9855e	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-02-01	17703908.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	761456.00	17513544.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20020006
a0a6c0fc-0ecd-430c-99f6-3e43f1905bf2	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-03-01	17513544.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	951820.00	17323180.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20030006
a0a6c0fc-0fa7-40cc-ba7a-c525ac035afc	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-04-01	17323180.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	1142184.00	17132816.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20040006
a0a6c0fc-1068-44a2-b649-001cc8066126	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-05-01	17132816.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	1332548.00	16942452.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20050006
a0a6c0fc-112f-4100-bcba-d9c2ec0731de	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-06-01	16942452.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	1522912.00	16752088.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20060006
a0a6c0fc-11f6-41fb-9bff-decdafc55261	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-07-01	16752088.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	1713276.00	16561724.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20070006
a0a6c0fc-12b5-4cec-850e-f2d9b778811b	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-08-01	16561724.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	1903640.00	16371360.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20080008
a0a6c0fc-1377-499f-8921-652e1d84fae3	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-09-01	16371360.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	2094004.00	16180996.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20090008
a0a6c0fc-140a-47b8-85d9-2241e5b7476a	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-10-01	16180996.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	2284368.00	15990632.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20100009
a0a6c0fc-14bb-4d4b-8f31-3fa07f3fce58	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-11-01	15990632.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	2474732.00	15800268.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20110012
a0a6c0fc-1571-414d-b601-0ac511f31feb	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2020-12-01	15800268.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	2665096.00	15609904.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP20120015
a0a6c0fc-161e-4fd5-a5bf-600b39ed56e8	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-01-01	15609904.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	2855460.00	15419540.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21010018
a0a6c0fc-1890-4318-8eea-45cf4f735c3d	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-02-01	15419540.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3045824.00	15229176.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21020019
a0a6c0fc-194a-45b5-8502-c76761d7f5ec	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-03-01	15229176.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3236188.00	15038812.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21030019
a0a6c0fc-1a09-45df-a204-aac0135a04dd	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-04-01	15038812.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3426552.00	14848448.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21040019
a0a6c0fc-1add-45ea-990a-7ec4488b5d12	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-05-01	14848448.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3616916.00	14658084.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21050019
a0a6c0fc-1c9c-4d4c-980d-da8e94ee7987	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-06-01	14658084.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3807280.00	14467720.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21060019
a0a6c0fc-1e92-4449-a6da-7183e26fa1fb	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-07-01	14467720.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	3997644.00	14277356.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21070019
a0a6c0fc-204c-4934-ae63-9731318ac957	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-08-01	14277356.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	4188008.00	14086992.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21080019
a0a6c0fc-2192-4f26-8d52-953c1b5b88d9	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-09-01	14086992.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	4378372.00	13896628.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21090019
a0a6c0fc-2251-4d69-9413-718299d094d5	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-10-01	13896628.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	4568736.00	13706264.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21100019
a0a6c0fc-22fe-4c7e-a69a-48dacb1338a9	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-11-01	13706264.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	4759100.00	13515900.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21110019
a0a6c0fc-237e-4411-b5a1-c17c7095f028	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2021-12-01	13515900.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	4949464.00	13325536.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP21120019
a0a6c0fc-2416-4d85-aaaa-e010c164a765	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-01-01	13325536.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	5139828.00	13135172.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22010019
a0a6c0fc-24b7-4db1-98c3-3590cfc46372	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-02-01	13135172.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	5330192.00	12944808.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22020019
a0a6c0fc-2555-460b-bbb0-4fd129ec1cc1	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-03-01	12944808.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	5520556.00	12754444.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22030019
a0a6c0fc-25fc-45b5-95e6-f42c3ea61eb1	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-04-01	12754444.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	5710920.00	12564080.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22040019
a0a6c0fc-26b2-4147-a5bb-6942039c8ba5	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-05-01	12564080.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	5901284.00	12373716.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22050019
a0a6c0fc-275e-49a1-9651-597974c88719	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-06-01	12373716.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	6091648.00	12183352.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22060019
a0a6c0fc-2882-497a-baec-19fdb9cfb462	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-07-01	12183352.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	6282012.00	11992988.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22070019
a0a6c0fc-29ae-4bb7-99ee-8d5f74a19fe8	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-08-01	11992988.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	6472376.00	11802624.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22080019
a0a6c0fc-2aa1-46b2-85d4-7ea4b6969578	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-09-01	11802624.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	6662740.00	11612260.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22090019
a0a6c0fc-2b62-4b23-bce4-bfbcc392fd70	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-10-01	11612260.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	6853104.00	11421896.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22100019
a0a6c0fc-2c56-4080-bafe-e9181367709b	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-11-01	11421896.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	7043468.00	11231532.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22110019
a0a6c0fc-2d79-4562-a9b7-21636841509f	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2022-12-01	11231532.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	7233832.00	11041168.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP22120019
a0a6c0fc-2e6a-4de2-939b-17e6c74b74d9	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-01-01	11041168.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	7424196.00	10850804.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23010019
a0a6c0fc-2f2c-46c7-ae57-053f319fc7b7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-02-01	10850804.00	0.00	0.00	0.00	0.00	0.00	0.00	190364.00	7614560.00	10660440.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23020020
a0a6c0fc-300b-4732-bfc7-9d82f52f508c	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-03-01	10660440.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	7804925.00	10470075.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23030020
a0a6c0fc-30e7-48ed-92d0-8aec674df374	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-04-01	10470075.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	7995290.00	10279710.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23040020
a0a6c0fc-31da-4084-b40a-63ce1dcfb9d8	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-05-01	10279710.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	8185655.00	10089345.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23050020
a0a6c0fc-32b2-4cd6-9fdb-59e57ab281c4	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-06-01	10089345.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	8376020.00	9898980.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23060020
a0a6c0fc-3378-4a92-acd7-e596e80a0b8a	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-07-01	9898980.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	8566385.00	9708615.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23070020
a0a6c0fc-345d-4150-aa76-083e78e793e8	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-08-01	9708615.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	8756750.00	9518250.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23080020
a0a6c0fc-3541-4ab9-a6ba-5a726e0713a7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-09-01	9518250.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	8947115.00	9327885.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23090020
a0a6c0fc-360f-4c7c-91ea-dd700cc0e33e	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-10-01	9327885.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	9137480.00	9137520.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23100019
a0a6c0fc-36c6-4e06-b80e-2c0ff1b9d617	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-11-01	9137520.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	9327845.00	8947155.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23110019
a0a6c0fc-3797-47ad-8f7a-d0f0a7353fc6	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2023-12-01	8947155.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	9518210.00	8756790.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP23120019
a0a6c0fc-39ab-471a-adf8-b47f14fed3e5	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-01-01	8756790.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	9708575.00	8566425.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24010019
a0a6c0fc-3acd-4e81-9e8d-513b7c80f763	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-02-01	8566425.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	9898940.00	8376060.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24020019
a0a6c0fc-3bab-40b1-bd0f-95534bb2bf74	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-03-01	8376060.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	10089305.00	8185695.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24030019
a0a6c0fc-3d54-4f17-b07d-e13fd356b8d7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-04-01	8185695.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	10279670.00	7995330.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24040019
a0a6c0fc-3e9c-4aaa-a8f2-68b5d2d200e1	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-05-01	7995330.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	10470035.00	7804965.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24050019
a0a6c0fc-3f8f-4f5c-835d-a714fea41b03	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-06-01	7804965.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	10660400.00	7614600.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24060019
a0a6c0fc-4062-41c9-9782-b4980f43a7b7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-07-01	7614600.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	10850765.00	7424235.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24070019
a0a6c0fc-4149-4e46-97e5-d02d7094d4cc	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-08-01	7424235.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11041130.00	7233870.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24080017
a0a6c0fc-41f6-49a5-bc0e-83b8dc27f15e	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-09-01	7233870.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11231495.00	7043505.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24090017
a0a6c0fc-4390-42c3-b70e-dadf9d50467f	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-10-01	7043505.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11421860.00	6853140.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24100016
a0a6c0fc-4455-4f86-b3c8-b1ade94713df	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-11-01	6853140.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11612225.00	6662775.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24110013
a0a6c0fc-4550-43ce-bcad-42101453da97	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2024-12-01	6662775.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11802590.00	6472410.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP24120013
a0a6c0fc-4620-4771-b169-43c4bc8524e7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-01-01	6472410.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	11992955.00	6282045.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25010010
a0a6c0fc-46cb-4ac6-8755-a2de83c5f198	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-02-01	6282045.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	12183320.00	6091680.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25020009
a0a6c0fc-47a1-4fde-b74b-59580b9a40b7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-03-01	6091680.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	12373685.00	5901315.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25030009
a0a6c0fc-486a-4cdf-bb36-ba8063f89236	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-04-01	5901315.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	12564050.00	5710950.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25040009
a0a6c0fc-4947-4980-9e5a-9b23b27ac33c	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-05-01	5710950.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	12754415.00	5520585.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25050009
a0a6c0fc-49d3-46f8-844f-085c5acf0301	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-06-01	5520585.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	12944780.00	5330220.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25060009
a0a6c0fc-4b33-46a4-954b-4d6e99d2d6b7	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-07-01	5330220.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	13135145.00	5139855.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25070009
a0a6c0fc-4d31-4fe4-bb8a-2dcbb6030e50	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-08-01	5139855.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	13325510.00	4949490.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25080009
a0a6c0fc-4f24-42a8-919f-f0af818d256f	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-09-01	4949490.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	13515875.00	4759125.00	2025-12-22 12:17:07	2025-12-22 12:17:07	DEP25090009
a0a7306c-7704-4862-830d-f68c343a15f6	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	58822500.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:28	DEP25100412
a0a7306c-7ab0-4522-aa07-009431e40a02	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	46750000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:28	DEP25100413
a0a7306c-7e28-41e5-bcc2-85515e7da8fc	99970f15-9c4a-4d4f-b550-a7ef488054d0	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	398612500.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:28	DEP25100414
a0a6c0fc-510a-465b-b0db-6bc4a3ed8f47	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-11-01	4568760.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	13896605.00	4378395.00	2025-12-22 12:17:07	2025-12-24 11:50:54	DEP25110735
a0a6c16a-2e83-461c-8ac6-f57b72152049	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	28240000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120490
a0a6c0fa-8fc2-46e7-b65d-02561b784a30	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-10-01	99313552.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	204834192.00	93106455.12	2025-12-22 12:17:06	2025-12-24 11:50:28	DEP25100415
a0a6c0fa-ee5d-45f2-b9da-d642e15dd160	101fda0f-877a-4290-9df5-00a84859c3e9	2025-10-01	8366446.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28521974.00	7986153.52	2025-12-22 12:17:06	2025-12-24 11:50:28	DEP25100416
a0a6c0fb-3d3b-40f8-a164-8974bb07ba47	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-10-01	8366446.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28521974.00	7986153.52	2025-12-22 12:17:07	2025-12-24 11:50:28	DEP25100417
a0a72eba-cd92-4b2e-a721-0ce412be9b6f	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-05-01	996336188.00	0.00	0.00	0.00	0.00	0.00	0.00	20757003.00	20757003.00	975579185.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25050010
a0a72eba-d086-4228-a858-fc9f7f13b4e6	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-06-01	975579185.00	0.00	0.00	0.00	0.00	0.00	0.00	20757003.00	41514006.00	954822182.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25060010
a0a72eba-d2a3-43ac-a46d-0ea96beca33d	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-07-01	954822182.00	0.00	0.00	0.00	0.00	0.00	0.00	20757003.00	62271009.00	934065179.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25070010
a0a72eba-d428-4a04-9c38-376692677772	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-08-01	934065179.00	0.00	0.00	0.00	0.00	0.00	0.00	20757003.00	83028012.00	913308176.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25080010
a0a72eba-d653-4da5-a405-63fb8d607930	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-09-01	913308176.00	0.00	0.00	0.00	0.00	0.00	0.00	20757004.00	103785016.00	892551172.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25090010
a0a6c0fb-9523-4364-a707-5befeee1ca9a	19c63207-1947-4bb3-9193-554042ba6da7	2025-10-01	4966896.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16932562.00	4741128.48	2025-12-22 12:17:07	2025-12-24 11:50:28	DEP25100418
a0a72eba-e800-4dac-9285-d88912a76f75	e47f3b62-82ae-4322-8660-bf104df108a5	2025-08-01	10323000.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	215062.00	10107938.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25080011
a0a72eba-e976-4a7c-a50b-c8aeedb3f292	e47f3b62-82ae-4322-8660-bf104df108a5	2025-09-01	10107938.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	430124.00	9892876.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25090011
a0a6c0fb-f5f5-43db-9b73-9c9bd2fa2e5f	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-10-01	4966896.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	16932562.00	4741128.48	2025-12-22 12:17:07	2025-12-24 11:50:28	DEP25100419
a0a72eba-fa12-4506-ad22-99dbdb9e8149	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025-08-01	10323000.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	215062.00	10107938.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25080012
a0a72eba-fad0-4ee4-8b15-9227d7850081	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025-09-01	10107938.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	430124.00	9892876.00	2025-12-22 17:23:59	2025-12-22 17:23:59	DEP25090012
a0a6c0fc-5028-4dfb-b92e-42179b6cd6b8	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-10-01	4759125.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	13706240.00	4568760.00	2025-12-22 12:17:07	2025-12-24 11:50:28	DEP25100420
a0a72ebb-bada-4efa-95ef-494da13ad672	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-02-01	27750000.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	578125.00	27171875.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25020010
a0a72ebb-bda5-417c-b300-714da404d7b6	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-03-01	27171875.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	1156250.00	26593750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25030010
a0a72ebb-c0e3-4225-a186-9ebb80f8616f	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-04-01	26593750.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	1734375.00	26015625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040010
a0a72ebb-c193-4fa7-8c61-9cefd69112cf	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-05-01	26015625.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	2312500.00	25437500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050011
a0a72ebb-c237-4443-b5f3-129cb91d5d70	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-06-01	25437500.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	2890625.00	24859375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060011
a0a72ebb-c357-418d-befb-a98ba595be6d	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-07-01	24859375.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	3468750.00	24281250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070011
a0a72ebb-c427-40e0-b155-e8a9644e443d	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-08-01	24281250.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	4046875.00	23703125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080013
a0a72ebb-c67a-4db8-820d-99944863944b	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-09-01	23703125.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	4625000.00	23125000.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090013
a0a72eba-bb4b-4cda-8bba-0fa8ad653fb3	36e92940-a131-4ac0-b45b-b8500ff4b040	2025-10-01	70790251.00	0.00	0.00	0.00	0.00	0.00	0.00	1474796.00	1474796.00	69315455.00	2025-12-22 17:23:59	2025-12-24 11:50:28	DEP25100421
a0a72eba-d722-4252-8499-3d85d8c4b1ae	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-10-01	892551172.00	0.00	0.00	0.00	0.00	0.00	0.00	20757004.00	124542020.00	871794168.00	2025-12-22 17:23:59	2025-12-24 11:50:28	DEP25100422
a0a72eba-eaad-4357-a474-42bcebec33fa	e47f3b62-82ae-4322-8660-bf104df108a5	2025-10-01	9892876.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	645186.00	9677814.00	2025-12-22 17:23:59	2025-12-24 11:50:28	DEP25100423
a0a72eba-fb7c-4e62-a58f-fbc7cd6542a5	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025-10-01	9892876.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	645186.00	9677814.00	2025-12-22 17:23:59	2025-12-24 11:50:28	DEP25100424
a0a72ebc-1b8b-4f7b-8177-5b041bf6371f	f743b734-490e-470d-bc30-19e730a855b2	2025-04-01	72150000.00	0.00	0.00	0.00	0.00	0.00	0.00	1503124.00	1503124.00	70646876.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040011
a0a72ebc-1c6b-4d8e-b98f-d2f6f6b302f2	f743b734-490e-470d-bc30-19e730a855b2	2025-05-01	70646876.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	3006249.00	69143751.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050012
a0a72ebc-1d5e-4533-ad92-a64f2982e940	f743b734-490e-470d-bc30-19e730a855b2	2025-06-01	69143751.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	4509374.00	67640626.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060012
a0a72ebc-1e21-4b21-a432-dc12d7002994	f743b734-490e-470d-bc30-19e730a855b2	2025-07-01	67640626.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	6012499.00	66137501.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070012
a0a6c16a-456c-49fe-a9e7-c63133caad7a	c88e2c69-914f-403e-ab36-0a9322d6591f	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120494
a0a6c16a-4959-4006-9ed8-a0206b8d2128	9580ea1b-0f93-4c89-b167-a089131d5761	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120495
a0a6c16a-4ede-49df-816d-2a1b697f9026	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120496
a0a6c16a-5442-4a4c-8b27-543598eb23e9	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-12-01	50759496300.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10713942095.00	50583857905.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120497
a0a6c16a-59e2-4935-92bb-502bbbe72c66	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-12-01	50759496300.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10713942095.00	50583857905.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120498
a0a6c16a-5e76-4c1e-9ec7-562ec2a5dead	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-12-01	50759496300.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10713942095.00	50583857905.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120499
a0a6c16a-6334-4578-8ec3-c279ef0b6500	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	209440000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120500
a0a6c16a-67ea-4185-83b0-6c386b2be744	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	58822500.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120501
a0a6c16a-6dfa-4990-ab4e-fc45118f4ac0	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	46750000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120502
a0a72ebc-1efd-4e91-aee9-e477714d1c9c	f743b734-490e-470d-bc30-19e730a855b2	2025-08-01	66137501.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	7515624.00	64634376.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080014
a0a72ebc-2012-46f6-9f41-ffa2f8aa27fd	f743b734-490e-470d-bc30-19e730a855b2	2025-09-01	64634376.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	9018749.00	63131251.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090014
a0a72ebc-2217-4739-bf0a-42bf9745596a	f743b734-490e-470d-bc30-19e730a855b2	2025-11-01	61628126.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	12024999.00	60125001.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110755
a0a72ebc-3332-4791-a18a-75d901761e26	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-04-01	35520000.00	0.00	0.00	0.00	0.00	0.00	0.00	739999.00	739999.00	34780001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040012
a0a72ebc-34a7-4e3d-93e3-28c8ee95ffdd	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-05-01	34780001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	1479999.00	34040001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050013
a0a72ebc-365b-488a-b360-c74f5a2bb6bd	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-06-01	34040001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	2219999.00	33300001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060013
a0a72ebc-378f-4917-a7de-7bea9e9d1e71	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-07-01	33300001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	2959999.00	32560001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070013
a0a72ebc-38c8-4a3e-8d1c-a905c2b203b1	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-08-01	32560001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	3699999.00	31820001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080015
a0a72ebc-39db-4528-916f-4f26a90a4999	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-09-01	31820001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	4439999.00	31080001.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090015
a0a72ebb-c797-4ca6-aeff-35e3524e8e05	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-10-01	23125000.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	5203125.00	22546875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100425
a0a72ebc-504d-444d-9b2f-e37f0af07e3c	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040013
a0a72ebc-5108-4bbf-b709-f439fc974df2	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050014
a0a72ebc-51c6-4bbb-998b-ad431586d1d0	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060014
a0a72ebc-5272-4bf3-befe-88ad5144ee89	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070014
a0a72ebc-531d-4534-9c36-876ffd5b3111	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080016
a0a72ebc-53ef-4151-a3fe-19c1988c5e18	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090016
a0a72ebb-d75d-40d4-b335-64aeddaf8531	9dbcc529-de27-4753-a772-90aa5f8c7894	2025-10-01	25794000.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	537375.00	25256625.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100426
a0a72ebc-656c-4332-a5ae-5114778d1ec9	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040014
a0a72ebc-664a-4be9-a30d-4043670612f3	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050015
a0a72ebc-6750-4949-8b83-5bcd771ac046	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060015
a0a72ebc-6810-4040-9de3-c9ade21e911d	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070015
a0a72ebc-68f1-465d-9771-ca1339322924	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080017
a0a72ebc-69c0-4bff-9a81-9c0a19e9a093	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090017
a0a72ebb-e963-4adc-b65c-432c1a29efc2	47665328-ff67-40a5-aac0-24572afbdcf8	2025-10-01	25794000.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	537375.00	25256625.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100427
a0a72ebc-7927-403f-b5f7-e24db9895b8f	30a9ed88-3599-4d7f-8456-cce980762f96	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040015
a0a72ebc-7a01-489a-aade-5e8fe2363ef0	30a9ed88-3599-4d7f-8456-cce980762f96	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050016
a0a72ebc-7afa-48ad-949a-2166660e9437	30a9ed88-3599-4d7f-8456-cce980762f96	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060016
a0a72ebc-7c2f-4535-8ad7-d08fe0905b82	30a9ed88-3599-4d7f-8456-cce980762f96	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070016
a0a72ebc-7cd6-4cac-9e33-815e2c6728e6	30a9ed88-3599-4d7f-8456-cce980762f96	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080018
a0a72ebc-7d70-485b-a706-a7a6c65fd353	30a9ed88-3599-4d7f-8456-cce980762f96	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090018
a0a72ebb-f819-43f5-9c17-ad4bada6136b	747d2923-ba5d-475d-a784-e41bc58e5561	2025-10-01	25794000.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	537375.00	25256625.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100428
a0a72ebc-8fdd-4779-a28f-c8223c9d808d	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040016
a0a72ebc-90c4-47d3-9303-241e8c1734f2	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050017
a0a72ebc-91a7-4e2f-8ff2-8f28b191eeb1	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060017
a0a72ebc-928c-4899-808c-5c0ba0f05acf	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070017
a0a72ebc-934a-44b3-bcc7-f47d7b80ed24	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080019
a0a72ebc-9449-4963-b32d-7dc6215a28c2	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090019
a0a72ebc-0749-405b-886c-2c304a50f72e	2f8f647c-1936-4b32-93f7-9ebbcda6d039	2025-10-01	25794000.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	537375.00	25256625.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100429
a0a72ebc-a69f-4f08-b22c-cc42c1ab60ae	34453391-14df-41b0-8475-2d31c5371f29	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25040017
a0a72ebc-a797-42cf-8a4c-d47e2bdbd199	34453391-14df-41b0-8475-2d31c5371f29	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25050018
a0a72ebc-a87b-4e41-9c1e-900d93d371dc	34453391-14df-41b0-8475-2d31c5371f29	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25060018
a0a72ebc-a9ed-4187-89be-e61a8b644959	34453391-14df-41b0-8475-2d31c5371f29	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25070018
a0a72ebc-ab03-4696-9f3c-321337f70a85	34453391-14df-41b0-8475-2d31c5371f29	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25080020
a0a72ebc-ac22-4f19-b4ce-2197ea4013f3	34453391-14df-41b0-8475-2d31c5371f29	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:00	2025-12-22 17:24:00	DEP25090020
a0a72ebc-2146-4718-84c4-ed4e6c87c4fa	f743b734-490e-470d-bc30-19e730a855b2	2025-10-01	63131251.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	10521874.00	61628126.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100430
a0a72ebc-c4e6-4f26-afce-21972037c63d	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040018
a0a72ebc-c70c-4e00-a77a-1d9c37bae226	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050019
a0a72ebc-c96e-439a-a67e-df6060b2421b	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060019
a0a72ebc-ca54-491b-bf3c-d896e628babb	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070019
a0a72ebc-cb78-455e-82e7-72c1391a9cf7	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080021
a0a72ebc-ccd7-441a-9b34-0730cec53516	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090021
a0a72ebc-3afe-491c-99c7-aa76c4a25598	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-10-01	31080001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	5179999.00	30340001.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100431
a0a72ebc-e527-488c-b2b3-8d82e9b7f90d	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040019
a0a72ebc-e5fc-4ca0-bfd5-81aceacc046c	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050020
a0a72ebc-e6db-400a-89d6-228dbe62d184	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060020
a0a72ebc-e7ae-431c-8f1d-1ed5ef5263fc	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070020
a0a72ebc-e879-4889-a73e-381d478f0686	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080022
a0a72ebc-e9ca-420b-a185-af2f421adaeb	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090022
a0a72ebc-549e-4bb1-82b2-b588a4d81da3	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100432
a0a72ebc-fa33-49a5-ac0f-d2f4198634b3	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040020
a0a72ebc-fb41-4985-baa4-2f5895ca078c	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050021
a0a72ebc-fc0c-419a-9fbf-2717f84405ee	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060021
a0a72ebc-fe1c-4686-a15b-4b26bbc4d566	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070021
a0a72ebc-ff7c-469b-9349-8b5cc2c8f065	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080023
a0a72ebd-011c-4be4-9a10-ce4ae3d49884	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090023
a0a72ebc-6ab5-4bbf-8f58-1ff00cbd8c54	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100433
a0a72ebd-1ab7-494b-a397-b6335343dd79	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040021
a0a72ebd-1c15-44a6-b6e8-a44989620f30	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050022
a0a72ebd-1d13-490d-a607-6d3c36983b9c	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060022
a0a72ebd-1e28-4392-8dfc-19a4d73b2522	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070022
a0a72ebd-1f3f-4d61-839c-3c59c0571ef9	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080024
a0a72ebd-202f-439d-84a9-e97c1b2455cf	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090024
a0a72ebc-7e59-4716-be0f-5ae95173d9de	30a9ed88-3599-4d7f-8456-cce980762f96	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100434
a0a72ebd-3532-418b-8ba5-e6bc1f14060b	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040022
a0a72ebd-35f8-4a95-8e13-e675ae682765	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050023
a0a72ebd-379c-466f-b110-cb5b91b69600	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060023
a0a72ebd-38a7-4473-9d5f-16235eb7f1e0	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070023
a0a72ebd-39f8-4403-9279-b184f549a322	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080025
a0a72ebd-3b06-4afa-8a15-c4182789b6f8	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090025
a0a72ebc-9574-4479-bfa0-89b50a26a3fe	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100435
a0a72ebd-4fab-411f-bacb-f1f5f5499e84	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040023
a0a72ebd-5081-4600-9435-e16a58d8c17f	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050024
a0a72ebd-522d-476e-a53e-a60da85a8054	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060024
a0a72ebd-546c-44b0-b843-20902b338d07	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070024
a0a72ebd-56c7-4e0e-bf04-0bc8d522c152	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080026
a0a72ebd-595f-49a6-ae08-4157feecbbec	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090026
a0a72ebc-ad7e-4159-966d-7b25b832e0d4	34453391-14df-41b0-8475-2d31c5371f29	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:00	2025-12-24 11:50:28	DEP25100436
a0a72ebd-7962-4efc-98e2-a5b1bdc89886	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040024
a0a72ebd-7a01-44d3-94f4-3d3df02cbd57	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050025
a0a72ebd-7b12-42a2-b0c0-f6f882fc3053	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060025
a0a72ebd-7c6b-4062-a1a8-7708e40a93ce	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070025
a0a72ebd-7d90-47a2-9e96-708c4bc8a9ea	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080027
a0a72ebd-7ea7-4723-a1d8-6b3c4974adcc	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090027
a0a72ebc-cdda-40fa-bf93-26bc4ef02d84	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100437
a0a72ebd-965e-4823-916e-50b2c6593292	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040025
a0a72ebd-9779-4b75-a741-d14583d87201	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050026
a0a72ebd-9880-480e-a1ea-53b22fc0e19a	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060026
a0a72ebd-9995-4d06-b0db-637e600eeeba	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070026
a0a72ebd-9abd-420a-ae67-0d91095f54d4	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080028
a0a72ebd-9bcf-407f-8ab2-b49a2ad5325c	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090028
a0a72ebc-eaba-49a1-b97b-7d5bdbeab219	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100438
a0a72ebd-b9d3-46b6-8cc3-c5b8bc01f621	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040026
a0a72ebd-baa7-4bd9-a8e7-397861165a64	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050027
a0a72ebd-bbde-4ec0-9807-c0e69d8a717a	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060027
a0a72ebd-bd20-4cc7-9b30-f84cb47710d1	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070027
a0a72ebd-be2c-4641-9f74-8e949de155b1	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080029
a0a72ebd-c03a-4eda-a3ee-4230e31b1f8e	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090029
a0a72ebd-0208-45bf-b468-4d167d9bda07	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100439
a0a72ebd-def9-42bd-ae2b-b5e7a3438894	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040027
a0a72ebd-e268-4752-9ed4-f590b0f6b16f	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050028
a0a72ebd-e300-4081-8272-153dae63d5b6	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060028
a0a72ebd-e444-4995-b135-49d1e58253a6	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070028
a0a72ebd-e645-4993-8b1d-f08e4b8c7713	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080030
a0a72ebd-e7ee-4bf7-9b01-bda1be6e188c	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090030
a0a72ebd-2126-40a1-b64e-7c37e751674f	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100440
a0a72ebe-097c-408c-bfd8-02ba89b5b18d	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040028
a0a72ebe-0a2b-45d4-8a01-27c3bc0b1b1f	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050029
a0a72ebe-0e06-4233-ad03-eb2b5e012c5d	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060029
a0a72ebe-105b-4cfc-8a3b-d0852c8b7a31	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070029
a0a72ebe-114b-4d2e-97ad-57bf16cc527a	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080031
a0a72ebe-1209-4575-9a67-31cf2a17a341	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090031
a0a72ebd-3d0c-43bb-9af1-94fbdae22f54	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100441
a0a72ebe-2742-4940-be9f-aff3a4a3cb6d	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25040029
a0a72ebe-2827-464a-a692-c5c463253f90	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25050030
a0a72ebe-2931-4db6-bb40-004f85a6ba90	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25060030
a0a72ebe-2c68-462f-8946-6520d9443544	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25070030
a0a72ebe-2fae-4390-8d30-0bd1d11d7167	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25080032
a0a72ebe-3308-4719-a1d1-8101387f5942	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:01	2025-12-22 17:24:01	DEP25090032
a0a72ebd-5abc-42d9-abb5-741ac3a023a0	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100442
a0a72ebe-4680-4af9-8725-645102776f0c	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040030
a0a72ebe-4742-43b4-bce7-c1615377cc75	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050031
a0a72ebe-4801-4323-8bfe-d97e7e884c4c	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060031
a0a72ebe-48ac-49e2-830b-c48cefbbfe23	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070031
a0a72ebe-49b8-4d82-bbf1-c713bcf5fe69	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080033
a0a72ebe-4a9c-4a80-95cc-5ca145833165	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090033
a0a72ebd-8088-4c83-9d46-2bc601f832af	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100443
a0a72ebe-5f05-4e01-b6b5-4e71363e83b7	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040031
a0a72ebe-5f99-4582-bb60-a76c8d6f8cf4	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050032
a0a72ebe-6025-4581-9157-cbe518e9973a	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060032
a0a72ebe-60cd-49ec-8bc8-04ffd0b4311d	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070032
a0a72ebe-6179-43fc-9a8a-165fd17ad14b	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080034
a0a72ebe-622a-4200-9a7a-0794dabd25f3	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090034
a0a72ebd-9d49-4f04-a684-7c05e4034b9b	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100444
a0a72ebe-77c9-4bbf-83b0-553441c61064	c521f578-f2c7-446d-b351-9b47fdb59913	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040032
a0a72ebe-78ca-4874-9494-26eb7faab842	c521f578-f2c7-446d-b351-9b47fdb59913	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050033
a0a72ebe-79b2-420a-9616-849764d1e9c8	c521f578-f2c7-446d-b351-9b47fdb59913	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060033
a0a72ebe-7a5e-43ff-bb07-878644487520	c521f578-f2c7-446d-b351-9b47fdb59913	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070033
a0a72ebe-7b29-4a07-8649-17755cc07f71	c521f578-f2c7-446d-b351-9b47fdb59913	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080035
a0a72ebe-7bd3-4218-b781-87278ba262a5	c521f578-f2c7-446d-b351-9b47fdb59913	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090035
a0a72ebd-c16b-463f-bc9c-06d58214435e	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100445
a0a72ebe-9050-47cb-8f0c-de801b6af162	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040033
a0a72ebe-90f1-414c-a6f6-b4b8fbe0b3b2	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050034
a0a72ebe-91a2-45fc-81a8-ccf4f35fd45f	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060034
a0a72ebe-9251-41e1-babb-3eb298c4ec5c	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070034
a0a72ebe-930e-4229-b1c7-3b91090b698e	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080036
a0a72ebe-93b8-423b-93cc-02c7e45d727e	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090036
a0a72ebd-f1d1-4663-9f35-42364b15c50f	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100446
a0a72ebe-afb7-43b4-b393-533b02e0c2ff	6630f300-223a-4694-a3b5-28193c508cba	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040034
a0a72ebe-b057-4c1b-8da3-e9abfd4b5942	6630f300-223a-4694-a3b5-28193c508cba	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050035
a0a72ebe-b0dc-4f0d-ac51-3cf57ba37269	6630f300-223a-4694-a3b5-28193c508cba	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060035
a0a72ebe-b169-4410-9b1b-dec2f60d702e	6630f300-223a-4694-a3b5-28193c508cba	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070035
a0a72ebe-b2ef-4a78-a4fe-a8a8e04be57c	6630f300-223a-4694-a3b5-28193c508cba	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080037
a0a72ebe-b6a7-4945-81b4-05ba45cbaf2f	6630f300-223a-4694-a3b5-28193c508cba	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090037
a0a72ebe-1520-41a6-beaa-a6cb16c9b966	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100447
a0a72ebe-cf01-4baf-8b20-8b42ad55c953	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-04-01	34410000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	716875.00	33693125.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25040035
a0a72ebe-d035-4938-a74c-b15363226399	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-05-01	33693125.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	1433750.00	32976250.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25050036
a0a72ebe-d178-46bc-a04b-099fdf245846	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-06-01	32976250.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2150625.00	32259375.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25060036
a0a72ebe-d7c9-4aaa-baa9-d93d8d7c3228	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-07-01	32259375.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	2867500.00	31542500.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25070036
a0a72ebe-d8ae-4d84-a465-3ac93bddadad	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-08-01	31542500.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	3584375.00	30825625.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25080038
a0a72ebe-d97a-414f-afc5-654fbb9461a8	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-09-01	30825625.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	4301250.00	30108750.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090038
a0a72ebe-33e2-49b1-a7cd-2c634c2495b4	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:01	2025-12-24 11:50:28	DEP25100448
a0a72ebe-f251-45a0-95fa-ed03691d3e97	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090039
a0a72ebe-4b4b-49a6-a7f0-67d9c2e617c3	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100449
a0a72ebf-0894-48f3-a37b-a53cfbb7957d	896e640c-3b59-4bc8-aba1-5ac076e99c49	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090040
a0a72ebe-62d4-4815-a506-5afb01b8046a	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100450
a0a72ebf-1de4-4822-b179-d140fd9633a8	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090041
a0a72ebe-7c76-4086-8662-1a01f65cd17c	c521f578-f2c7-446d-b351-9b47fdb59913	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100451
a0a72ebf-3300-4ee6-9527-237e573d478b	bb12563d-78e3-4121-84df-edae5df20c63	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090042
a0a72ebe-9475-4e8b-8c82-1d3de8cacb20	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100452
a0a72ebf-503b-43f9-83cf-ecd73814dbb7	da02d35f-1531-49f7-89f3-9c9fed5f9553	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090043
a0a72ebe-b7e9-4f07-a4b7-dd3c99570190	6630f300-223a-4694-a3b5-28193c508cba	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100453
a0a72ebf-6d28-4d44-884e-e33504b02143	d30045d9-6179-4162-b8dc-e8d16ce29802	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090044
a0a72ebe-da98-4b40-bbc0-a55f8111bf2f	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-10-01	30108750.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5018125.00	29391875.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100454
a0a72ebf-871e-4c69-a0a9-bd32d8f83116	46930604-8016-42a6-9329-ffdac3236bc1	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090045
a0a72ebe-f313-4da8-8a34-f317b22a92ad	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100455
a0a72ebf-9d0c-4838-9740-0b2dd9505798	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090046
a0a72ebf-09a5-4e04-b21f-5378522a3af7	896e640c-3b59-4bc8-aba1-5ac076e99c49	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100456
a0a72ebf-b23a-40eb-a4ad-c7a8fb8b4123	538a6d2a-ec13-4d7c-87e7-f2e56d089780	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:02	2025-12-22 17:24:02	DEP25090047
a0a72ebf-1f11-435e-bb19-aed55130d2e5	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100457
a0a72ebf-cc76-4bdf-affc-1af8fcf16b46	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090048
a0a72ebf-342a-469b-ac49-4bd4b2fbaf17	bb12563d-78e3-4121-84df-edae5df20c63	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100458
a0a72ebf-e164-4342-a72c-bcefdc7efbd0	1a2dda94-1f32-444a-a4dd-310edef0d76d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090049
a0a72ebf-553a-4a1a-932e-a0879d153173	da02d35f-1531-49f7-89f3-9c9fed5f9553	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100459
a0a72ebf-f36a-43de-9ad1-c1959ccf1c2f	e3e63659-175c-4748-b571-d2224a256534	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090050
a0a72ebf-6dfd-442b-be35-a5b2390a4503	d30045d9-6179-4162-b8dc-e8d16ce29802	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100460
a0a72ec0-0305-4858-aa6f-19660360a227	80be2e71-1ead-4023-bd82-148c11e82d2f	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090051
a0a72ebf-8872-4ffb-8579-47f22f6e5863	46930604-8016-42a6-9329-ffdac3236bc1	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100461
a0a72ec0-1613-4d5c-b23a-57faedb7e4f4	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090052
a0a72ebf-9e09-4a52-9a93-bf5049353bd7	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100462
a0a72ec0-261b-448c-ba5c-52245a94a386	0192e4a7-0901-4db9-aa00-c192d6adaa37	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090053
a0a72ebf-b429-48c5-a4f7-ab8bd9c735c0	538a6d2a-ec13-4d7c-87e7-f2e56d089780	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:02	2025-12-24 11:50:28	DEP25100463
a0a72ec0-386a-4fa0-9952-d4b2a04fdedb	9398fd93-f9b2-4639-8c65-51086cf62165	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090054
a0a72ebf-cdd8-49d1-97e5-4bec9e2e71f5	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100464
a0a72ec0-4881-4f25-b015-8cd8501fcafa	db95bb38-c227-48ec-ac5a-69d642ba910e	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090055
a0a72ebf-e225-4003-a6a2-e1fec81edc93	1a2dda94-1f32-444a-a4dd-310edef0d76d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100465
a0a72ec0-5e26-49b2-a000-b6f13a431abb	e3222e82-d284-45f4-87c5-6ca46ea72fac	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090056
a0a72ebf-f434-4c50-aac8-4a1ea86562b8	e3e63659-175c-4748-b571-d2224a256534	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100466
a0a72ec0-78ab-46ea-9393-052dc00fe688	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090057
a0a72ec0-0403-49a6-8ca2-c0e073d596ee	80be2e71-1ead-4023-bd82-148c11e82d2f	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100467
a0a72ec0-8e86-475d-a994-f608e0d7417a	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090058
a0a72ec0-16fc-4d09-9645-5406f9db16ec	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100468
a0a72ec0-a533-49b7-aeb3-f2b098f519e5	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090059
a0a72ec0-2725-4cc1-a497-b12b404fc2d0	0192e4a7-0901-4db9-aa00-c192d6adaa37	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100469
a0a72ec0-bfbe-4e1b-b3de-9ca7f0af9c8e	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090060
a0a72ec0-395b-42d8-a340-c6e15949d469	9398fd93-f9b2-4639-8c65-51086cf62165	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100470
a0a72ec0-d59f-45c7-88ec-10c7ac44e402	abbaf21e-07b0-4097-889a-094bfeda26ef	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090061
a0a72ec0-4991-4e77-86b2-eea6debad657	db95bb38-c227-48ec-ac5a-69d642ba910e	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100471
a0a72ec0-e3c1-4c8d-94df-d0be280021ab	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090062
a0a72ec0-5ff1-4aef-8906-41ee786a7eab	e3222e82-d284-45f4-87c5-6ca46ea72fac	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100472
a0a72ec0-f62f-4d85-9f9b-8244bac92aa4	66df45b6-5011-45a5-be1d-f140cc3e4b7d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090063
a0a72ec0-7a23-4848-b8e1-9110f41c4f04	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100473
a0a72ec1-0465-46ee-bc31-5769bd7b1326	0c5094e1-9380-4a00-aef4-46048c2ec697	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090064
a0a72ec0-8f9f-4b24-9b34-1e441d2ab6b8	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100474
a0a72ec1-17c3-472f-9855-d1248a6c6085	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090065
a0a72ec0-a686-45c0-ab5d-b45d20b04420	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100475
a0a72ec1-2abb-44fc-b409-e4f327559741	d403907f-306d-4dfb-8ca4-a950b548394d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090066
a0a72ec0-c1a3-4601-b3c4-c8bbe3dea019	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100476
a0a72ec1-41e2-4fdf-9b81-2a1fccd33b03	3998992b-b5bf-4d03-9cd7-526c45df750c	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:03	2025-12-22 17:24:03	DEP25090067
a0a72ec0-d678-4831-bcdd-63300f77f0bc	abbaf21e-07b0-4097-889a-094bfeda26ef	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100477
a0a72ec1-5de1-46d1-b299-436933d5aefd	b6368e98-7f87-42db-8b28-4084b11a0972	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090068
a0a72ec0-e4aa-48fc-aa9b-68b4a87dd424	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100478
a0a72ec1-6fb3-4fe6-9619-c51044103d97	82ef3eb4-8b79-47e0-915e-7276ea7bd578	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090069
a0a72ec0-f711-4551-8bd4-20ebc4fdcf42	66df45b6-5011-45a5-be1d-f140cc3e4b7d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100479
a0a72ec1-800e-4207-abb8-e43b36248dfc	899b064e-2a20-489f-b713-56a3a1bcaf20	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090070
a0a72ec1-0525-4858-bd07-2d4a48578454	0c5094e1-9380-4a00-aef4-46048c2ec697	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100480
a0a72ec1-92ba-4f54-a5d8-19ca6383afb5	3f0dafbf-7fd9-407b-b1a9-4141e6326797	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090071
a0a72ec1-19bd-4eec-b375-b8a8f075ae53	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100481
a0a72ec1-a287-4341-8d83-6e70d89f63d2	659ca9a0-f2de-4890-86ed-ef404f8d93fd	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090072
a0a72ec1-2c1e-46af-b64a-08d7816b8084	d403907f-306d-4dfb-8ca4-a950b548394d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:03	2025-12-24 11:50:28	DEP25100482
a0a72ec1-b2a3-48b0-b3cf-e1af7d08b96f	67bd771b-1a68-403b-b081-4727a5b09bbe	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090073
a0a72ec1-43cf-4ae0-90dc-488d8a8730a8	3998992b-b5bf-4d03-9cd7-526c45df750c	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100483
a0a72ec1-c71e-4c1d-8606-9b8b7696ea79	45925fe4-a66c-4e4c-92e4-81f818fd71c8	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090074
a0a72ec1-5eb9-4d26-9eb7-a263e9b2abad	b6368e98-7f87-42db-8b28-4084b11a0972	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100484
a0a72ec1-d83a-41ff-bc66-bb7497282272	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090075
a0a72ec1-70bc-4ddd-915d-441ecc629e25	82ef3eb4-8b79-47e0-915e-7276ea7bd578	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100485
a0a72ec1-e926-4dfc-90bf-6eabc76672d7	9bb4b946-4d3b-427a-914c-accbaf7c362d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090076
a0a72ec1-8106-4d78-b616-ce9a1c9b9cd6	899b064e-2a20-489f-b713-56a3a1bcaf20	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100486
a0a72ec1-fea4-4877-9dbf-c8d1d13bf46d	e74afc6c-038f-4875-a703-89b52e09ee91	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090077
a0a72ec1-93bb-4bda-81c8-2eea9e06b15e	3f0dafbf-7fd9-407b-b1a9-4141e6326797	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100487
a0a72ec2-11bc-491e-8dd7-dd9d012d8a11	43f964a1-fc8a-42fe-85b7-af80de5688a7	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090078
a0a72ec1-a378-4507-bc53-59be8409a729	659ca9a0-f2de-4890-86ed-ef404f8d93fd	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100488
a0a72ec2-28a2-4ed5-82ab-e9d6243293fe	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090079
a0a72ec1-b3bf-4684-a85c-ee23f4f943e8	67bd771b-1a68-403b-b081-4727a5b09bbe	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100489
a0a72ec2-37c7-4788-a11d-a9c72f168043	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090080
a0a72ec1-c806-49f8-afe1-fa1722bd7c65	45925fe4-a66c-4e4c-92e4-81f818fd71c8	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100490
a0a72ec2-4dc3-429f-ac55-fbb4b4949414	06b7d765-707f-4860-a0e7-3e520d4c1578	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090081
a0a72ec1-d92d-446b-a725-b4de6f14bf50	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100491
a0a72ec2-5c91-4c9a-b347-e1db4c234711	d81dabec-1c10-4269-9548-808f65039d63	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090082
a0a72ec2-6b78-47b4-ae9a-85acccfb83ba	df697add-1313-4c55-957d-e53f28e5b499	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090083
a0a72ec2-7c74-4adb-b7d0-79a5ff73c63c	68147a4a-8037-42ff-862a-64cc61cad395	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090084
a0a72ec2-915d-4491-86b2-622b0565291a	3387ade2-a790-4808-9294-4308ebe93867	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090085
a0a72ec2-a814-4008-9fe8-7772fa238d0b	620120f2-1730-4c93-b033-954f79d02e56	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090086
a0a72ec1-eaae-433c-a4a4-313bf923ceac	9bb4b946-4d3b-427a-914c-accbaf7c362d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100492
a0a72ec2-bb2b-44bc-9479-2162e4537a92	c55b5d93-f4c2-4588-9bc0-e3051f907091	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:04	2025-12-22 17:24:04	DEP25090087
a0a72ec2-00e2-4e79-9899-d604e37f6a47	e74afc6c-038f-4875-a703-89b52e09ee91	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100493
a0a72ec2-d3a7-44b5-977a-1c05027e97de	a0a355f7-a358-4e92-bdbd-9b31808a868e	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090088
a0a72ec2-135f-43c6-bac6-2fda5db0d136	43f964a1-fc8a-42fe-85b7-af80de5688a7	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100494
a0a72ec2-e835-4072-afab-62872653b06f	091d5401-cef6-40f8-8778-87389d39e51f	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090089
a0a72ec2-297c-4124-b7e8-0d398cf4ffb6	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100495
a0a72ec2-fe68-48a7-a801-64cbafa1ab4b	3189250e-a44f-4b07-9d24-4b9b128485f9	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090090
a0a72ec2-3af3-415e-91ac-223ce83bde6e	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100496
a0a72ec3-10b6-4494-8c1f-9e0804b200e4	55042527-68f7-43ac-9e1a-b2d1872b8b82	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090091
a0a72ec2-4e85-415b-aab8-e759c7a0fd51	06b7d765-707f-4860-a0e7-3e520d4c1578	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:28	DEP25100497
a0a72ec3-242e-4c89-baa4-cf17a35cd5d1	2e1343ff-6d31-4246-9667-83ecf97a93ba	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090092
a0a72ec2-5d64-4c36-bc2c-3ff142a9804d	d81dabec-1c10-4269-9548-808f65039d63	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100498
a0a72ec3-387c-485b-b4ce-07641389aaf3	c54762d7-e7d2-499f-a4db-fb340f1e740d	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090093
a0a72ec2-6c64-4e32-a64c-f0abb022af6f	df697add-1313-4c55-957d-e53f28e5b499	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100499
a0a72ec3-4748-4ad7-90ce-342e5f5f3a78	27ae456f-8d57-4820-a7e1-e478df363acf	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090094
a0a72ec2-7f73-4567-8231-112a40a6c3e5	68147a4a-8037-42ff-862a-64cc61cad395	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100500
a0a72ec3-5c27-4335-a8cf-328b65e0001e	a48bed46-6d76-4a7e-ad06-77a533df7482	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090095
a0a72ec2-9433-4ca5-a626-ccb0cb968551	3387ade2-a790-4808-9294-4308ebe93867	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100501
a0a72ec3-7357-4501-825f-22058762ff4a	361667bd-377a-46f0-83ea-bdce1a20b6ad	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090096
a0a72ec2-a8fe-465b-a680-7d080bd7babf	620120f2-1730-4c93-b033-954f79d02e56	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100502
a0a72ec3-86d2-418a-baf4-43c6f9036784	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090097
a0a72ec2-bc9c-4ddd-9f25-8f5cba85b5f5	c55b5d93-f4c2-4588-9bc0-e3051f907091	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:04	2025-12-24 11:50:29	DEP25100503
a0a72ec3-a2df-49f8-b8ff-de04a7d6aa39	ebd82a70-8c97-4dad-80e0-7ece07478479	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090098
a0a72ec2-d4de-4422-9bed-a26cad3b0061	a0a355f7-a358-4e92-bdbd-9b31808a868e	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100504
a0a72ec3-c339-4d0b-a2c6-7a9c80ae39b6	d2dac9ea-3c11-4698-8628-9c3412693fe6	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090099
a0a72ec4-a049-4039-84fc-64578e8d540b	f99028fd-3f94-4fc4-8635-13369d98711f	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100525
a0a72ec3-d737-497b-8019-040ff5d4dc61	0b19b75b-02ea-429d-b638-696f626d1384	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090100
a0a72ec4-b496-4cfb-8fa2-0ae735c3568d	c0677d15-296c-4d34-98e1-cc940baa7a99	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100526
a0a72ec3-f09a-4f07-82b4-e7b1057d184b	b11ed488-c372-47f4-bf13-3a27148b98f0	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090101
a0a72ec4-cc73-459c-8149-6ec38c0fdf57	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100527
a0a72ec4-df2b-4279-828e-e4c7e7ea5780	e00e59a1-0f30-4ea7-8094-3956711ff682	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100528
a0a72ec4-02f5-42f6-a0ee-a4bc1a94ff6e	76c9fc95-c035-4d55-a192-88b22c907aaf	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090102
a0a72ec4-ef52-4492-b614-64cb447cd300	5373b97d-245d-4889-9018-20958b798c17	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100529
a0a72ec2-81bb-4fa3-ac54-5d4be08fbff3	68147a4a-8037-42ff-862a-64cc61cad395	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110825
a0a72ec2-95ab-4d65-98bf-5cf5e0cde8e1	3387ade2-a790-4808-9294-4308ebe93867	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110826
a0a72ec4-24d5-4179-ba75-89d1fc1cd78a	e83c6db7-1c8d-44d3-818d-5fabf4127734	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090103
a0a72ec5-09bd-4a94-8365-62637cf1027f	e1b3ed82-00ea-485e-b741-070c71fe1d2c	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100530
a0a72ec4-3798-40a2-b4ce-60182d52db44	993c08e7-1142-433b-93c1-61aad85798f2	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090104
a0a72ec5-1c99-4d0d-a335-e1e8fceddd72	627738fb-548f-49a7-ade4-0f7ae516c3c3	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100531
a0a72ec4-479f-43df-a093-241bf2adce46	1bb9f4ba-525d-4390-800d-140404e63991	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:05	2025-12-22 17:24:05	DEP25090105
a0a72ec5-315a-4858-b211-92150e57d0ce	d71ea253-d0e4-42f4-861d-a743fd7a8900	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100532
a0a72ec4-5d7b-4fb4-9850-efddb9e83294	593b6e25-ee8c-4702-b04e-b8675711696b	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090106
a0a72ec5-4ded-4e10-a7fd-5810f31a256d	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-10-01	436479751.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	72746624.00	426087376.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100533
a0a72ec4-73d0-49da-8183-461307b8f1c4	34cb37c8-43d8-42bf-8be8-3622219b1fd2	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090107
a0a72ec5-7601-4923-8e7c-8bf137e5eada	d00fd50d-fdfa-440b-8698-8ba7c354386a	2025-10-01	7276050.00	0.00	0.00	0.00	0.00	0.00	0.00	151584.00	151584.00	7124466.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100534
a0a72ec4-86b7-4af4-86b2-ad30fceca8be	a7072c26-3165-47e4-81bc-5a88a2d43ab1	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090108
a0a72ec5-92b4-4022-83d8-7addc5c782ea	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025-10-01	5371938.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	350343.00	5255157.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100535
a0a72ec4-9f37-40ed-ac6d-588a037d93e2	f99028fd-3f94-4fc4-8635-13369d98711f	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090109
a0a72ec5-aade-42ef-a6cd-1de19dd71998	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025-10-01	5371938.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	350343.00	5255157.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100536
a0a72ec4-b2fe-4965-b323-0970402c912d	c0677d15-296c-4d34-98e1-cc940baa7a99	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090110
a0a72ec5-bd26-4607-bf55-605849613761	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-10-01	1478612500.00	0.00	0.00	0.00	0.00	0.00	0.00	32143750.00	96431250.00	1446468750.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100537
a0a72ec4-cb5c-40b1-bfb7-52f9f22a4364	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090111
a0a7316c-9715-4e49-b44d-c90943896aa9	9beb94c2-f47d-4b48-9281-54ec00cf0758	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	24145000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110719
a0a72ec4-de09-454a-b7af-996cb098c52f	e00e59a1-0f30-4ea7-8094-3956711ff682	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090112
a0a7316c-9d6a-4313-86b1-65dbd04dcda1	c88e2c69-914f-403e-ab36-0a9322d6591f	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110720
a0a72ec4-ee9a-4948-931e-79347afb2362	5373b97d-245d-4889-9018-20958b798c17	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090113
a0a7316c-a2d0-49b9-8ebe-14c7d16df603	9580ea1b-0f93-4c89-b167-a089131d5761	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110721
a0a72ec5-08dd-4a24-96d4-e2e6897cc8ac	e1b3ed82-00ea-485e-b741-070c71fe1d2c	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090114
a0a7316c-a641-4b18-afdd-fbf0f0b034dd	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110722
a0a72ec5-1b61-4e5f-8af1-96f294c816ba	627738fb-548f-49a7-ade4-0f7ae516c3c3	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090115
a0a6c0f8-c9b1-4059-9b83-a28533432a2b	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025-11-01	50935134695.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10538303700.00	50759496300.00	2025-12-22 12:17:05	2025-12-24 11:50:54	DEP25110723
a0a72ec5-3082-495f-b3d0-61808cbdf22b	d71ea253-d0e4-42f4-861d-a743fd7a8900	2025-09-01	23000000.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	638888.00	22361112.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090116
a0a6c0f9-1687-4dc1-accb-6ebdf63b5755	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025-11-01	50935134695.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10538303700.00	50759496300.00	2025-12-22 12:17:05	2025-12-24 11:50:54	DEP25110724
a0a72ec5-4641-44fe-8dc9-6c616f13e59c	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-04-01	498834000.00	0.00	0.00	0.00	0.00	0.00	0.00	10392374.00	10392374.00	488441626.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25040036
a0a72ec5-4879-43df-919c-090f86c04272	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-05-01	488441626.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	20784749.00	478049251.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25050037
a0a72ec5-4a05-4cdd-82e9-092f73341f41	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-06-01	478049251.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	31177124.00	467656876.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25060037
a0a72ec5-4aed-48c2-b5b1-929ad96ac86c	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-07-01	467656876.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	41569499.00	457264501.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25070037
a0a72ec5-4bd7-4c46-9d24-a6e78c7e53f7	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-08-01	457264501.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	51961874.00	446872126.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25080039
a0a72ec5-4ce6-45fe-95c3-7a4604c268f1	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-09-01	446872126.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	62354249.00	436479751.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090117
a0a6c0f9-5dab-4de2-8aa2-c670a8da04ae	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025-11-01	50935134695.00	0.00	0.00	0.00	0.00	0.00	0.00	175638395.00	10538303700.00	50759496300.00	2025-12-22 12:17:05	2025-12-24 11:50:54	DEP25110725
a0a7316c-b4b6-4272-9ddd-022b6bcacd3c	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	209440000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110726
a0a72ec5-913f-4489-bee9-01866f89c0ff	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025-08-01	5605500.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	116781.00	5488719.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25080040
a0a72ec5-91f3-4773-ad42-39b58fe8eeb6	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025-09-01	5488719.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	233562.00	5371938.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090118
a0a7316c-b885-4ce1-b299-33e675465708	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	58822500.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110727
a0a7306c-329d-45e1-9e72-4bd47480f23e	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	28240000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100401
a0a72ec5-a64b-4ce1-9c4e-4af3a20df1c1	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025-08-01	5605500.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	116781.00	5488719.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25080041
a0a72ec5-a9bf-4a4d-a446-ffb62b8b3320	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025-09-01	5488719.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	233562.00	5371938.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090119
a0a72ebd-818a-42e8-b1c4-7d880fe31837	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110768
a0a7306c-4052-46b8-8966-8aa93d9718fc	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	82500000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100402
a0a72ec5-badc-48bc-ad7e-ce0384d135e4	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-08-01	1542900000.00	0.00	0.00	0.00	0.00	0.00	0.00	32143750.00	32143750.00	1510756250.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25080042
a0a72ec5-bc0e-48b7-ae4b-87208368c7c9	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-09-01	1510756250.00	0.00	0.00	0.00	0.00	0.00	0.00	32143750.00	64287500.00	1478612500.00	2025-12-22 17:24:06	2025-12-22 17:24:06	DEP25090120
a0a72ebd-9e79-4797-9892-93fe23d115cf	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110769
a0a72ebd-c251-4b37-90ed-75f9722cde3f	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110770
a0a7306c-457e-4a54-a756-ae57cbbc5714	42b0073a-07f3-4dcc-b82c-e2851b626433	2025-10-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	154550000.00	0.00	2025-12-22 17:28:43	2025-12-24 11:50:27	DEP25100403
a0a7316c-bb55-4f9f-b0af-763e6c88cbec	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	46750000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110728
a0a7316c-bf6b-427e-94c9-84fd90a01416	99970f15-9c4a-4d4f-b550-a7ef488054d0	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	398612500.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110729
a0a6c0fa-9069-4d65-aaf9-fb87268c1666	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-11-01	93106455.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	211041289.00	86899358.12	2025-12-22 12:17:06	2025-12-24 11:50:54	DEP25110730
a0a6c0fa-eeed-4f63-8163-e52ea209a371	101fda0f-877a-4290-9df5-00a84859c3e9	2025-11-01	7986153.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28902267.00	7605860.52	2025-12-22 12:17:06	2025-12-24 11:50:54	DEP25110731
a0a6c0fb-3deb-49ff-9595-4a1c2407ca08	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-11-01	7986153.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	28902267.00	7605860.52	2025-12-22 12:17:07	2025-12-24 11:50:54	DEP25110732
a0a6c0fb-9654-4bed-8429-94d5d1797fcb	19c63207-1947-4bb3-9193-554042ba6da7	2025-11-01	4741128.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	17158330.00	4515360.48	2025-12-22 12:17:07	2025-12-24 11:50:54	DEP25110733
a0a6c0fb-f6b1-4d90-9bf2-f9d75e5ccfe5	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-11-01	4741128.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	17158330.00	4515360.48	2025-12-22 12:17:07	2025-12-24 11:50:54	DEP25110734
a0a72eba-bcd7-42ea-8f5c-9bb557d441e9	36e92940-a131-4ac0-b45b-b8500ff4b040	2025-11-01	69315455.00	0.00	0.00	0.00	0.00	0.00	0.00	1474796.00	2949592.00	67840659.00	2025-12-22 17:23:59	2025-12-24 11:50:54	DEP25110736
a0a72eba-d7cf-4343-b2d6-2ade65d0db36	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-11-01	871794168.00	0.00	0.00	0.00	0.00	0.00	0.00	20757004.00	145299024.00	851037164.00	2025-12-22 17:23:59	2025-12-24 11:50:54	DEP25110737
a0a72eba-ecfc-4638-ab77-639baf2bc628	e47f3b62-82ae-4322-8660-bf104df108a5	2025-11-01	9677814.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	860248.00	9462752.00	2025-12-22 17:23:59	2025-12-24 11:50:54	DEP25110738
a0a72eba-fd9e-4c70-a5c8-adb38e5a382c	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025-11-01	9677814.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	860248.00	9462752.00	2025-12-22 17:23:59	2025-12-24 11:50:54	DEP25110739
a0a7316d-001d-4873-a869-f391cabd5925	80acf346-539e-4c9a-aed0-9ff88df294f5	2025-11-01	19425000.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	19425000.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110740
a0a7316d-0459-43c0-8f29-e1dce94d209e	4517635a-b083-4bba-bbba-22c060cff5b6	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110741
a0a7316d-078a-4beb-bf50-92a92af48d11	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110742
a0a7316d-0cb2-4362-bff9-a69f37968982	5450ed79-c9ee-45ac-abd3-d657d1a8897c	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110743
a0a7316d-10aa-4099-85b3-66478276e2bd	50a845bf-b203-4b10-b292-fda3c7b5ac6e	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110744
a0a7316d-1411-49c2-b6c9-4db540dc4f35	de6479e8-c9c2-41c1-9ad6-c74439bc986f	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110745
a0a7316d-192d-4627-a68f-cd3825061de2	c7f80482-89d8-4f80-975d-34a752e992aa	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110746
a0a7316d-1d0e-4331-b57b-3f5a1f7861eb	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110747
a0a7316d-214b-4159-89c0-a3cf09c4711c	8e4323ee-5954-4946-b50e-252f098ee44e	2025-11-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7373952.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110748
a0a7316d-2711-44fb-9884-d8730ae1b487	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	2025-11-01	7270500.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	7270500.00	2025-12-22 17:31:32	2025-12-24 11:50:54	DEP25110749
a0a72ebb-c857-4adf-ab3e-e292c082d458	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-11-01	22546875.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	5781250.00	21968750.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110750
a0a72ebb-d973-4ca7-9138-4da4d1c7ab05	9dbcc529-de27-4753-a772-90aa5f8c7894	2025-11-01	25256625.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1074750.00	24719250.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110751
a0a72ebb-ea4a-419a-bc10-a4bf946b4bb7	47665328-ff67-40a5-aac0-24572afbdcf8	2025-11-01	25256625.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1074750.00	24719250.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110752
a0a72ebb-f8fa-4fad-9d14-4a060fabca4d	747d2923-ba5d-475d-a784-e41bc58e5561	2025-11-01	25256625.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1074750.00	24719250.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110753
a0a72ebc-086f-49da-97b0-3f9d13d97044	2f8f647c-1936-4b32-93f7-9ebbcda6d039	2025-11-01	25256625.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1074750.00	24719250.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110754
a0a72ebc-3cf7-4a10-bff8-06539372ba66	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-11-01	30340001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	5919999.00	29600001.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110756
a0a72ebc-555b-4b4e-bfb2-45fc4037d8fb	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110757
a0a72ebc-6bda-4f3d-ae5a-4bad9eab8a53	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110758
a0a72ebc-7f0d-44e5-9e29-1239d40e85d9	30a9ed88-3599-4d7f-8456-cce980762f96	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110759
a0a72ebc-972c-4663-ab81-e00b8afc8c00	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:00	2025-12-24 11:50:54	DEP25110760
a0a72ebc-aeab-4b43-81aa-5bf511f88074	34453391-14df-41b0-8475-2d31c5371f29	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110761
a0a72ebc-cedb-4bcc-825c-eb43a7a2149f	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110762
a0a72ebc-eb7d-4aef-b7df-b3e6e76df827	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110763
a0a72ebd-030e-4d5c-bbb5-cf3f7cd0ef82	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110764
a0a72ebd-2207-4bcc-b600-9f3da78882f9	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110765
a0a72ebd-3f0c-4c9a-99e4-ea82e296bec1	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110766
a0a72ebd-5bb0-4ffa-92cc-60486fd1a3e6	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110767
a0a72ebd-f5c4-486c-81fd-60d4e373a49c	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110771
a0a72ebe-1606-4895-b432-86502e6531c9	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110772
a0a72ebe-34a2-4a2a-a10f-f1717996ae60	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:01	2025-12-24 11:50:54	DEP25110773
a0a72ebe-4bf7-4113-8764-d7affd25fb25	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110774
a0a72ebe-6362-4440-9b48-99761bff2c56	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110775
a0a72ebe-7d17-4bbc-bdc5-385c71bbbf38	c521f578-f2c7-446d-b351-9b47fdb59913	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110776
a0a72ebe-951e-413d-b0bc-406ddbfa96c9	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110777
a0a72ebe-b8e3-4f00-a1c2-96fa441188f6	6630f300-223a-4694-a3b5-28193c508cba	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110778
a0a72ebe-def7-49fc-8229-791d445f78fe	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-11-01	29391875.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	5735000.00	28675000.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110779
a0a72ebe-f3bd-48fb-aa81-d47a30a39df5	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110780
a0a72ebf-0a75-4c65-88d2-6c21fea59be3	896e640c-3b59-4bc8-aba1-5ac076e99c49	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110781
a0a72ebf-2003-49a9-b4e0-0d3b18ad7878	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110782
a0a72ebf-364e-47ba-8557-d859c45cb60f	bb12563d-78e3-4121-84df-edae5df20c63	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110783
a0a72ebf-56c8-49f3-9341-12136ba3568a	da02d35f-1531-49f7-89f3-9c9fed5f9553	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110784
a0a72ebf-6fae-4e84-8635-049577425edb	d30045d9-6179-4162-b8dc-e8d16ce29802	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110785
a0a72ebf-8967-4d11-b07a-d3ce63b69b79	46930604-8016-42a6-9329-ffdac3236bc1	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:54	DEP25110786
a0a72ebf-9f04-487e-9425-caa6326fe808	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:55	DEP25110787
a0a72ebf-b7e3-4d3a-a8b7-284a5d96cbb6	538a6d2a-ec13-4d7c-87e7-f2e56d089780	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:02	2025-12-24 11:50:55	DEP25110788
a0a72ebf-cef5-44a8-b421-befee7a5eada	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110789
a0a72ebf-e2fe-4e86-a4ab-9418f95ce217	1a2dda94-1f32-444a-a4dd-310edef0d76d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110790
a0a72ebf-f4e1-4e7b-b2b7-c070873cfbb1	e3e63659-175c-4748-b571-d2224a256534	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110791
a0a72ec0-04fd-4d47-876c-e44a045cdcba	80be2e71-1ead-4023-bd82-148c11e82d2f	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110792
a0a72ec0-17e6-4a34-9a6e-a228ea1f21e2	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110793
a0a72ec0-27e9-4f22-a6b3-7a325e74272c	0192e4a7-0901-4db9-aa00-c192d6adaa37	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110794
a0a72ec2-a9cb-4404-b1b9-d00087af0743	620120f2-1730-4c93-b033-954f79d02e56	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110827
a0a72ec2-bdb5-4d22-ae56-db35d6502a75	c55b5d93-f4c2-4588-9bc0-e3051f907091	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110828
a0a72ec2-d61c-495d-a7aa-e195718a6456	a0a355f7-a358-4e92-bdbd-9b31808a868e	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110829
a0a72ec2-eb98-4ca2-a9c7-98e6a5ee839e	091d5401-cef6-40f8-8778-87389d39e51f	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110830
a0a72ec3-000e-49d9-8c7a-e48de19c6b8d	3189250e-a44f-4b07-9d24-4b9b128485f9	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110831
a0a72ec3-12d9-41ee-8aaa-4dc05cc1aee3	55042527-68f7-43ac-9e1a-b2d1872b8b82	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110832
a0a72ec3-265c-4c83-93f1-891653fb9e5b	2e1343ff-6d31-4246-9667-83ecf97a93ba	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110833
a0a72ec3-3a40-43fb-8526-454044d15fca	c54762d7-e7d2-499f-a4db-fb340f1e740d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110834
a0a72ec3-491b-4725-ac34-826f9f94499b	27ae456f-8d57-4820-a7e1-e478df363acf	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110835
a0a72ec3-5ee7-4327-a5e6-c6325f1a15a1	a48bed46-6d76-4a7e-ad06-77a533df7482	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110836
a0a72ec3-7656-4ed3-856a-323c2053c41d	361667bd-377a-46f0-83ea-bdce1a20b6ad	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110837
a0a72ec3-889f-4585-b127-1bd684c0623b	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110838
a0a72ec3-a4f5-4e73-ba13-963428535ffc	ebd82a70-8c97-4dad-80e0-7ece07478479	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110839
a0a72ec3-c524-4929-b53f-cb6353fd8871	d2dac9ea-3c11-4698-8628-9c3412693fe6	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110840
a0a72ec3-d951-46fd-ad1e-24f3bec1483e	0b19b75b-02ea-429d-b638-696f626d1384	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110841
a0a72ec5-76f8-49a7-98cb-d79444e72e45	d00fd50d-fdfa-440b-8698-8ba7c354386a	2025-11-01	7124466.00	0.00	0.00	0.00	0.00	0.00	0.00	151584.00	303168.00	6972882.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110859
a0a72ec5-936b-4612-9ab2-f899edf499f0	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025-11-01	5255157.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	467124.00	5138376.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110860
a0a72ec5-aba8-4cd0-b186-b07536a1c428	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025-11-01	5255157.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	467124.00	5138376.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110861
a0a72ec5-bde6-4c93-a1bc-83c882756f9f	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-11-01	1446468750.00	0.00	0.00	0.00	0.00	0.00	-250.00	32143750.00	128574750.00	1414324750.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110862
a0a72ec2-e9a3-474f-b411-64e9aaf99701	091d5401-cef6-40f8-8778-87389d39e51f	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100505
a0a72ec2-ff48-4594-90a5-80f98f150eff	3189250e-a44f-4b07-9d24-4b9b128485f9	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100506
a0a72ec3-11de-4ec7-b17a-393c47a3cead	55042527-68f7-43ac-9e1a-b2d1872b8b82	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100507
a0a72ec3-2569-4bcd-bcb5-8cf58af046b9	2e1343ff-6d31-4246-9667-83ecf97a93ba	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100508
a0a72ec3-3967-4882-a0a2-b16f479ff121	c54762d7-e7d2-499f-a4db-fb340f1e740d	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100509
a0a72ec3-482d-4101-8a26-084e34ca535f	27ae456f-8d57-4820-a7e1-e478df363acf	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100510
a0a72ec3-5de4-4e6c-b3f0-87f479eea1d9	a48bed46-6d76-4a7e-ad06-77a533df7482	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100511
a0a72ec3-74f2-4c66-a754-7d23ed340f16	361667bd-377a-46f0-83ea-bdce1a20b6ad	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100512
a0a72ec3-87bf-4e96-a6d8-57460d92037d	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100513
a0a72ec3-a406-485e-97da-3d8ffc2b0607	ebd82a70-8c97-4dad-80e0-7ece07478479	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100514
a0a72ec3-c42d-4586-9537-2f28f49667f1	d2dac9ea-3c11-4698-8628-9c3412693fe6	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100515
a0a72ec3-d845-4825-b754-8be8e9a7a991	0b19b75b-02ea-429d-b638-696f626d1384	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100516
a0a72ec3-f190-4b92-b20a-3fe97cac5311	b11ed488-c372-47f4-bf13-3a27148b98f0	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100517
a0a7316c-fae7-403d-a0f2-c220b9629a51	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	2025-11-01	19425000.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	19425000.00	2025-12-22 17:31:32	2025-12-22 17:31:32	DEP25110151
a0a72ec4-1002-4964-b349-b48663d6c3b0	76c9fc95-c035-4d55-a192-88b22c907aaf	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100518
a0a72ec4-25c8-4cfa-bdc2-5dba7b612b06	e83c6db7-1c8d-44d3-818d-5fabf4127734	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100519
a0a72ec4-384b-4fa1-aaaf-b4c503042782	993c08e7-1142-433b-93c1-61aad85798f2	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100520
a0a72ec4-4bf5-42db-b55a-e15a39499b00	1bb9f4ba-525d-4390-800d-140404e63991	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:05	2025-12-24 11:50:29	DEP25100521
a0a72ec4-5e83-4333-b19f-1ce3da3d3bd5	593b6e25-ee8c-4702-b04e-b8675711696b	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100522
a0a72ec4-74cc-4c5a-bb97-c72af6e1d171	34cb37c8-43d8-42bf-8be8-3622219b1fd2	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100523
a0a72ec4-8964-4aa5-b57b-14802c687f8f	a7072c26-3165-47e4-81bc-5a88a2d43ab1	2025-10-01	22361112.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1277776.00	21722224.00	2025-12-22 17:24:06	2025-12-24 11:50:29	DEP25100524
a0a7316c-843b-43d5-9726-497448eee1e4	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	28240000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110716
a0a72ec1-b611-4e8a-93c1-168400dc8e32	67bd771b-1a68-403b-b081-4727a5b09bbe	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110814
a0a72ec1-c8f8-4301-b35b-e6c363f63a14	45925fe4-a66c-4e4c-92e4-81f818fd71c8	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110815
a0a72ec1-da12-4193-9dd0-2c438902b109	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110816
a0a72ec1-ec1a-4833-a7e2-dcdbddbe6270	9bb4b946-4d3b-427a-914c-accbaf7c362d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110817
a0a72ec2-01f4-4dcf-9974-c271d1e8c043	e74afc6c-038f-4875-a703-89b52e09ee91	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110818
a0a72ec2-1579-4c92-8562-aa20b47ede86	43f964a1-fc8a-42fe-85b7-af80de5688a7	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110819
a0a72ec2-2a74-4536-b328-dadd044d444d	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110820
a0a72ec2-3c7b-4688-93aa-57011d2ac52e	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110821
a0a72ec2-4f67-418c-8d6a-81f548584e05	06b7d765-707f-4860-a0e7-3e520d4c1578	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110822
a0a72ec2-5e1e-47b7-b56f-43c6d4e50e2e	d81dabec-1c10-4269-9548-808f65039d63	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110823
a0a72ec2-6d98-4de4-8e65-1d81b4898f37	df697add-1313-4c55-957d-e53f28e5b499	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110824
a0a72ec3-f249-4082-95bb-0b3c657e7be3	b11ed488-c372-47f4-bf13-3a27148b98f0	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110842
a0a72ec4-116a-4dd2-a1c7-47edc50a71b4	76c9fc95-c035-4d55-a192-88b22c907aaf	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110843
a0a72ec4-269e-4fc6-aa22-4a152309cc8d	e83c6db7-1c8d-44d3-818d-5fabf4127734	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110844
a0a72ec4-3916-414a-851a-cf31f7892558	993c08e7-1142-433b-93c1-61aad85798f2	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110845
a0a72ec4-4db2-43e5-b883-e46bb6cf8b01	1bb9f4ba-525d-4390-800d-140404e63991	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:05	2025-12-24 11:50:55	DEP25110846
a0a72ec4-5f4c-4182-b554-06c56caae54e	593b6e25-ee8c-4702-b04e-b8675711696b	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110847
a0a72ec4-76e3-4307-9b5a-3ac1de8ee8e9	34cb37c8-43d8-42bf-8be8-3622219b1fd2	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110848
a0a72ec4-8a79-414a-bd90-ada0b39af21d	a7072c26-3165-47e4-81bc-5a88a2d43ab1	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110849
a0a72ec4-a129-45f8-a45d-b34c0e870806	f99028fd-3f94-4fc4-8635-13369d98711f	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110850
a0a72ec4-b5f1-4efa-b880-5ffe224161bf	c0677d15-296c-4d34-98e1-cc940baa7a99	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110851
a0a72ec4-cd6e-40df-bbc9-369f381e7318	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110852
a0a72ec4-e029-45ab-8a28-9b09429c50c4	e00e59a1-0f30-4ea7-8094-3956711ff682	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110853
a0a6c16a-7459-4395-be98-5d65da9d3244	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025-12-01	86899358.12	0.00	0.00	0.00	0.00	0.00	0.00	6207097.00	217248386.00	80692261.12	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120504
a0a72ec0-3a3d-4b14-8973-b0364e60b860	9398fd93-f9b2-4639-8c65-51086cf62165	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110795
a0a72ec0-4a83-42fb-837f-4a30f3e0a510	db95bb38-c227-48ec-ac5a-69d642ba910e	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110796
a0a72ec0-6149-4162-87b6-ca5441e450b0	e3222e82-d284-45f4-87c5-6ca46ea72fac	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110797
a0a72ec0-7af4-4350-865e-d8dec2dd45b9	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110798
a0a72ec0-90c3-4f88-84e1-03a89ec28856	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110799
a0a72ec0-a91a-41a5-8300-f7e857e36561	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110800
a0a72ec0-c34d-4d4d-807b-8ff9f59ebc58	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110801
a0a72ec0-d751-4bb3-9fbc-af6c10cf492d	abbaf21e-07b0-4097-889a-094bfeda26ef	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110802
a0a72ec0-e574-4790-8593-605856f37a3c	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110803
a0a72ec0-f7de-4eac-b2db-59549a079d4e	66df45b6-5011-45a5-be1d-f140cc3e4b7d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110804
a0a731bd-176e-4823-ba2f-da96b5a307b7	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	2025-12-01	19425000.00	0.00	0.00	0.00	0.00	0.00	0.00	404687.00	404687.00	19020313.00	2025-12-22 17:32:24	2025-12-22 18:04:07	DEP25120215
a0a72ec1-05dd-48ad-938a-b2983f49ef4e	0c5094e1-9380-4a00-aef4-46048c2ec697	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110805
a0a72ec1-1ab8-48ef-8bf8-a819eea15549	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110806
a0a72ec1-2ebf-415e-b72b-dea218bf9403	d403907f-306d-4dfb-8ca4-a950b548394d	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:03	2025-12-24 11:50:55	DEP25110807
a0a72ec1-481f-4837-9465-d4c3d3d06a42	3998992b-b5bf-4d03-9cd7-526c45df750c	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110808
a0a72ec1-5fc1-404d-90ff-f362cce44934	b6368e98-7f87-42db-8b28-4084b11a0972	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110809
a0a72ec1-7329-4b42-aae9-6b97a2e6430d	82ef3eb4-8b79-47e0-915e-7276ea7bd578	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110810
a0a72ec1-81b5-4503-9d17-6bd6ad520097	899b064e-2a20-489f-b713-56a3a1bcaf20	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110811
a0a72ec1-949f-4d4a-b1c8-4d8d4dab80d4	3f0dafbf-7fd9-407b-b1a9-4141e6326797	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110812
a0a72ec1-a43f-4db5-8ef9-fb050a74fe90	659ca9a0-f2de-4890-86ed-ef404f8d93fd	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:04	2025-12-24 11:50:55	DEP25110813
a0a72ec4-f13a-4215-85e4-05fabb0b7a32	5373b97d-245d-4889-9018-20958b798c17	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110854
a0a72ec5-0a7a-4d60-9382-4c952c815309	e1b3ed82-00ea-485e-b741-070c71fe1d2c	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110855
a0a72ec5-1d86-46ac-9201-ce6034d78030	627738fb-548f-49a7-ade4-0f7ae516c3c3	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110856
a0a72ec5-3232-49f1-a22f-dad798c31c24	d71ea253-d0e4-42f4-861d-a743fd7a8900	2025-11-01	21722224.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	1916664.00	21083336.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110857
a0a72ec5-4e99-432f-832f-94f6128d7edb	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-11-01	426087376.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	83138999.00	415695001.00	2025-12-22 17:24:06	2025-12-24 11:50:55	DEP25110858
a0a6c16a-34e4-4e11-b35c-97d0a6ba828a	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	82500000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120491
a0a6c16a-3c67-4b28-8457-7a9629365285	42b0073a-07f3-4dcc-b82c-e2851b626433	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	154550000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120492
a0a6c16a-41d1-426f-84c6-312e6eb577e6	9beb94c2-f47d-4b48-9281-54ec00cf0758	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	24145000.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120493
a0a6c16a-70cf-44f5-b185-239b3e991314	99970f15-9c4a-4d4f-b550-a7ef488054d0	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	398612500.00	0.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120503
a0a6c16a-770b-47b5-9d37-712228927cb9	101fda0f-877a-4290-9df5-00a84859c3e9	2025-12-01	7605860.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	29282560.00	7225567.52	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120505
a0a6c16a-7960-455d-9605-0702c98b6f9f	6504929e-7f0b-47a6-b6d6-25032344b55f	2025-12-01	7605860.52	0.00	0.00	0.00	0.00	0.00	0.00	380293.00	29282560.00	7225567.52	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120506
a0a6c16a-7d2a-4219-aab3-86a672efc995	19c63207-1947-4bb3-9193-554042ba6da7	2025-12-01	4515360.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	17384098.00	4289592.48	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120507
a0a6c16a-8102-4fea-9dda-4a46892ba9b2	03e94a29-9883-46a5-9294-21d22f2fba7f	2025-12-01	4515360.48	0.00	0.00	0.00	0.00	0.00	0.00	225768.00	17384098.00	4289592.48	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120508
a0a6c16a-83e9-40e0-85d6-95e7ce123398	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025-12-01	4378395.00	0.00	0.00	0.00	0.00	0.00	0.00	190365.00	14086970.00	4188030.00	2025-12-22 12:18:19	2025-12-24 11:44:00	DEP25120509
a0a731bd-0892-4c38-85e3-0e1a33e3c9f7	36e92940-a131-4ac0-b45b-b8500ff4b040	2025-12-01	67840659.00	0.00	0.00	0.00	0.00	0.00	0.00	1474796.00	4424388.00	66365863.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120510
a0a731bd-0e4a-4aa1-9bf7-e4c610490cea	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025-12-01	851037164.00	0.00	0.00	0.00	0.00	0.00	0.00	20757004.00	166056028.00	830280160.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120511
a0a731bd-114c-44c5-858f-e9c2b13698bc	e47f3b62-82ae-4322-8660-bf104df108a5	2025-12-01	9462752.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	1075310.00	9247690.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120512
a0a731bd-1449-44f6-b498-131b097d6678	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025-12-01	9462752.00	0.00	0.00	0.00	0.00	0.00	0.00	215062.00	1075310.00	9247690.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120513
a0a731bd-1ab1-4974-b3b5-db7772d5dbf7	80acf346-539e-4c9a-aed0-9ff88df294f5	2025-12-01	19425000.00	0.00	0.00	0.00	0.00	0.00	0.00	404687.00	404687.00	19020313.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120514
a0a731bd-1d86-4528-b918-27eed4add1ba	4517635a-b083-4bba-bbba-22c060cff5b6	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120515
a0a731bd-2001-4fa7-93bc-cd54e78003db	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:00	DEP25120516
a0a731bd-245f-44b3-9864-a65dee90209a	5450ed79-c9ee-45ac-abd3-d657d1a8897c	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120517
a0a731bd-280e-4d20-969f-403044104f79	50a845bf-b203-4b10-b292-fda3c7b5ac6e	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120518
a0a731bd-2b5c-4d70-9dea-e115a25251d1	de6479e8-c9c2-41c1-9ad6-c74439bc986f	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120519
a0a731bd-2f0a-42cd-8d70-75367ef525eb	c7f80482-89d8-4f80-975d-34a752e992aa	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120520
a0a731bd-3294-4b3a-a4c8-755e48888680	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120521
a0a731bd-3650-4b7f-85bc-2a20efeb64e2	8e4323ee-5954-4946-b50e-252f098ee44e	2025-12-01	7373952.00	0.00	0.00	0.00	0.00	0.00	0.00	153624.00	153624.00	7220328.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120522
a0a731bd-3d48-4e56-a93b-5fd55fcb6c1d	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	2025-12-01	7270500.00	0.00	0.00	0.00	0.00	0.00	0.00	151468.00	151468.00	7119032.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120523
a0a731bd-40a6-4116-a764-aeadb05a314b	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025-12-01	21968750.00	0.00	0.00	0.00	0.00	0.00	0.00	578125.00	6359375.00	21390625.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120524
a0a731bd-45f7-4c7d-b3ed-a9d7f1b22444	9dbcc529-de27-4753-a772-90aa5f8c7894	2025-12-01	24719250.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1612125.00	24181875.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120525
a0a731bd-48c6-48ad-8174-084a9b9e3eb8	47665328-ff67-40a5-aac0-24572afbdcf8	2025-12-01	24719250.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1612125.00	24181875.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120526
a0a731bd-4c21-41d4-bcce-af5022cee74d	747d2923-ba5d-475d-a784-e41bc58e5561	2025-12-01	24719250.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1612125.00	24181875.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120527
a0a731bd-a001-4f74-a6aa-5dc317d21a5e	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120546
a0a731bd-a496-49d2-baed-46a2602cca1e	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120547
a0a731bd-a8a4-480d-87eb-5a005281cf2b	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120548
a0a731bd-ab64-4887-9a04-aa8f0d44807e	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120549
a0a731bd-af73-4cb3-ba79-2f497b92fd90	c521f578-f2c7-446d-b351-9b47fdb59913	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120550
a0a731bd-b39b-4f0d-8287-bc787d5578dc	836f58bc-d2d9-4543-bc82-7859db2da9be	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120551
a0a731bd-b8de-456c-b501-81c7606ad664	6630f300-223a-4694-a3b5-28193c508cba	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120552
a0a731bd-bd3f-4bd5-b3e6-b3bb95112393	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120553
a0a731bd-c104-40ef-aecc-18162e8fb1e4	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120554
a0a731bd-c668-41f9-bbf5-e59f203cb361	896e640c-3b59-4bc8-aba1-5ac076e99c49	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120555
a0a731bd-c9e8-460b-ba65-5128a8afa27c	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120556
a0a731bd-d0b8-4557-bd3d-97b5a381688c	bb12563d-78e3-4121-84df-edae5df20c63	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120557
a0a731bd-d582-4c68-8b8b-58be281d33f6	da02d35f-1531-49f7-89f3-9c9fed5f9553	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120558
a0a731bd-dbb3-47a0-ba49-c83e818a01fb	d30045d9-6179-4162-b8dc-e8d16ce29802	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120559
a0a731bd-e142-4568-ac3c-cfff5b94d2ed	46930604-8016-42a6-9329-ffdac3236bc1	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120560
a0a731bd-e521-43e3-bc5c-7ebe1e5eae7a	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120561
a0a731bd-ea28-4e7a-be80-89a93b6b74c6	538a6d2a-ec13-4d7c-87e7-f2e56d089780	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120562
a0a731bd-ef9c-486c-8c1e-c2e70a6e2a73	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120563
a0a731bd-f3a5-403c-8d90-5d975506ba16	1a2dda94-1f32-444a-a4dd-310edef0d76d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120564
a0a731bd-f6c2-4f49-b5e6-7d4fb2059ccc	e3e63659-175c-4748-b571-d2224a256534	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120565
a0a731bd-f9ba-422b-8a38-81a98f7d5eb9	80be2e71-1ead-4023-bd82-148c11e82d2f	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120566
a0a731bd-fcb2-447d-99fd-86c184df75f3	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120567
a0a731be-00fe-4025-897e-0c87292abf7c	0192e4a7-0901-4db9-aa00-c192d6adaa37	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120568
a0a731be-049e-4d6a-b6e0-6d57681409f1	9398fd93-f9b2-4639-8c65-51086cf62165	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120569
a0a731be-078a-4577-b50d-bfca8be9e5e8	db95bb38-c227-48ec-ac5a-69d642ba910e	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120570
a0a731be-0a96-4953-bab6-f9b05487ae6d	e3222e82-d284-45f4-87c5-6ca46ea72fac	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120571
a0a731be-0da7-4528-b086-e58737d9ce2f	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120572
a0a731be-117e-4134-9149-5d3d67a95076	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120573
a0a731be-1516-476f-9f68-d3346c40a7aa	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:01	DEP25120574
a0a731be-1a8a-43e9-965f-54e1225c8698	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120575
a0a731be-1f08-46e9-9c0f-911bf86f282d	abbaf21e-07b0-4097-889a-094bfeda26ef	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120576
a0a731be-2239-4476-ade4-26fd11313458	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120577
a0a731be-2755-4488-9359-f192362d1003	66df45b6-5011-45a5-be1d-f140cc3e4b7d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120578
a0a731be-2caa-4b87-aa55-9bff45d0770c	0c5094e1-9380-4a00-aef4-46048c2ec697	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120579
a0a731be-306c-4f1b-bf77-91408248772b	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120580
a0a731be-33d3-4ab8-9aac-f4e59c3c700d	d403907f-306d-4dfb-8ca4-a950b548394d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120581
a0a731be-3763-4db2-8ae6-b81cbda84f5e	3998992b-b5bf-4d03-9cd7-526c45df750c	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120582
a0a731be-3ef0-4f4b-8b92-2cad277776ae	b6368e98-7f87-42db-8b28-4084b11a0972	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120583
a0a731be-441a-4ceb-8ed4-537bdb0ee7bd	82ef3eb4-8b79-47e0-915e-7276ea7bd578	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120584
a0a731be-477a-46f3-bc0c-766022b04832	899b064e-2a20-489f-b713-56a3a1bcaf20	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120585
a0a731be-4a49-46e6-8237-8785aa1ad652	3f0dafbf-7fd9-407b-b1a9-4141e6326797	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120586
a0a731be-4ce8-421a-8bcd-cc57f4981962	659ca9a0-f2de-4890-86ed-ef404f8d93fd	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120587
a0a731be-5027-46b7-9ab7-08aa22d9532b	67bd771b-1a68-403b-b081-4727a5b09bbe	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120588
a0a731be-5450-4200-aa69-6ca4ed9a40af	45925fe4-a66c-4e4c-92e4-81f818fd71c8	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120589
a0a731be-5afb-4804-a330-a2ac93f48144	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120590
a0a731be-5f31-4ca2-be44-60f985158c50	9bb4b946-4d3b-427a-914c-accbaf7c362d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120591
a0a731be-6311-4833-b168-82e05865a5f5	e74afc6c-038f-4875-a703-89b52e09ee91	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120592
a0a731be-69ca-4163-b866-4733a6381586	43f964a1-fc8a-42fe-85b7-af80de5688a7	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120593
a0a731be-6f32-453f-b062-2be4fcc90f90	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120594
a0a731be-72c5-4632-bd33-33d19f97a23d	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120595
a0a731be-760f-4b32-918f-6cc2e0674dc8	06b7d765-707f-4860-a0e7-3e520d4c1578	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120596
a0a731be-78d1-49df-84e6-7816d29dc011	d81dabec-1c10-4269-9548-808f65039d63	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120597
a0a731be-7b31-4cb9-9ba3-77126c893a65	df697add-1313-4c55-957d-e53f28e5b499	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120598
a0a731be-7f84-42d0-9172-b10e2be717ae	68147a4a-8037-42ff-862a-64cc61cad395	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120599
a0a731be-8642-47fb-9bba-59e9c69fea01	3387ade2-a790-4808-9294-4308ebe93867	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120600
a0a731be-8a4e-48b4-aaba-8eb9ab4e0452	620120f2-1730-4c93-b033-954f79d02e56	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120601
a0a731be-8d77-45ee-bfcb-0adc198ba928	c55b5d93-f4c2-4588-9bc0-e3051f907091	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120602
a0a731be-908e-4616-b90d-e930d1110409	a0a355f7-a358-4e92-bdbd-9b31808a868e	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120603
a0a731be-940f-4493-ae2b-9567b35b7fb5	091d5401-cef6-40f8-8778-87389d39e51f	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120604
a0a731be-96f9-431c-956e-77b7f4f9d722	3189250e-a44f-4b07-9d24-4b9b128485f9	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120605
a0a731be-99bd-4090-bd63-8d967cc75f4d	55042527-68f7-43ac-9e1a-b2d1872b8b82	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120606
a0a731be-9ec3-4c87-bfd4-e4f642fbcb4c	2e1343ff-6d31-4246-9667-83ecf97a93ba	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120607
a0a731be-a326-4c6c-8757-43b8e6fe73c6	c54762d7-e7d2-499f-a4db-fb340f1e740d	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120608
a0a731be-a628-4f0f-827f-98b5ef2cc5c4	27ae456f-8d57-4820-a7e1-e478df363acf	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120609
a0a731be-aa1e-4ab8-a7be-a2488255605b	a48bed46-6d76-4a7e-ad06-77a533df7482	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120610
a0a731be-ad46-4da8-92ca-3e04b1efc2ba	361667bd-377a-46f0-83ea-bdce1a20b6ad	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120611
a0a731be-b136-4864-9706-b5fc3cdef4f0	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120612
a0a731be-b4da-40bf-9e86-b8fb27d81be9	ebd82a70-8c97-4dad-80e0-7ece07478479	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120613
a0a731be-bbc3-420b-a3c2-ac3a7949acc7	d2dac9ea-3c11-4698-8628-9c3412693fe6	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120614
a0a731be-bf5d-40cd-9b98-444535552e3d	0b19b75b-02ea-429d-b638-696f626d1384	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120615
a0a731be-c2b6-4b7b-9f92-df3ef3db74ad	b11ed488-c372-47f4-bf13-3a27148b98f0	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120616
a0a731bd-89e8-4b5f-a4b1-71fc3526a103	5e69818f-651f-4e8b-8a69-513fa0a773db	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120540
a0a7316c-8b20-4295-b99c-2ba2ccb756e0	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	82500000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110717
a0a7316c-8f0b-46bd-a53d-60fd526be43b	42b0073a-07f3-4dcc-b82c-e2851b626433	2025-11-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	154550000.00	0.00	2025-12-22 17:31:31	2025-12-24 11:50:54	DEP25110718
a0a731bd-8d33-4670-a2fd-47f63136d4a5	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120541
a0a731bd-53b6-4875-be4c-8aa5e95c2da0	2f8f647c-1936-4b32-93f7-9ebbcda6d039	2025-12-01	24719250.00	0.00	0.00	0.00	0.00	0.00	0.00	537375.00	1612125.00	24181875.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120528
a0a731bd-5899-497a-b610-85f68aaf1509	f743b734-490e-470d-bc30-19e730a855b2	2025-12-01	60125001.00	0.00	0.00	0.00	0.00	0.00	0.00	1503125.00	13528124.00	58621876.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120529
a0a731bd-5db4-4b26-b606-c8ccf69d6992	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025-12-01	29600001.00	0.00	0.00	0.00	0.00	0.00	0.00	740000.00	6659999.00	28860001.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120530
a0a731bd-63bf-4fe6-b19b-9d79df6550ca	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120531
a0a731bd-67ab-4493-b956-a679f3802007	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120532
a0a731bd-6b72-4194-a072-3eca5bfb46be	30a9ed88-3599-4d7f-8456-cce980762f96	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120533
a0a731bd-6f28-4c94-9081-ca2a3492207d	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120534
a0a731bd-72e7-4799-8a67-2b51d3fbdfd3	34453391-14df-41b0-8475-2d31c5371f29	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120535
a0a731bd-7767-4359-90b5-d08b1ef65859	edddbe54-8ed9-496c-88d9-1a96279445c6	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120536
a0a731bd-7e51-4560-889c-4d8b0bcaee00	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120537
a0a731bd-8241-420d-a995-b275bcc05c69	e8925ef1-66f5-432d-92c3-c37b79062eef	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120538
a0a731bd-85e9-406e-9c23-59d7f4529c3a	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120539
a0a731bd-9190-4fd5-bbe1-13502ada203c	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120542
a0a731bd-9459-434f-8d33-a6f549b485cb	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120543
a0a731bd-97e4-4bff-a3c9-34c9bf52182c	3bd5cf1d-ae87-4735-b753-1f810b177052	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120544
a0a731bd-9cdc-4b45-90ea-2d6ee4fd51d1	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025-12-01	28675000.00	0.00	0.00	0.00	0.00	0.00	0.00	716875.00	6451875.00	27958125.00	2025-12-22 17:32:24	2025-12-24 11:44:01	DEP25120545
a0a731be-c5aa-46b0-a8a8-2d91830dea1b	76c9fc95-c035-4d55-a192-88b22c907aaf	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120617
a0a731be-c89a-4bc8-9fbd-6b2a04e19dff	e83c6db7-1c8d-44d3-818d-5fabf4127734	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120618
a0a731be-cbe4-49c2-be59-cbf8ff93235d	993c08e7-1142-433b-93c1-61aad85798f2	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120619
a0a731be-cf9c-4283-bdbe-d69cdd3e77dd	1bb9f4ba-525d-4390-800d-140404e63991	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120620
a0a731be-d4a7-402d-8877-24a8e3dcf46d	593b6e25-ee8c-4702-b04e-b8675711696b	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120621
a0a731be-d822-4c7a-a83b-f65c3f94a7a9	34cb37c8-43d8-42bf-8be8-3622219b1fd2	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120622
a0a731be-dafe-47f7-9430-a64da0432cf0	a7072c26-3165-47e4-81bc-5a88a2d43ab1	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120623
a0a731be-de99-4926-b310-62df1b192ecf	f99028fd-3f94-4fc4-8635-13369d98711f	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120624
a0a731be-e201-4f57-be89-dc7dd9324ad8	c0677d15-296c-4d34-98e1-cc940baa7a99	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120625
a0a731be-e511-4e00-8403-44bae72de2e3	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120626
a0a731be-e807-49ac-913f-1ddf90273a66	e00e59a1-0f30-4ea7-8094-3956711ff682	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120627
a0a731be-eb04-4b83-9231-af33805bf290	5373b97d-245d-4889-9018-20958b798c17	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120628
a0a731be-ee21-4e80-b950-895c8600edc6	e1b3ed82-00ea-485e-b741-070c71fe1d2c	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120629
a0a731be-f1aa-4930-a457-9ac03dbafd3f	627738fb-548f-49a7-ade4-0f7ae516c3c3	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120630
a0a731be-f528-4659-b8b7-1e707e6625bf	d71ea253-d0e4-42f4-861d-a743fd7a8900	2025-12-01	21083336.00	0.00	0.00	0.00	0.00	0.00	0.00	638888.00	2555552.00	20444448.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120631
a0a731be-f7df-4995-8e8a-d5330d389b89	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025-12-01	415695001.00	0.00	0.00	0.00	0.00	0.00	0.00	10392375.00	93531374.00	405302626.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120632
a0a731be-fc04-40c6-a44b-b7f2bd27deba	de204b49-049f-4e74-9fad-76680c0ec640	2025-12-01	69375000.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	69375000.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120633
a0a731be-febe-4962-87bd-01b6510c3af1	d00fd50d-fdfa-440b-8698-8ba7c354386a	2025-12-01	6972882.00	0.00	0.00	0.00	0.00	0.00	0.00	151584.00	454752.00	6821298.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120634
a0a731bf-029a-44dc-a68e-709ebd73bdae	d88cc5d9-f493-4156-821e-29602853c857	2025-12-01	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	0.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120635
a0a731bf-0700-4e06-b276-a05d2dc11b55	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025-12-01	5138376.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	583905.00	5021595.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120636
a0a731bf-0b0f-4b3b-bc36-9b386ef05fa5	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025-12-01	5138376.00	0.00	0.00	0.00	0.00	0.00	0.00	116781.00	583905.00	5021595.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120637
a0a731bf-0ed5-4ee6-b449-b8b526bbce9f	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-12-01	1414324750.00	0.00	0.00	0.00	0.00	0.00	0.00	32143744.00	160718494.00	1382181006.00	2025-12-22 17:32:25	2025-12-24 11:44:02	DEP25120638
\.


--
-- Data for Name: assets_depr_movements; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_depr_movements (uuid, asset_uuid, period, category, amount, depr_start_period, group_uuid, source_type, source_uuid, note, created_at, updated_at) FROM stdin;
a0aababf-f7ac-4937-b0bd-edddd45870ea	e31d30be-ccad-45b8-a337-70e5c00155e2	2025-11-01	ADJUSTMENT_DEPRECIATION	-250.00	2025-11-01	\N	manual	\N	\N	2025-12-24 11:43:00	2025-12-24 11:43:00
\.


--
-- Data for Name: assets_depr_policy; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_depr_policy (uuid, asset_uuid, method, useful_life_months, salvage_value, depr_start_date, convention, cutoff_day, start_rule, is_active, created_at, updated_at) FROM stdin;
a0a6c0f6-bbce-4b61-bbce-2019642bf584	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	SL	48	0.00	2019-09-04	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f7-112c-4f7c-ab46-5da37ae80d7d	c9edde02-2af4-43a8-8e4c-6a02c17357b9	SL	48	0.00	2020-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f7-4d51-4d98-841f-caba63808963	42b0073a-07f3-4dcc-b82c-e2851b626433	SL	48	0.00	2020-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f7-8b1d-432f-b987-c0982f9c7857	9beb94c2-f47d-4b48-9281-54ec00cf0758	SL	48	0.00	2020-09-03	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f7-c19a-4058-9730-6dabf4d457a2	c88e2c69-914f-403e-ab36-0a9322d6591f	SL	48	0.00	2020-10-08	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f7-fe45-47c3-8281-99605c3310d5	9580ea1b-0f93-4c89-b167-a089131d5761	SL	48	0.00	2020-10-08	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:04	2025-12-22 12:17:04
a0a6c0f8-4450-48bd-b03c-3a18fb7ca754	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	SL	48	0.00	2020-10-08	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:05	2025-12-22 12:17:05
a0a6c0f8-7ebe-4f36-8172-3d6f0ae22853	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	SL	349	0.00	2020-11-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:05	2025-12-22 12:17:05
a0a6c0f8-d6e6-4b70-9086-4f6cd0b4f43a	1c4a40c1-aeb5-4287-a4b1-383d158920e5	SL	349	0.00	2020-11-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:05	2025-12-22 12:17:05
a0a6c0f9-25d6-453f-9b44-841c5a07a1be	f875c2ca-1800-433b-b0a4-2d4d31ba308e	SL	349	0.00	2020-11-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:05	2025-12-22 12:17:05
a0a6c0f9-6beb-4460-9a56-5320c67ba0cd	54ec2fba-0b2b-4783-ab74-464ba53d2e07	SL	48	0.00	2020-12-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:05	2025-12-22 12:17:05
a0a6c0f9-ab55-4a5c-a0af-06555b4514df	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	SL	48	0.00	2020-12-03	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0f9-e9e6-459e-bdcb-bcd32a16ed53	52d9a146-b1cf-4110-b89d-be03c22a6e0e	SL	48	0.00	2020-12-04	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0fa-2872-4cc5-bc67-7b607b0f80e9	99970f15-9c4a-4d4f-b550-a7ef488054d0	SL	48	0.00	2021-01-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0fa-67b0-425d-b012-450cf2bef9c0	e971913d-0f93-4a70-85eb-c0ed12a172d8	SL	48	0.00	2023-01-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0fa-a0a2-4a55-87fb-90a53d6f9f43	101fda0f-877a-4290-9df5-00a84859c3e9	SL	96	0.00	2019-07-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0fa-f98f-45cd-afe5-523c93e9e6dc	6504929e-7f0b-47a6-b6d6-25032344b55f	SL	96	0.00	2019-07-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:06	2025-12-22 12:17:06
a0a6c0fb-4c58-4a8a-8980-101d2053a46f	19c63207-1947-4bb3-9193-554042ba6da7	SL	96	0.00	2019-07-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:07	2025-12-22 12:17:07
a0a6c0fb-a7ae-436e-9439-3a76bfff15da	03e94a29-9883-46a5-9294-21d22f2fba7f	SL	96	0.00	2019-07-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:07	2025-12-22 12:17:07
a0a6c0fc-0879-4566-ae2f-6b2658993fc9	49fe0c73-3650-4c46-b8b2-28b11191c8fb	SL	96	0.00	2019-10-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 12:17:07	2025-12-22 12:17:07
a0a72eba-b428-4431-88f2-bd0885f87c61	36e92940-a131-4ac0-b45b-b8500ff4b040	SL	48	0.00	2025-09-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72eba-cbe3-4f63-b232-197aab50b0d6	a11862d4-69a5-4d2b-a426-57a89de1b13c	SL	48	0.00	2025-04-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72eba-e5a9-4be3-9559-cf6a97aab122	e47f3b62-82ae-4322-8660-bf104df108a5	SL	48	0.00	2025-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72eba-f8b5-4853-9060-d292af67e333	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	SL	48	0.00	2025-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72ebb-0b0d-4049-ada2-d093d8efda7e	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	SL	48	0.00	2025-11-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72ebb-1ad4-4fe4-b5a3-5efba1aee3b3	80acf346-539e-4c9a-aed0-9ff88df294f5	SL	48	0.00	2025-11-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:23:59	2025-12-22 17:23:59
a0a72ebb-2ca8-4a44-a641-de4796223559	4517635a-b083-4bba-bbba-22c060cff5b6	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-3a0c-4dbc-8ce8-56d03c042a02	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-47f4-4ade-a967-97d68c95f6c8	5450ed79-c9ee-45ac-abd3-d657d1a8897c	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-55bb-4130-9fcc-cfc9d1ecb647	50a845bf-b203-4b10-b292-fda3c7b5ac6e	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-6746-4628-bc58-c24a48a92979	de6479e8-c9c2-41c1-9ad6-c74439bc986f	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-7469-4fe0-8cad-551660fbaeb2	c7f80482-89d8-4f80-975d-34a752e992aa	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-8612-4629-9670-c5b0fac65c2f	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-959e-47b7-9baf-9b8d94b94a92	8e4323ee-5954-4946-b50e-252f098ee44e	SL	48	0.00	2025-11-06	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-a462-4467-abb1-73492a0f3627	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	SL	48	0.00	2025-11-03	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-b935-4769-be49-2c567687550d	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	SL	48	0.00	2025-01-08	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-d640-46c9-b617-c13e7e09f347	9dbcc529-de27-4753-a772-90aa5f8c7894	SL	48	0.00	2025-09-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-e84c-4c3f-8e55-14808144b7f0	47665328-ff67-40a5-aac0-24572afbdcf8	SL	48	0.00	2025-09-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebb-f708-4ce6-b409-8d965263b8c9	747d2923-ba5d-475d-a784-e41bc58e5561	SL	48	0.00	2025-09-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-064a-4946-b1fe-0b041324ef24	2f8f647c-1936-4b32-93f7-9ebbcda6d039	SL	48	0.00	2025-09-09	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-1a9b-444c-bdd7-4ea818deae0f	f743b734-490e-470d-bc30-19e730a855b2	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-319e-4a2f-ae16-cc4e7f522be9	ac204bbb-af9f-4e3a-9734-082c29c9641f	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-4f54-452a-9696-f69b7fd51522	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-6473-4d78-b1d9-c181fc903dfa	31a57d16-cb30-4e53-8e7d-3ee074f5770b	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-7842-4940-9227-8fba99f73db9	30a9ed88-3599-4d7f-8456-cce980762f96	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-8efa-4bca-a01d-2a93a4848a3f	4ee48863-fa9b-4ff3-9c00-2304ada83c29	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-a5a2-4ebc-a0ff-82c8b74d1206	34453391-14df-41b0-8475-2d31c5371f29	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:00	2025-12-22 17:24:00
a0a72ebc-c3aa-4ba2-b940-da4087ae3990	edddbe54-8ed9-496c-88d9-1a96279445c6	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebc-e424-4974-8235-14168258adcb	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebc-f945-4563-bd1b-ca531875e39b	e8925ef1-66f5-432d-92c3-c37b79062eef	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-196a-45d5-be63-cfa28e766553	a1906f9d-e1c1-4072-99c4-51cba2577d90	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-3434-40e8-99ab-b750070a75b2	5e69818f-651f-4e8b-8a69-513fa0a773db	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-4edd-4384-90ed-617051b7f257	ba105ad8-72ad-40f6-8634-03d1e712b9af	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-7868-4e2b-ae1a-79302e111e8c	fc890cda-3a6a-436b-8aee-2b1e22131cfd	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-9531-48ee-854f-93f12b4482bb	62fcc371-d6de-4ef0-88ef-413b40c6783d	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-b8f0-40af-8f3b-4218817d4cf0	3bd5cf1d-ae87-4735-b753-1f810b177052	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebd-ddf3-49ea-8429-008aaab7954b	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebe-089a-465d-91f8-5e92dccc4c10	cddab2cb-f430-4819-b9cf-c35a54b156cd	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebe-2634-4aa1-9fcc-45c7cf47f185	4852ac97-baee-4c1e-8b48-7e0fd276ec48	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:01	2025-12-22 17:24:01
a0a72ebe-4598-4ed1-a8c3-2b0ba13c6d61	3b16435d-b93f-4811-bf25-6d03a45cc6dc	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-5e3c-4177-a63a-683dfead8e65	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-76f4-431c-b6c2-3add8a9b39c2	c521f578-f2c7-446d-b351-9b47fdb59913	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-8f83-4243-a954-bf40f02620d6	836f58bc-d2d9-4543-bc82-7859db2da9be	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-aeed-4522-93db-913f0587e6d7	6630f300-223a-4694-a3b5-28193c508cba	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-cdc3-46cb-98bd-3271c5dd50f1	75c6a8ad-7be8-47cb-9165-89d42bb233c7	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebe-f16c-4de6-a1f5-9c0a07fa253f	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-05a0-4524-bab4-a3898386225c	896e640c-3b59-4bc8-aba1-5ac076e99c49	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-1acf-4583-910f-e96e1f2ef495	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-3184-43bc-a3e4-b5e0395b6a79	bb12563d-78e3-4121-84df-edae5df20c63	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-4dc6-4553-a569-53caa832e5a0	da02d35f-1531-49f7-89f3-9c9fed5f9553	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-6c09-4129-97e3-69113996f26e	d30045d9-6179-4162-b8dc-e8d16ce29802	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-85df-4929-b99f-559747c296a8	46930604-8016-42a6-9329-ffdac3236bc1	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-9c1d-4b27-ae35-c71dede152dd	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-ac3f-4011-a08f-4ef56753a329	538a6d2a-ec13-4d7c-87e7-f2e56d089780	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:02	2025-12-22 17:24:02
a0a72ebf-ca4d-4e6c-8e13-ce6ee240f36f	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ebf-e02f-4fe3-b8b8-7c2db792b666	1a2dda94-1f32-444a-a4dd-310edef0d76d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ebf-f261-4cbb-ba8d-b9f7c0d5d9ed	e3e63659-175c-4748-b571-d2224a256534	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-020f-485f-9dbe-b5db8685f9a5	80be2e71-1ead-4023-bd82-148c11e82d2f	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-1503-4b65-a9aa-589938b97b86	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-2533-4cd2-ad87-31e2f200ffd4	0192e4a7-0901-4db9-aa00-c192d6adaa37	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-3750-4c38-b4c4-4046275cef14	9398fd93-f9b2-4639-8c65-51086cf62165	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-4785-414f-8e3f-dab3c2bef792	db95bb38-c227-48ec-ac5a-69d642ba910e	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-59eb-4476-8dc9-e3555226634f	e3222e82-d284-45f4-87c5-6ca46ea72fac	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-7778-45dd-974d-cb5057d63309	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-8d52-4c09-9b86-c3996fbfcb75	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-a3b6-4cf0-b5f1-cedbd3c7052d	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-bdaa-4c61-94af-908405a49d00	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-d4a5-48df-840e-0309ef026061	abbaf21e-07b0-4097-889a-094bfeda26ef	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-e2e0-4164-a057-11bde82c3f76	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec0-f515-4b64-a263-5d1ede31c912	66df45b6-5011-45a5-be1d-f140cc3e4b7d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec1-03aa-40ff-80d9-e997190e4090	0c5094e1-9380-4a00-aef4-46048c2ec697	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec1-1633-48be-bf9e-21b8ae3a6dc9	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec1-2790-4771-ae4d-a1297b323ca8	d403907f-306d-4dfb-8ca4-a950b548394d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec1-3eed-4d75-b651-b84d1c2662ec	3998992b-b5bf-4d03-9cd7-526c45df750c	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:03	2025-12-22 17:24:03
a0a72ec1-5cbb-473d-830a-10a9b8b68f1a	b6368e98-7f87-42db-8b28-4084b11a0972	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-6dc9-4d7a-a589-ccaad2f4d8e2	82ef3eb4-8b79-47e0-915e-7276ea7bd578	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-7f37-4645-add0-f0705f3be993	899b064e-2a20-489f-b713-56a3a1bcaf20	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-91e3-4ee8-a023-4e76fc4a86d6	3f0dafbf-7fd9-407b-b1a9-4141e6326797	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-a19f-4c4d-9730-a852cce1008f	659ca9a0-f2de-4890-86ed-ef404f8d93fd	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-b1a0-43e5-b587-e183f859ca32	67bd771b-1a68-403b-b081-4727a5b09bbe	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-c628-4533-9c73-a786a793e071	45925fe4-a66c-4e4c-92e4-81f818fd71c8	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-d746-4c24-88b1-f674310f66ca	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-e81a-4e73-9efb-7ccb371530fb	9bb4b946-4d3b-427a-914c-accbaf7c362d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec1-fce0-49b5-ba4f-fe1d29cc2606	e74afc6c-038f-4875-a703-89b52e09ee91	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-1065-49fa-8893-dc525ff11b5f	43f964a1-fc8a-42fe-85b7-af80de5688a7	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-27b7-45be-b4fa-4293f90d336b	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-36d8-45c2-9e2d-b642571df2b8	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-4ce1-46a1-b002-b860e8f1a5a2	06b7d765-707f-4860-a0e7-3e520d4c1578	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-5b97-4e81-b98e-8335bea0cdc4	d81dabec-1c10-4269-9548-808f65039d63	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-6a8d-4d66-844e-dc786f075405	df697add-1313-4c55-957d-e53f28e5b499	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-7aca-4bf1-b778-3d1b7b675e5a	68147a4a-8037-42ff-862a-64cc61cad395	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-8f96-4ea8-b253-0ae5eb415da2	3387ade2-a790-4808-9294-4308ebe93867	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-a6c5-46f0-9c15-88f3d08d8c4a	620120f2-1730-4c93-b033-954f79d02e56	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-ba14-44aa-b7c1-07fa4420d0ab	c55b5d93-f4c2-4588-9bc0-e3051f907091	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:04	2025-12-22 17:24:04
a0a72ec2-d154-49da-b1b9-c37743b54952	a0a355f7-a358-4e92-bdbd-9b31808a868e	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec2-e731-49c0-8117-7db7a7f3bc56	091d5401-cef6-40f8-8778-87389d39e51f	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec2-fc6b-4617-acf8-59a91f39d5e3	3189250e-a44f-4b07-9d24-4b9b128485f9	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-0f9f-4940-8611-cc0a677114d6	55042527-68f7-43ac-9e1a-b2d1872b8b82	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-2234-45f1-9222-6c8b649ab7e3	2e1343ff-6d31-4246-9667-83ecf97a93ba	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-3755-4371-84b5-2f177a1d4904	c54762d7-e7d2-499f-a4db-fb340f1e740d	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-4649-4770-b342-54a479314cc5	27ae456f-8d57-4820-a7e1-e478df363acf	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-5a8c-4c7a-878f-0f66ecf3019a	a48bed46-6d76-4a7e-ad06-77a533df7482	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-70be-4017-aa2b-86a0adf11101	361667bd-377a-46f0-83ea-bdce1a20b6ad	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-85b0-41f9-976c-3090531e3de2	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-a1ec-4e3f-a33d-06b8f1666dce	ebd82a70-8c97-4dad-80e0-7ece07478479	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-c255-43c7-abdd-dd40eca25193	d2dac9ea-3c11-4698-8628-9c3412693fe6	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-d5fd-4818-9498-6ece8fd24d6c	0b19b75b-02ea-429d-b638-696f626d1384	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec3-efbd-4f07-b091-d525665a7afa	b11ed488-c372-47f4-bf13-3a27148b98f0	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec4-010d-47f6-a4a6-8e945acb1cec	76c9fc95-c035-4d55-a192-88b22c907aaf	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec4-2368-409e-b3d7-f24d0ed101f5	e83c6db7-1c8d-44d3-818d-5fabf4127734	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec4-36d1-463e-92ba-5d47bd9aa0b6	993c08e7-1142-433b-93c1-61aad85798f2	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec4-4685-4465-8dda-2fe34d266477	1bb9f4ba-525d-4390-800d-140404e63991	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:05	2025-12-22 17:24:05
a0a72ec4-5c6e-46e3-bab1-1922f87dd63b	593b6e25-ee8c-4702-b04e-b8675711696b	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-72e0-42ec-ae63-8902fe1ef85c	34cb37c8-43d8-42bf-8be8-3622219b1fd2	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-8574-45af-b560-69ab5c010c4e	a7072c26-3165-47e4-81bc-5a88a2d43ab1	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-9acd-4a07-8549-900f89cf1bd8	f99028fd-3f94-4fc4-8635-13369d98711f	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-b1bc-457f-af6d-cbdb3fcc08d4	c0677d15-296c-4d34-98e1-cc940baa7a99	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-c9cc-4c74-a73f-a47ce20e4a58	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-dca0-4f23-9f6e-11ae4ce96764	e00e59a1-0f30-4ea7-8094-3956711ff682	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec4-edb0-4c1e-853b-32bf03ccc82e	5373b97d-245d-4889-9018-20958b798c17	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-07ee-4fa0-a3a8-3297512dd10e	e1b3ed82-00ea-485e-b741-070c71fe1d2c	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-1900-4a8c-b368-e772f108d8ce	627738fb-548f-49a7-ade4-0f7ae516c3c3	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-2f45-403a-8c15-12e2e0895a39	d71ea253-d0e4-42f4-861d-a743fd7a8900	SL	36	0.00	2025-08-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-452d-43af-84bb-752324a3d5b8	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	SL	48	0.00	2025-03-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-5ca8-4dcd-8a97-2379cfbdca70	de204b49-049f-4e74-9fad-76680c0ec640	SL	48	0.00	2025-12-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-750b-4da8-854c-6a64666d4248	d00fd50d-fdfa-440b-8698-8ba7c354386a	SL	48	0.00	2025-09-15	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-902c-4cad-9b9a-b6ced8d4e40c	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	SL	48	0.00	2025-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-a4eb-4e13-97e9-48c530f2f543	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	SL	48	0.00	2025-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a72ec5-b9f2-4a55-b602-0ed9a7fa58b2	e31d30be-ccad-45b8-a337-70e5c00155e2	SL	48	0.00	2025-07-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:24:06	2025-12-22 17:24:06
a0a7306c-22a8-4bc1-8d8a-7ddf90eaad72	d88cc5d9-f493-4156-821e-29602853c857	SL	48	0.00	2025-12-01	PRORATA_MONTH	16	CUT_OFF_NEXT_OR_NEXT2	t	2025-12-22 17:28:43	2025-12-22 17:28:43
\.


--
-- Data for Name: assets_depr_transfer_requests; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_depr_transfer_requests (uuid, from_asset_uuid, to_asset_uuid, transfer_type, amount, actual_date, note, attachment_path, kode_status, requested_by, approved_by, approved_at, group_uuid, created_at, updated_at, deleted_at, transfer_code) FROM stdin;
\.


--
-- Data for Name: assets_depr_yearly; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_depr_yearly (uuid, asset_uuid, fiscal_year, opening_balance, total_additions, depr_expense_year, adjustment_depreciation_year, accumulated_depr_end, ending_balance_year, created_at, updated_at) FROM stdin;
a0aab722-c014-4ea6-9d84-6c17ac6c4652	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	2025	52691518645.00	0.00	2107660740.00	0.00	10713942095.00	52515880250.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-c738-4e30-81e2-1d800c8dc85c	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	2025	0.00	0.00	93531374.00	0.00	93531374.00	488441626.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-c7f6-4087-bae3-b28af6358a20	a1906f9d-e1c1-4072-99c4-51cba2577d90	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-c8b6-4ee6-8273-f07c90bf7b19	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-c955-4d27-84e1-8de66e6e087c	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-ca07-438b-8c13-800df294266e	b11ed488-c372-47f4-bf13-3a27148b98f0	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-caaf-4361-8b1e-9bf27839d2d3	19c63207-1947-4bb3-9193-554042ba6da7	2025	6998808.48	0.00	2709216.00	0.00	17384098.00	6773040.48	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-cb46-4ba1-a734-6efac98b2b1a	54ec2fba-0b2b-4783-ab74-464ba53d2e07	2025	0.00	0.00	0.00	0.00	209440000.00	0.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-cbde-4fea-b784-d36140860d7d	82ef3eb4-8b79-47e0-915e-7276ea7bd578	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-cc7b-468b-9a84-15c4eb4bd854	538a6d2a-ec13-4d7c-87e7-f2e56d089780	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-cd0d-44cd-86de-1e44fe71e92b	627738fb-548f-49a7-ade4-0f7ae516c3c3	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d1ef-46a2-99c4-87e9601932e3	66df45b6-5011-45a5-be1d-f140cc3e4b7d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d362-49d1-8bec-62ff98c44d01	cddab2cb-f430-4819-b9cf-c35a54b156cd	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d49c-4955-8b3a-66cbace23af3	46930604-8016-42a6-9329-ffdac3236bc1	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d65e-47a0-b2d8-1216b7c7c97f	de6479e8-c9c2-41c1-9ad6-c74439bc986f	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d76e-404e-8548-172bc4c20043	a48bed46-6d76-4a7e-ad06-77a533df7482	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d881-4671-95a4-b3309411a684	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	2025	0.00	0.00	0.00	0.00	58822500.00	0.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-d9c8-4261-b3b9-22f8a51e06b6	3bd5cf1d-ae87-4735-b753-1f810b177052	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-dc73-4539-8318-e6f095ade654	36e92940-a131-4ac0-b45b-b8500ff4b040	2025	0.00	0.00	4424388.00	0.00	4424388.00	69315455.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-dd85-4ccb-a165-3bd17416a080	d00fd50d-fdfa-440b-8698-8ba7c354386a	2025	0.00	0.00	454752.00	0.00	454752.00	7124466.00	2025-12-24 11:32:53	2025-12-24 11:32:53
a0aab722-df9f-455f-a16f-fc80da73f66a	80acf346-539e-4c9a-aed0-9ff88df294f5	2025	0.00	0.00	404687.00	0.00	404687.00	19425000.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e11e-408a-9362-6a06a95ce043	3189250e-a44f-4b07-9d24-4b9b128485f9	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e28a-4da4-959c-e1903e8cc588	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e381-4a63-8bee-a9e515a20f28	e83c6db7-1c8d-44d3-818d-5fabf4127734	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e454-447b-9d7e-dbb9303497e3	e8925ef1-66f5-432d-92c3-c37b79062eef	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e543-4ecf-aa76-7d1d52ea12c6	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	2025	0.00	0.00	6359375.00	0.00	6359375.00	27171875.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e60c-4079-8418-5f8ff05d04f4	836f58bc-d2d9-4543-bc82-7859db2da9be	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e6ef-4b73-87be-55ed0b934923	c521f578-f2c7-446d-b351-9b47fdb59913	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e78e-4d36-9d56-9dbf750919e4	03e94a29-9883-46a5-9294-21d22f2fba7f	2025	6998808.48	0.00	2709216.00	0.00	17384098.00	6773040.48	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e851-4594-bb4c-7886fded6199	d88cc5d9-f493-4156-821e-29602853c857	2025	0.00	0.00	0.00	0.00	0.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e8f8-442c-b819-b079c5a19836	f743b734-490e-470d-bc30-19e730a855b2	2025	0.00	0.00	13528124.00	0.00	13528124.00	70646876.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-e9c8-4032-83b5-18eed0241bc2	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-ea95-4ba3-8879-071d6f54f7c0	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	2025	0.00	0.00	151468.00	0.00	151468.00	7270500.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-eb5f-464a-af46-40a68bf58a21	bb12563d-78e3-4121-84df-edae5df20c63	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-ec2b-4f3b-ae8c-80c787d78c5b	896e640c-3b59-4bc8-aba1-5ac076e99c49	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-ed2e-4732-ba7d-8bfab77c201a	9398fd93-f9b2-4639-8c65-51086cf62165	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-ee04-4b1c-807f-819fa3bd6f17	c54762d7-e7d2-499f-a4db-fb340f1e740d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-eecc-45ca-9175-c7af08f18439	db95bb38-c227-48ec-ac5a-69d642ba910e	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-efbc-40da-ab1e-32df760f2204	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f08e-4589-9da1-13e2cdb4a094	c0677d15-296c-4d34-98e1-cc940baa7a99	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f146-4763-93f5-f6957d52e968	e3e63659-175c-4748-b571-d2224a256534	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f216-4dfd-80d2-57201d927b15	0b19b75b-02ea-429d-b638-696f626d1384	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f40e-4d2d-9a2b-1c66831407bc	a7072c26-3165-47e4-81bc-5a88a2d43ab1	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f509-4dbb-b447-3e27ecf408c3	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	2025	0.00	0.00	583905.00	0.00	583905.00	5488719.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f5e0-4750-80e5-682e27ef59de	e74afc6c-038f-4875-a703-89b52e09ee91	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f6b7-447c-b40d-55071dd52480	5e69818f-651f-4e8b-8a69-513fa0a773db	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f7a6-404a-b3f7-8fc4be290419	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f859-4859-9dd3-e5e0cc50a6b9	9bb4b946-4d3b-427a-914c-accbaf7c362d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f914-4fda-b65b-8138fa143c77	1c4a40c1-aeb5-4287-a4b1-383d158920e5	2025	52691518645.00	0.00	2107660740.00	0.00	10713942095.00	52515880250.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-f9c1-4aa8-8f11-9ca403591d81	68147a4a-8037-42ff-862a-64cc61cad395	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-fa5c-49ec-aeda-607165c5da14	e3222e82-d284-45f4-87c5-6ca46ea72fac	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-fb40-4dd0-88a3-df882e26a49e	9beb94c2-f47d-4b48-9281-54ec00cf0758	2025	0.00	0.00	0.00	0.00	24145000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-fc57-416a-bc7a-85c0eea3a1bf	c55b5d93-f4c2-4588-9bc0-e3051f907091	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-fd1e-4872-828a-f7cdaba57cb3	edddbe54-8ed9-496c-88d9-1a96279445c6	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-fe00-4d6d-b1d3-790bcd4c5b5e	e47f3b62-82ae-4322-8660-bf104df108a5	2025	0.00	0.00	1075310.00	0.00	1075310.00	10107938.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab722-feba-4d0a-a82a-6609d9ad28ba	76c9fc95-c035-4d55-a192-88b22c907aaf	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0073-4d03-85cd-fbe37f1b81ed	55042527-68f7-43ac-9e1a-b2d1872b8b82	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-01ca-4c04-bef1-5b65b59b207a	6504929e-7f0b-47a6-b6d6-25032344b55f	2025	11789083.52	0.00	4563516.00	0.00	29282560.00	11408790.52	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-030d-4a41-bd7c-74463eebc191	3998992b-b5bf-4d03-9cd7-526c45df750c	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-04a6-41c4-a0d5-e59282d78f03	9580ea1b-0f93-4c89-b167-a089131d5761	2025	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-057a-4865-9f35-937473da6b66	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-062f-4601-9853-9fa566a3652f	2e1343ff-6d31-4246-9667-83ecf97a93ba	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-073b-40ba-b613-69f65709da1a	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0835-4857-af82-483425e860a2	6630f300-223a-4694-a3b5-28193c508cba	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0919-4875-b2db-0a54dfe89b6f	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-09e2-48e4-a9db-ea2b4b8b4c7f	50a845bf-b203-4b10-b292-fda3c7b5ac6e	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0ab8-425c-866a-f5119aefed69	3387ade2-a790-4808-9294-4308ebe93867	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0be0-4f3f-b905-8c1661e07425	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	2025	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0d33-4bac-b558-7ea69593a967	62fcc371-d6de-4ef0-88ef-413b40c6783d	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0e04-4d21-95cf-8a39bc3f6d60	45925fe4-a66c-4e4c-92e4-81f818fd71c8	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0edf-4e69-a040-7ca59167d519	ac204bbb-af9f-4e3a-9734-082c29c9641f	2025	0.00	0.00	6659999.00	0.00	6659999.00	34780001.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-0fd2-4f21-affd-364d76235684	993c08e7-1142-433b-93c1-61aad85798f2	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-10a5-4e15-a5e2-84e34e5a739a	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1153-467d-8267-9319c5113388	d2dac9ea-3c11-4698-8628-9c3412693fe6	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1203-47c5-bc2a-f1eda9e61f57	fc890cda-3a6a-436b-8aee-2b1e22131cfd	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-12d2-4671-b5c5-343fb037bd42	091d5401-cef6-40f8-8778-87389d39e51f	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-13fa-480e-9c21-0235ce51a63a	1bb9f4ba-525d-4390-800d-140404e63991	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-150e-4c4d-a6ac-f1fdab1e4a13	2f8f647c-1936-4b32-93f7-9ebbcda6d039	2025	0.00	0.00	1612125.00	0.00	1612125.00	25256625.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-15b3-427e-a3a1-e145e198a4ed	ebd82a70-8c97-4dad-80e0-7ece07478479	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1648-4bff-8114-5541359b4784	e971913d-0f93-4a70-85eb-c0ed12a172d8	2025	155177425.12	0.00	74485164.00	0.00	217248386.00	148970328.12	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-16f6-408f-a79b-ea902e7f92d9	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-179d-417b-b986-dcace18fba4c	a0a355f7-a358-4e92-bdbd-9b31808a868e	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1882-46ad-af8b-c52126acac60	f99028fd-3f94-4fc4-8635-13369d98711f	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1940-4e2a-b7d5-7e953f82315f	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1a46-492a-8f78-314ec4617d98	42b0073a-07f3-4dcc-b82c-e2851b626433	2025	0.00	0.00	0.00	0.00	154550000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1b8b-4746-997e-8aca4db98d95	3f0dafbf-7fd9-407b-b1a9-4141e6326797	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1cbe-4814-8683-fc051e15331b	9dbcc529-de27-4753-a772-90aa5f8c7894	2025	0.00	0.00	1612125.00	0.00	1612125.00	25256625.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1dad-47c8-964f-327f1e44bd4f	27ae456f-8d57-4820-a7e1-e478df363acf	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-1fd0-4015-a09a-75e82a8f078d	e31d30be-ccad-45b8-a337-70e5c00155e2	2025	0.00	0.00	160718750.00	0.00	160718750.00	1510756250.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-20d1-4b9e-be8d-5c62af1e5f8a	0192e4a7-0901-4db9-aa00-c192d6adaa37	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-21c6-4caf-a27f-8eb38e4b9e52	d81dabec-1c10-4269-9548-808f65039d63	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-228e-4df1-9511-f132b32e1805	3b16435d-b93f-4811-bf25-6d03a45cc6dc	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2346-4a81-9822-3f6202c76bd8	43f964a1-fc8a-42fe-85b7-af80de5688a7	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-23e8-45b8-b8c4-57595163c154	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	2025	0.00	0.00	1075310.00	0.00	1075310.00	10107938.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2490-4094-8317-6bb434eb8537	0c5094e1-9380-4a00-aef4-46048c2ec697	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2523-405c-b5e7-db8c783de6e2	c7f80482-89d8-4f80-975d-34a752e992aa	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-269e-4640-81d4-d859cc6d49c6	5373b97d-245d-4889-9018-20958b798c17	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2748-427e-872f-df61a68483ba	c88e2c69-914f-403e-ab36-0a9322d6591f	2025	0.00	0.00	0.00	0.00	25520000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-27f8-4d62-ae22-506741d0b879	e1b3ed82-00ea-485e-b741-070c71fe1d2c	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2897-4f57-bcf1-b5a4ff00a51f	49fe0c73-3650-4c46-b8b2-28b11191c8fb	2025	6472410.00	0.00	2284380.00	0.00	14086970.00	6282045.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2932-41a3-a4e6-753026b6914b	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	2025	0.00	0.00	0.00	0.00	28240000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-29eb-4815-8a21-5fbe3d063439	31a57d16-cb30-4e53-8e7d-3ee074f5770b	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2abf-4722-92d7-9d8343d79d0e	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2b91-48be-83b1-8e4af7549f22	47665328-ff67-40a5-aac0-24572afbdcf8	2025	0.00	0.00	1612125.00	0.00	1612125.00	25256625.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2c23-4781-8d83-84096eb6e374	df697add-1313-4c55-957d-e53f28e5b499	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2cbc-4d09-9a44-7855ea665c7e	5450ed79-c9ee-45ac-abd3-d657d1a8897c	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2d4c-4fe8-bd49-b82dd64fa734	75c6a8ad-7be8-47cb-9165-89d42bb233c7	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2e11-46e2-ad09-e31c0088b8be	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2f29-4084-a455-75f4e9b9670c	d71ea253-d0e4-42f4-861d-a743fd7a8900	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-2fe1-43f2-aa5b-300e309b24d9	899b064e-2a20-489f-b713-56a3a1bcaf20	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-306d-4cb6-8045-88dde0db4bff	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3143-48f6-bea7-e5d0a5d84e7f	30a9ed88-3599-4d7f-8456-cce980762f96	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3212-42b7-8d48-9282d97e84b3	d30045d9-6179-4162-b8dc-e8d16ce29802	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-33a5-4e49-afc7-bcbc83d0c6e9	d403907f-306d-4dfb-8ca4-a950b548394d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-34be-41e8-be79-7541386aa3f9	4ee48863-fa9b-4ff3-9c00-2304ada83c29	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3580-49a3-ba36-eb748f63f69a	80be2e71-1ead-4023-bd82-148c11e82d2f	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3683-4d78-9af2-1a1270c622ea	e00e59a1-0f30-4ea7-8094-3956711ff682	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-37bd-42bc-8236-587505fe2b39	34453391-14df-41b0-8475-2d31c5371f29	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-389f-40c7-899f-8f63b728d197	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-39af-4bba-9960-93e1a4d06157	c9edde02-2af4-43a8-8e4c-6a02c17357b9	2025	0.00	0.00	0.00	0.00	82500000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3a8e-4869-a2d3-ff19c98f47e9	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3b91-4c86-a8a3-5f92b4517d2a	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3c9f-463d-8bf6-73727ad69aa5	99970f15-9c4a-4d4f-b550-a7ef488054d0	2025	8304428.00	0.00	8304428.00	0.00	398612500.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3daa-46f7-94c7-3d01d7fdd7cb	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3ec8-47fa-9988-928d6b97ed13	06b7d765-707f-4860-a0e7-3e520d4c1578	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-3fae-4f37-9593-ddc44e703dd9	593b6e25-ee8c-4702-b04e-b8675711696b	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4081-401a-8eec-85a322b55fe4	659ca9a0-f2de-4890-86ed-ef404f8d93fd	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4195-4913-8c2d-681631fbde24	52d9a146-b1cf-4110-b89d-be03c22a6e0e	2025	0.00	0.00	0.00	0.00	46750000.00	0.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4292-4a56-8ddb-7f4a4cdb782f	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-437c-477b-821a-5c356f6aa730	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	2025	0.00	0.00	583905.00	0.00	583905.00	5488719.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4443-4513-8872-1374bb8af4a3	1a2dda94-1f32-444a-a4dd-310edef0d76d	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4516-4dcc-8b92-d3836a01eae2	abbaf21e-07b0-4097-889a-094bfeda26ef	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-479f-4986-93de-475fdf54adb9	8e4323ee-5954-4946-b50e-252f098ee44e	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-48f4-4c38-a425-6864b1a0d58b	361667bd-377a-46f0-83ea-bdce1a20b6ad	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4a01-4cc6-977a-fcfe7a34921e	f875c2ca-1800-433b-b0a4-2d4d31ba308e	2025	52691518645.00	0.00	2107660740.00	0.00	10713942095.00	52515880250.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4be5-4c92-b7b0-eb6e15b8a335	b6368e98-7f87-42db-8b28-4084b11a0972	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4ce7-4e7c-ae82-431772eb4ffa	4852ac97-baee-4c1e-8b48-7e0fd276ec48	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4db0-49fd-8428-3a7ade62d62a	747d2923-ba5d-475d-a784-e41bc58e5561	2025	0.00	0.00	1612125.00	0.00	1612125.00	25256625.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4e90-45b9-b0ad-831281358191	4517635a-b083-4bba-bbba-22c060cff5b6	2025	0.00	0.00	153624.00	0.00	153624.00	7373952.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-4f72-4fe1-9d03-5d7f9763f5a5	620120f2-1730-4c93-b033-954f79d02e56	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-502f-4a04-9a34-6289d19d1829	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-514a-4ebc-b153-c35996bd6162	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-5315-44ce-a604-dd5e0bb1d5e8	34cb37c8-43d8-42bf-8be8-3622219b1fd2	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-53cd-4418-99d8-4d3bc7611e84	ba105ad8-72ad-40f6-8634-03d1e712b9af	2025	0.00	0.00	6451875.00	0.00	6451875.00	33693125.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-5458-437e-8a5a-6cebc3d7173e	de204b49-049f-4e74-9fad-76680c0ec640	2025	0.00	0.00	0.00	0.00	0.00	69375000.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-5524-403b-b9d3-7cb5d791c563	67bd771b-1a68-403b-b081-4727a5b09bbe	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-5629-4882-95b0-d3f5667957ca	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	2025	0.00	0.00	404687.00	0.00	404687.00	19425000.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-577a-47d9-b5b3-ed8573b9e02f	a11862d4-69a5-4d2b-a426-57a89de1b13c	2025	0.00	0.00	166056028.00	0.00	166056028.00	975579185.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-583a-4480-ac88-9cab0dc77e1b	da02d35f-1531-49f7-89f3-9c9fed5f9553	2025	0.00	0.00	2555552.00	0.00	2555552.00	22361112.00	2025-12-24 11:32:54	2025-12-24 11:32:54
a0aab723-5949-4b11-b827-2ed256789e8e	101fda0f-877a-4290-9df5-00a84859c3e9	2025	11789083.52	0.00	4563516.00	0.00	29282560.00	11408790.52	2025-12-24 11:32:54	2025-12-24 11:32:54
\.


--
-- Data for Name: assets_disposals; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_disposals (uuid, asset_uuid, disposal_code, target_status, kode_status, note, file_path, pic_request_uid, pic_approve_uid, created_at, updated_at, deleted_at, file_name, file_mime, file_size, before_status, flow, flow_file_path, flow_file_name, flow_file_mime, flow_file_size, ba_file_path, ba_file_name, ba_file_mime, ba_file_size, reason) FROM stdin;
7eb27eba-6a24-4b1a-97d9-e3e613c6b035	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	OPN25120001	DIS	ACC	testing use case	disposals/a48628da-4c0c-4ffa-9a04-cf89ea2d1b17/OPN25120001-20251222201535-c1o57h.jpeg	Asset Management Head	Asset Management Head	2025-12-22 20:15:35	2025-12-22 20:15:35	\N	WhatsApp Image 2025-12-22 at 18.04.52.jpeg	image/jpeg	183991	OPE	\N	disposals/a48628da-4c0c-4ffa-9a04-cf89ea2d1b17/OPN25120001-20251222201535-qyckln.xlsx	Form_Disposal_Preview_J3100000001-01.xlsx	application/vnd.openxmlformats-officedocument.spreadsheetml.sheet	285914	disposals/a48628da-4c0c-4ffa-9a04-cf89ea2d1b17/OPN25120001-20251222201535-PwwQG7.docx	BA_Disposal_Preview_J3100000001-01.docx	application/vnd.openxmlformats-officedocument.wordprocessingml.document	18644	Waste
407eb089-a1c4-4b64-b023-0b78ef30a722	49fe0c73-3650-4c46-b8b2-28b11191c8fb	DSP25120001	DIS	REJ	test uat	disposals/49fe0c73-3650-4c46-b8b2-28b11191c8fb/DSP25120001-20251223161333-s7cdOl.jpg	Department User	Department Head	2025-12-23 16:13:33	2025-12-23 16:57:49	\N	9.Motor Elektrik Viar New Q1 Tahun 2019 (3 Putih, 3 Merah).jpg	image/jpeg	1537354	OPE	{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-12-23 16:13:33", "approved_by": "Department User"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": null, "approved_by": null}, {"code": "am_head", "role": "Asset Management Head", "label": "Approval Asset Management Head", "approved_at": null, "approved_by": null}, {"code": "akp_head", "role": "Dept.Head AKP", "label": "Approval Accounting", "approved_at": null, "approved_by": null}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": null, "approved_by": null}]}	\N	\N	\N	\N	\N	\N	\N	\N	Waste
ad236063-db12-45c9-aeb7-03bfa265b41b	a11862d4-69a5-4d2b-a426-57a89de1b13c	DSP25120002	DIS	APR	\N	\N	Administrator	\N	2025-12-29 16:31:58	2025-12-29 16:31:58	\N	\N	\N	\N	OPE	{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-12-29 16:31:58", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": null, "approved_by": null}, {"code": "am_head", "role": "Asset Management Head", "label": "Approval Asset Management Head", "approved_at": null, "approved_by": null}, {"code": "akp_head", "role": "Dept.Head AKP", "label": "Approval Accounting", "approved_at": null, "approved_by": null}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": null, "approved_by": null}]}	\N	\N	\N	\N	\N	\N	\N	\N	Waste
\.


--
-- Data for Name: assets_document; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_document (asset_uuid, no_po_perjanjian_spk, nota_referensi, no_document, created_at, updated_at, deleted_at) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	\N	PPA-1909-00364	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c9edde02-2af4-43a8-8e4c-6a02c17357b9	\N	PPA-2006-00332	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
42b0073a-07f3-4dcc-b82c-e2851b626433	\N	PPA-2006-00332	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9beb94c2-f47d-4b48-9281-54ec00cf0758	\N	PPA-2008-00452	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
c88e2c69-914f-403e-ab36-0a9322d6591f	\N	PPA-2010-00554	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9580ea1b-0f93-4c89-b167-a089131d5761	\N	PPA-2010-00554	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	\N	PPA-2010-00554	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	\N	AKTA INBRENG	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
1c4a40c1-aeb5-4287-a4b1-383d158920e5	\N	AKTA INBRENG	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
f875c2ca-1800-433b-b0a4-2d4d31ba308e	\N	AKTA INBRENG	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
54ec2fba-0b2b-4783-ab74-464ba53d2e07	\N	PPA-2012-00678, PPA-2102-00137	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	\N	PPA-2012-00715	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
52d9a146-b1cf-4110-b89d-be03c22a6e0e	\N	AUDIT	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
99970f15-9c4a-4d4f-b550-a7ef488054d0	\N	AUDIT	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
e971913d-0f93-4a70-85eb-c0ed12a172d8	\N	PPA-2205-00342 ; PPA-2208-00719;  PPA-2212-01521	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
101fda0f-877a-4290-9df5-00a84859c3e9	\N	PPA-1907-00285	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
6504929e-7f0b-47a6-b6d6-25032344b55f	\N	PPA-1907-00285	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
19c63207-1947-4bb3-9193-554042ba6da7	\N	PPA-1907-00285	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
03e94a29-9883-46a5-9294-21d22f2fba7f	\N	PPA-1907-00285	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
49fe0c73-3650-4c46-b8b2-28b11191c8fb	\N	0	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
36e92940-a131-4ac0-b45b-b8500ff4b040	\N	PPA-25-00752;PPA-25-01029	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
a11862d4-69a5-4d2b-a426-57a89de1b13c	\N	PPA-25-00190	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
e47f3b62-82ae-4322-8660-bf104df108a5	PO-2506-00004	PT TIGAPUTRA GEMILANG JAYA	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	PO-2506-00004	PT TIGAPUTRA GEMILANG JAYA	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
80acf346-539e-4c9a-aed0-9ff88df294f5	PO-2510-00023	PT VIEIRINDO SASEIKO	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
4517635a-b083-4bba-bbba-22c060cff5b6	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
bc1fdef0-b3ba-4655-867f-8038f2a0c04f	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
5450ed79-c9ee-45ac-abd3-d657d1a8897c	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
50a845bf-b203-4b10-b292-fda3c7b5ac6e	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
de6479e8-c9c2-41c1-9ad6-c74439bc986f	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
c7f80482-89d8-4f80-975d-34a752e992aa	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
8e4323ee-5954-4946-b50e-252f098ee44e	PO-2511-00004	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
0023be09-5f8c-4f86-9a6f-78cdd74e63a7	PO-2508-00037	PT WIRAPANDU SUKSES MAKMUR	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	PO-2412-00023	PT MONOTARO INDONESIA	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
f743b734-490e-470d-bc30-19e730a855b2	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ac204bbb-af9f-4e3a-9734-082c29c9641f	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
31a57d16-cb30-4e53-8e7d-3ee074f5770b	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
30a9ed88-3599-4d7f-8456-cce980762f96	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
4ee48863-fa9b-4ff3-9c00-2304ada83c29	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
34453391-14df-41b0-8475-2d31c5371f29	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
edddbe54-8ed9-496c-88d9-1a96279445c6	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
e8925ef1-66f5-432d-92c3-c37b79062eef	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
a1906f9d-e1c1-4072-99c4-51cba2577d90	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
5e69818f-651f-4e8b-8a69-513fa0a773db	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
ba105ad8-72ad-40f6-8634-03d1e712b9af	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
fc890cda-3a6a-436b-8aee-2b1e22131cfd	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
62fcc371-d6de-4ef0-88ef-413b40c6783d	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3bd5cf1d-ae87-4735-b753-1f810b177052	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
cddab2cb-f430-4819-b9cf-c35a54b156cd	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
4852ac97-baee-4c1e-8b48-7e0fd276ec48	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3b16435d-b93f-4811-bf25-6d03a45cc6dc	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
c521f578-f2c7-446d-b351-9b47fdb59913	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
836f58bc-d2d9-4543-bc82-7859db2da9be	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
6630f300-223a-4694-a3b5-28193c508cba	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
75c6a8ad-7be8-47cb-9165-89d42bb233c7	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	PO-2411-00034	PPA-25-00081	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
de204b49-049f-4e74-9fad-76680c0ec640	001A/SCM/114/XII/2025	PT LEAP NETWORKS INDONESIA	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	PO-2506-00004	PT TIGAPUTRA GEMILANG JAYA	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f613464f-be5b-4c3d-9ff5-8ff2793f9d05	PO-2506-00004	PT TIGAPUTRA GEMILANG JAYA	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e31d30be-ccad-45b8-a337-70e5c00155e2	PO-2411-00025	Kreatif Dinamika Integrasi PT	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
\.


--
-- Data for Name: assets_identifiers; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_identifiers (asset_uuid, asset_number_maximo, asset_number_dynamic_365, asset_number_internal, created_at, updated_at, deleted_at, alias) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	\N	MPS1909000001	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
c9edde02-2af4-43a8-8e4c-6a02c17357b9	\N	MPS2007000001	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
42b0073a-07f3-4dcc-b82c-e2851b626433	\N	MPS2007000002	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
9beb94c2-f47d-4b48-9281-54ec00cf0758	\N	MPS2009000001	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
c88e2c69-914f-403e-ab36-0a9322d6591f	\N	MPS2010000001	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
9580ea1b-0f93-4c89-b167-a089131d5761	\N	MPS2010000001	\N	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	\N
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	\N	MPS2010000001	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	K1-1-18-107-108/LRV/1106A-1106B	LRV20110000001	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N
1c4a40c1-aeb5-4287-a4b1-383d158920e5	K1-1-18-109-110/LRV/1107A-1107B	LRV20110000001	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N
f875c2ca-1800-433b-b0a4-2d4d31ba308e	K1-1-18-111-112/LRV/1108A-1108B	LRV20110000001	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N
54ec2fba-0b2b-4783-ab74-464ba53d2e07	\N	APS20120000001	\N	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	\N
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	\N	APS20120000002	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
52d9a146-b1cf-4110-b89d-be03c22a6e0e	\N	APS20120000003	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
99970f15-9c4a-4d4f-b550-a7ef488054d0	\N	APS21020000004	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
e971913d-0f93-4a70-85eb-c0ed12a172d8	\N	APS22120000001	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
101fda0f-877a-4290-9df5-00a84859c3e9	\N	MCN1907000001	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
6504929e-7f0b-47a6-b6d6-25032344b55f	\N	MCN1907000001	\N	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	\N
19c63207-1947-4bb3-9193-554042ba6da7	\N	MCN1907000002	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N
03e94a29-9883-46a5-9294-21d22f2fba7f	\N	MCN1907000002	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N
49fe0c73-3650-4c46-b8b2-28b11191c8fb	\N	MCN1909000001	\N	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	\N
36e92940-a131-4ac0-b45b-b8500ff4b040	\N	BLD25080000001	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N
a11862d4-69a5-4d2b-a426-57a89de1b13c	\N	CEO2503000001	\N	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	\N
f743b734-490e-470d-bc30-19e730a855b2	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
ac204bbb-af9f-4e3a-9734-082c29c9641f	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
31a57d16-cb30-4e53-8e7d-3ee074f5770b	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
30a9ed88-3599-4d7f-8456-cce980762f96	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
4ee48863-fa9b-4ff3-9c00-2304ada83c29	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
34453391-14df-41b0-8475-2d31c5371f29	\N	CEO2502000001	\N	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	\N
edddbe54-8ed9-496c-88d9-1a96279445c6	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
e8925ef1-66f5-432d-92c3-c37b79062eef	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
a1906f9d-e1c1-4072-99c4-51cba2577d90	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
5e69818f-651f-4e8b-8a69-513fa0a773db	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
ba105ad8-72ad-40f6-8634-03d1e712b9af	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
fc890cda-3a6a-436b-8aee-2b1e22131cfd	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
62fcc371-d6de-4ef0-88ef-413b40c6783d	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
3bd5cf1d-ae87-4735-b753-1f810b177052	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
cddab2cb-f430-4819-b9cf-c35a54b156cd	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
4852ac97-baee-4c1e-8b48-7e0fd276ec48	\N	CEO2502000001	\N	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	\N
3b16435d-b93f-4811-bf25-6d03a45cc6dc	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
c521f578-f2c7-446d-b351-9b47fdb59913	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
836f58bc-d2d9-4543-bc82-7859db2da9be	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
6630f300-223a-4694-a3b5-28193c508cba	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
75c6a8ad-7be8-47cb-9165-89d42bb233c7	\N	CEO2502000001	\N	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	\N
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	\N	CEO2502000001	\N	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	\N
\.


--
-- Data for Name: assets_qr; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_qr (uuid, asset_uuid, qr_data, image_path, is_active, generated_at, created_at, updated_at, deleted_at) FROM stdin;
82113872-a6a4-4c40-849d-6d33d06b9599	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	57f05f2c-a4fb-4667-9289-5a6b92dc1a21	qrcodes/57f05f2c-a4fb-4667-9289-5a6b92dc1a21.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9a4261ab-b5c7-400d-b21c-047acf41965e	c9edde02-2af4-43a8-8e4c-6a02c17357b9	c9edde02-2af4-43a8-8e4c-6a02c17357b9	qrcodes/c9edde02-2af4-43a8-8e4c-6a02c17357b9.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
9b1a4ca3-f09b-4842-abfd-ebdf1e91d2b0	42b0073a-07f3-4dcc-b82c-e2851b626433	42b0073a-07f3-4dcc-b82c-e2851b626433	qrcodes/42b0073a-07f3-4dcc-b82c-e2851b626433.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
ac4bd1ce-0650-49ae-82fb-fa455dd05aed	9beb94c2-f47d-4b48-9281-54ec00cf0758	9beb94c2-f47d-4b48-9281-54ec00cf0758	qrcodes/9beb94c2-f47d-4b48-9281-54ec00cf0758.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
2a0a24a6-cb56-4584-a68e-8a77e9255d4c	c88e2c69-914f-403e-ab36-0a9322d6591f	c88e2c69-914f-403e-ab36-0a9322d6591f	qrcodes/c88e2c69-914f-403e-ab36-0a9322d6591f.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
8388960a-36c7-4665-aca5-b2e4972a36d7	9580ea1b-0f93-4c89-b167-a089131d5761	9580ea1b-0f93-4c89-b167-a089131d5761	qrcodes/9580ea1b-0f93-4c89-b167-a089131d5761.svg	t	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N
777b449d-6f1c-4c8e-9186-13ede6a9403c	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	qrcodes/80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8.svg	t	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
e88f3a96-4b81-49e2-a3fc-ec149a68288b	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	qrcodes/b1d1a3b4-6b0e-448c-8d59-f582b9d106f3.svg	t	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
5ddc3a95-e7b4-4aa2-8a85-0d171f16e889	1c4a40c1-aeb5-4287-a4b1-383d158920e5	1c4a40c1-aeb5-4287-a4b1-383d158920e5	qrcodes/1c4a40c1-aeb5-4287-a4b1-383d158920e5.svg	t	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
d6320272-fffd-49d1-b6c4-79d7b168a5ba	f875c2ca-1800-433b-b0a4-2d4d31ba308e	f875c2ca-1800-433b-b0a4-2d4d31ba308e	qrcodes/f875c2ca-1800-433b-b0a4-2d4d31ba308e.svg	t	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
63b2a005-5171-4b6b-8e85-8ef08829131b	54ec2fba-0b2b-4783-ab74-464ba53d2e07	54ec2fba-0b2b-4783-ab74-464ba53d2e07	qrcodes/54ec2fba-0b2b-4783-ab74-464ba53d2e07.svg	t	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N
782238a6-c0bd-4f17-ae30-af9a6576ce37	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	qrcodes/607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
bad88335-7efc-4a1d-9b3a-13b06f9d13c5	52d9a146-b1cf-4110-b89d-be03c22a6e0e	52d9a146-b1cf-4110-b89d-be03c22a6e0e	qrcodes/52d9a146-b1cf-4110-b89d-be03c22a6e0e.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
d8d4155e-0b70-4852-8beb-12ee468df9cf	99970f15-9c4a-4d4f-b550-a7ef488054d0	99970f15-9c4a-4d4f-b550-a7ef488054d0	qrcodes/99970f15-9c4a-4d4f-b550-a7ef488054d0.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
19ceb9a4-b058-4080-b0af-016a6046f6e0	e971913d-0f93-4a70-85eb-c0ed12a172d8	e971913d-0f93-4a70-85eb-c0ed12a172d8	qrcodes/e971913d-0f93-4a70-85eb-c0ed12a172d8.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
4e1bb675-95e0-47cc-bc9f-d6d509999964	101fda0f-877a-4290-9df5-00a84859c3e9	101fda0f-877a-4290-9df5-00a84859c3e9	qrcodes/101fda0f-877a-4290-9df5-00a84859c3e9.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
a8d6a53c-c679-496f-8e68-ff8757d37d90	6504929e-7f0b-47a6-b6d6-25032344b55f	6504929e-7f0b-47a6-b6d6-25032344b55f	qrcodes/6504929e-7f0b-47a6-b6d6-25032344b55f.svg	t	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N
2cf946ca-2375-43fa-a240-6163010bc0b1	19c63207-1947-4bb3-9193-554042ba6da7	19c63207-1947-4bb3-9193-554042ba6da7	qrcodes/19c63207-1947-4bb3-9193-554042ba6da7.svg	t	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
7ac35c9b-6a6b-4b31-a141-29e42e913b3a	03e94a29-9883-46a5-9294-21d22f2fba7f	03e94a29-9883-46a5-9294-21d22f2fba7f	qrcodes/03e94a29-9883-46a5-9294-21d22f2fba7f.svg	t	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
0f305ce8-b89b-4d77-92c8-eb5c725a3739	49fe0c73-3650-4c46-b8b2-28b11191c8fb	49fe0c73-3650-4c46-b8b2-28b11191c8fb	qrcodes/49fe0c73-3650-4c46-b8b2-28b11191c8fb.svg	t	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N
6081ab0a-d274-4df3-9fd8-7cd771fae104	36e92940-a131-4ac0-b45b-b8500ff4b040	36e92940-a131-4ac0-b45b-b8500ff4b040	qrcodes/36e92940-a131-4ac0-b45b-b8500ff4b040.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
bbabecd8-2ac6-4d93-a47e-fc950a666a12	a11862d4-69a5-4d2b-a426-57a89de1b13c	a11862d4-69a5-4d2b-a426-57a89de1b13c	qrcodes/a11862d4-69a5-4d2b-a426-57a89de1b13c.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
986c6522-ac37-4e73-b5d9-2bd012884851	e47f3b62-82ae-4322-8660-bf104df108a5	e47f3b62-82ae-4322-8660-bf104df108a5	qrcodes/e47f3b62-82ae-4322-8660-bf104df108a5.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
2d7726da-0873-4ad3-87f7-1156fdaa8312	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	qrcodes/3627bbbb-fa2f-4fc8-9dc4-79c094cfab38.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
45d566fd-05f4-499b-bf2f-ab3104117e23	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	qrcodes/a48628da-4c0c-4ffa-9a04-cf89ea2d1b17.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
5a69f6ee-5ded-4626-82e2-f3a2c81a5e82	80acf346-539e-4c9a-aed0-9ff88df294f5	80acf346-539e-4c9a-aed0-9ff88df294f5	qrcodes/80acf346-539e-4c9a-aed0-9ff88df294f5.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
9d289928-2c78-4d20-b3bd-2ffbb227e2ab	4517635a-b083-4bba-bbba-22c060cff5b6	4517635a-b083-4bba-bbba-22c060cff5b6	qrcodes/4517635a-b083-4bba-bbba-22c060cff5b6.svg	t	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N
b12f7d66-4540-41db-a957-c5038b9c81f2	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	bc1fdef0-b3ba-4655-867f-8038f2a0c04f	qrcodes/bc1fdef0-b3ba-4655-867f-8038f2a0c04f.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
b007fff4-463f-4322-87df-1fa59ff46e0f	5450ed79-c9ee-45ac-abd3-d657d1a8897c	5450ed79-c9ee-45ac-abd3-d657d1a8897c	qrcodes/5450ed79-c9ee-45ac-abd3-d657d1a8897c.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
7ccbb59a-0442-4a8c-9162-263f397eedc1	50a845bf-b203-4b10-b292-fda3c7b5ac6e	50a845bf-b203-4b10-b292-fda3c7b5ac6e	qrcodes/50a845bf-b203-4b10-b292-fda3c7b5ac6e.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
2ebb8160-43be-4f5a-9451-1927ff69446e	de6479e8-c9c2-41c1-9ad6-c74439bc986f	de6479e8-c9c2-41c1-9ad6-c74439bc986f	qrcodes/de6479e8-c9c2-41c1-9ad6-c74439bc986f.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
f67a4c29-2ab6-4fa2-a2b9-74770d263bf7	c7f80482-89d8-4f80-975d-34a752e992aa	c7f80482-89d8-4f80-975d-34a752e992aa	qrcodes/c7f80482-89d8-4f80-975d-34a752e992aa.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
d08acb8d-4497-4bb1-bbe5-2c7fa9cdb865	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	qrcodes/e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
70c61be9-1a22-41f9-b980-1ada73548e76	8e4323ee-5954-4946-b50e-252f098ee44e	8e4323ee-5954-4946-b50e-252f098ee44e	qrcodes/8e4323ee-5954-4946-b50e-252f098ee44e.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
8a1461bd-c77a-444f-82cb-58933bf976a6	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	0023be09-5f8c-4f86-9a6f-78cdd74e63a7	qrcodes/0023be09-5f8c-4f86-9a6f-78cdd74e63a7.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
174f6a01-e7b7-4c14-a914-7803f9cbf568	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	qrcodes/fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
b9199566-cc4c-48ab-b981-85f8229c5145	9dbcc529-de27-4753-a772-90aa5f8c7894	9dbcc529-de27-4753-a772-90aa5f8c7894	qrcodes/9dbcc529-de27-4753-a772-90aa5f8c7894.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
018c309d-50de-471a-81c5-ba34c929a9c9	47665328-ff67-40a5-aac0-24572afbdcf8	47665328-ff67-40a5-aac0-24572afbdcf8	qrcodes/47665328-ff67-40a5-aac0-24572afbdcf8.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
7fb868a5-1652-4927-bcf8-f8361276207d	747d2923-ba5d-475d-a784-e41bc58e5561	747d2923-ba5d-475d-a784-e41bc58e5561	qrcodes/747d2923-ba5d-475d-a784-e41bc58e5561.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
2d623be9-8b49-44d0-9eee-e36b44b9c5f9	2f8f647c-1936-4b32-93f7-9ebbcda6d039	2f8f647c-1936-4b32-93f7-9ebbcda6d039	qrcodes/2f8f647c-1936-4b32-93f7-9ebbcda6d039.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
090cfed6-fe10-457a-b88a-3bc9c265b338	f743b734-490e-470d-bc30-19e730a855b2	f743b734-490e-470d-bc30-19e730a855b2	qrcodes/f743b734-490e-470d-bc30-19e730a855b2.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
36931f4b-2512-4f82-81bc-042c497222b7	ac204bbb-af9f-4e3a-9734-082c29c9641f	ac204bbb-af9f-4e3a-9734-082c29c9641f	qrcodes/ac204bbb-af9f-4e3a-9734-082c29c9641f.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
9e73a195-203d-48d8-be95-fb51d0557bab	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	qrcodes/ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
14969a22-c01a-4ff9-893e-a0349429e926	31a57d16-cb30-4e53-8e7d-3ee074f5770b	31a57d16-cb30-4e53-8e7d-3ee074f5770b	qrcodes/31a57d16-cb30-4e53-8e7d-3ee074f5770b.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
ce95c9c5-7ceb-446e-b213-d5bf2beed44b	30a9ed88-3599-4d7f-8456-cce980762f96	30a9ed88-3599-4d7f-8456-cce980762f96	qrcodes/30a9ed88-3599-4d7f-8456-cce980762f96.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
50e39fcb-d6ea-427a-a291-048ebbd7644b	4ee48863-fa9b-4ff3-9c00-2304ada83c29	4ee48863-fa9b-4ff3-9c00-2304ada83c29	qrcodes/4ee48863-fa9b-4ff3-9c00-2304ada83c29.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
bbcd207a-0384-4813-b462-7348c8e78d3c	34453391-14df-41b0-8475-2d31c5371f29	34453391-14df-41b0-8475-2d31c5371f29	qrcodes/34453391-14df-41b0-8475-2d31c5371f29.svg	t	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N
b107b347-cd0e-4f6a-84b7-035b05f77234	edddbe54-8ed9-496c-88d9-1a96279445c6	edddbe54-8ed9-496c-88d9-1a96279445c6	qrcodes/edddbe54-8ed9-496c-88d9-1a96279445c6.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
f68f0dc6-c21b-4148-ac4c-9bdcb4188da0	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	qrcodes/bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
bcb2e4a0-23c7-4db3-94b4-25cfb415fd50	e8925ef1-66f5-432d-92c3-c37b79062eef	e8925ef1-66f5-432d-92c3-c37b79062eef	qrcodes/e8925ef1-66f5-432d-92c3-c37b79062eef.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
862216ef-3cbf-452f-b873-fce39825852d	a1906f9d-e1c1-4072-99c4-51cba2577d90	a1906f9d-e1c1-4072-99c4-51cba2577d90	qrcodes/a1906f9d-e1c1-4072-99c4-51cba2577d90.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
19ee6b30-2d45-4177-972b-73256474264f	5e69818f-651f-4e8b-8a69-513fa0a773db	5e69818f-651f-4e8b-8a69-513fa0a773db	qrcodes/5e69818f-651f-4e8b-8a69-513fa0a773db.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
6d8d6a52-5eff-4966-9b3f-6bc7af3806e6	ba105ad8-72ad-40f6-8634-03d1e712b9af	ba105ad8-72ad-40f6-8634-03d1e712b9af	qrcodes/ba105ad8-72ad-40f6-8634-03d1e712b9af.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
8d44766b-9171-462e-9a2f-5d3dde08871c	fc890cda-3a6a-436b-8aee-2b1e22131cfd	fc890cda-3a6a-436b-8aee-2b1e22131cfd	qrcodes/fc890cda-3a6a-436b-8aee-2b1e22131cfd.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
ff64d55e-8d9a-4980-830d-da00237e7f8a	62fcc371-d6de-4ef0-88ef-413b40c6783d	62fcc371-d6de-4ef0-88ef-413b40c6783d	qrcodes/62fcc371-d6de-4ef0-88ef-413b40c6783d.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
3065a2fd-e25e-4540-8f1c-00468cc5ced5	3bd5cf1d-ae87-4735-b753-1f810b177052	3bd5cf1d-ae87-4735-b753-1f810b177052	qrcodes/3bd5cf1d-ae87-4735-b753-1f810b177052.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
8795880e-cbc0-4840-b2e8-139aaee28f44	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	qrcodes/d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
42187bee-306a-4322-b5d4-aad6ff9b9197	cddab2cb-f430-4819-b9cf-c35a54b156cd	cddab2cb-f430-4819-b9cf-c35a54b156cd	qrcodes/cddab2cb-f430-4819-b9cf-c35a54b156cd.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
30cc4707-332a-4939-b190-d9ef86791dfd	4852ac97-baee-4c1e-8b48-7e0fd276ec48	4852ac97-baee-4c1e-8b48-7e0fd276ec48	qrcodes/4852ac97-baee-4c1e-8b48-7e0fd276ec48.svg	t	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N
0ff4293c-8b0a-4ca5-a52f-dc30ccb11160	3b16435d-b93f-4811-bf25-6d03a45cc6dc	3b16435d-b93f-4811-bf25-6d03a45cc6dc	qrcodes/3b16435d-b93f-4811-bf25-6d03a45cc6dc.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
7269e9d2-70fc-46d4-974a-2389d2640562	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	qrcodes/ceb2c4b5-2711-4a2d-944a-d37cf1f68e33.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
22560b54-21dc-4a40-b8c3-d00301e9b095	c521f578-f2c7-446d-b351-9b47fdb59913	c521f578-f2c7-446d-b351-9b47fdb59913	qrcodes/c521f578-f2c7-446d-b351-9b47fdb59913.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
50be5c52-4f47-41d5-97e3-261ab8e9a290	836f58bc-d2d9-4543-bc82-7859db2da9be	836f58bc-d2d9-4543-bc82-7859db2da9be	qrcodes/836f58bc-d2d9-4543-bc82-7859db2da9be.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
b3992bfe-30e3-4a56-b7a6-6bf3eefd73f0	6630f300-223a-4694-a3b5-28193c508cba	6630f300-223a-4694-a3b5-28193c508cba	qrcodes/6630f300-223a-4694-a3b5-28193c508cba.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
f383d1cc-cbc6-49eb-be33-bf4ae89c52a5	75c6a8ad-7be8-47cb-9165-89d42bb233c7	75c6a8ad-7be8-47cb-9165-89d42bb233c7	qrcodes/75c6a8ad-7be8-47cb-9165-89d42bb233c7.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
952c800f-a7cc-40b4-b189-782c5027ca27	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	6d3c3c19-3b28-4cab-9aa1-e700bdcef883	qrcodes/6d3c3c19-3b28-4cab-9aa1-e700bdcef883.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
01f347e6-8d8d-4a9a-a8a3-d315ba8715e5	896e640c-3b59-4bc8-aba1-5ac076e99c49	896e640c-3b59-4bc8-aba1-5ac076e99c49	qrcodes/896e640c-3b59-4bc8-aba1-5ac076e99c49.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
fa69ed37-5a1e-49ef-b98a-5bcc20789abe	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	qrcodes/eb65a09e-1f7c-4ba2-84a8-fdf9f530a146.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
ba692c3f-cfa8-4604-9689-11972b586448	bb12563d-78e3-4121-84df-edae5df20c63	bb12563d-78e3-4121-84df-edae5df20c63	qrcodes/bb12563d-78e3-4121-84df-edae5df20c63.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
40194eae-8d3e-4893-8656-1f3ec4ce7a18	da02d35f-1531-49f7-89f3-9c9fed5f9553	da02d35f-1531-49f7-89f3-9c9fed5f9553	qrcodes/da02d35f-1531-49f7-89f3-9c9fed5f9553.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
2008c4bd-8e5b-484e-924e-d7fa6c72e086	d30045d9-6179-4162-b8dc-e8d16ce29802	d30045d9-6179-4162-b8dc-e8d16ce29802	qrcodes/d30045d9-6179-4162-b8dc-e8d16ce29802.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
928c96b1-1e2c-41a6-856b-b73037d45415	46930604-8016-42a6-9329-ffdac3236bc1	46930604-8016-42a6-9329-ffdac3236bc1	qrcodes/46930604-8016-42a6-9329-ffdac3236bc1.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
cd0cbdbb-382a-439d-9ddb-a0033af1aa4b	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	fe41bf26-c9b0-406f-8000-7f9469e1fe7d	qrcodes/fe41bf26-c9b0-406f-8000-7f9469e1fe7d.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
dd4e2dab-5176-4020-b71c-60d42c086de5	538a6d2a-ec13-4d7c-87e7-f2e56d089780	538a6d2a-ec13-4d7c-87e7-f2e56d089780	qrcodes/538a6d2a-ec13-4d7c-87e7-f2e56d089780.svg	t	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N
19d16fff-5756-4739-a037-cca572dab91a	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	fdcd74dc-bb14-44bb-8ee0-c12839b31f44	qrcodes/fdcd74dc-bb14-44bb-8ee0-c12839b31f44.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
02467dec-cfcc-4e94-a2ba-62429c54ad14	1a2dda94-1f32-444a-a4dd-310edef0d76d	1a2dda94-1f32-444a-a4dd-310edef0d76d	qrcodes/1a2dda94-1f32-444a-a4dd-310edef0d76d.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
979cea08-5355-4c69-98d6-f4a37ab825e0	e3e63659-175c-4748-b571-d2224a256534	e3e63659-175c-4748-b571-d2224a256534	qrcodes/e3e63659-175c-4748-b571-d2224a256534.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
82aca41c-e4ba-43ed-93d5-ab7efbd87ccd	80be2e71-1ead-4023-bd82-148c11e82d2f	80be2e71-1ead-4023-bd82-148c11e82d2f	qrcodes/80be2e71-1ead-4023-bd82-148c11e82d2f.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0c088349-c5f0-4005-9ac4-6854e22c942f	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	60ab9154-7025-4b9a-93f7-d8c7f276cbc3	qrcodes/60ab9154-7025-4b9a-93f7-d8c7f276cbc3.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
ba7fe1f7-ac90-40c3-b7c7-b6be998405fc	0192e4a7-0901-4db9-aa00-c192d6adaa37	0192e4a7-0901-4db9-aa00-c192d6adaa37	qrcodes/0192e4a7-0901-4db9-aa00-c192d6adaa37.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
d77f01e6-6fd6-49e0-ba1a-0c0a4f440da9	9398fd93-f9b2-4639-8c65-51086cf62165	9398fd93-f9b2-4639-8c65-51086cf62165	qrcodes/9398fd93-f9b2-4639-8c65-51086cf62165.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
2a275b9a-22e1-4414-80e3-3899f49f5446	db95bb38-c227-48ec-ac5a-69d642ba910e	db95bb38-c227-48ec-ac5a-69d642ba910e	qrcodes/db95bb38-c227-48ec-ac5a-69d642ba910e.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
3ea43a9c-2c72-4138-922d-09b46aa7b7c5	e3222e82-d284-45f4-87c5-6ca46ea72fac	e3222e82-d284-45f4-87c5-6ca46ea72fac	qrcodes/e3222e82-d284-45f4-87c5-6ca46ea72fac.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
58017329-1e9f-4b3c-afd3-54bdece7543c	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	221f2223-7885-4f5d-9d6c-c3ac40c50f9e	qrcodes/221f2223-7885-4f5d-9d6c-c3ac40c50f9e.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
751a0d7a-73cf-4a03-a90d-6226f17498a6	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	3c8eab4b-ba11-42c3-bc67-9290d52a36f9	qrcodes/3c8eab4b-ba11-42c3-bc67-9290d52a36f9.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
1696bb6f-94e1-48ca-8c1d-da9b13fa1f29	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	f2f26243-a41c-42c5-b593-2fe4e12bc4aa	qrcodes/f2f26243-a41c-42c5-b593-2fe4e12bc4aa.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
0b4f31df-ab9f-4b56-906b-d30383a0cbca	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	5bd44432-06b9-41e2-a0c4-cd8e616f52c9	qrcodes/5bd44432-06b9-41e2-a0c4-cd8e616f52c9.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
1c10fb48-67d8-42a9-a3f5-22810bfb174b	abbaf21e-07b0-4097-889a-094bfeda26ef	abbaf21e-07b0-4097-889a-094bfeda26ef	qrcodes/abbaf21e-07b0-4097-889a-094bfeda26ef.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
f0cc526e-ee4f-4066-baa2-89588a2ac7dd	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	qrcodes/2251c4a6-eed4-46d4-aebd-a49d54f8b2cc.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
cd679826-0460-46c5-ab82-1eaa501340a1	66df45b6-5011-45a5-be1d-f140cc3e4b7d	66df45b6-5011-45a5-be1d-f140cc3e4b7d	qrcodes/66df45b6-5011-45a5-be1d-f140cc3e4b7d.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
c7a29a57-c6a3-417e-acdd-daf84d0cebc8	0c5094e1-9380-4a00-aef4-46048c2ec697	0c5094e1-9380-4a00-aef4-46048c2ec697	qrcodes/0c5094e1-9380-4a00-aef4-46048c2ec697.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
264ced2d-1655-4b61-b702-0d0de2c696ed	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	qrcodes/5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
67ce827c-4b45-455c-84d0-6b1469f1e7a0	d403907f-306d-4dfb-8ca4-a950b548394d	d403907f-306d-4dfb-8ca4-a950b548394d	qrcodes/d403907f-306d-4dfb-8ca4-a950b548394d.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
7f699fd2-5448-4174-963a-b21796040b83	3998992b-b5bf-4d03-9cd7-526c45df750c	3998992b-b5bf-4d03-9cd7-526c45df750c	qrcodes/3998992b-b5bf-4d03-9cd7-526c45df750c.svg	t	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N
50ba7af8-132d-4b43-94c3-e4133d70caa2	b6368e98-7f87-42db-8b28-4084b11a0972	b6368e98-7f87-42db-8b28-4084b11a0972	qrcodes/b6368e98-7f87-42db-8b28-4084b11a0972.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
9c5e380c-b331-4171-9925-a3b6fce40807	82ef3eb4-8b79-47e0-915e-7276ea7bd578	82ef3eb4-8b79-47e0-915e-7276ea7bd578	qrcodes/82ef3eb4-8b79-47e0-915e-7276ea7bd578.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
e81c9433-cce7-4f36-8f8c-c098a96f5bb5	899b064e-2a20-489f-b713-56a3a1bcaf20	899b064e-2a20-489f-b713-56a3a1bcaf20	qrcodes/899b064e-2a20-489f-b713-56a3a1bcaf20.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
869b24bf-47ee-40b9-aa1f-8085f7b2780a	3f0dafbf-7fd9-407b-b1a9-4141e6326797	3f0dafbf-7fd9-407b-b1a9-4141e6326797	qrcodes/3f0dafbf-7fd9-407b-b1a9-4141e6326797.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
b8789f0f-8b06-48bd-aeb7-37244427a5fb	659ca9a0-f2de-4890-86ed-ef404f8d93fd	659ca9a0-f2de-4890-86ed-ef404f8d93fd	qrcodes/659ca9a0-f2de-4890-86ed-ef404f8d93fd.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
992dde31-3a22-4e07-b69d-a4882145c120	67bd771b-1a68-403b-b081-4727a5b09bbe	67bd771b-1a68-403b-b081-4727a5b09bbe	qrcodes/67bd771b-1a68-403b-b081-4727a5b09bbe.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
cf04195d-e8f5-4e6c-9967-b24972188e37	45925fe4-a66c-4e4c-92e4-81f818fd71c8	45925fe4-a66c-4e4c-92e4-81f818fd71c8	qrcodes/45925fe4-a66c-4e4c-92e4-81f818fd71c8.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
714859cc-3269-4876-ba7f-cab6925f8781	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	qrcodes/2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
89ad4ec4-79a6-4a23-8731-df1b5b0723bd	9bb4b946-4d3b-427a-914c-accbaf7c362d	9bb4b946-4d3b-427a-914c-accbaf7c362d	qrcodes/9bb4b946-4d3b-427a-914c-accbaf7c362d.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
88af4d28-db74-4573-a193-f0e0835196cb	e74afc6c-038f-4875-a703-89b52e09ee91	e74afc6c-038f-4875-a703-89b52e09ee91	qrcodes/e74afc6c-038f-4875-a703-89b52e09ee91.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
a0517036-d607-4cb3-b04f-0d79c876f5bf	43f964a1-fc8a-42fe-85b7-af80de5688a7	43f964a1-fc8a-42fe-85b7-af80de5688a7	qrcodes/43f964a1-fc8a-42fe-85b7-af80de5688a7.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
65c59eb6-9134-4660-b2da-a97c80a1c0c1	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	qrcodes/a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
f23e6bf7-aac9-47ba-a45d-bcc1a3d4029b	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	a267ecca-f8a6-4fde-8bfb-eaba58162ba2	qrcodes/a267ecca-f8a6-4fde-8bfb-eaba58162ba2.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
312336a1-eeaf-481f-83df-f7ff570180ef	06b7d765-707f-4860-a0e7-3e520d4c1578	06b7d765-707f-4860-a0e7-3e520d4c1578	qrcodes/06b7d765-707f-4860-a0e7-3e520d4c1578.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
466eaf14-70b6-4cf1-8c74-721bca67165a	d81dabec-1c10-4269-9548-808f65039d63	d81dabec-1c10-4269-9548-808f65039d63	qrcodes/d81dabec-1c10-4269-9548-808f65039d63.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
9922f9d5-2774-4032-b097-21b5b208d625	df697add-1313-4c55-957d-e53f28e5b499	df697add-1313-4c55-957d-e53f28e5b499	qrcodes/df697add-1313-4c55-957d-e53f28e5b499.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
3469781e-c595-487e-a0de-cd3966c772a8	68147a4a-8037-42ff-862a-64cc61cad395	68147a4a-8037-42ff-862a-64cc61cad395	qrcodes/68147a4a-8037-42ff-862a-64cc61cad395.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
55e2dbee-e936-4a4c-ba7b-c43228864852	3387ade2-a790-4808-9294-4308ebe93867	3387ade2-a790-4808-9294-4308ebe93867	qrcodes/3387ade2-a790-4808-9294-4308ebe93867.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
efaf34e5-158e-4830-a4ff-ffa4e4e9cbc5	620120f2-1730-4c93-b033-954f79d02e56	620120f2-1730-4c93-b033-954f79d02e56	qrcodes/620120f2-1730-4c93-b033-954f79d02e56.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
78d8f0b6-b61b-4ca3-97e4-fe40f0af137f	c55b5d93-f4c2-4588-9bc0-e3051f907091	c55b5d93-f4c2-4588-9bc0-e3051f907091	qrcodes/c55b5d93-f4c2-4588-9bc0-e3051f907091.svg	t	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N
c93104ca-0d83-4d9b-8bea-0e6cbdc5d276	a0a355f7-a358-4e92-bdbd-9b31808a868e	a0a355f7-a358-4e92-bdbd-9b31808a868e	qrcodes/a0a355f7-a358-4e92-bdbd-9b31808a868e.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
a0ba38b9-3068-4d86-bd3d-68182e20ac09	091d5401-cef6-40f8-8778-87389d39e51f	091d5401-cef6-40f8-8778-87389d39e51f	qrcodes/091d5401-cef6-40f8-8778-87389d39e51f.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
8946be62-30b7-46b7-955c-bd476244e3ee	3189250e-a44f-4b07-9d24-4b9b128485f9	3189250e-a44f-4b07-9d24-4b9b128485f9	qrcodes/3189250e-a44f-4b07-9d24-4b9b128485f9.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
b7f34458-6c5a-46f9-b329-2815ad38bd79	55042527-68f7-43ac-9e1a-b2d1872b8b82	55042527-68f7-43ac-9e1a-b2d1872b8b82	qrcodes/55042527-68f7-43ac-9e1a-b2d1872b8b82.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
47c1255e-1b05-4236-a41b-4eae93b2c080	2e1343ff-6d31-4246-9667-83ecf97a93ba	2e1343ff-6d31-4246-9667-83ecf97a93ba	qrcodes/2e1343ff-6d31-4246-9667-83ecf97a93ba.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
77f0def8-4f42-4143-921b-75524e568fb8	c54762d7-e7d2-499f-a4db-fb340f1e740d	c54762d7-e7d2-499f-a4db-fb340f1e740d	qrcodes/c54762d7-e7d2-499f-a4db-fb340f1e740d.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
8b910cfb-9a2d-4c5e-9c65-7d6d843dd5c6	27ae456f-8d57-4820-a7e1-e478df363acf	27ae456f-8d57-4820-a7e1-e478df363acf	qrcodes/27ae456f-8d57-4820-a7e1-e478df363acf.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
2ffae878-58ff-4dad-ad9c-9804f0166dac	a48bed46-6d76-4a7e-ad06-77a533df7482	a48bed46-6d76-4a7e-ad06-77a533df7482	qrcodes/a48bed46-6d76-4a7e-ad06-77a533df7482.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
801a0004-419c-44f9-b6dc-967d67f0c38e	361667bd-377a-46f0-83ea-bdce1a20b6ad	361667bd-377a-46f0-83ea-bdce1a20b6ad	qrcodes/361667bd-377a-46f0-83ea-bdce1a20b6ad.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
6d580584-784f-49b8-8c37-1b18be66f7bd	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	qrcodes/f0c27de1-6c21-482c-b8f0-ecd4e0ef96db.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
8fc582d9-ee00-4d24-bb47-0746aeb7d6c5	ebd82a70-8c97-4dad-80e0-7ece07478479	ebd82a70-8c97-4dad-80e0-7ece07478479	qrcodes/ebd82a70-8c97-4dad-80e0-7ece07478479.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
d9917bdd-d178-4adc-9d78-8812c9b6e22f	d2dac9ea-3c11-4698-8628-9c3412693fe6	d2dac9ea-3c11-4698-8628-9c3412693fe6	qrcodes/d2dac9ea-3c11-4698-8628-9c3412693fe6.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
f62b6871-f9ac-4554-a209-a1cf3b7df5e9	0b19b75b-02ea-429d-b638-696f626d1384	0b19b75b-02ea-429d-b638-696f626d1384	qrcodes/0b19b75b-02ea-429d-b638-696f626d1384.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
ff44f369-b63a-4150-a0f6-cdedd2efd99f	b11ed488-c372-47f4-bf13-3a27148b98f0	b11ed488-c372-47f4-bf13-3a27148b98f0	qrcodes/b11ed488-c372-47f4-bf13-3a27148b98f0.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
69e48268-4f3a-4065-a807-ab2c1ce8074d	76c9fc95-c035-4d55-a192-88b22c907aaf	76c9fc95-c035-4d55-a192-88b22c907aaf	qrcodes/76c9fc95-c035-4d55-a192-88b22c907aaf.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
76249910-df97-4427-8a76-5ecbd62d1188	e83c6db7-1c8d-44d3-818d-5fabf4127734	e83c6db7-1c8d-44d3-818d-5fabf4127734	qrcodes/e83c6db7-1c8d-44d3-818d-5fabf4127734.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
9dbacdd7-e793-4c10-928d-e034b48a6e66	993c08e7-1142-433b-93c1-61aad85798f2	993c08e7-1142-433b-93c1-61aad85798f2	qrcodes/993c08e7-1142-433b-93c1-61aad85798f2.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
494721b3-2772-48c4-be16-64230ec69913	1bb9f4ba-525d-4390-800d-140404e63991	1bb9f4ba-525d-4390-800d-140404e63991	qrcodes/1bb9f4ba-525d-4390-800d-140404e63991.svg	t	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N
e40da2bc-0ea3-425d-aa6e-bcf7faa9d1db	593b6e25-ee8c-4702-b04e-b8675711696b	593b6e25-ee8c-4702-b04e-b8675711696b	qrcodes/593b6e25-ee8c-4702-b04e-b8675711696b.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
be07b03b-df4c-4d2e-859e-13509b8caf5d	34cb37c8-43d8-42bf-8be8-3622219b1fd2	34cb37c8-43d8-42bf-8be8-3622219b1fd2	qrcodes/34cb37c8-43d8-42bf-8be8-3622219b1fd2.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
ec825a72-63c5-4012-b283-0dc13837ca62	a7072c26-3165-47e4-81bc-5a88a2d43ab1	a7072c26-3165-47e4-81bc-5a88a2d43ab1	qrcodes/a7072c26-3165-47e4-81bc-5a88a2d43ab1.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
de2c02ad-9718-4d6e-ab5d-54f646e17734	f99028fd-3f94-4fc4-8635-13369d98711f	f99028fd-3f94-4fc4-8635-13369d98711f	qrcodes/f99028fd-3f94-4fc4-8635-13369d98711f.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
48dec699-a5f6-4182-b23f-24a852e8d93f	c0677d15-296c-4d34-98e1-cc940baa7a99	c0677d15-296c-4d34-98e1-cc940baa7a99	qrcodes/c0677d15-296c-4d34-98e1-cc940baa7a99.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
4b9efd10-7c33-42f9-a61e-cae72a9ebde0	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	qrcodes/b8ad8325-dc2d-4055-bb6c-3bbf731e87bd.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
5b914444-3212-41a7-a9a4-79e6ccfa46e9	e00e59a1-0f30-4ea7-8094-3956711ff682	e00e59a1-0f30-4ea7-8094-3956711ff682	qrcodes/e00e59a1-0f30-4ea7-8094-3956711ff682.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d55235af-fa93-4014-aa1f-87426468d756	5373b97d-245d-4889-9018-20958b798c17	5373b97d-245d-4889-9018-20958b798c17	qrcodes/5373b97d-245d-4889-9018-20958b798c17.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
ff0acf88-5eb5-4d95-8cd0-9a222ad030db	e1b3ed82-00ea-485e-b741-070c71fe1d2c	e1b3ed82-00ea-485e-b741-070c71fe1d2c	qrcodes/e1b3ed82-00ea-485e-b741-070c71fe1d2c.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
bb088602-ca45-4135-b101-ef34efa4083a	627738fb-548f-49a7-ade4-0f7ae516c3c3	627738fb-548f-49a7-ade4-0f7ae516c3c3	qrcodes/627738fb-548f-49a7-ade4-0f7ae516c3c3.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
8cee1709-8c2a-4bbf-bd37-88f3a813561c	d71ea253-d0e4-42f4-861d-a743fd7a8900	d71ea253-d0e4-42f4-861d-a743fd7a8900	qrcodes/d71ea253-d0e4-42f4-861d-a743fd7a8900.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
83df747d-2c98-4317-83df-0583ef1d9411	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	7fd0bc26-61c9-494f-b0cb-1b5c686444f5	qrcodes/7fd0bc26-61c9-494f-b0cb-1b5c686444f5.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
e724cde5-6e82-413b-a879-d6c086d59647	de204b49-049f-4e74-9fad-76680c0ec640	de204b49-049f-4e74-9fad-76680c0ec640	qrcodes/de204b49-049f-4e74-9fad-76680c0ec640.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
6716e276-3ad2-4c4a-a8cb-fa52406bef45	d00fd50d-fdfa-440b-8698-8ba7c354386a	d00fd50d-fdfa-440b-8698-8ba7c354386a	qrcodes/d00fd50d-fdfa-440b-8698-8ba7c354386a.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
d76dcad3-fa39-4505-af40-a942006c03b7	d88cc5d9-f493-4156-821e-29602853c857	d88cc5d9-f493-4156-821e-29602853c857	qrcodes/d88cc5d9-f493-4156-821e-29602853c857.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
5de339ab-f21a-4840-aed8-67a29dc157bb	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	qrcodes/0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
05a687dd-ae6e-4424-b9a8-e43a130e7904	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	f613464f-be5b-4c3d-9ff5-8ff2793f9d05	qrcodes/f613464f-be5b-4c3d-9ff5-8ff2793f9d05.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
f972c592-dc35-4b9d-8774-7d379bc39773	e31d30be-ccad-45b8-a337-70e5c00155e2	e31d30be-ccad-45b8-a337-70e5c00155e2	qrcodes/e31d30be-ccad-45b8-a337-70e5c00155e2.svg	t	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N
\.


--
-- Data for Name: assets_rfid; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_rfid (uuid, asset_uuid, epc, tag_type, encoded_at, is_active, deactivated_at, note, created_at, updated_at, deleted_at) FROM stdin;
defdd0c9-a893-4745-bf88-b8b9c414c246	e31d30be-ccad-45b8-a337-70e5c00155e2	E31D30BECCAD45B8A33770E5C00155E2	UHF	\N	t	\N	\N	2025-12-23 16:13:32+08	2025-12-23 16:13:32+08	\N
838c5e58-41ef-4139-9dfb-029ea86cf322	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	B1D1A3B46B0E448C8D59F582B9D106F3	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
97c7df3c-fb9c-43b8-9181-e544e955f47b	1c4a40c1-aeb5-4287-a4b1-383d158920e5	1C4A40C1AEB54287A4B1383D158920E5	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
2a919b9d-1e9f-4926-ad01-bfff61f91515	f875c2ca-1800-433b-b0a4-2d4d31ba308e	F875C2CA1800433BB0A42D4D31BA308E	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
6ebf8b04-dfae-4317-8d02-5ca270f395b9	52d9a146-b1cf-4110-b89d-be03c22a6e0e	52D9A146B1CF4110B89DBE03C22A6E0E	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
06a077a1-132e-4f13-bff2-c5c202841a68	49fe0c73-3650-4c46-b8b2-28b11191c8fb	49FE0C7336504C46B8B228B11191C8FB	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
02dee319-a38d-42fb-a254-b02c44e547cd	36e92940-a131-4ac0-b45b-b8500ff4b040	36E92940A1314AC0B45BB8500FF4B040	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
199584c7-ca80-48ba-ba06-71e1c533fa56	a11862d4-69a5-4d2b-a426-57a89de1b13c	A11862D469A54D2BA42657A89DE1B13C	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
0e73cfa2-17b0-41ae-9e90-c9cb1156bd5e	e47f3b62-82ae-4322-8660-bf104df108a5	E47F3B6282AE43228660BF104DF108A5	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
a9978db6-e7cc-40e4-9977-0cbd7af7402e	3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	3627BBBBFA2F4FC89DC479C094CFAB38	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
7952d5bb-b5b4-42e2-ad19-b3baf3962bca	a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	A48628DA4C0C4FFA9A04CF89EA2D1B17	UHF	\N	t	\N	\N	2025-12-24 11:56:00+08	2025-12-24 11:56:00+08	\N
\.


--
-- Data for Name: assets_transfers; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_transfers (uuid, asset_uuid, transfer_code, type, before, after, kode_status, note, created_at, updated_at, deleted_at, pic_request_uid, pic_approve_uid, file_path, file_name, file_mime, file_size, flow, flow_file_path, flow_file_name, flow_file_mime, flow_file_size) FROM stdin;
0e845335-934d-4702-a313-ba106d0c61a9	b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	MOV25120001	owner	{"value": "SAR"}	{"value": "ASP"}	ACC	\N	2025-12-22 21:03:39+08	2025-12-22 21:12:25+08	\N	Administrator	Asset Management Head	\N	\N	\N	\N	{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen","approved_by":"Administrator","approved_at":"2025-12-22 20:03:39"},{"code":"new_dept_head","label":"Approval Dept.Head \\/ Section (Move To)","role":"User - Dept.Head \\/ Section","approved_by":"Administrator","approved_at":"2025-12-22 20:04:28"},{"code":"old_dept_head","label":"Approval Dept.Head \\/ Section (Move From)","role":"User - Dept.Head \\/ Section","approved_by":"Administrator","approved_at":"2025-12-22 20:04:31"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":"Asset Management Head","approved_at":"2025-12-22 20:12:25"}]}	\N	\N	\N	\N
7f8f84da-4a47-41af-b460-128617808563	e47f3b62-82ae-4322-8660-bf104df108a5	MOV25120002	owner	{"value": "OIT"}	{"value": "WRH"}	APR	test uat	2025-12-23 16:39:37+08	2025-12-23 16:39:37+08	\N	Department User	\N	transfers/e47f3b62-82ae-4322-8660-bf104df108a5/MOV25120002-20251223153937-76qYgB.jpg	1. Motor Elektrik Viar New Q1 Tahun 2019 (Merah) (2).jpg	image/jpeg	1687255	{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen","approved_by":"Department User","approved_at":"2025-12-23 15:39:37"},{"code":"new_dept_head","label":"Approval Dept.Head \\/ Section (Move To)","role":"User - Dept.Head \\/ Section","approved_by":null,"approved_at":null},{"code":"old_dept_head","label":"Approval Dept.Head \\/ Section (Move From)","role":"User - Dept.Head \\/ Section","approved_by":null,"approved_at":null},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}]}	\N	\N	\N	\N
93ca22e8-1649-4bf0-9853-e6e6488aa87b	d88cc5d9-f493-4156-821e-29602853c857	MOV25120003	user	{"value": "FOP"}	{"value": "WRH"}	REJ	\N	2025-12-23 16:42:33+08	2025-12-23 17:47:21+08	\N	Department User	Department Head	\N	\N	\N	\N	{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen","approved_by":"Department User","approved_at":"2025-12-23 15:42:33"},{"code":"new_dept_head","label":"Approval Dept.Head \\/ Section (Move To)","role":"User - Dept.Head \\/ Section","approved_by":null,"approved_at":null,"rejected_by":"Department Head","rejected_at":"2025-12-23 16:47:21"},{"code":"old_dept_head","label":"Approval Dept.Head \\/ Section (Move From)","role":"User - Dept.Head \\/ Section","approved_by":null,"approved_at":null},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}],"rejected_by":"Department Head","rejected_at":"2025-12-23 16:47:21"}	\N	\N	\N	\N
\.


--
-- Data for Name: assets_value; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_value (asset_uuid, price, quantity, is_pajak, vat_in, kode_uom, total, useful_life_month, useful_life_year, created_at, updated_at, deleted_at, actual_date, capitalization_date) FROM stdin;
57f05f2c-a4fb-4667-9289-5a6b92dc1a21	28240000.00	1.000	f	0.00	EA	28240000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2019-09-04	2019-09-04
c9edde02-2af4-43a8-8e4c-6a02c17357b9	82500000.00	1.000	f	0.00	EA	82500000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2020-07-01	2020-07-01
42b0073a-07f3-4dcc-b82c-e2851b626433	154550000.00	1.000	f	0.00	EA	154550000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2020-07-01	2020-07-01
9beb94c2-f47d-4b48-9281-54ec00cf0758	24145000.00	1.000	f	0.00	EA	24145000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2020-09-03	2020-09-03
c88e2c69-914f-403e-ab36-0a9322d6591f	25520000.00	1.000	f	0.00	EA	25520000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2020-10-08	2020-10-08
9580ea1b-0f93-4c89-b167-a089131d5761	25520000.00	1.000	f	0.00	EA	25520000.00	48	4.00	2025-12-22 13:17:04+08	2025-12-22 13:17:04+08	\N	2020-10-08	2020-10-08
80498ce5-e2e0-47c3-9dc6-ec570f0cd8a8	25520000.00	1.000	f	0.00	EA	25520000.00	48	4.00	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	2020-10-08	2020-10-08
b1d1a3b4-6b0e-448c-8d59-f582b9d106f3	61297800000.00	1.000	f	0.00	EA	61297800000.00	349	29.08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	2020-11-09	2020-11-09
1c4a40c1-aeb5-4287-a4b1-383d158920e5	61297800000.00	1.000	f	0.00	EA	61297800000.00	349	29.08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	2020-11-09	2020-11-09
f875c2ca-1800-433b-b0a4-2d4d31ba308e	61297800000.00	1.000	f	0.00	EA	61297800000.00	349	29.08	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	2020-11-09	2020-11-09
54ec2fba-0b2b-4783-ab74-464ba53d2e07	209440000.00	1.000	f	0.00	EA	209440000.00	48	4.00	2025-12-22 13:17:05+08	2025-12-22 13:17:05+08	\N	2020-12-01	2020-12-01
607cb0d8-94cc-4b4c-9763-9cfc7c7e0c45	58822500.00	1.000	f	0.00	EA	58822500.00	48	4.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2020-12-03	2020-12-03
52d9a146-b1cf-4110-b89d-be03c22a6e0e	46750000.00	1.000	f	0.00	EA	46750000.00	48	4.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2020-12-04	2020-12-04
99970f15-9c4a-4d4f-b550-a7ef488054d0	398612500.00	1.000	f	0.00	EA	398612500.00	48	4.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2020-12-25	2021-01-01
e971913d-0f93-4a70-85eb-c0ed12a172d8	297940647.12	1.000	f	0.00	EA	297940647.12	48	4.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2022-12-31	2023-01-01
101fda0f-877a-4290-9df5-00a84859c3e9	36508127.52	1.000	f	0.00	EA	36508127.52	96	8.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2019-07-15	2019-07-15
6504929e-7f0b-47a6-b6d6-25032344b55f	36508127.52	1.000	f	0.00	EA	36508127.52	96	8.00	2025-12-22 13:17:06+08	2025-12-22 13:17:06+08	\N	2019-07-15	2019-07-15
19c63207-1947-4bb3-9193-554042ba6da7	21673690.48	1.000	f	0.00	EA	21673690.48	96	8.00	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	2019-07-15	2019-07-15
03e94a29-9883-46a5-9294-21d22f2fba7f	21673690.48	1.000	f	0.00	EA	21673690.48	96	8.00	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	2019-07-15	2019-07-15
49fe0c73-3650-4c46-b8b2-28b11191c8fb	18275000.00	1.000	f	0.00	EA	18275000.00	96	8.00	2025-12-22 13:17:07+08	2025-12-22 13:17:07+08	\N	2019-09-13	2019-10-01
36e92940-a131-4ac0-b45b-b8500ff4b040	63775000.90	1.000	f	0.00	EA	70790251.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-08-25	2025-09-01
a11862d4-69a5-4d2b-a426-57a89de1b13c	897600169.37	1.000	f	0.00	EA	996336188.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-03-18	2025-04-01
e47f3b62-82ae-4322-8660-bf104df108a5	9300000.00	1.000	f	0.00	EA	10323000.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-06-24	2025-07-01
3627bbbb-fa2f-4fc8-9dc4-79c094cfab38	9300000.00	1.000	f	0.00	EA	10323000.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-06-24	2025-07-01
a48628da-4c0c-4ffa-9a04-cf89ea2d1b17	17500000.00	1.000	f	0.00	EA	19425000.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-10-16	2025-11-01
80acf346-539e-4c9a-aed0-9ff88df294f5	17500000.00	1.000	f	0.00	EA	19425000.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-10-16	2025-11-01
4517635a-b083-4bba-bbba-22c060cff5b6	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:23:59+08	2025-12-22 18:23:59+08	\N	2025-11-06	2025-11-06
bc1fdef0-b3ba-4655-867f-8038f2a0c04f	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
5450ed79-c9ee-45ac-abd3-d657d1a8897c	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
50a845bf-b203-4b10-b292-fda3c7b5ac6e	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
de6479e8-c9c2-41c1-9ad6-c74439bc986f	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
c7f80482-89d8-4f80-975d-34a752e992aa	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
e8ad2dd4-ecda-40cb-9423-a95a9aa5a3f7	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
8e4323ee-5954-4946-b50e-252f098ee44e	6643200.00	1.000	f	0.00	EA	7373952.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-06	2025-11-06
0023be09-5f8c-4f86-9a6f-78cdd74e63a7	6550000.00	1.000	f	0.00	EA	7270500.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-11-03	2025-11-03
fc4e2b9c-65c3-43dd-bcb7-fd74f38576c2	25000000.00	1.000	f	0.00	EA	27750000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-01-08	2025-01-08
9dbcc529-de27-4753-a772-90aa5f8c7894	25794000.00	1.000	f	0.00	EA	25794000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-09-09	2025-09-09
47665328-ff67-40a5-aac0-24572afbdcf8	25794000.00	1.000	f	0.00	EA	25794000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-09-09	2025-09-09
747d2923-ba5d-475d-a784-e41bc58e5561	25794000.00	1.000	f	0.00	EA	25794000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-09-09	2025-09-09
2f8f647c-1936-4b32-93f7-9ebbcda6d039	25794000.00	1.000	f	0.00	EA	25794000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-09-09	2025-09-09
f743b734-490e-470d-bc30-19e730a855b2	65000000.00	1.000	f	0.00	EA	72150000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
ac204bbb-af9f-4e3a-9734-082c29c9641f	32000000.00	1.000	f	0.00	EA	35520000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
ead690b6-5e8f-4e89-b7d1-a2f57cbaf8b0	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
31a57d16-cb30-4e53-8e7d-3ee074f5770b	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
30a9ed88-3599-4d7f-8456-cce980762f96	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
4ee48863-fa9b-4ff3-9c00-2304ada83c29	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
34453391-14df-41b0-8475-2d31c5371f29	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:00+08	2025-12-22 18:24:00+08	\N	2025-02-18	2025-03-01
edddbe54-8ed9-496c-88d9-1a96279445c6	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
bc78ad64-70b6-49e9-b9c4-c1f89c85ce5e	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
e8925ef1-66f5-432d-92c3-c37b79062eef	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
a1906f9d-e1c1-4072-99c4-51cba2577d90	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
5e69818f-651f-4e8b-8a69-513fa0a773db	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
ba105ad8-72ad-40f6-8634-03d1e712b9af	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
fc890cda-3a6a-436b-8aee-2b1e22131cfd	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
62fcc371-d6de-4ef0-88ef-413b40c6783d	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
3bd5cf1d-ae87-4735-b753-1f810b177052	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
d4bbd0a4-709e-4a0e-bf5b-82fdd78c4d6e	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
cddab2cb-f430-4819-b9cf-c35a54b156cd	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
4852ac97-baee-4c1e-8b48-7e0fd276ec48	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:01+08	2025-12-22 18:24:01+08	\N	2025-02-18	2025-03-01
3b16435d-b93f-4811-bf25-6d03a45cc6dc	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
ceb2c4b5-2711-4a2d-944a-d37cf1f68e33	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
c521f578-f2c7-446d-b351-9b47fdb59913	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
836f58bc-d2d9-4543-bc82-7859db2da9be	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
6630f300-223a-4694-a3b5-28193c508cba	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
75c6a8ad-7be8-47cb-9165-89d42bb233c7	31000000.00	1.000	f	0.00	EA	34410000.00	48	4.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-02-18	2025-03-01
6d3c3c19-3b28-4cab-9aa1-e700bdcef883	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
896e640c-3b59-4bc8-aba1-5ac076e99c49	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
eb65a09e-1f7c-4ba2-84a8-fdf9f530a146	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
bb12563d-78e3-4121-84df-edae5df20c63	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
da02d35f-1531-49f7-89f3-9c9fed5f9553	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
d30045d9-6179-4162-b8dc-e8d16ce29802	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
46930604-8016-42a6-9329-ffdac3236bc1	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
fe41bf26-c9b0-406f-8000-7f9469e1fe7d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
538a6d2a-ec13-4d7c-87e7-f2e56d089780	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:02+08	2025-12-22 18:24:02+08	\N	2025-08-01	2025-08-01
fdcd74dc-bb14-44bb-8ee0-c12839b31f44	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
1a2dda94-1f32-444a-a4dd-310edef0d76d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
e3e63659-175c-4748-b571-d2224a256534	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
80be2e71-1ead-4023-bd82-148c11e82d2f	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
60ab9154-7025-4b9a-93f7-d8c7f276cbc3	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
0192e4a7-0901-4db9-aa00-c192d6adaa37	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
9398fd93-f9b2-4639-8c65-51086cf62165	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
db95bb38-c227-48ec-ac5a-69d642ba910e	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
e3222e82-d284-45f4-87c5-6ca46ea72fac	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
221f2223-7885-4f5d-9d6c-c3ac40c50f9e	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
3c8eab4b-ba11-42c3-bc67-9290d52a36f9	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
f2f26243-a41c-42c5-b593-2fe4e12bc4aa	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
5bd44432-06b9-41e2-a0c4-cd8e616f52c9	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
abbaf21e-07b0-4097-889a-094bfeda26ef	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
2251c4a6-eed4-46d4-aebd-a49d54f8b2cc	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
66df45b6-5011-45a5-be1d-f140cc3e4b7d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
0c5094e1-9380-4a00-aef4-46048c2ec697	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
5fa7d14c-de3a-4137-9e9f-bdeee7bbf0fe	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
d403907f-306d-4dfb-8ca4-a950b548394d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
3998992b-b5bf-4d03-9cd7-526c45df750c	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:03+08	2025-12-22 18:24:03+08	\N	2025-08-01	2025-08-01
b6368e98-7f87-42db-8b28-4084b11a0972	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
82ef3eb4-8b79-47e0-915e-7276ea7bd578	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
899b064e-2a20-489f-b713-56a3a1bcaf20	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
3f0dafbf-7fd9-407b-b1a9-4141e6326797	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
659ca9a0-f2de-4890-86ed-ef404f8d93fd	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
67bd771b-1a68-403b-b081-4727a5b09bbe	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
45925fe4-a66c-4e4c-92e4-81f818fd71c8	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
2c7c6864-7ce3-46f3-8af2-4d87afdd7b8c	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
9bb4b946-4d3b-427a-914c-accbaf7c362d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
e74afc6c-038f-4875-a703-89b52e09ee91	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
43f964a1-fc8a-42fe-85b7-af80de5688a7	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
a8af8b25-ea8a-4d3c-8bfd-8055fc33fa24	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
a267ecca-f8a6-4fde-8bfb-eaba58162ba2	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
06b7d765-707f-4860-a0e7-3e520d4c1578	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
d81dabec-1c10-4269-9548-808f65039d63	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
df697add-1313-4c55-957d-e53f28e5b499	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
68147a4a-8037-42ff-862a-64cc61cad395	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
3387ade2-a790-4808-9294-4308ebe93867	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
620120f2-1730-4c93-b033-954f79d02e56	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
c55b5d93-f4c2-4588-9bc0-e3051f907091	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:04+08	2025-12-22 18:24:04+08	\N	2025-08-01	2025-08-01
a0a355f7-a358-4e92-bdbd-9b31808a868e	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
091d5401-cef6-40f8-8778-87389d39e51f	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
3189250e-a44f-4b07-9d24-4b9b128485f9	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
55042527-68f7-43ac-9e1a-b2d1872b8b82	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
2e1343ff-6d31-4246-9667-83ecf97a93ba	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
c54762d7-e7d2-499f-a4db-fb340f1e740d	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
27ae456f-8d57-4820-a7e1-e478df363acf	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
a48bed46-6d76-4a7e-ad06-77a533df7482	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
361667bd-377a-46f0-83ea-bdce1a20b6ad	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
f0c27de1-6c21-482c-b8f0-ecd4e0ef96db	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
ebd82a70-8c97-4dad-80e0-7ece07478479	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
d2dac9ea-3c11-4698-8628-9c3412693fe6	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
0b19b75b-02ea-429d-b638-696f626d1384	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
b11ed488-c372-47f4-bf13-3a27148b98f0	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
76c9fc95-c035-4d55-a192-88b22c907aaf	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
e83c6db7-1c8d-44d3-818d-5fabf4127734	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
993c08e7-1142-433b-93c1-61aad85798f2	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
1bb9f4ba-525d-4390-800d-140404e63991	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:05+08	2025-12-22 18:24:05+08	\N	2025-08-01	2025-08-01
593b6e25-ee8c-4702-b04e-b8675711696b	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
34cb37c8-43d8-42bf-8be8-3622219b1fd2	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
a7072c26-3165-47e4-81bc-5a88a2d43ab1	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
f99028fd-3f94-4fc4-8635-13369d98711f	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
c0677d15-296c-4d34-98e1-cc940baa7a99	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
b8ad8325-dc2d-4055-bb6c-3bbf731e87bd	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
e00e59a1-0f30-4ea7-8094-3956711ff682	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
5373b97d-245d-4889-9018-20958b798c17	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
e1b3ed82-00ea-485e-b741-070c71fe1d2c	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
627738fb-548f-49a7-ade4-0f7ae516c3c3	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
d71ea253-d0e4-42f4-861d-a743fd7a8900	23000000.00	1.000	f	0.00	EA	23000000.00	36	3.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-08-01	2025-08-01
7fd0bc26-61c9-494f-b0cb-1b5c686444f5	449400000.00	1.000	f	0.00	LOT	498834000.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-02-18	2025-03-01
de204b49-049f-4e74-9fad-76680c0ec640	62500000.00	1.000	f	0.00	EA	69375000.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-12-15	2025-12-15
d00fd50d-fdfa-440b-8698-8ba7c354386a	7276050.00	1.000	f	0.00	EA	7276050.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-09-15	2025-09-15
d88cc5d9-f493-4156-821e-29602853c857	0.00	1.000	f	0.00	EA	0.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-12-01	2025-12-01
0025067f-86c1-4b11-bdbf-3e4dc8ae1c2b	5050000.00	1.000	f	0.00	EA	5605500.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-06-24	2025-07-01
f613464f-be5b-4c3d-9ff5-8ff2793f9d05	5050000.00	1.000	f	0.00	EA	5605500.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-06-24	2025-07-01
e31d30be-ccad-45b8-a337-70e5c00155e2	1390000000.00	1.000	f	0.00	EA	1542900000.00	48	4.00	2025-12-22 18:24:06+08	2025-12-22 18:24:06+08	\N	2025-06-18	2025-07-01
\.


--
-- Data for Name: assets_value_history; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.assets_value_history (uuid, asset_uuid, before_payload, after_payload, pic_request_uid, note, created_at, updated_at, deleted_at, acq_code) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.cache (key, value, expiration) FROM stdin;
751e66bd94b208466096190f93b056ee:timer	i:1766548191;	1766548191
4e2b70de8d9eb01c7e5f79b8ede93841:timer	i:1766454878;	1766454878
4e2b70de8d9eb01c7e5f79b8ede93841	i:1;	1766454878
751e66bd94b208466096190f93b056ee	i:1;	1766548191
009f0b77e105714ef1633ca2385ae42c:timer	i:1766548191;	1766548191
009f0b77e105714ef1633ca2385ae42c	i:1;	1766548191
90ca11301540d858b20cd5d89b068ebb:timer	i:1766475056;	1766475056
90ca11301540d858b20cd5d89b068ebb	i:1;	1766475056
7ccd0dcc9ce012ea78f7ce9fb1299151:timer	i:1766475295;	1766475295
7ccd0dcc9ce012ea78f7ce9fb1299151	i:1;	1766475295
60c7974763fb3f922375ce1560c77b02:timer	i:1766561153;	1766561153
60c7974763fb3f922375ce1560c77b02	i:1;	1766561153
b919f32b86ee3e57d66d1ab540c1d47b:timer	i:1766978538;	1766978538
b919f32b86ee3e57d66d1ab540c1d47b	i:1;	1766978538
de260793fb2ed767e483b99f99533f48:timer	i:1766978538;	1766978538
de260793fb2ed767e483b99f99533f48	i:1;	1766978538
004356492feef2cdd2d3e5d43e48041c:timer	i:1767317973;	1767317973
004356492feef2cdd2d3e5d43e48041c	i:1;	1767317973
d0ee8b04038ed143ca6aa0efa7a6401b:timer	i:1767317973;	1767317973
d0ee8b04038ed143ca6aa0efa7a6401b	i:1;	1767317973
a5fc55b22fa62d71c12f0c4ed1d551fc:timer	i:1767317973;	1767317973
a5fc55b22fa62d71c12f0c4ed1d551fc	i:1;	1767317973
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: master_action; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_action (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
16c26417-908e-4b3f-a837-9aaee50ace09	C	Create	t	2025-11-10 14:05:30	2025-11-10 14:05:30	\N
a3c3dbc9-f303-43da-89ec-ce992cc42043	R	Read	t	2025-11-10 14:05:30	2025-11-10 14:05:30	\N
af1252e9-52ab-4222-89b7-7a74e12a2701	U	Update	t	2025-11-10 14:05:30	2025-11-10 14:05:30	\N
7f7c630e-f82b-4601-8021-5c940ea1dbb9	D	Delete	t	2025-11-10 14:05:30	2025-11-10 14:05:30	\N
dc544ed9-1c29-40b4-84a2-0ff2ca5b3b54	APR	Approve	t	2025-11-10 14:05:30	2025-11-10 14:05:30	\N
\.


--
-- Data for Name: master_asset_class; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_asset_class (uuid, kode, name, status, created_at, updated_at, deleted_at, kode_transaction) FROM stdin;
db22bdf0-621f-44c2-b768-bae63b0e9015	1301	LVA - Main Machinery (Train Set)	f	2025-10-24 14:55:39	2025-12-11 13:47:29	\N	A
c11c2bfd-2f00-46ce-85fb-262fecb26ca8	1302	URA - Main Machinery (Train Set)	f	2025-10-24 14:55:39	2025-12-11 13:48:28	\N	A
3cb69f04-65da-4e72-858f-4f1172c63e67	1303	ROU - Main Machinery (Train Set)	f	2025-10-24 14:55:39	2025-12-11 13:48:42	\N	A
b67e693f-3a96-4512-993e-68ba691ef442	KDM-1	Test Kode	t	2025-10-23 10:05:03	2025-10-23 10:05:03	\N	A
b309efd2-e831-4673-adc9-283f0d9649ed	1100	Land	t	2025-10-24 14:53:59	2025-10-24 14:53:59	\N	A
64e90339-b75c-4fad-81e5-3ef60370220d	1101	LVA - Land	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
f28579a7-e5a4-4037-bd5a-9fed8e2728d8	1102	URA - Land	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
7f8d525e-16ab-4a71-ba9a-74e02b5396a0	1103	ROU - Land	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
f8400281-5bd3-4c83-a762-ce11e795ac69	1200	Building Structure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
4c8a2670-5aa5-47aa-85ff-9b722931a3e3	1201	LVA - Building Structure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e987204e-4676-45eb-b297-7e335d2fdac4	1202	URA - Building Structure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
0ab5f78c-c6fb-4f92-8c72-6415d92403c5	1203	ROU - Building Structure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
41344822-c36e-4ab0-ad3c-154a358edd36	1210	Building Structure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
75a8b3a7-1d46-4b4b-bac0-29cfb177bcc3	1211	LVA - Building Structure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
2d3bb592-6cb0-4be7-af4d-40912f47e210	1212	URA - Building Structure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
9b250108-213a-4a47-b0c1-6d493d0047f2	1213	ROU - Building Structure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
13055817-0ab4-4286-83ae-6e9f90965534	1220	Infrastructure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
85b8de38-bde4-4d2a-90d0-6a9638a781b6	1221	LVA - Infrastructure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a92131bc-a116-407b-85dc-ff0f57bff5a7	1222	URA - Infrastructure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
d8f5d677-ba38-426f-add1-6587009d7b74	1223	ROU - Infrastructure	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
693ed381-4782-4d3a-9e02-96409cd12a66	1230	Infrastructure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
4ce8bcc1-e535-4ab0-9541-4f29e16828a1	1231	LVA - Infrastructure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
edaa1d5c-1b48-4e5b-815d-6f1752dc8b6e	1232	URA - Infrastructure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
97ad52ff-715c-402b-a71d-8909eecde9d2	1233	ROU - Infrastructure Operation	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e29ed940-2e06-4220-a2cd-9bc10374d67b	1300	Main Machinery (Train Set)	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
f448bef4-76b3-4c2b-826b-95f6ebf87d73	1310	Machinery & Equipment Testing	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
12351158-833c-47e9-a32e-6c160851cfbd	1311	LVA - Machinery & Equipment Testing	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
2de3cf14-f231-4445-bc9c-827bc588a519	1312	URA - Machinery & Equipment Testing	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
3ebbcbd2-cfbd-43d7-912d-fc3360a603ae	1313	ROU - Machinery & Equipment Testing	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
8beb3e66-2837-47d2-aa1e-da418d0a17aa	1320	Machinery & Equipment Maintenance	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
614bb4af-6459-4b14-a473-5d44cf3d6d3b	1321	LVA - Machinery & Equipment Maintenance	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
fcce881a-5fac-4702-9bc2-f6ead9e37407	1322	URA - Machinery & Equipment Maintenance	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a7ef9d7b-b26a-489d-8787-82e43142ba2f	1323	ROU - Machinery & Equipment Maintenance	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
43996443-df63-4f4a-8534-72da5b35fddf	1330	Machinery & Equipment Operational	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
53d0c6af-aaed-41a5-a450-14d141fa9586	1331	LVA - Machinery & Equipment Operational	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
6ed19fa6-a907-4675-b2c9-4d6c72c44c08	1332	URA - Machinery & Equipment Operational	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e7183419-aa36-4452-9f5a-6b3a2e6f6f72	1333	ROU - Machinery & Equipment Operational	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
0c1979a0-c1b6-4b28-b031-8da3ebfd4d95	1340	Machinery & Equipment Electrical & Signal System	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
354b72f2-82b3-4e3e-adc4-c8a16221c738	1341	LVA - Machinery & Equipment Electrical & Signal System	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
20d3b83a-6947-43b1-af80-79d469f483a9	1342	URA - Machinery & Equipment Electrical & Signal System	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
0c7292ce-b41a-48ee-a63f-981c15ed9ece	1343	ROU - Machinery & Equipment Electrical & Signal System	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
157b06de-9fbf-4413-9484-2dae381afe07	1350	Machinery & Equipment Others	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
98abda7f-f5fd-47cb-a698-6cfbdaf8e36a	1351	LVA - Machinery & Equipment Others	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
b01ad7f9-a3e9-4d75-ad69-15adfd8c81e4	1352	URA - Machinery & Equipment Others	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
d9dd7ead-43f2-47af-958b-28605aa6a1c7	1353	ROU - Machinery & Equipment Others	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
0e9ceaa5-dadf-434e-b1d0-390c948c0d3c	1400	Office Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
0248e982-5482-47ee-b8e5-86fca7f8dbf5	1401	LVA - Office Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
44c68b56-5247-4644-bf96-268ee6352889	1402	URA - Office Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
7e20cc1f-7ad7-4ecf-8df1-7261d097e084	1403	ROU - Office Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
322c7059-0803-495d-a69b-e5631080c4f9	1410	Office Furniture & Fixture	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
7ad6e316-5ed6-4fd5-891a-4c996fa7c971	1411	LVA - Office Furniture & Fixture	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a7be9584-7806-4797-a0a3-6bf33677ef6d	1412	URA - Office Furniture & Fixture	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
2a2c299d-132f-479c-8326-92cbee554142	1413	ROU - Office Furniture & Fixture	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a2927e02-9909-4348-ae26-44064bd67555	1420	IT Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
34ed680c-3933-4598-811d-405afd900a7c	1421	LVA - IT Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
dcf5ab39-9111-4dd8-a150-4c0522808f0a	1422	URA - IT Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
abb9ede1-e342-485f-97f8-2aa3e7bba8ed	1423	ROU - IT Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
34fd488e-f7c5-4a3e-a1a1-76c4b4cdf86b	1430	Network & Signal Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a1b964a2-1320-44c5-bdf5-77fd3bee0d50	1431	LVA - Network & Signal Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
c3a07308-1f71-4dd0-b734-5810766d0f28	1432	URA - Network & Signal Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
c9d58d6f-dc80-4236-8cfa-e118b93aa0fb	1433	ROU - Network & Signal Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
ea3f4adf-6b9f-4af5-b8d8-e6994b86e915	1440	Operational Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
6a81b0ad-87a3-4a8d-ac57-1991dbe2d956	1441	LVA - Operational Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e3636ef8-3393-47e2-b35e-792ef40a0756	1442	URA - Operational Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
15f22c3c-142a-4f9c-9481-da5d04e9b85a	1443	ROU - Operational Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
6762c79c-a254-4564-b00f-b0deb8d34afc	1450	Infrastructure Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
43858739-6ccc-4aac-bdf7-39c3cffd7035	1451	LVA - Infrastructure Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
464fbe6a-f357-448c-90d3-185b029b0e8d	1452	URA - Infrastructure Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
2c59b7b8-be14-4d07-9149-0bbce7e3f24a	1453	ROU - Infrastructure Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
c156bcb6-dbe4-4ce6-927f-2883e0090920	1460	Measuring Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
7f264669-24fa-431a-a009-c49027105b48	1461	LVA - Measuring Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
11193d70-8a63-4b53-a67b-6e538026f8a6	1462	URA - Measuring Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
ac1eaa45-aaee-455b-814c-8716e9948976	1463	ROU - Measuring Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
908f07f3-5e43-44dc-be4e-9046a218e2e8	1470	Other Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
accf813a-b684-4e79-9332-6eee4604e114	1471	LVA - Other Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
672bfde1-344f-4744-bb2e-903d24068da2	1472	URA - Other Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e8926df2-d876-4a65-9426-3ce9470752ce	1473	ROU - Other Equipment	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
6ec7411e-136b-4e3f-ad70-59bb0bdd2add	1500	Vehicle	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
e0874a25-8a1b-4ad6-9b9a-b6ea5b53d803	1501	LVA - Vehicle	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
bd31ec5a-ea21-4612-84f7-d815a8bd6512	1502	URA - Vehicle	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
3088d802-5f41-4df9-8837-e79152e34bbc	1503	ROU - Vehicle	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
86cf80b2-593e-409c-8be7-6a8ad1721a07	2100	Intangible Asset	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
758ce361-3cef-49fb-bff6-4b775d9b995d	2101	LVA - Intangible Asset	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
b4aa407d-5aaa-43a2-bb67-2081d8c37fbe	2102	URA - Intangible Asset	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
f8aa4481-e005-4f93-89b1-c3fe3a26dcbd	2103	ROU - Intangible Asset	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
a96273ba-1aed-40da-a041-f9fa6fee84a4	3100	Invenstment Property	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
d4a30d97-c084-4e53-8214-cea4effcd27a	3101	LVA - Invenstment Property	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
8b7c6504-417c-4890-817d-5cca88021c66	3102	URA - Invenstment Property	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
87aace77-0be4-43a7-9dfe-7cb412bf0d94	3103	ROU - Invenstment Property	t	2025-10-24 14:55:39	2025-10-24 14:55:39	\N	A
\.


--
-- Data for Name: master_asset_type; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_asset_type (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: master_category; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_category (uuid, kode, name, kode_asset_type, status, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: master_category_2; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_category_2 (uuid, kode, name, status, kode_category, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: master_division; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_division (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
734465f8-6a74-4d02-b931-31c3b2b6f122	MSL	QUALITY SAFETY, SECURITY, HEALTH & ENVIRONMENT DIVISION	t	2025-12-11 10:39:43	2025-12-11 10:39:43	\N
8c3c6c73-6527-4d34-8461-245251f0c6b1	AIT	INTERNAL AUDIT DIVISION	t	2025-12-11 10:39:55	2025-12-11 10:39:55	\N
20f0d992-7399-410c-81e8-53d73d7b386b	SPR	CORPORATE SECRETARY DIVISION	t	2025-12-11 10:40:12	2025-12-11 10:40:12	\N
19f064e2-1d21-4a2c-8168-55e25b06bcad	SMR	CORPORATE STRATEGY & RISK MANAGEMENT DIVISION	t	2025-12-11 10:40:32	2025-12-11 10:40:32	\N
043350a9-693a-4445-bd8b-95d2ecf20902	BDV	BUSINESS DEVELOPMENT & COMMERCIAL DIVISION	t	2025-12-11 10:40:54	2025-12-11 10:40:54	\N
93ba10ec-d710-4470-9303-aa879901b7c3	OPL	OPERATION & SERVICES DIVISION	t	2025-12-11 10:41:14	2025-12-11 10:41:14	\N
38082f64-9879-495f-9b07-cc6726334199	PRS	INFRASTRUCTURE DIVISION	t	2025-12-11 10:41:33	2025-12-11 10:41:33	\N
03408c76-e296-4ec4-a271-3cf865474bf8	SAR	ROLLINGSTOCK DIVISION	t	2025-12-11 10:41:50	2025-12-11 10:41:50	\N
5127efef-66fb-4cff-8f8f-bc36f081b78c	SDM	HUMAN CAPITAL & GENERAL AFFAIR DIVISION	t	2025-12-11 10:42:07	2025-12-11 10:42:07	\N
789b6b24-5d33-4edd-979e-d3e70720382c	KAD	FINANCE & ACCOUNTING DIVISION	t	2025-12-11 10:42:25	2025-12-11 10:42:25	\N
b1fc8a1c-195b-4e6f-8a8a-ee287c74f3d7	SCM	SUPPLY CHAIN MANAGEMENT DIVISION	t	2025-12-11 10:42:42	2025-12-11 10:42:42	\N
3402cdf2-a483-45c9-a6f0-c06cda12331a	MIT	INFORMATION TECHNOLOGY DIVISION	t	2025-12-11 10:42:58	2025-12-11 10:42:58	\N
c8803a2e-32fe-4a97-a5ef-b3afdabd5df6	JPR	PT Jakarta Propertindo	t	2025-12-11 12:01:00	2025-12-11 12:01:00	\N
\.


--
-- Data for Name: master_group_category; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_group_category (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: master_location; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_location (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
55a74fc4-259e-48d7-9058-79c2e31413c3	STSN-00	All Station	t	2025-11-12 12:20:34	\N	\N
45198e0a-a591-4792-92a1-5b3be92da565	STSN-01	All Peron Station	t	2025-11-12 12:20:34	\N	\N
ff07b4a1-9812-4283-bef4-0481443de3c9	GATE-00	Gerbang Utama	t	2025-11-12 12:20:34	\N	\N
81c7477a-5296-4d37-8c19-ada31809b2d9	GATE-01	Pos Keamanan Gerbang Utama 	t	2025-11-12 12:20:34	\N	\N
1129ea10-5ca0-4bdf-b963-2db38a5f2317	WRHS-00	Warehouse G	t	2025-11-12 12:20:34	\N	\N
239fed6c-a824-4535-9048-2177bb41225c	WRHS-01	Warehouse H	t	2025-11-12 12:20:34	\N	\N
346ba2ba-7f10-41e3-8a6e-66893abe7965	WRHS-02	Warehouse I	t	2025-11-12 12:20:34	\N	\N
ca66f63a-4686-49cb-8d63-7dd28c7653a4	TEMP-00	Resto Nasi Tempong Indra	t	2025-11-12 12:20:34	\N	\N
314f2052-82b8-40ce-a798-c75014aae0c2	MSJD-00	Masjid Raudhatul Jannah LRT Jakarta	t	2025-11-12 12:20:34	\N	\N
1efdc9fe-c901-4454-8f77-ea58b150df04	BOCC-00	BOCC Building	t	2025-11-12 12:20:34	\N	\N
b7426bad-924c-4519-8060-ce56badfeab6	BOCC-01	BOCC UPS & Battery Room	t	2025-11-12 12:20:34	\N	\N
f11905c3-5697-40fe-a74d-4749c13b6b72	BOCC-02	BOCC Control Room	t	2025-11-12 12:20:34	\N	\N
18ab26b4-0ba2-4169-a565-e274fdea40f3	APSS-00	APS Building	t	2025-11-12 12:20:34	\N	\N
73a72c8a-02b3-4114-b83e-eb27b8e934fc	ATWP-00	ATWP Building	t	2025-11-12 12:20:34	\N	\N
970056f5-ea71-4dfd-a678-de9cec3f15fd	PORT-00	Portable	t	2025-11-12 12:20:34	\N	\N
55f6d6ce-6b1c-448b-860b-06b35fd0dfe0	SFWR-00	Intangible Asset	t	2025-11-12 12:20:34	\N	\N
8900ed55-13cb-4c67-be76-cad44bdfe363	MCC0-00	MCC Building	t	2025-11-12 12:20:34	\N	\N
4094b7e0-b851-4b4b-85cf-0409fe861c79	MCC1-01	MCC 1F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
ff3e9215-56b9-44a2-9690-e41b5ab5948d	MCC1-02	MCC 1F, Lobby	t	2025-11-12 12:20:34	\N	\N
daa1451d-48e2-4887-9516-e2f7f90b040b	MCC1-03	MCC 1F, Meeting Room	t	2025-11-12 12:20:34	\N	\N
92907aa4-4ed4-4068-ba3d-62a13a2fa034	MCC1-04	MCC 1F, Security Office	t	2025-11-12 12:20:34	\N	\N
203542b2-4b7d-4d3f-a9eb-01874e6bca22	MCC1-05	MCC 1F, Security CCTV Room	t	2025-11-12 12:20:34	\N	\N
5214eca8-1026-429a-93b0-1712dd2cde83	MCC1-06	MCC 1F, Security Meeting Room	t	2025-11-12 12:20:34	\N	\N
af1ee503-c586-46e1-b5d5-86e4a4a6ec4b	MCC1-07	MCC 1F, Storage SHE	t	2025-11-12 12:20:34	\N	\N
69a2f8e2-0da6-404c-a423-562ec62c5331	MCC1-08	MCC 1F, Terrace	t	2025-11-12 12:20:34	\N	\N
4a77074d-5170-42e4-8040-6753eaa38ac0	MCC1-09	MCC 1F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
61479634-4d88-45a6-8102-29111f6abc49	MCC1-10	MCC 1F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
df9af023-c17c-48ef-92c7-c773893651cf	MCC2-01	MCC 2F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
53677a50-5f99-4e86-ba53-2287de07dd0c	MCC2-02	MCC 2F, Koridor Depan	t	2025-11-12 12:20:34	\N	\N
52e3c396-07a9-44b2-808b-4ea1f483dd72	MCC2-03	MCC 2F, Meeting Room	t	2025-11-12 12:20:34	\N	\N
d86c3d63-5414-4f5a-9f1c-1c04553549d5	MCC2-04	MCC 2F, Storage GA	t	2025-11-12 12:20:34	\N	\N
70cb1b03-0f1c-4db4-92f2-7493cab4d808	MCC2-05	MCC 2F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
aaa74d79-d74a-4ac5-ba63-899e3b6d1c62	MCC2-06	MCC 2F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
989c2900-75ef-4110-8699-7a60fa9de4ef	MCC3-01	MCC 3F, Jakpro Office	t	2025-11-12 12:20:34	\N	\N
7814d6ee-b7b8-4a2a-ba62-20884f8018d8	MCC3-02	MCC 3F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
23d405dc-3f61-4844-8763-7aa4fa5c5f58	MCC3-03	MCC 3F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
121405c9-e42b-45a9-a522-9ca5604e5d80	MCC3-04	MCC 3F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
2d962f05-7ea4-43fb-b5c9-7852de0fcc1b	MCC4-01	MCC 4F, AFC Room	t	2025-11-12 12:20:34	\N	\N
8202d8df-e8d1-4919-a128-cced6f62c1a5	MCC4-02	MCC 4F, Breakout Room	t	2025-11-12 12:20:34	\N	\N
98efda0a-ff8e-4437-ab08-43910552380f	MCC4-03	MCC 4F, Busdev Room	t	2025-11-12 12:20:34	\N	\N
b2600aed-dc8e-40f2-896a-69af6904544c	MCC4-04	MCC 4F, Dinning Room	t	2025-11-12 12:20:34	\N	\N
e883f6a2-7c49-499c-b88f-1898e8b22300	MCC4-05	MCC 4F, Finance Room	t	2025-11-12 12:20:34	\N	\N
4ddb7b6a-aae5-405c-80f0-2ffd883a476f	MCC4-06	MCC 4F, IT Room	t	2025-11-12 12:20:34	\N	\N
9449067a-6f09-47fa-b9b0-5c5659c8a9da	MCC4-07	MCC 4F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
b44b4a20-6c1f-4e88-b4c0-f15d61ce80cb	MCC4-08	MCC 4F, Koridor Depan	t	2025-11-12 12:20:34	\N	\N
05c85c7f-3bef-4a2f-812d-1571fc5fdf36	MCC4-09	MCC 4F, Lobby	t	2025-11-12 12:20:34	\N	\N
eedc38f9-1955-423f-ba2d-24909dbd260c	MCC4-10	MCC 4F, Main Office 	t	2025-11-12 12:20:34	\N	\N
6d5e2dc9-bf5d-4d13-a383-5330a2cd1a48	MCC4-11	MCC 4F, R Meeting Labdagati	t	2025-11-12 12:20:34	\N	\N
67d2c3d6-dac8-4074-9813-b4a5b4f5b56f	MCC4-12	MCC 4F, R Meeting Reynor	t	2025-11-12 12:20:34	\N	\N
f7c86123-8525-4c4d-bbc0-afeb967f910e	MCC4-13	MCC 4F, SCM Room	t	2025-11-12 12:20:34	\N	\N
b88b76e5-b4c2-424c-a2a4-ae029e492631	MCC4-14	MCC 4F, SDM Room	t	2025-11-12 12:20:34	\N	\N
e59ac1a3-d482-4a49-b731-c7b756ab787a	MCC4-15	MCC 4F, Server room	t	2025-11-12 12:20:34	\N	\N
e560bba5-b8fe-40ef-831c-aa21d7bba3ff	MCC4-16	MCC 4F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
9310d268-74c9-49df-b60f-7a63c3fa0e47	MCC4-17	MCC 4F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
2776236a-5843-457b-9838-975b627d8b38	MCC4-18	MCC 4F, Pantry Room	t	2025-11-12 12:20:34	\N	\N
d205d9ce-2af5-4d22-8d5c-f2a25a84e613	MCC4-19	MCC 4F, R Meeting Lobby	t	2025-11-12 12:20:34	\N	\N
b7c10b28-70db-4411-8fa8-0bde8111a9c6	MCC4-20	MCC 4F, Storage ATK	t	2025-11-12 12:20:34	\N	\N
8b6b7e00-9cb7-493b-90d6-bc4841861423	MCC5-01	MCC 5F, Corcom Studio	t	2025-11-12 12:20:34	\N	\N
748ea30e-12b8-4c93-81e7-faac174b77e1	MCC5-02	MCC 5F, Corsec Room	t	2025-11-12 12:20:34	\N	\N
88eec860-e993-4c24-bc91-f27486448535	MCC5-03	MCC 5F, Dirkeu Room	t	2025-11-12 12:20:34	\N	\N
38072395-7708-4c91-ade3-135d5c7340af	MCC5-04	MCC 5F, Dirops Room	t	2025-11-12 12:20:34	\N	\N
cc410fad-fc1b-4a54-9cb7-824607ac7416	MCC5-05	MCC 5F, Dirut Room	t	2025-11-12 12:20:34	\N	\N
63575564-083f-46ee-9ede-d0d56d1a1520	MCC5-06	MCC 5F, Int Audit Room	t	2025-11-12 12:20:34	\N	\N
da87b204-22ba-49cd-b5c4-eb26ed1c7823	MCC5-07	MCC 5F, IT Storage	t	2025-11-12 12:20:34	\N	\N
2707ec2c-a524-4f34-9754-03176a1fc0e2	MCC5-08	MCC 5F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
79c97fe5-376d-419a-916e-22aefd693723	MCC5-09	MCC 5F, Koridor Depan	t	2025-11-12 12:20:34	\N	\N
d0b7f52a-a084-4dcb-84e5-403f0bf7a4e5	MCC5-10	MCC 5F, Koridor Direksi	t	2025-11-12 12:20:34	\N	\N
48c48819-a581-4dfd-9e5e-ad9d0447de64	MCC5-11	MCC 5F, Lounge	t	2025-11-12 12:20:34	\N	\N
2613e624-d0ff-4049-853c-36dfa75c2b89	MCC5-12	MCC 5F, R Meeting Direksi	t	2025-11-12 12:20:34	\N	\N
021abcab-f698-4012-bdc6-875246e51a96	MCC5-13	MCC 5F, Sekdir Room	t	2025-11-12 12:20:34	\N	\N
dbbab087-e1fb-42d6-bce6-9e5713df293c	MCC5-14	MCC 5F, Server room	t	2025-11-12 12:20:34	\N	\N
5aff8c25-3603-4f01-bfce-45509162d607	MCC5-15	MCC 5F, Server Telecom	t	2025-11-12 12:20:34	\N	\N
667e6dc3-afd9-447d-b1af-8ecc324f7017	MCC5-16	MCC 5F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
3c222aa8-4878-463a-80c0-779f349b2101	MCC5-17	MCC 5F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
3d8926bb-699b-4948-b685-39dadf056b62	MCC5-18	MCC 5F, Toilet Pria	t	2025-11-12 12:20:34	\N	\N
b28f0a2d-2beb-4387-be02-d9d15694a44f	MCC5-19	MCC 5F, Toilet Wanita	t	2025-11-12 12:20:34	\N	\N
451ae1f6-016d-45ae-aead-f45484b2a1d3	MCC6-01	MCC 6F, Koridor Belakang	t	2025-11-12 12:20:34	\N	\N
86e7db2c-4f4e-43eb-b32e-e266865695b9	MCC6-02	MCC 6F, Koridor Tengah	t	2025-11-12 12:20:34	\N	\N
e63ef568-f1f0-4640-949d-b9eaf9ec0941	MCC6-03	MCC 6F, Maintenance Room	t	2025-11-12 12:20:34	\N	\N
b989a755-6c36-462a-86dd-6b7a803786d8	MCC6-04	MCC 6F, OCC Room	t	2025-11-12 12:20:34	\N	\N
c1526aad-1b27-4b73-820f-41ce32a8ea8d	MCC6-05	MCC 6F, OCC Manager Room	t	2025-11-12 12:20:34	\N	\N
f9a9ce62-a06f-4302-8568-8655a09d7f72	MCC6-06	MCC 6F, OCC Meeting Room	t	2025-11-12 12:20:34	\N	\N
2840cbaf-dd0c-4fd0-87ef-4c351e7c07ad	MCC6-07	MCC 6F, Toilet Female	t	2025-11-12 12:20:34	\N	\N
5d22fb25-e339-471b-bc02-55e8f66db288	MCC6-08	MCC 6F, Toilet Male	t	2025-11-12 12:20:34	\N	\N
d72c8c2a-3046-455a-9d10-db4beb14c84c	MCC6-09	MCC 6F, Nursery Room	t	2025-11-12 12:20:34	\N	\N
4bffd7ba-1dfe-46e5-9a4b-0fbb61516570	MCC6-10	MCC 6F, Dormitory 	t	2025-11-12 12:20:34	\N	\N
9839789d-c15d-4f1e-b45c-a919d0198453	MCC6-11	MCC 6F, Fitness Room	t	2025-11-12 12:20:34	\N	\N
62c50c47-2d16-43a3-886f-6d482e199d9e	MCC6-12	MCC 6F, Janitor Room	t	2025-11-12 12:20:34	\N	\N
fc68776d-6bbd-40a9-9e63-461bc85eb303	MCC0-03	MCC, Elevator 1	t	2025-11-12 12:20:34	\N	\N
670f3c77-aa45-4f88-a73b-881f5293bd24	MCC0-04	MCC, Elevator 2	t	2025-11-12 12:20:34	\N	\N
cdb90092-f8df-4384-9c42-1a8d56d0c816	MCC0-05	MCC, Parking Area	t	2025-11-12 12:20:34	\N	\N
45c9c37e-a942-4a80-aae3-cca7a7a19ba2	MCC0-06	MCC, Basketball Court	t	2025-11-12 12:20:34	\N	\N
a75299b8-db8d-4ca6-a270-47dedce6f8d9	MCC0-07	MCC, Danau	t	2025-11-12 12:20:34	\N	\N
52e40bc0-1a00-45db-ad1f-8f2fc5dfe8e3	DEPO-00	DEPO Building	t	2025-11-12 12:20:34	\N	\N
ed694c15-57de-4668-80bc-349967a2db09	DEPO-01	DEPO, Dormitory ASP	t	2025-11-12 12:20:34	\N	\N
0808bd74-2020-42ec-b5ab-13c0727ccc15	DEPO-02	DEPO, Heavy Maintenance	t	2025-11-12 12:20:34	\N	\N
ac4ccc06-1b29-402e-b7c1-d5182b8f12e4	DEPO-03	DEPO, HM Brake Tester Room	t	2025-11-12 12:20:34	\N	\N
58235a5b-1598-4dc0-977a-f526df3925a7	DEPO-04	DEPO, HM Compressor Tester Room	t	2025-11-12 12:20:34	\N	\N
98b23b06-20b4-4aa4-89b4-5dbb74d159bb	DEPO-05	DEPO, HM Copler tester Room	t	2025-11-12 12:20:34	\N	\N
a905f2b8-bbed-4967-9747-f5b2ba90a7ca	DEPO-06	DEPO, HM Jalbang Storage Room	t	2025-11-12 12:20:34	\N	\N
fcecc2ff-cf6a-4dcb-baf9-c44beb37ea3c	DEPO-07	DEPO, HM Line 1	t	2025-11-12 12:20:34	\N	\N
81f2ad65-39e3-4304-8940-0b7e80c919f7	DEPO-08	DEPO, HM Line 2	t	2025-11-12 12:20:34	\N	\N
8800210e-ff39-4632-ba6d-d885429278d3	DEPO-09	DEPO, HM Line 3	t	2025-11-12 12:20:34	\N	\N
6c13844a-c37e-46af-a236-e04403630447	DEPO-10	DEPO, HM Line 4	t	2025-11-12 12:20:34	\N	\N
9ec96d07-388b-4a8a-8088-b79bc66ab929	DEPO-11	DEPO, HM SIV tester Room	t	2025-11-12 12:20:34	\N	\N
80457258-545c-492d-ab2c-eb3283c6ddce	DEPO-12	DEPO, HM Tools Storage Room	t	2025-11-12 12:20:34	\N	\N
514526c8-2651-46d9-a6bd-b7b7a8b775b8	DEPO-13	DEPO, HM Trailer MRV Room	t	2025-11-12 12:20:34	\N	\N
76170af7-d53c-49f0-b77a-c5359fdbaf46	DEPO-14	DEPO, HM VAC tester Room	t	2025-11-12 12:20:34	\N	\N
9863a5c4-7ff7-426c-b1a7-17f4a147fd66	DEPO-15	DEPO, Jalbang Storage Jaring	t	2025-11-12 12:20:34	\N	\N
25427215-d470-4488-b6e4-4181298b8b95	DEPO-16	DEPO, Light Maintenance	t	2025-11-12 12:20:34	\N	\N
cf452fec-bd3e-4fdb-8ef5-9fbf8966cc3c	DEPO-17	DEPO, LM Line 5	t	2025-11-12 12:20:34	\N	\N
c9c78c36-1aaf-422d-a46e-7327c5e9f0a9	DEPO-18	DEPO, LM Line 7	t	2025-11-12 12:20:34	\N	\N
88c33959-39cc-4a87-9fa0-1de4b154506b	DEPO-19	DEPO, LM Line 8	t	2025-11-12 12:20:34	\N	\N
4e9bfa16-8924-4bed-95bd-cecedf542509	DEPO-20	DEPO, Mechanical Room	t	2025-11-12 12:20:34	\N	\N
e89e5642-f983-4cb1-9f79-5860e9d0c75e	DEPO-21	DEPO, Poskes	t	2025-11-12 12:20:34	\N	\N
00f5d780-84cf-4c34-b9e7-39633a2be009	DEPO-22	DEPO, Jalbang Office	t	2025-11-12 12:20:34	\N	\N
1c9ad834-836e-4e00-8e15-ec0033736ebb	DEPO-23	DEPO, R6 Pintu Masuk	t	2025-11-12 12:20:34	\N	\N
00f171e5-794f-42e5-9aa1-743585c4fca7	DEPO-24	DEPO, SAR - FPM Office	t	2025-11-12 12:20:34	\N	\N
9e15713b-cb1e-4296-a5e4-190a6e76a9ec	DEPO-25	DEPO, SAR - PRP Office	t	2025-11-12 12:20:34	\N	\N
9abc5a6d-79fe-498c-8142-60f9842c2860	DEPO-26	DEPO, SAR - RSN (MWS) Office	t	2025-11-12 12:20:34	\N	\N
16b81dc0-c994-4aa4-8389-38a9ce106bb2	DEPO-27	DEPO, SAR Division Office	t	2025-11-12 12:20:34	\N	\N
4578ccf9-cdca-48b0-8f9b-8ab1be822213	DEPO-28	DEPO, Test Track (Stabling)	t	2025-11-12 12:20:34	\N	\N
0c9ee407-4600-4f2b-86cf-d4f3e5803ddf	DEPO-29	DEPO, TM tester	t	2025-11-12 12:20:34	\N	\N
14e97ad5-7a43-4442-841c-6481f765da81	DEPO-30	DEPO, Warehouse A	t	2025-11-12 12:20:34	\N	\N
02bb80ce-bddd-447e-b453-fdec637029bc	DEPO-31	DEPO, Warehouse B	t	2025-11-12 12:20:34	\N	\N
645b4be7-5e85-4e77-b45d-16aea33da45b	DEPO-32	DEPO, Warehouse C	t	2025-11-12 12:20:34	\N	\N
f6cf3e66-6469-4596-95c9-88b82ec220cf	DEPO-33	DEPO, Warehouse D	t	2025-11-12 12:20:34	\N	\N
06649b4a-a3da-4e21-b4f4-55d4ca9ef10b	DEPO-34	DEPO, Warehouse E	t	2025-11-12 12:20:34	\N	\N
2e91f826-d15d-413d-864e-adda47692a9a	DEPO-35	DEPO, Warehouse F	t	2025-11-12 12:20:34	\N	\N
20ef47fe-0c3e-4452-a2c2-5f2b144d6827	DEPO-36	DEPO, Warehouse Office	t	2025-11-12 12:20:34	\N	\N
c71227c3-2daa-4c05-b7ad-1b0468631274	DEPO-37	DEPO, Workshop TDL	t	2025-11-12 12:20:34	\N	\N
d57c438d-95a9-4b84-a802-bf98a3104b20	DEPO-38	DEPO, Prasarana Storage	t	2025-11-12 12:20:34	\N	\N
1a831720-232f-4d3d-90d2-af1b1b2f0078	PGD0-00	PGD Pegangsaan Dua Station	t	2025-11-12 12:20:34	\N	\N
b42ba360-ab90-423c-a513-a964bd1c7083	PGD1-01	PGD 1F, Entrance Selatan	t	2025-11-12 12:20:34	\N	\N
526f17cd-79f1-4358-a5a9-5f39b5a80b11	PGD1-02	PGD 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
a6514967-c0bf-4491-a9a4-828d38ba5f55	PGD1-03	PGD 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
170f01a0-0f3a-42bf-923e-8cb274dc8d03	PGD1-04	PGD 1F, Entrance Utara	t	2025-11-12 12:20:34	\N	\N
40f62f71-4b7b-4117-ab46-a20ff9f313ac	PGD2-01	PGD 2F, Peron	t	2025-11-12 12:20:34	\N	\N
fbe8ebf9-517e-4906-a4f8-e3a422f9d09e	PGD2-02	PGD 2F, BOH 1	t	2025-11-12 12:20:34	\N	\N
f493436a-69a6-4cd2-b991-6190f00161de	PGD2-03	PGD 2F, BOH 2	t	2025-11-12 12:20:34	\N	\N
da95be48-2b1b-473b-8314-437cf36a93bd	PGD3-01	PGD 3F, Assesment Room ASP	t	2025-11-12 12:20:34	\N	\N
9e2e9bbf-a22d-4f6c-bc5b-94487147328f	PGD3-02	PGD 3F, Coordination Room	t	2025-11-12 12:20:34	\N	\N
68ca318d-c89a-426d-9eac-bad03c195522	PGD3-03	PGD 3F, Entrance Gate Selatan	t	2025-11-12 12:20:34	\N	\N
57b4adae-163d-4a91-9422-44040f54e1a4	PGD3-04	PGD 3F, Entrance Gate Utara	t	2025-11-12 12:20:34	\N	\N
0c21b259-12a5-432c-9feb-7f83977204c7	PGD3-05	PGD 3F, Gudang ASP	t	2025-11-12 12:20:34	\N	\N
e8e6fb90-bba8-455b-8cd7-71accfe74ea7	PGD3-06	PGD 3F, Koridor Selatan	t	2025-11-12 12:20:34	\N	\N
dbade73e-f5db-41a2-80da-73ef9632be87	PGD3-07	PGD 3F, Koridor Utara	t	2025-11-12 12:20:34	\N	\N
3ecbd469-bbc3-4e51-bf17-05fb37b5d61b	PGD3-08	PGD 3F, Management Room ASP	t	2025-11-12 12:20:34	\N	\N
4ecb4c1b-7c42-4373-80d0-a2ee011d0027	PGD3-09	PGD 3F, PAO Selatan	t	2025-11-12 12:20:34	\N	\N
9fb097f7-1df7-4f36-aae3-66e7b7cbc0ee	PGD3-10	PGD 3F, PAO Utara	t	2025-11-12 12:20:34	\N	\N
875c198e-a259-48a5-97db-778ea7d8d919	PGD3-11	PGD 3F, Peron	t	2025-11-12 12:20:34	\N	\N
d2873d25-fb5a-492a-8979-9defab0b8eeb	PGD3-12	PGD 3F, Poskes	t	2025-11-12 12:20:34	\N	\N
40a0b8ba-9413-4de6-a345-76e4248cb21f	PGD3-13	PGD 3F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
17b64749-4e63-4691-b7dd-f4230a920938	PGD3-14	PGD 3F, Ruang Keamanan	t	2025-11-12 12:20:34	\N	\N
f03ab3c9-3d52-4317-8133-498674a71676	PGD3-15	PGD 3F, BOH 3	t	2025-11-12 12:20:34	\N	\N
dbd76448-6f9f-423e-8f46-0b00ab8008bf	PGD0-01	PGD, Elevator Utara	t	2025-11-12 12:20:34	\N	\N
51f66f05-e480-46ca-a92e-5a8099b82040	PGD0-02	PGD, Elevator Selatan	t	2025-11-12 12:20:34	\N	\N
bb9d576b-09c1-432c-bebd-8cfc9aaa1ada	PGD0-03	PGD, Elevator Peron Tengah	t	2025-11-12 12:20:34	\N	\N
bdc50757-ddad-423d-95fa-5a58b1ad7453	PGD0-04	PGD, Parking Area 	t	2025-11-12 12:20:34	\N	\N
1e1fdd92-7df5-4709-a897-aa080453afe4	PGD0-05	PGD, Pedestrian 	t	2025-11-12 12:20:34	\N	\N
92154f16-bc31-4ca3-92ca-afe8ce6c69d1	BVU0-00	BVU Boulevard Utara Station	t	2025-11-12 12:20:34	\N	\N
9627b006-e4d0-42ec-9fc2-2b39d0ad263a	BVU1-01	BVU 1F, Entrance Barat, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
b4bba82b-0508-4bb8-bc5c-b473ed24f4e9	BVU1-02	BVU 1F, Entrance Barat, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
676c7b1b-2881-4642-b2a1-1d2c6b3bedc7	BVU1-03	BVU 1F, Entrance Timur, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
09371d26-dce0-4545-84d3-186bd37670ab	BVU1-04	BVU 1F, Entrance Timur, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
e12cf532-dd2e-43de-a571-08fccc21f102	BVU1-05	BVU 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
02879ce2-3b2f-46bd-8d43-ceddaf424fe8	BVU1-06	BVU 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
70eafcae-da66-4eec-b05d-a3c4922e0363	BVU1-07	BVU 1F, BOH 1	t	2025-11-12 12:20:34	\N	\N
f3d13193-c09e-4990-ac7b-861e142f566d	BVU1-08	BVU 1F, BOH 2	t	2025-11-12 12:20:34	\N	\N
169fbd9d-8207-4d83-80c5-aa896661f1b5	BVU1-09	BVU 1F, BOH 3	t	2025-11-12 12:20:34	\N	\N
7803150b-f0f3-4a2a-8619-ef9cf95ca2c6	BVU2-01	BVU 2F, Entrance Gate Barat	t	2025-11-12 12:20:34	\N	\N
3eb76205-f276-4662-802b-d4b4c005d8e9	BVU2-02	BVU 2F, Entrance Gate Timur	t	2025-11-12 12:20:34	\N	\N
4e794c5d-8bde-4691-9f0b-36b7bf653829	BVU2-03	BVU 2F, PAO Barat	t	2025-11-12 12:20:34	\N	\N
95fbb58d-3dbd-4ea9-9192-0c410c506b62	BVU2-04	BVU 2F, PAO Timur	t	2025-11-12 12:20:34	\N	\N
523ec68a-87ba-4758-b407-af73e5c0af63	BVU2-05	BVU 2F, Peron Barat	t	2025-11-12 12:20:34	\N	\N
d907c110-f5e8-4e3e-bffa-38dd1d9c0259	BVU2-06	BVU 2F, Peron Timur	t	2025-11-12 12:20:34	\N	\N
3e823686-e4b5-4a9e-972e-4ea5459320cb	BVU2-07	BVU 2F, Poskes	t	2025-11-12 12:20:34	\N	\N
4efdde12-4608-4384-af5b-9dcff5a2e5dd	BVU2-08	BVU 2F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
a135f6cc-f7e7-4828-a158-463212134c58	BVU3-01	BVU 3F	t	2025-11-12 12:20:34	\N	\N
80528204-4e18-473e-b410-3502af67ba52	BVU0-01	BVU Elevator Barat	t	2025-11-12 12:20:34	\N	\N
eaa6a0f4-98b2-41ed-873c-caf0b7c04e3e	BVU0-02	BVU Elevator Timur	t	2025-11-12 12:20:34	\N	\N
d696c487-87c6-4aea-a90b-fe4ce5fe5ece	BVS0-00	BVS Boulevard Selatan Station	t	2025-11-12 12:20:34	\N	\N
bb7f5ab0-b82c-48b7-affe-b23885663c98	BVS1-01	BVS 1F, Entrance Barat, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
48d8b22f-b3ed-4919-a170-9d0213b5a54f	BVS1-02	BVS 1F, Entrance Barat, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
ba417757-8f5d-462d-bc59-c5679c7ccd98	BVS1-03	BVS 1F, Entrance Timur, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
68dfd612-e21a-44ac-afbc-9872c9b3147e	BVS1-04	BVS 1F, Entrance Timur, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
c41e3922-8cea-41d9-8641-8b94f3acecc1	BVS1-05	BVS 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
ce867c56-d5ee-422a-a8e7-c7ba8d7f826e	BVS1-06	BVS 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
23d898ee-afe6-4c9b-b3c4-5a73241a0f87	BVS1-07	BVS 1F, BOH 1	t	2025-11-12 12:20:34	\N	\N
de721c8e-e2f5-41f0-822d-3833f3d67186	BVS1-08	BVS 1F, BOH 2	t	2025-11-12 12:20:34	\N	\N
457bb46e-5b68-418e-8b4b-23d398000660	BVS1-09	BVS 1F, BOH 3	t	2025-11-12 12:20:34	\N	\N
868b1519-4873-4193-a641-faede72d319c	BVS2-01	BVS 2F, Entrance Gate Barat	t	2025-11-12 12:20:34	\N	\N
e7d40e2f-7b72-408e-bcc8-9f3eb6a97f6e	BVS2-02	BVS 2F, Entrance Gate Timur	t	2025-11-12 12:20:34	\N	\N
d5c82318-8dc7-4ae0-a870-fcd570a61c40	BVS2-03	BVS 2F, PAO Barat	t	2025-11-12 12:20:34	\N	\N
34515d0c-3b57-4bad-93f2-d5d0acaac1f7	BVS2-04	BVS 2F, PAO Timur	t	2025-11-12 12:20:34	\N	\N
60d7955b-2287-499b-b2cf-bc431411b494	BVS2-05	BVS 2F, Peron Barat	t	2025-11-12 12:20:34	\N	\N
7011aeec-42a2-4cb6-97e1-a4a932790c9d	BVS2-06	BVS 2F, Peron Timur	t	2025-11-12 12:20:34	\N	\N
ee1f369b-f436-4b46-9e43-97d251da38d2	BVS2-07	BVS 2F, Poskes	t	2025-11-12 12:20:34	\N	\N
37223db1-b0a1-44d5-8f26-1abb6026c73f	BVS2-08	BVS 2F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
334a0877-33bf-4a4c-adcd-50b647ecb6e3	BVS3-01	BVS 3F	t	2025-11-12 12:20:34	\N	\N
de2746f4-a9e8-4eef-9c04-44eb3f046971	BVS0-01	BVS Elevator Barat	t	2025-11-12 12:20:34	\N	\N
ae541532-311b-4bfa-a9df-2967cb5470ca	BVS0-02	BVS Elevator Timur	t	2025-11-12 12:20:34	\N	\N
04c410f2-865c-4213-8d40-0384087d94bb	PUM0-00	PUM Pulo Mas Station	t	2025-11-12 12:20:34	\N	\N
21779565-8bf4-4006-83ad-00f6edfe5a7f	PUM1-01	PUM 1F, Entrance Barat, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
5a992812-3206-4b66-8d79-14dd269a7717	PUM1-02	PUM 1F, Entrance Barat, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
5aee18fa-d246-431f-9e37-fbcf97282771	PUM1-03	PUM 1F, Entrance Timur, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
d969f172-4d20-4c31-97be-ba444ee0f6de	PUM1-04	PUM 1F, Entrance Timur, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
2cf8de3f-927a-4c2a-bee0-cdcee985dd37	PUM1-05	PUM 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
b7f81336-4412-426a-950b-647a65f7a19a	PUM1-06	PUM 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
25dc409a-f137-4ece-a3dd-3f298f52c433	PUM1-07	PUM 1F, BOH 1	t	2025-11-12 12:20:34	\N	\N
633ad6f6-0a8c-4ae2-9dc0-8f0fd2dd4778	PUM1-08	PUM 1F, BOH 2	t	2025-11-12 12:20:34	\N	\N
4285d167-417b-4e9a-b3bf-2d420f2977df	PUM1-09	PUM 1F, BOH 3	t	2025-11-12 12:20:34	\N	\N
eaf1ae0c-03ad-462c-93b5-1c1baf6654ac	PUM2-01	PUM 2F, Entrance Gate Barat	t	2025-11-12 12:20:34	\N	\N
dd2f8fd0-aee0-438e-baa7-4e7bff21502c	PUM2-02	PUM 2F, Entrance Gate Timur	t	2025-11-12 12:20:34	\N	\N
63e103e0-aa82-4539-84de-f0d80d58f453	PUM2-03	PUM 2F, PAO Barat	t	2025-11-12 12:20:34	\N	\N
609c29a4-287e-4432-983b-edfa66e366a7	PUM2-04	PUM 2F, PAO Timur	t	2025-11-12 12:20:34	\N	\N
c5b9471c-66e9-48ca-8e91-c1b0609fd806	PUM2-05	PUM 2F, Peron Barat	t	2025-11-12 12:20:34	\N	\N
572a4386-2255-4fc6-b058-22cb7d10d066	PUM2-06	PUM 2F, Peron Timur	t	2025-11-12 12:20:34	\N	\N
4520b896-d223-499e-9ce9-62a7734e6756	PUM2-07	PUM 2F, Poskes	t	2025-11-12 12:20:34	\N	\N
93f59d8d-fe15-4a99-aa5d-1d2854715480	PUM2-08	PUM 2F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
221b2886-1c87-445e-8c08-defb194649f2	PUM3-01	PUM 3F	t	2025-11-12 12:20:34	\N	\N
9c217daa-69c1-4c2b-8eeb-3f350e4fb10c	PUM0-01	PUM Elevator Barat	t	2025-11-12 12:20:34	\N	\N
d5a62ecd-1019-49b4-9e0d-5a1916f38f2d	PUM0-02	PUM Elevator Timur	t	2025-11-12 12:20:34	\N	\N
a5b109c1-6a9b-4814-ac48-4f68b40c40f3	EQS0-00	EQS Equestrian Station	t	2025-11-12 12:20:34	\N	\N
9f780d16-774f-4ac1-b973-f1c37c42cf23	EQS1-01	EQS 1F, Entrance Barat, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
e1942951-72d8-4d78-a784-dc3227ec6591	EQS1-02	EQS 1F, Entrance Barat, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
dc5d9d6b-d03a-40db-822e-38b352af7f9f	EQS1-03	EQS 1F, Entrance Timur, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
a7241a19-27cf-4921-9e79-c20fa34a9c3a	EQS1-04	EQS 1F, Entrance Timur, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
b16222eb-f2c6-412d-b603-bfe0c3d467fb	EQS1-05	EQS 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
8e8d5074-f6fa-464b-8e5c-79bc1ccbf575	EQS1-06	EQS 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
43485de6-0803-4212-adba-f8685c70c022	EQS1-07	EQS 1F, BOH 1	t	2025-11-12 12:20:34	\N	\N
74ee8f76-08f2-416f-8940-91f291f7b2dc	EQS1-08	EQS 1F, BOH 2	t	2025-11-12 12:20:34	\N	\N
467a6030-634f-4c63-a0e9-4f57b007cd71	EQS1-09	EQS 1F, BOH 3	t	2025-11-12 12:20:34	\N	\N
bb6b974d-f1dc-4347-a9ab-e91e8fa5e2f7	EQS2-01	EQS 2F, Entrance Gate Barat	t	2025-11-12 12:20:34	\N	\N
2cc51c1e-5dd9-4851-9769-3ab0a5118e95	EQS2-02	EQS 2F, Entrance Gate Timur	t	2025-11-12 12:20:34	\N	\N
bce28782-1bb8-43f8-a8b1-e57536954d27	EQS2-03	EQS 2F, PAO Barat	t	2025-11-12 12:20:34	\N	\N
e9ea7719-3c78-4807-b856-930ed56bac0a	EQS2-04	EQS 2F, PAO Timur	t	2025-11-12 12:20:34	\N	\N
a095edc6-9988-4039-9e8d-8571f812e961	EQS2-05	EQS 2F, Peron Barat	t	2025-11-12 12:20:34	\N	\N
56ae7cbb-4cbe-42f0-9e21-6e9000928367	EQS2-06	EQS 2F, Peron Timur	t	2025-11-12 12:20:34	\N	\N
c773a768-7fc3-4ea8-b588-63483a438073	EQS2-07	EQS 2F, Poskes	t	2025-11-12 12:20:34	\N	\N
e8958d50-d4fa-4275-9e1e-6797ef4f138a	EQS2-08	EQS 2F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
062248d1-0df4-46a9-ab3d-bee1cd796a4b	EQS3-01	EQS 3F	t	2025-11-12 12:20:34	\N	\N
dce5dac9-825e-498f-b5fc-4fd1376a5d28	EQS0-01	EQS Elevator Barat	t	2025-11-12 12:20:34	\N	\N
ef180dfe-d95e-4794-975f-94fd6af1bfaf	EQS0-02	EQS Elevator Timur	t	2025-11-12 12:20:34	\N	\N
5d5ca960-8ce6-4ba4-b19e-105e070a9945	VEL0-00	VEL Velodrom Station	t	2025-11-12 12:20:34	\N	\N
a5088fa0-fd47-496d-a978-08c3c56c9fbb	VEL1-01	VEL 1F, Entrance Barat, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
84cc43c7-01dd-4978-b9e5-d499218cfe8d	VEL1-02	VEL 1F, Entrance Barat, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
47b678a5-a054-4441-8f4b-6d91ff4124b1	VEL1-03	VEL 1F, Entrance Timur, Sisi Selatan	t	2025-11-12 12:20:34	\N	\N
dc590462-1afa-45df-818d-2edce9c819a0	VEL1-04	VEL 1F, Entrance Timur, Sisi Utara	t	2025-11-12 12:20:34	\N	\N
138e2186-642d-4dc3-9ebf-d9cfd08c8de3	VEL1-05	VEL 1F, Parkir Sepeda	t	2025-11-12 12:20:34	\N	\N
90b53cf3-1614-447e-b494-29e6b8cce7d4	VEL1-06	VEL 1F, Pos Keamanan	t	2025-11-12 12:20:34	\N	\N
ceb3fb87-6790-472a-9bbe-65c1a6ea908f	VEL1-07	VEL 1F, BOH 1	t	2025-11-12 12:20:34	\N	\N
c11529d6-8e99-4e1b-97c8-13ea9cffb650	VEL1-08	VEL 1F, BOH 2	t	2025-11-12 12:20:34	\N	\N
efd65b01-282f-4a8e-9939-3efa9f36a51a	VEL1-09	VEL 1F, BOH 3	t	2025-11-12 12:20:34	\N	\N
7b5aa062-5920-463f-b152-d4a489db3a83	VEL2-01	VEL 2F, Entrance Gate Barat	t	2025-11-12 12:20:34	\N	\N
46e58caa-f0c7-4578-983f-12a3bea5d39e	VEL2-02	VEL 2F, Entrance Gate Timur	t	2025-11-12 12:20:34	\N	\N
ebf08fb0-313a-47ef-b2ec-71cdd6533041	VEL2-03	VEL 2F, PAO Barat	t	2025-11-12 12:20:34	\N	\N
b7337095-f92c-48b1-8c7f-e4cd91cb334c	VEL2-04	VEL 2F, PAO Timur	t	2025-11-12 12:20:34	\N	\N
9a2a84f3-c615-4c9a-9ef1-93e50a58809f	VEL2-05	VEL 2F, Peron Barat	t	2025-11-12 12:20:34	\N	\N
c3bf41da-0699-4a4f-924c-70066efa057b	VEL2-06	VEL 2F, Peron Timur	t	2025-11-12 12:20:34	\N	\N
ae5c9954-92df-4350-a29f-2365c62859e7	VEL2-07	VEL 2F, Poskes	t	2025-11-12 12:20:34	\N	\N
b632be99-7a27-494e-b282-e10b87c543d1	VEL2-08	VEL 2F, R. Kepala Stasiun	t	2025-11-12 12:20:34	\N	\N
991c3290-f37b-419e-80a2-bb0a8253a4c0	VEL3-01	VEL 3F, VIP Room	t	2025-11-12 12:20:34	\N	\N
dded90c9-5b94-4cc3-ae83-3dd383e20835	VEL0-01	VEL Elevator Barat	t	2025-11-12 12:20:34	\N	\N
b256d4e6-fcbc-4e2f-a595-e14dfd5f7584	VEL0-02	VEL Elevator Timur	t	2025-11-12 12:20:34	\N	\N
c623d00f-88b9-4b5c-9810-b02b599bc2a7	mcc_t	testing	t	2025-12-08 17:39:14	2025-12-09 09:52:18	2025-12-09 09:52:18
\.


--
-- Data for Name: master_menu; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_menu (uuid, kode, name, sort_order, status, created_at, updated_at, deleted_at, actions) FROM stdin;
d9862c48-264e-4e02-a4ee-952fd6b58f07	MASTER_DATA	Master Data	2	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","U","D"]
611cf464-b5e7-4c12-9b81-1f8f3bd49474	USER_MGMT	User Management	3	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["R","U"]
275ea155-50ca-4b6d-a946-8b4a26650ed5	ASSETS	Assets	5	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","U","D"]
197b4cae-1fcf-4fd2-b335-f51a3b1da7db	DEPRECIATION	Depreciation	6	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R"]
9da966b7-f08e-46e1-996d-f6eed4171033	MOVEMENT	Movement	9	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","U","D","APR"]
a0eaaee8-98bf-43b3-958c-d8a8d259b736	TRASH	Trash	14	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["R","U","D"]
bf5a2509-07f4-4282-ba02-d1d681f85c1a	DISPOSAL	Disposal	10	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","U","D", "APR"]
9cb3f51c-5f8b-4a35-91fb-d3839befaabe	STOCK_OPN	Stock Opname	12	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R"]
11e8e2a9-d37d-4f5c-93b4-50aa64d13c5b	RETURN	Return	11	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","D"]
429cec35-4b62-43c8-88df-eea2fac3e3e3	TRANSFER	Transfer Value	8	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R","U","D","APR"]
fdfcf397-c74d-4aa1-90d4-a21528061beb	ACQUISITION	Acquisition	7	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["C","R"]
951dc24c-bd06-4a20-b2a0-a4fc145c5779	REPORTING	Reporting	13	t	2025-11-10 14:21:09	2025-11-10 14:21:09	\N	["R"]
\.


--
-- Data for Name: master_role; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_role (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
c8f6127a-5993-427d-8c7b-5797ac70fffc	SYSADMIN	System Administrator	t	2025-11-10 14:05:30	2025-11-21 16:50:05	\N
5e0d513f-19ff-4d5b-84e8-3cad4ab9c49c	AM_ADMIN	Asset Management Admin	t	2025-11-10 14:05:30	2025-12-03 10:40:16	\N
f42b61ee-c65a-4662-8c28-3cf605718d31	AM_HEAD	Asset Management Head	t	2025-11-10 14:05:30	2025-12-03 10:41:15	\N
6a6962ee-8878-438b-affb-a37f4353885f	DEPT_HEAD	User - Department Head	t	2025-11-10 14:05:30	2025-12-03 10:46:12	\N
b59bd014-1bc0-4f0e-9da0-157d0b7a1255	DEPT_USER	User Departemen	t	2025-11-10 14:05:30	2025-12-08 11:51:25	\N
8d012067-abee-453c-a3c5-286dc8b7987f	AUDITOR	EXTERNAL	t	2025-11-10 14:05:30	2025-12-11 11:10:19	\N
\.


--
-- Data for Name: master_role_menu; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_role_menu (uuid, role_kode, menu_kode, actions, status, created_at, updated_at, deleted_at) FROM stdin;
5c30abfe-b6b0-4afa-9039-98b724e9ab74	SYSADMIN	MASTER_DATA	["C", "R", "U", "D"]	t	2025-11-10 16:02:10	2025-11-21 16:50:05	\N
c660e0b6-d003-4943-9335-067165160767	SYSADMIN	USER_MGMT	["R", "U"]	t	2025-11-10 14:26:17	2025-11-21 16:50:05	\N
115ee2c7-ebf7-4353-84ad-afb5c81cb655	SYSADMIN	ASSETS	["C", "R", "U", "D"]	t	2025-11-10 16:34:00	2025-11-21 16:50:05	\N
473304c4-0652-4b75-a578-e3579cdc5e23	SYSADMIN	DEPRECIATION	["C", "R"]	t	2025-11-11 09:28:13	2025-11-21 16:50:05	\N
21854ab4-cc40-4e6b-a478-4e00aa30364c	SYSADMIN	MOVEMENT	["C", "R", "U", "D", "APR"]	t	2025-11-10 17:25:00	2025-11-21 16:50:05	\N
986fb011-bad0-4d60-9af2-d3f28a12a04e	SYSADMIN	TRASH	["R", "U", "D"]	t	2025-11-11 09:31:22	2025-11-21 16:50:05	\N
0135f57e-db40-48c6-bd6a-1fa956e4730a	SYSADMIN	DISPOSAL	["C", "R", "U", "D", "APR"]	t	2025-11-10 17:46:34	2025-11-21 16:50:05	\N
adf81ab0-d944-49b0-9277-257c4439234f	SYSADMIN	STOCK_OPN	["C", "R"]	t	2025-11-10 18:34:06	2025-11-21 16:50:05	\N
ba2c7b68-0610-465e-a08d-08c0f5056cf4	SYSADMIN	RETURN	["C", "R", "D"]	t	2025-11-10 18:02:56	2025-11-21 16:50:05	\N
ab866370-5308-42c4-8b8a-09facaa25da6	SYSADMIN	TRANSFER	["C", "R", "U", "D", "APR"]	t	2025-11-10 18:16:04	2025-11-21 16:50:05	\N
aed7d7d6-0a7f-49c3-8181-b8b5be046724	SYSADMIN	ACQUISITION	["C", "R"]	t	2025-11-10 17:08:44	2025-11-21 16:50:05	\N
42804bda-b2f9-4f30-bf21-35128a672c3e	SYSADMIN	REPORTING	["R"]	t	2025-11-21 16:50:05	2025-11-21 16:50:05	\N
f1faf9a6-b9c9-4592-ba18-5ae3d0b70312	AM_ADMIN	MASTER_DATA	["C", "R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
1aa5d3b2-0724-4da1-a93a-cb4ef7127277	AM_ADMIN	USER_MGMT	["R", "U"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
c6b7be54-d579-4107-bbc5-488117c46727	AM_ADMIN	ASSETS	["C", "R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
c6397076-2c4d-4ef8-83a3-24ac8c0a3cca	AM_ADMIN	DEPRECIATION	["C", "R"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
e3fe6a1b-b141-412a-8cc5-8ac502b3699d	AM_ADMIN	MOVEMENT	["C", "R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
bbb4b17e-34a5-4b49-a9a0-e7e96dc40c65	AM_ADMIN	TRASH	["R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
083ca721-13ff-4690-8bb2-2a6213c6efd2	AM_ADMIN	DISPOSAL	["C", "R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
786a07aa-cbd7-46e8-8026-9de57629cdc6	AM_ADMIN	STOCK_OPN	["C", "R"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
fe47cba3-79db-4e1b-892c-0571feed16b4	AM_ADMIN	RETURN	["C", "R", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
25dba86e-40f7-48d4-87f6-5ab32c414063	AM_ADMIN	TRANSFER	["C", "R", "U", "D"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
6905a9a1-a553-4ffd-8bab-f7b153668e35	AM_ADMIN	ACQUISITION	["C", "R"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
9ecb7b56-86fd-4f79-b0f3-386bc3f8f324	AM_ADMIN	REPORTING	["R"]	t	2025-12-03 10:40:09	2025-12-03 10:40:16	\N
427ad65c-15d1-458e-9494-a628f3876f59	AM_HEAD	MASTER_DATA	["C", "R", "U", "D"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
8d0bf89c-28ac-4b1a-a396-019841629112	AM_HEAD	USER_MGMT	["R", "U"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
ef48565a-babf-4dd6-a985-78edd8bba666	AM_HEAD	ASSETS	["C", "R", "U", "D"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
f659c37c-ea89-4fc1-9473-a111c0552339	AM_HEAD	DEPRECIATION	["C", "R"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
0212a0b4-1e4e-44d3-ae7d-2feb7e395d90	AM_HEAD	MOVEMENT	["C", "R", "U", "D", "APR"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
c55e593f-75a8-448c-89e8-b2c0acd403a9	AM_HEAD	TRASH	["R", "U", "D"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
53614690-844c-4ccc-ac71-e0a690cc0c3a	AM_HEAD	DISPOSAL	["C", "R", "U", "D", "APR"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
3e307751-9438-4104-a217-8503cb645daf	AM_HEAD	STOCK_OPN	["C", "R"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
a21b4bb7-b43d-4dd0-a28a-e7a8b15027f6	AM_HEAD	RETURN	["C", "R", "D"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
afe9f974-3509-4454-93f5-89b1c9f1bd6d	AM_HEAD	TRANSFER	["C", "R", "U", "D", "APR"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
f35ea2f8-e6d0-4aae-982f-d2d041a8e4ce	AM_HEAD	ACQUISITION	["C", "R"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
c6826dd9-3863-4757-ad00-322ed6f92027	AM_HEAD	REPORTING	["R"]	t	2025-12-03 10:41:15	2025-12-03 10:41:15	\N
21d26122-0e02-473a-b8bf-38c07200cf5d	DEPT_HEAD	MASTER_DATA	["R"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
ff966f8b-a79d-4c1b-b62a-d6b09d5452a1	DEPT_HEAD	ASSETS	["R"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
e58eb5c3-d3d5-497f-88a4-c26f66a9af0b	DEPT_HEAD	MOVEMENT	["R", "APR"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
c02e6373-c4ab-449b-849a-ebfb593d063a	DEPT_HEAD	DISPOSAL	["R", "APR"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
60bdd83b-e09c-47f5-b25f-a0488276f8fa	DEPT_HEAD	STOCK_OPN	["R"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
4305a8df-655c-42d5-a751-04168d13aca3	DEPT_HEAD	TRANSFER	["R", "APR"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
dd5a59a3-5757-4663-b193-bcda91de952b	DEPT_HEAD	ACQUISITION	["R"]	t	2025-12-03 10:43:13	2025-12-03 10:46:12	\N
e65c0c19-a436-4b0d-a1c4-7e120eab9a8c	DEPT_USER	TRANSFER	["C", "R"]	t	2025-12-03 10:45:36	2025-12-04 15:40:59	2025-12-04 15:40:59
52a3fae8-c533-4bf5-b90f-7e4737aa6517	DEPT_USER	REPORTING	["R"]	t	2025-12-03 10:45:36	2025-12-04 15:42:45	\N
0f292196-ef54-4a6d-af36-d51b42ef78b0	DEPT_USER	MASTER_DATA	["R"]	t	2025-12-03 10:45:36	2025-12-08 11:51:25	\N
dd574868-7d4f-49fb-bd26-0cc15c75ef7e	DEPT_USER	USER_MGMT	["R"]	t	2025-12-03 10:45:36	2025-12-08 11:51:25	\N
ed509112-f8fe-4527-9a11-568155fe6cd5	DEPT_USER	ASSETS	["R"]	t	2025-12-03 10:45:36	2025-12-08 11:51:25	\N
fa65689f-f57d-4655-baf1-563e58c34bf9	DEPT_USER	MOVEMENT	["C", "R"]	t	2025-12-03 10:45:36	2025-12-08 11:51:25	\N
8d4b02e9-0b2c-45fe-9fe0-26913d850ff1	DEPT_USER	DISPOSAL	["C", "R"]	t	2025-12-03 10:45:36	2025-12-08 11:51:25	\N
06301358-e24b-41f0-8ac3-5371acec8762	AUDITOR	MASTER_DATA	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
8e216230-db5f-49f2-a6d0-9d2544236c8b	AUDITOR	USER_MGMT	["R"]	t	2025-12-11 11:10:05	2025-12-11 11:10:19	2025-12-11 11:10:19
7f1d1348-3d9a-4ab4-958d-d1151c5f2bca	AUDITOR	ASSETS	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
eaffb36b-e505-4b5a-ae73-8b1d57ada340	AUDITOR	DEPRECIATION	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
b915d2a9-8a2b-402e-a3cc-08d5b415fc74	AUDITOR	MOVEMENT	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
33779aca-854c-42fb-9a14-547d4df0dcfd	AUDITOR	TRASH	["R"]	t	2025-12-11 11:10:05	2025-12-11 11:10:19	2025-12-11 11:10:19
92bb107f-9aec-41f0-9da5-b29ca3be2bf3	AUDITOR	DISPOSAL	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
00a277a7-e12f-404e-a47e-9c03dc23c8f6	AUDITOR	STOCK_OPN	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
5f29a33f-6dda-472b-b0c3-088c3cccaf4a	AUDITOR	RETURN	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
e01db5bf-c464-4a28-a311-f421c1503639	AUDITOR	TRANSFER	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
7b179ba1-6c5c-4584-8e94-3b043f72a11c	AUDITOR	ACQUISITION	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
c405302c-ee2e-4149-ad5a-a5b09a7e794a	AUDITOR	REPORTING	["R"]	t	2025-12-03 10:42:13	2025-12-11 11:10:19	\N
\.


--
-- Data for Name: master_status; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_status (uuid, kode, name, type, status, created_at, updated_at, deleted_at) FROM stdin;
156d097f-5eba-4939-9b5c-b302027f1696	OPE	Operation	Asset	t	2025-10-23 10:15:37	2025-10-23 10:15:37	\N
e2a5efeb-30be-4f8a-b4ab-2b55b948e2bd	DIS	Disposal	Asset	t	2025-10-23 10:15:45	2025-10-23 10:15:45	\N
fe0814dc-28fc-4481-bfc5-e149aa53ee7d	IDL	Idle	Asset	t	2025-10-23 10:15:54	2025-10-23 10:15:54	\N
d74cc228-fd98-40ae-9d43-7ebd64d5aa51	RPR	Repair	Asset	t	2025-10-23 10:16:06	2025-10-23 10:16:06	\N
809f5494-0556-41eb-8512-0c315f6e0b2f	APR	Waiting for Approval	Transfer	t	2025-10-23 10:16:18	2025-10-23 10:16:18	\N
a873e4df-4582-466b-98ea-5822171125ad	RET	Returned	Return	t	2025-10-23 10:16:51	2025-10-23 10:17:06	\N
d12d6230-2d37-4814-a807-e303f5283998	ACC	Accepted	Transfer	t	2025-10-23 10:16:28	2025-10-23 10:17:17	\N
6f69d010-3ca1-43fe-a65f-81eebcd54ebf	REJ	Rejected	Transfer	t	2025-10-23 10:16:41	2025-10-23 10:17:30	\N
ab019554-541d-4387-a1a8-4718845d087c	DMG	Damage	Asset	t	2025-12-11 10:01:16	2025-12-11 10:01:16	\N
f875276d-3b64-49e3-9efb-f906202ed9ce	STS-1	Active 1	Asset	t	2025-10-23 10:06:22	2025-12-11 10:43:33	2025-12-11 10:43:33
24d5e7cb-613a-4df9-a0b0-c187992ef26b	MIS	Missing	Asset	t	2025-12-11 19:43:23	2025-12-11 19:43:48	\N
0cdd8240-b892-4e30-988e-c0534fe4f64a	INA	Inactive Asset	Asset	t	2025-12-19 11:57:07	2025-12-19 11:57:07	\N
edbdb483-e450-46b6-8520-7daaf1fe493d	CON	Consumable	Asset	t	2025-12-20 16:12:37	2025-12-20 16:12:37	\N
\.


--
-- Data for Name: master_sub_category; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_sub_category (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: master_sumber; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_sumber (uuid, name, status, created_at, updated_at, deleted_at, kode) FROM stdin;
c8503732-f936-4aea-b592-01b38aa1920e	Maximo	t	2025-10-23 11:05:30+08	2025-10-23 11:05:30+08	\N	MXM
c9e5c550-eb9e-440e-a0fb-5cfc45f08c75	dfd	t	2025-12-01 14:10:29+08	2025-12-01 14:10:29+08	\N	dfd
7354a5dd-5616-4203-8b0c-5f661f0e9ac2	ERP DYNAMICS 365	t	2025-10-27 12:40:33+08	2025-12-11 11:28:03+08	\N	DYM
b332c6de-5aeb-42af-b29e-756e5d98f188	EXCEL UPLOAD	t	2025-10-27 12:43:54+08	2025-12-11 11:28:43+08	\N	EXC
e1f680db-4dfb-4b80-86bc-8c1ae9563410	Directly Registration by Website	t	2025-11-12 12:47:15+08	2025-12-11 11:29:33+08	\N	REG
\.


--
-- Data for Name: master_transaction; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_transaction (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
de7563af-4f55-4752-a25a-7554d661d84c	A	LRT Jakarta	t	2025-10-23 09:14:29	2025-10-23 09:14:29	\N
b8e95e3e-3c26-4e8e-855a-1f5d5ed00254	J	JAKPRO	t	2025-10-23 09:14:39	2025-10-23 09:14:39	\N
cf42bf42-0cd6-42e9-a23d-f2955bf2f170	M	MRT Jakarta	t	2025-12-11 10:43:58	2025-12-11 10:44:40	2025-12-11 10:44:40
8bbeeba1-059f-4478-84e9-296902fd2019	test pentest	<iframe onload=alert('xss') onclick=alert('xss')>	t	2025-12-16 11:08:52	2025-12-24 14:03:38	2025-12-24 14:03:38
\.


--
-- Data for Name: master_uom; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_uom (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
c03d717f-7210-451a-a7e1-b485b06971ae	EA	Each	t	2025-10-27 11:32:45	2025-10-27 11:32:45	\N
4d169380-5d84-4b5a-944a-85d9abc84bdc	SET	Set	t	2025-10-30 13:48:25	2025-10-30 13:48:25	\N
aea27054-67e9-4007-a9fb-ae52af3b221d	PCS	Pieces	t	2025-11-03 13:33:33	2025-11-03 13:33:33	\N
47e980db-2905-4344-934c-bdb5944652f0	LOT	lot	t	2025-11-03 13:33:57	2025-11-03 13:33:57	\N
740a4dfe-34d9-4ad7-b07d-25d755da9d3f	PAIR	Pair	t	2025-11-03 13:34:13	2025-11-03 13:34:13	\N
b46372bf-dede-46ef-a8ca-deca2a339e6a	KIT	kit	t	2025-11-03 13:34:24	2025-11-03 13:34:24	\N
786926df-18dd-4603-8d8c-88abc087e17a	PKG	Package	t	2025-11-03 13:34:41	2025-11-03 13:34:41	\N
d90a810a-ea36-4467-bf68-31fd09430461	BDL	Bundle	t	2025-11-03 13:35:10	2025-11-03 13:35:10	\N
4468b446-bea1-4d24-9231-4fd59b474b62	ROLL	Roll	t	2025-11-03 13:36:58	2025-11-03 13:36:58	\N
673fcea1-dad0-4df7-875a-ebf0a1bcb9a6	UN	Unit	t	2025-11-03 13:33:20	2025-11-03 14:17:13	\N
\.


--
-- Data for Name: master_user_code; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.master_user_code (uuid, kode, department, description, status, created_at, updated_at, deleted_at, kode_division) FROM stdin;
4478b1b7-2e7b-4a81-af61-bf81b27955d3	DRU	MAIN DIRECTORATE	MAIN DIRECTORATE	t	2025-10-27 12:13:04	\N	\N	\N
3079191a-7962-43e7-a65c-d9d450e07c4a	MSL	QUALITY SAFETY, SECURITY, HEALTH & ENVIRONMENT DIVISION	QUALITY SAFETY, SECURITY, HEALTH & ENVIRONMENT DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
794f712b-9158-470b-afae-6a8f4d20069b	MTU	QUALITY ASSURANCES DEPARTMENT	QUALITY ASSURANCES DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
bad192a4-68d2-4bf0-a2c9-458211ccaa19	MKL	SAFETY, HEALTH & ENVIRONMENT DEPARTMENT	SAFETY, HEALTH & ENVIRONMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
51b3f5bf-2a96-4bf7-8676-d4ce135b07f5	KAM	SECURITY DEPARTMENT	SECURITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
1979561c-543d-434b-8938-64ebbf0cb1cd	AIT	INTERNAL AUDIT DIVISION	INTERNAL AUDIT DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
4cef0d2a-459f-444e-b558-2b775def4404	SPR	CORPORATE SECRETARY DIVISION	CORPORATE SECRETARY DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
6170c63f-5336-4169-9486-cc305a3aabfe	HKM	LEGAL DEPARTMENT CORPORATE	LEGAL DEPARTMENT CORPORATE	t	2025-10-27 12:14:15	\N	\N	\N
8933c318-7f44-4680-99c2-005f0cad1d8e	KOM	COMMUNICATION DEPARTMENT	COMMUNICATION DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
4758787e-0cb5-4a45-bd40-aa4779e519ba	KAP	SECRETARIAT & ADMINISTRATION DEPARTMENT	SECRETARIAT & ADMINISTRATION DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
80c54ab2-7ec0-423d-9f81-991e78c103c5	SMR	CORPORATE STRATEGY & RISK MANAGEMENT DIVISION	CORPORATE STRATEGY & RISK MANAGEMENT DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
ebe71575-990b-47de-8273-67e3f6fd5b1e	PBI	BUSINESS PROCESS MANAGEMENT & INNOVATION DEPARTMENT	BUSINESS PROCESS MANAGEMENT & INNOVATION DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
7a371fd9-39cb-4d74-9c37-ce859b160e17	MRK	RISK MANAGEMENT & COMPLIANCE DEPARTMENT	RISK MANAGEMENT & COMPLIANCE DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
eadaf213-19ec-45fb-a208-c4ab80a8d24c	RPH	CORPORATE PLANNING DEPARTMENT	CORPORATE PLANNING DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
52268aa3-5eff-4720-897f-4f61e1125ae9	DOP	OPERATION & DEVELOPMENT DIRECTORATE	OPERATION & DEVELOPMENT DIRECTORATE	t	2025-10-27 12:14:15	\N	\N	\N
e4be0e31-c817-419a-a211-9665f7203965	BDV	BUSINESS DEVELOPMENT & COMMERCIAL DIVISION	BUSINESS DEVELOPMENT & COMMERCIAL DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
621360c4-6dd2-4916-9074-a404d198fc29	BEX	BUSINESS DEVELOPMENT DEPARTMEN	BUSINESS DEVELOPMENT DEPARTMEN	t	2025-10-27 12:14:15	\N	\N	\N
25fbd8a2-51c9-4fea-a0ee-ce38ea58436f	BKR	COMMERCIAL DEPARTMENT	COMMERCIAL DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
fa762033-8496-42c2-a84a-64096ec062a7	OPL	OPERATION & SERVICES DIVISION	OPERATION & SERVICES DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
84c6b6b4-2680-434e-b363-000be7e78792	POP	OPERATION CONTROL DEPARTMENT	OPERATION CONTROL DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
5d675759-de56-4a98-a6f4-6b5a66144e96	ASP	TRAIN CREW DEPARTMENT	TRAIN CREW DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
7b3b2874-e709-4b46-9680-447ac86e5505	PEL	SERVICES DEPARTMENT	SERVICES DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
a13043cf-9fc8-4ac3-8c90-21a50e70e888	KPL	CUSTOMER ENGAGEMENT DEPARTMENT	CUSTOMER ENGAGEMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
5f976f99-6a75-4055-afa7-ada797d3dccc	PRS	INFRASTRUCTURE DIVISION	INFRASTRUCTURE DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
f26f67fc-225d-4d61-b56f-ed61c119f42e	RMP	INFRASTRUCTURE ENGINEERING & QUALITY DEPARTMENT	INFRASTRUCTURE ENGINEERING & QUALITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
79b03d65-6546-44c6-b09d-35e01b213e66	FOP	INFRASTRUCTURE OPERATION FACILITY DEPARTMENT	INFRASTRUCTURE OPERATION FACILITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
cfc58d41-ffef-4b81-a5b6-90c964b3440d	JLB	INFRASTRUCTURE TRACK & BUILDING DEPARTMENT	INFRASTRUCTURE TRACK & BUILDING DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
12b6f281-673c-4248-9af8-15a33fc89a15	SAR	ROLLINGSTOCK DIVISION	ROLLINGSTOCK DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
834e0753-7e91-4090-93bc-cb5a8e797067	PRP	ROLLING STOCK PLANNING & QUALITY DEPARTMENT	ROLLING STOCK PLANNING & QUALITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
f03e30b1-a089-4192-8f2e-59402679bb1f	RSN	ROLLING STOCK MAINTENANCE DEPARTMENT	ROLLING STOCK MAINTENANCE DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
20297c59-dae9-4a98-8ab6-b3cc25b567f6	FPM	ROLLING STOCK MAINTENANCE FACILITY DEPARTMENT	ROLLING STOCK MAINTENANCE FACILITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
ba1f7330-01c3-45ba-a998-769ec4ec6708		FINANCIAL & BUSINESS SUPPORT DIRECTORATE	FINANCIAL & BUSINESS SUPPORT DIRECTORATE	t	2025-10-27 12:14:15	\N	\N	\N
15b54083-cc92-4f90-9f14-b8de2d2ba4e5	SDM	HUMAN CAPITAL & GENERAL AFFAIR DIVISION	HUMAN CAPITAL & GENERAL AFFAIR DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
d8dfd288-9e18-4178-ae5c-67d3614d4ff7	PDM	HUMANCAPITAL DEVELOPMENT DEPARTMENT	HUMANCAPITAL DEVELOPMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
a48c5cef-9a6c-4e01-971f-038aaca8445a	LDM	HUMANCAPITAL SERVICES DEPARTMENT	HUMANCAPITAL SERVICES DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
a2345a64-7cf7-4f50-912b-d1cd3a20e841	BUM	GENERAL AFFAIR DEPARTMENT	GENERAL AFFAIR DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
360eb462-3cc6-48d6-8800-8469a474e371	KAD	FINANCE & ACCOUNTING DIVISION	FINANCE & ACCOUNTING DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
498728e3-de4f-4569-bbcd-93a755d0fcb5	SBP	SUBSIDY & BUDGETING DEPARTMENT	SUBSIDY & BUDGETING DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
7c9ffcc0-3138-4fdc-b209-f47445a655a9	AKP	ACCOUNTING & TAXATION DEPARTMENT	ACCOUNTING & TAXATION DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
89b255a7-25a0-4e45-93b9-5adb26cb753e	KDA	FINANCE & TREASURY DEPARTMENT	FINANCE & TREASURY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
bde860bb-afd5-4bda-9156-58aab7c133ed	SCM	SUPPLY CHAIN MANAGEMENT DIVISION	SUPPLY CHAIN MANAGEMENT DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
ccbfe255-c579-4c2c-ae46-24c553244c75	PGD	PROCUREMENT DEPARTMENT	PROCUREMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
37d9bd26-2856-4213-b551-3483bd5fbe76	WRH	ASSET & INVENTORY MANAGEMENT DEPARTMENT	ASSET & INVENTORY MANAGEMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
a2a0a185-c5e1-4639-b9a1-922d2c925b92	MIT	INFORMATION TECHNOLOGY DIVISION	INFORMATION TECHNOLOGY DIVISION	t	2025-10-27 12:14:15	\N	\N	\N
b16ebec0-31fa-4f7b-8ea5-18e37e57fa3a	DIT	IT SYSTEM PLANNING & DEVELOPMENT DEPARTMENT	IT SYSTEM PLANNING & DEVELOPMENT DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
6ba49dec-9faa-488b-b729-38e7d992f312	OIT	IT OPERATION & SERVICES DEPARTMENT	IT OPERATION & SERVICES DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
b31c9b8d-a3d0-47af-85e4-20c22f94508c	KIT	IT INFRASTRUCTURE & SECURITY DEPARTMENT	IT INFRASTRUCTURE & SECURITY DEPARTMENT	t	2025-10-27 12:14:15	\N	\N	\N
e49179c6-2b55-4235-b841-670b3caa5af3	ADV	BOD ADVISORY	BOD ADVISORY	t	2025-10-27 12:14:15	\N	\N	\N
61749758-9918-4834-8da6-7f65734fb4c2	JPRO	Jakpro	Aset Milik PT Jakarta Propertindo	t	2025-12-11 13:36:58	2025-12-11 13:36:58	\N	JPR
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: easymain_u_lrtj
--

COPY public.migrations (id, migration, batch) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
74	user	2	api	56a5403da86575ecf5e6cb6fce9f4b2c47a3f828ed395ad5ef955d7bbb27623c	["*"]	2025-11-26 11:08:09	\N	2025-11-26 11:07:59	2025-11-26 11:08:09
62	user	2	api	314aa211202c8b0eeffa8327ff834d718faa11bf7ffad426983773c7eb9d3616	["*"]	2025-11-10 13:08:51	\N	2025-11-10 13:08:40	2025-11-10 13:08:51
63	user	2	api	c0817ea3397109fb740e9115e3d2ece5db92791262015cdc018b68f32b14e9e2	["*"]	2025-11-10 13:43:58	\N	2025-11-10 13:43:24	2025-11-10 13:43:58
64	user	2	api	addb80b6bed9d89cc131b69c4ae4abe7d3c1672a50828885728af98734482180	["*"]	2025-11-10 20:47:41	\N	2025-11-10 20:47:41	2025-11-10 20:47:41
65	user	2	api	c8c8549500e892639aec2c4a8ebf32c88e2eef8c5111ee1fb78f44d5cd7931b3	["*"]	2025-11-11 11:59:42	\N	2025-11-11 11:59:39	2025-11-11 11:59:42
66	user	2	api	ea4b7b88a396419c653774303bfc873ab96a94b6c00a16e75f545f882f859711	["*"]	\N	\N	2025-11-11 20:30:25	2025-11-11 20:30:25
91	user	2	api	d413983b1c54ddecffcaa949069bd39cabb36efe796f81b392d31bb58bf39cf1	["*"]	2025-11-26 14:30:43	\N	2025-11-26 14:28:22	2025-11-26 14:30:43
71	user	2	api	111aad821d75345a8a28e54344b2b745c467b3262c5c1629797f3a80ffb4ce93	["*"]	2025-11-26 10:21:24	\N	2025-11-26 10:19:11	2025-11-26 10:21:24
69	user	2	api	96512ff89f9375f6d1d243dd0c5dd862ea2de0c77bc2d59d7f795f2aed4b0117	["*"]	2025-11-25 11:55:15	\N	2025-11-25 11:53:25	2025-11-25 11:55:15
95	user	2	api	44bae4067b24ae19cf0369d92a93480ae3c0ec22a82769f912ad9b3c2dbd5478	["*"]	2025-11-26 16:14:37	\N	2025-11-26 16:11:22	2025-11-26 16:14:37
67	user	2	api	f80d62f03344982410685bdaa58226d0d2dd5dc71ff2e47e7640e38b6348bbba	["*"]	2025-11-25 10:38:51	\N	2025-11-25 10:38:05	2025-11-25 10:38:51
75	user	2	api	8cd73816513a1e47c8b40e49f95cac882e5ac7125d8672de88052383a01a190d	["*"]	2025-11-26 11:10:24	\N	2025-11-26 11:10:07	2025-11-26 11:10:24
89	user	2	api	b66993923c2765959fa35b9ede71d09b935a68b73293f8d3ac194e13e5c576db	["*"]	2025-11-26 14:23:17	\N	2025-11-26 14:23:04	2025-11-26 14:23:17
68	user	2	api	063cebc955a59e7af54fa3cbdfb347af2088c6fbbed214900b9f62b0f8738a52	["*"]	2025-11-25 11:52:10	\N	2025-11-25 11:50:33	2025-11-25 11:52:10
76	user	2	api	47b25c272928bf1667f1cd515d500f0744c5601846124529f10d1454a447db91	["*"]	2025-11-26 11:16:57	\N	2025-11-26 11:16:43	2025-11-26 11:16:57
77	user	2	api	2d4eef7f209243de817f24d89636ffb3d05aa58af955756ef8d32d2ae1883abd	["*"]	2025-11-26 11:18:50	\N	2025-11-26 11:18:44	2025-11-26 11:18:50
72	user	2	api	35f867b365261db44eb12dbee33f6888eb6d1c1938f4721cc938c01cc1ce91aa	["*"]	2025-11-26 10:21:54	\N	2025-11-26 10:21:36	2025-11-26 10:21:54
100	user	2	api	bb2568836df2357a662c370f45b14e32d0184115d3fa192ed1564bf98496d3f5	["*"]	2025-11-27 16:09:51	\N	2025-11-27 16:09:46	2025-11-27 16:09:51
78	user	2	api	fdfc4024d13e1ac82385dfc5a50ae7299fd7234a347f3d652bc7637adb1c574f	["*"]	2025-11-26 11:32:01	\N	2025-11-26 11:28:59	2025-11-26 11:32:01
70	user	2	api	6bb40c8d27778b507f3dc71bcd470fb2ce844c5eeb9e88e265433a521f4a4be3	["*"]	2025-11-26 10:22:38	\N	2025-11-26 10:10:15	2025-11-26 10:22:38
93	user	2	api	f19ca45646f2e45585c391c139bec27e02e6ef7e8c06688c27545baee565f473	["*"]	2025-11-26 14:43:47	\N	2025-11-26 14:43:23	2025-11-26 14:43:47
90	user	2	api	5a632552e4bcf7907eb4e938e0dffbbd8300fcaee4ee03d3149e5a9861c4b181	["*"]	2025-11-26 14:27:41	\N	2025-11-26 14:25:56	2025-11-26 14:27:41
79	user	2	api	b265037b88b1805fc5e91801b4a78559d38d0e53249260813d31defe58fee4a9	["*"]	2025-11-26 11:36:50	\N	2025-11-26 11:36:43	2025-11-26 11:36:50
88	user	2	api	46a0fa1269834340426172af5b32138999a401e7169999b1463aa15f2afb0a57	["*"]	2025-11-26 14:20:55	\N	2025-11-26 14:20:30	2025-11-26 14:20:55
73	user	2	api	96e1f313f81140aaf670c70f984491c38441a3d9d839c4fac14f95d3e2acd620	["*"]	2025-11-26 11:04:39	\N	2025-11-26 11:04:25	2025-11-26 11:04:39
84	user	2	api	153d74abbdf621026f6381fd986d9b38f4a80ba87e7c638abbc437d0ea732108	["*"]	2025-11-26 13:53:03	\N	2025-11-26 13:52:55	2025-11-26 13:53:03
80	user	2	api	1e97af5bab51c27a629de5d84b765b1a5953e8d7699d5789bb802a4c478ac3f5	["*"]	2025-11-26 12:46:36	\N	2025-11-26 12:46:30	2025-11-26 12:46:36
85	user	2	api	5ec349bce184e5495ff16f3332d845e8366f260d0d6f0f7f18da17db91e30f98	["*"]	2025-11-26 13:54:14	\N	2025-11-26 13:54:11	2025-11-26 13:54:14
81	user	2	api	f9a6426fe912c640cd21234c7ce690591628e38a83486e33ea4a654fff89bc2a	["*"]	2025-11-26 12:58:48	\N	2025-11-26 12:57:18	2025-11-26 12:58:48
83	user	2	api	5662f9e2cca9b91ccb6d1043dca2215b7ec60c82968743ca21b00c29d8e899dc	["*"]	2025-11-26 14:10:04	\N	2025-11-26 13:46:25	2025-11-26 14:10:04
82	user	2	api	fbfed6b830b72d60bf981fcd40d7d098b1369b314c44cb8e294a0a2fa2a90acf	["*"]	2025-11-26 13:18:19	\N	2025-11-26 13:03:45	2025-11-26 13:18:19
86	user	2	api	41a82102c3a57b04e13355df282d4e9703d2457c83006c4d245607062c72fafd	["*"]	2025-11-26 14:14:14	\N	2025-11-26 14:14:12	2025-11-26 14:14:14
97	user	2	api	f904fa0526cd407c238f14bbe958d03966e8bcf0523ac009b46a5eef1b714d0b	["*"]	2025-11-27 15:28:17	\N	2025-11-27 15:27:08	2025-11-27 15:28:17
87	user	2	api	e607e9bca850351dbba7bf2ac8421019bcb9e7927911dcd99db1b74405b29df9	["*"]	2025-11-26 14:17:01	\N	2025-11-26 14:15:49	2025-11-26 14:17:01
92	user	2	api	a7fde6786dfa95b5a924245c02c6b481ce008aa492a169c7dc5c350c36ae9513	["*"]	2025-11-26 14:31:52	\N	2025-11-26 14:31:25	2025-11-26 14:31:52
101	user	2	api	3caf0f1a4736bf339d63bedc716cd78caba4cd8365116217ff5be1cbfe0d05c1	["*"]	2025-11-27 16:26:58	\N	2025-11-27 16:26:53	2025-11-27 16:26:58
98	user	2	api	33e7472f3d8b8ed5fccfa4535c5db3730a8e336b372cae0bac0026d93ece2494	["*"]	2025-11-27 15:47:39	\N	2025-11-27 15:43:11	2025-11-27 15:47:39
99	user	2	api	1fca174742f616df3d87296d223db12df222d3a6cc533414d3b3c0cf1ac574be	["*"]	2025-11-27 15:53:53	\N	2025-11-27 15:53:12	2025-11-27 15:53:53
94	user	2	api	3045dc3a3ca74529b151be3f55a09539474b50eb96dc255d261589782a6f70cd	["*"]	2025-11-26 14:53:08	\N	2025-11-26 14:52:46	2025-11-26 14:53:08
96	user	2	api	dda037f763511de628309cff35aacbe92ece3d6ff41bc884dcb83f43b2a02d89	["*"]	2025-11-27 15:13:59	\N	2025-11-27 15:13:30	2025-11-27 15:13:59
102	user	2	api	2cb7e9f9df9ed43e94fa8da1d3a8d0b2a0dea65ff65460c84a5a40c435809e85	["*"]	2025-11-27 16:30:31	\N	2025-11-27 16:29:30	2025-11-27 16:30:31
103	user	2	api	7d2b0ad458322b55d01e45049d6523f98b99bb8627276d75be4f7e33f487baee	["*"]	2025-11-27 16:40:22	\N	2025-11-27 16:39:34	2025-11-27 16:40:22
104	user	2	api	f8e895d76d534db2a947971dd1027310cb9e8c4a5d059a6d0a957bcafb748319	["*"]	2025-11-27 16:44:58	\N	2025-11-27 16:44:15	2025-11-27 16:44:58
105	user	2	api	473e4a4f179fa56c7454b3a790955ba15c78e468b241baf75516d486e4fb7eef	["*"]	2025-11-27 16:46:53	\N	2025-11-27 16:46:45	2025-11-27 16:46:53
106	user	2	api	25e0ddec72d26b4e60db5b1eb1b14721d9ed3c5ba2eb8336ec634f8fc3444b55	["*"]	2025-11-28 08:39:36	\N	2025-11-28 08:38:18	2025-11-28 08:39:36
107	user	2	api	fa21bdf0e9d4aa7ff1463a5777a2796ac2c37480dac1162070996109d0a68cd8	["*"]	2025-11-28 08:44:51	\N	2025-11-28 08:44:42	2025-11-28 08:44:51
115	user	2	api	d9c54a551b6cc5c72466b0aceea7a13c8d91b97018b1a7a2683b0516ce9a6a54	["*"]	2025-11-28 09:23:59	\N	2025-11-28 09:23:44	2025-11-28 09:23:59
122	user	2	api	fb8869fd0e99f0dfc4a628c95c2c7f78af5c1412114553f422ed13d0083a8d55	["*"]	2025-11-28 14:27:10	\N	2025-11-28 14:11:15	2025-11-28 14:27:10
123	user	2	api	a1a25beb52210fe8f183d201ac15c62b241761cc00b00e0cfe9eeac47827c81f	["*"]	2025-11-28 14:30:43	\N	2025-11-28 14:30:37	2025-11-28 14:30:43
116	user	2	api	76d2d46980984e50cd8557e2a0427a17614d8d02a63de9442e57e97f318af329	["*"]	2025-11-28 09:26:52	\N	2025-11-28 09:26:42	2025-11-28 09:26:52
113	user	2	api	6aee5008e02913753a32131f98d3d1092bd52acc4b25957f76119595d7ef4522	["*"]	2025-11-28 09:02:44	\N	2025-11-28 09:02:30	2025-11-28 09:02:44
142	user	2	api	d35808a656dec0e1523277ab3f627242465a03c8306242c55bb51fd5f0873a25	["*"]	2025-12-01 10:54:14	\N	2025-12-01 10:53:51	2025-12-01 10:54:14
138	user	2	api	b7ed5976969275a14c23b799d10eb10ca9e62b32df8857f29822e6a371d58fb6	["*"]	2025-11-28 17:21:42	\N	2025-11-28 17:19:16	2025-11-28 17:21:42
117	user	2	api	f4e085934f8b1ab6e3b12844487539bbe4b647e5341b68d3fb3528d2eeca943c	["*"]	2025-11-28 10:43:30	\N	2025-11-28 10:40:40	2025-11-28 10:43:30
143	user	2	api	6bad1bf8c4c6dd745014821782d2facc7be8ab86303c099257f6250e68b003f6	["*"]	2025-12-01 10:56:46	\N	2025-12-01 10:56:23	2025-12-01 10:56:46
129	user	2	api	add628bc9a9ee59b89799aeb21f5df560bca1210c71c78d6c1dac1227ef8ec32	["*"]	2025-11-28 15:14:01	\N	2025-11-28 15:12:04	2025-11-28 15:14:01
110	user	2	api	b02b639688d66c771e37d7cf7b679fef955b28ce570eb0d2c2c62a00fa52f2e8	["*"]	2025-11-28 08:53:38	\N	2025-11-28 08:52:40	2025-11-28 08:53:38
111	user	2	api	79dfd917ec9ccd07322e377fcd8eafb0b86b7e7739b7d19e42ed27409734abde	["*"]	2025-11-28 08:57:04	\N	2025-11-28 08:57:01	2025-11-28 08:57:04
124	user	2	api	836b077b9d9defcab2d9b99dc2f8ad69dda3f41012a1953017142bf961b2f085	["*"]	2025-11-28 14:54:01	\N	2025-11-28 14:52:42	2025-11-28 14:54:01
151	user	2	api	5879cf55ff75cb0f4f206d05d9375a7193893c516a5d960efc85396d30ffcf86	["*"]	2025-12-01 15:53:42	\N	2025-12-01 11:34:06	2025-12-01 15:53:42
108	user	2	api	3112312b52ddec2c1213314abf2e52d12551a2905633982cff9c2096a2d666d2	["*"]	2025-11-28 08:49:43	\N	2025-11-28 08:45:42	2025-11-28 08:49:43
131	user	2	api	e7962a2c9e07fcd38d8fcd499819b8c6fb998e7265059b2b71bae1e10b984b8b	["*"]	2025-11-28 15:19:49	\N	2025-11-28 15:19:17	2025-11-28 15:19:49
125	user	2	api	07e85d8f2622832b98809d2a7f06fa3a4fba48ff23b958d798da00c5c0a8f645	["*"]	2025-11-28 14:58:34	\N	2025-11-28 14:58:30	2025-11-28 14:58:34
118	user	2	api	e38e9419d3c3048b3c14c3dc6c0a665ae60380dd5bb99b2846b778482706fbd5	["*"]	2025-11-28 13:16:09	\N	2025-11-28 13:12:58	2025-11-28 13:16:09
114	user	2	api	cfeef18acd43b03e9e32a43cf319058e7f1879e1698be2aab0bf5b9de5528efa	["*"]	2025-11-28 09:14:45	\N	2025-11-28 09:12:35	2025-11-28 09:14:45
119	user	2	api	da5ce065acabcd6f6b66ddd1ec93385026c470157739c9e2afd90f622471814f	["*"]	2025-11-28 13:59:54	\N	2025-11-28 13:28:26	2025-11-28 13:59:54
120	user	2	api	ed4529f729be8cbd52d1760a185aba78383e53bfd6341875ec0839db1587fdf1	["*"]	2025-11-28 14:05:04	\N	2025-11-28 14:05:01	2025-11-28 14:05:04
126	user	2	api	0b324f61bcf5416aefc23b3284e0d3256f48b53a5e426bbc5dc0183f4475a7ba	["*"]	2025-11-28 15:04:29	\N	2025-11-28 15:04:26	2025-11-28 15:04:29
109	user	2	api	135e4ccc3c114ddf7e5d09901d0aad6355984c49adaf48fcdf4c59a2aae3e8b1	["*"]	2025-11-28 08:51:46	\N	2025-11-28 08:51:21	2025-11-28 08:51:46
132	user	2	api	75fe869d92918e94e1a9e6ef94566f2d979ae50c92764e9b7a7165a1f794f6c7	["*"]	\N	\N	2025-11-28 16:21:03	2025-11-28 16:21:03
112	user	2	api	87b923a2cb865e97e9df5c5500fc7a41d970538424b2f2d408f9fe4a8ce33e6d	["*"]	2025-11-28 09:01:37	\N	2025-11-28 08:59:53	2025-11-28 09:01:37
121	user	2	api	c15d2f3e148e16764589af345586e68c1f2eec12bbe2069299712da2164a6dc8	["*"]	2025-11-28 14:05:44	\N	2025-11-28 14:05:40	2025-11-28 14:05:44
130	user	2	api	9eae09fc537abe7c6c4664800e133827a3c7ac82841ec4dfef064171bdf9a1ab	["*"]	2025-11-28 15:17:33	\N	2025-11-28 15:17:17	2025-11-28 15:17:33
128	user	2	api	f3fe18120f4ae9ff4a7886fc844dc223a2432daeba09d6c1ed1582c07b12b916	["*"]	2025-11-28 15:10:41	\N	2025-11-28 15:08:57	2025-11-28 15:10:41
146	user	2	api	0dd7886cf40a1f8b83216c5c1d7ae1fa76016f96c81349fb2cbe38d4d2398e5f	["*"]	2025-12-01 11:06:25	\N	2025-12-01 11:05:49	2025-12-01 11:06:25
136	user	2	api	b29149f29ce10bd3aaeeb75479eb445d53c35b923ce548772fe58fe3d87ef8a6	["*"]	2025-11-28 17:23:36	\N	2025-11-28 17:15:07	2025-11-28 17:23:36
127	user	2	api	51f3fd75c9b393674954e049620d2022599ad0c170936f43e8d376c31071e62f	["*"]	2025-11-28 15:05:55	\N	2025-11-28 15:05:00	2025-11-28 15:05:55
140	user	2	api	2c7d8a5fb803a5e9ef1e37f14700a657007c4a5fa031098e08f257a8c7152330	["*"]	2025-11-28 17:32:21	\N	2025-11-28 17:32:10	2025-11-28 17:32:21
135	user	2	api	4c40f598ae85ddf8aead3840b9d75d8018fbc1340bd7bf9f73eaa9492cc01929	["*"]	2025-11-28 17:12:38	\N	2025-11-28 17:11:15	2025-11-28 17:12:38
133	user	2	api	1adc975c01ae0eefa8cc81d806465a2928710db1ebc36d8f9d908a9fef0a2db9	["*"]	2025-11-28 16:51:30	\N	2025-11-28 16:51:10	2025-11-28 16:51:30
134	user	2	api	65832b0318025da6a4f207964895e697f7c89172c0f03c870a2d0124274c538f	["*"]	2025-11-28 17:10:28	\N	2025-11-28 17:10:25	2025-11-28 17:10:28
139	user	2	api	7c33a86f8311b048130123f8076f4cf3599915b5b7fe387c1209ba99b0589404	["*"]	2025-11-28 17:28:07	\N	2025-11-28 17:28:04	2025-11-28 17:28:07
141	user	2	api	6c594d1899f1926e64ffbe5e208f9a20203465e5dd672c926ed92203cdccca21	["*"]	2025-11-28 20:05:28	\N	2025-11-28 20:05:26	2025-11-28 20:05:28
137	user	2	api	d09c88e4f9d090a489990c778a39719b775b83a6107aa5ed859e8bcf3fdfbb0e	["*"]	\N	\N	2025-11-28 17:18:29	2025-11-28 17:18:29
148	user	2	api	eef329d33e952d0f5ab97f03e0c02f7142c6fab21cb5249e4bc5de767bf7efbf	["*"]	2025-12-01 11:19:44	\N	2025-12-01 11:19:35	2025-12-01 11:19:44
144	user	2	api	52fd8c5a087898f3962218b4cc1bdd56844231c745c4c09b53d2043362c0e6d5	["*"]	2025-12-01 11:00:52	\N	2025-12-01 11:00:17	2025-12-01 11:00:52
145	user	2	api	903ac704ed7a7d1f6fc22cfce0f7771adf572325ff9c08ee5b8d654b13306c38	["*"]	2025-12-01 11:04:51	\N	2025-12-01 11:04:36	2025-12-01 11:04:51
147	user	2	api	c242c97984bc10401d386464f10d756e9c6fe1d985689994a51968153875b85d	["*"]	2025-12-01 11:16:17	\N	2025-12-01 11:08:47	2025-12-01 11:16:17
149	user	2	api	125c7237968c70a8d0925ce3f45388051ecd723b6b665f6021fff946f229848d	["*"]	2025-12-01 11:27:20	\N	2025-12-01 11:25:38	2025-12-01 11:27:20
150	user	2	api	f746d061bb6be8d105dc41b65fc1f6a8a97baea1ac15ae7c389dbb83b8f95dd2	["*"]	\N	\N	2025-12-01 11:28:49	2025-12-01 11:28:49
153	user	2	api	973b40cdbec84f90da4518ecef13f80442cd3f8f71f15522275ae25c819c2af5	["*"]	2025-12-01 12:23:48	\N	2025-12-01 12:23:39	2025-12-01 12:23:48
152	user	2	api	b4b5eb5b3193e93fd401bdbc3614c81d1a841f6f57420afd18d14c96ee9737eb	["*"]	2025-12-01 12:20:06	\N	2025-12-01 12:19:56	2025-12-01 12:20:06
154	user	2	api	5868de5e5c56627b20f451ea587246df2ed495ee9f23ae33884791a2bb66d322	["*"]	2025-12-01 12:24:32	\N	2025-12-01 12:24:24	2025-12-01 12:24:32
160	user	2	api	186a6f622e5a852f2514274d76794df7cb151c98fdc1480d1d834bf4be117c99	["*"]	2025-12-01 13:29:18	\N	2025-12-01 13:29:14	2025-12-01 13:29:18
182	user	2	api	df1414038ed246c26f634c15a7c33126eb84d8e7ff55660dd8a1ac0be58785b1	["*"]	\N	\N	2025-12-02 09:51:03	2025-12-02 09:51:03
183	user	2	api	5ac68c025dc2b4c063810494e61d2cfa5a7d2b73fb10b6fed985e4da4178e9e2	["*"]	\N	\N	2025-12-02 09:51:50	2025-12-02 09:51:50
171	user	2	api	a90dc67acdb4c76a07851946122bc06c102ff139576fdaa9246d07fa6ac9dd25	["*"]	2025-12-01 16:40:39	\N	2025-12-01 16:40:28	2025-12-01 16:40:39
161	user	2	api	1f86f735af0cbd27dc03e9e1c60034bc4464a27d8f0b9c837bcf68a2152b342d	["*"]	2025-12-01 13:31:23	\N	2025-12-01 13:30:07	2025-12-01 13:31:23
184	user	2	api	489590cb6cb562a8032722b872cec5e0b1c45e019b7cbd40611ce143d3fc04fa	["*"]	\N	\N	2025-12-02 09:52:53	2025-12-02 09:52:53
155	user	2	api	886435522c689c9c72d33b3da79fb10af7f1ca691a653da1cd8dc43cb791e164	["*"]	2025-12-01 13:13:15	\N	2025-12-01 13:01:38	2025-12-01 13:13:15
185	user	2	api	6f29a1bdf20edaded8f7b8e8fbf501c90e217b6688f4be6a7eb9e01b8d130641	["*"]	\N	\N	2025-12-02 09:53:05	2025-12-02 09:53:05
162	user	2	api	989ecc4c1a6ea7d716b6e5ce9dab74b6adac2410f73c1932fcc1092b64d0a016	["*"]	2025-12-01 14:07:35	\N	2025-12-01 14:07:26	2025-12-01 14:07:35
157	user	2	api	6096b920f61a88ddc088ed21d646a80955e081651b257b157cc1e5e347df7adc	["*"]	\N	\N	2025-12-01 13:21:07	2025-12-01 13:21:07
163	user	2	api	244ca07299ecf15b828dd5a60872a351ac77677c2b8b0ee355f77b9f070600aa	["*"]	2025-12-01 14:53:03	\N	2025-12-01 14:52:54	2025-12-01 14:53:03
164	user	2	api	7c6e6ef368d527babd0b57430e751e593631050ba191996e874f9bf4b3633b8d	["*"]	\N	\N	2025-12-01 14:55:28	2025-12-01 14:55:28
165	user	2	api	73befccdd6b84bbaffe13877d7c5c5ff34e896e9c48570d0c63965db9ef39741	["*"]	\N	\N	2025-12-01 14:57:29	2025-12-01 14:57:29
186	user	2	api	20793a71452859714fcbe53d3670def003a24a5294770b24882f982cdeb8af87	["*"]	\N	\N	2025-12-02 09:57:16	2025-12-02 09:57:16
166	user	2	api	14232bef38f19bbacbe46e9eefe89bc5c8458770dff4fe0ddced8ea2c450efc4	["*"]	2025-12-01 15:04:35	\N	2025-12-01 15:04:32	2025-12-01 15:04:35
178	user	2	api	a69db32544096333dd6c08304e63dbaf9e60d83fc25ae2dcbaecc9ede7f7429f	["*"]	2025-12-01 18:29:24	\N	2025-12-01 18:28:26	2025-12-01 18:29:24
158	user	2	api	8f7d9056d42d35e26c0888d084703f94b0f9e69747590b95be99935e862797aa	["*"]	2025-12-01 13:21:50	\N	2025-12-01 13:21:35	2025-12-01 13:21:50
167	user	2	api	dd974ff2ae24ff14e31a453aef0cce575f3d66e074a0ee5bfeef7097b5818884	["*"]	2025-12-01 15:16:05	\N	2025-12-01 15:16:02	2025-12-01 15:16:05
187	user	2	api	69d8e0e2bcc580e262f720e36cd45688d77f311c342593b87e0176ec455a6854	["*"]	\N	\N	2025-12-02 09:57:41	2025-12-02 09:57:41
188	user	2	api	a161fa03632c7379667a6f59e260b4e8d1ba0886b98217ae3a6d27ffa343e409	["*"]	\N	\N	2025-12-02 09:58:06	2025-12-02 09:58:06
180	user	2	api	8ed43aa95067a76e5c1bd1867881297258f5b9fdf04a7b1e12aa57096f4d24d8	["*"]	2025-12-01 18:35:17	\N	2025-12-01 18:35:05	2025-12-01 18:35:17
174	user	2	api	36f15be6a907fb1238de2bd94b70edcb01448ff21d5064c4c71e81f8067e384e	["*"]	2025-12-01 18:03:24	\N	2025-12-01 18:00:11	2025-12-01 18:03:24
172	user	2	api	187ef75d2b0c20d4522fa33ae9378342ba1269ece8a94704088bf44789008508	["*"]	2025-12-01 16:56:21	\N	2025-12-01 16:56:07	2025-12-01 16:56:21
168	user	2	api	f2d1a2d9633ef5e91a136ad2f5ae743f3141eee5275e82b7ee8a5718aedc1670	["*"]	2025-12-01 15:32:54	\N	2025-12-01 15:32:38	2025-12-01 15:32:54
169	user	2	api	5763126de341d40fe29f5482bdbb724630949525dd65dd60f7debdb75a06cf26	["*"]	\N	\N	2025-12-01 15:34:09	2025-12-01 15:34:09
189	user	2	api	b811e0edcecb040e23991fe80f8b9b29f446773f94a1374c54d50ec3bc55cd02	["*"]	\N	\N	2025-12-02 10:26:36	2025-12-02 10:26:36
159	user	2	api	78dec63b253fdb93649475c4f04fb5a9479dfcb48f49429216b1445ac5369f50	["*"]	2025-12-01 13:25:14	\N	2025-12-01 13:24:18	2025-12-01 13:25:14
156	user	2	api	c35f80f272752f70c7204ade4cf60e9fdf683d50d5795dc55d86d052ccdcfa0e	["*"]	2025-12-01 13:25:44	\N	2025-12-01 13:14:43	2025-12-01 13:25:44
170	user	2	api	c8d1259230ef93118923d432cefc379731900ee19967898d05ea33cd411cb197	["*"]	2025-12-01 16:14:00	\N	2025-12-01 16:13:56	2025-12-01 16:14:00
190	user	2	api	ada827f045526031b80a9d41224e47ed23ec51b01f51aac1f630584f21f70f3f	["*"]	\N	\N	2025-12-02 10:27:04	2025-12-02 10:27:04
176	user	2	api	bd35155431a0faa8fb0a748672d858c33738a9343ea1efc8e552413211b83be1	["*"]	2025-12-01 18:08:45	\N	2025-12-01 18:08:05	2025-12-01 18:08:45
175	user	2	api	845a054e8bf9f622aa622a523f007e1f04dc9e4da4600fd3f3ef2ebd75421ee6	["*"]	2025-12-01 18:06:11	\N	2025-12-01 18:06:03	2025-12-01 18:06:11
173	user	2	api	9391d03e8dd1bff31376c33b942426decbed2e8ae3371ae79c897eb80a1563bd	["*"]	2025-12-01 18:00:11	\N	2025-12-01 17:51:16	2025-12-01 18:00:11
191	user	2	api	07c52a93f25989f8c7646473798406ea8ed2b40f4e94175fea1de8f5e5c28845	["*"]	\N	\N	2025-12-02 10:27:29	2025-12-02 10:27:29
192	user	2	api	5b8c4fb4f762a2c7c4cbd9ae708825f317677bb08967e4e03aaa001c0f50c9bb	["*"]	\N	\N	2025-12-02 10:28:09	2025-12-02 10:28:09
177	user	2	api	591e5d806505a5739bb2b77b1d8304d70af35196757162c1483b11919e575a2f	["*"]	2025-12-01 18:27:52	\N	2025-12-01 18:27:38	2025-12-01 18:27:52
193	user	2	api	4740f3773f3b259a6522f582cc9fb411e131262cd8082592dc5818ce9d402c1e	["*"]	\N	\N	2025-12-02 10:28:53	2025-12-02 10:28:53
194	user	2	api	5f7c255db6afdaeb07a158347afe25b05e61aec7ae8daccb0c800f10b6d3fd74	["*"]	\N	\N	2025-12-02 10:29:22	2025-12-02 10:29:22
195	user	2	api	435951c9604d6e1dd4af0da85c93f5977b14739e2c9532fea1bd38b0641e6dff	["*"]	\N	\N	2025-12-02 10:30:15	2025-12-02 10:30:15
196	user	2	api	1486db9b8b138e043b9d3ffbcc48a1e0d822665f5549152baa80eaf5bf93cc9e	["*"]	\N	\N	2025-12-02 10:30:56	2025-12-02 10:30:56
197	user	2	api	5ae0868dc4e48db9ac0fe00712475c4ca4071b464b9e90da3607544b0b89fbf2	["*"]	\N	\N	2025-12-02 10:31:18	2025-12-02 10:31:18
181	user	2	api	abe821520ad787f21063dc1de50cea7c1aea118a4dc4e33dfe1ee20bd5d484c9	["*"]	2025-12-01 18:42:28	\N	2025-12-01 18:42:14	2025-12-01 18:42:28
179	user	2	api	92cb1832e394daea86500d6a9dad7b645c2fe4aca8f2b1499a062395e8c8b1f2	["*"]	2025-12-01 18:33:52	\N	2025-12-01 18:33:12	2025-12-01 18:33:52
198	user	2	api	de457b52880b72a392146a07d57ac0134d2ebdb6c8f8822a5ffcef4e14750b08	["*"]	2025-12-02 10:40:42	\N	2025-12-02 10:40:39	2025-12-02 10:40:42
199	user	2	api	ca2787964034228f60a5123450d9fff3db263ebfd667223991a432b6a81acd74	["*"]	2025-12-02 11:00:05	\N	2025-12-02 10:47:33	2025-12-02 11:00:05
200	user	2	api	69d5fd889830325892146b03c7d68a899a049b50244e39417b20a8a3a1508534	["*"]	2025-12-02 11:07:39	\N	2025-12-02 11:00:33	2025-12-02 11:07:39
201	user	2	api	cd1d10ae11542a7079e96a76bbd132ac872902a3fc84fd6a9c827b4b3ac1774c	["*"]	2025-12-02 11:02:58	\N	2025-12-02 11:02:51	2025-12-02 11:02:58
202	user	2	api	f7daf377046380132ef9c5731fcd9c3b328614999ce9c27955d30f6f937785df	["*"]	2025-12-02 11:10:51	\N	2025-12-02 11:10:39	2025-12-02 11:10:51
203	user	2	api	183cada9394bdb02911f4871e377d73c3bc89dda405178eceb03b7f59ffcbfd7	["*"]	2025-12-02 11:23:23	\N	2025-12-02 11:23:13	2025-12-02 11:23:23
219	user	2	api	a91a980b30492feeb8d2c5df12cd5f0921bf5fc0c0b900e4cdfa4527f83012e5	["*"]	2025-12-02 14:51:11	\N	2025-12-02 14:51:06	2025-12-02 14:51:11
237	user	3	api	ad924198bb9e28304c63d2035a7059611cf9bbe62b3a4b21c5853f15dd3ba2d6	["*"]	2025-12-04 13:55:28	\N	2025-12-04 13:51:03	2025-12-04 13:55:28
216	user	2	api	935846bd41545cfc5a34894e20a010cb082ed7f494f0d58e96ca8b921dbb0245	["*"]	2025-12-02 14:25:20	\N	2025-12-02 14:22:33	2025-12-02 14:25:20
225	user	2	api	aff14e931067621c7e632a36e93290bb7589b3d7f58c1fe676ef34003f7d6a91	["*"]	2025-12-02 16:09:26	\N	2025-12-02 16:07:28	2025-12-02 16:09:26
209	user	2	api	97162531a6f5777a0730b9db3cce732b03dbfd15e35792667c987b3c4df910c3	["*"]	2025-12-02 13:55:31	\N	2025-12-02 12:31:12	2025-12-02 13:55:31
204	user	2	api	3ba6f0281e4dfb3be9aa10b98329f6f4c285129703136779de6914827a99c9f5	["*"]	2025-12-02 11:45:09	\N	2025-12-02 11:44:45	2025-12-02 11:45:09
211	user	2	api	2c5c81d20b0439c3c770e19ef76786df99d88b5ec7107a4e8d8fd344c5fd6b67	["*"]	2025-12-02 13:56:09	\N	2025-12-02 13:55:37	2025-12-02 13:56:09
207	user	2	api	95d146ceee338fee39a5219f1e025773c6a772313d2a782061b39b299f9fcc09	["*"]	2025-12-02 12:20:24	\N	2025-12-02 12:08:48	2025-12-02 12:20:24
210	user	2	api	98b5487c7f58459e3b6fd9a1684b12576d141162866d45fa77556a4de01b93a7	["*"]	2025-12-02 14:04:39	\N	2025-12-02 13:50:19	2025-12-02 14:04:39
205	user	2	api	b11a6d2b2d6cf7de75336e644bf0610bce12e49402f53ba26b0859919e28a100	["*"]	2025-12-02 11:55:26	\N	2025-12-02 11:54:27	2025-12-02 11:55:26
206	user	2	api	16ba09d1682fabef79a845f0e050a9cd3fab6ffeffa248d1f40e69d88279e8f1	["*"]	2025-12-02 12:02:41	\N	2025-12-02 12:02:33	2025-12-02 12:02:41
231	user	2	api	db35945125424caad727e1acfdf624ff6c0ff92d90fe7c4d6a1019e5c841b983	["*"]	2025-12-03 14:58:19	\N	2025-12-03 14:37:28	2025-12-03 14:58:19
230	user	2	api	ef31ddece1af5e1aacab94aa079481f1f65a85e1d67a1ada85dc75efe7d771c9	["*"]	2025-12-03 14:34:20	\N	2025-12-03 14:29:31	2025-12-03 14:34:20
224	user	2	api	3c62a9110c2d7395e730c80585616dbde29c1f320c2822dc092d17ee25287787	["*"]	2025-12-02 16:03:23	\N	2025-12-02 16:01:50	2025-12-02 16:03:23
208	user	2	api	ed385e66fb80a48bb67a8e6e9ecd43a1ac7d152a7b03adfaaca451ecef82030f	["*"]	2025-12-02 12:31:01	\N	2025-12-02 12:19:05	2025-12-02 12:31:01
213	user	2	api	7da86cd3086d292ef4a03394b8ec1868dbcc0077a8c13701c3023364f20807fc	["*"]	2025-12-02 14:23:45	\N	2025-12-02 14:09:38	2025-12-02 14:23:45
217	user	2	api	2bff563884826d3ef195d5fa63c73649292b959938753790b2891738c6f6c755	["*"]	2025-12-02 14:37:04	\N	2025-12-02 14:28:05	2025-12-02 14:37:04
223	user	2	api	12bc6aeb86e7468f5d8fdd8e11a09d5c677336e754923af498040fbddbc09789	["*"]	2025-12-02 15:46:46	\N	2025-12-02 15:46:23	2025-12-02 15:46:46
222	user	2	api	75294ceafa53594798a3a503f28d9b021447e754e1b96c3da5a58567174c03d6	["*"]	2025-12-02 15:47:02	\N	2025-12-02 15:45:20	2025-12-02 15:47:02
220	user	2	api	50b06a231eaf707f05783cc82ea941305d921b132f31f8296ccd5612d331086c	["*"]	2025-12-02 15:38:19	\N	2025-12-02 15:37:40	2025-12-02 15:38:19
212	user	2	api	4e39bbe0443505ea5ea629f3d966ea4590690d5d4f36717a432db2a6f78e0021	["*"]	2025-12-02 14:07:45	\N	2025-12-02 14:07:26	2025-12-02 14:07:45
215	user	2	api	91813a937bd2f9d6d1fb84aef55c051be6cc1be78c855df56a7ddfafe271fdc4	["*"]	2025-12-02 14:31:12	\N	2025-12-02 14:17:04	2025-12-02 14:31:12
234	user	4	api	d0fb6ecd5e47dc6859e26666970c0e96c273215a79714a511b938cd77e4429db	["*"]	2025-12-03 16:28:35	\N	2025-12-03 16:28:31	2025-12-03 16:28:35
214	user	2	api	7305fa1124c81dd16995e5d62676f827568968d5a8575aecee4efc0aa60737aa	["*"]	2025-12-02 14:14:09	\N	2025-12-02 14:13:03	2025-12-02 14:14:09
235	user	6	api	581891baac6d0a188718ab23ccedb79f7a037c8fe1ce7d706200f06613865bc0	["*"]	2025-12-03 16:36:10	\N	2025-12-03 16:29:18	2025-12-03 16:36:10
226	user	2	api	bd94e8e26de712c9d9ed4536738512414e277f00ce14c9478bb20a55d0976d3c	["*"]	2025-12-02 16:24:58	\N	2025-12-02 16:12:55	2025-12-02 16:24:58
238	user	4	api	c2bd426cf8ad7795a7d5cd85616a8245107b224181fab8bf9b90d34e31e9485e	["*"]	2025-12-04 14:11:47	\N	2025-12-04 13:55:56	2025-12-04 14:11:47
221	user	2	api	a7b1713df3ff5ccdd2c6c16bc9d0f4050e18c722c8d05953f3e2f137e0f35017	["*"]	2025-12-02 15:44:54	\N	2025-12-02 15:44:35	2025-12-02 15:44:54
227	user	2	api	ce8dfca39dbaf0b66a1d5b2faf7c40b9b1c164272727055d988125ae74f7629a	["*"]	2025-12-02 16:27:36	\N	2025-12-02 16:14:21	2025-12-02 16:27:36
232	user	4	api	83c34b689451471f0a7de12999dda5688d99ed6e0a4bf0a302fa4a734dc28af8	["*"]	2025-12-03 16:16:27	\N	2025-12-03 16:16:10	2025-12-03 16:16:27
229	user	2	api	d8f55eaa03378be65eac199bb217f1e0613f1d4599a1ca6c3bf488074f915ba8	["*"]	2025-12-02 20:36:30	\N	2025-12-02 19:41:52	2025-12-02 20:36:30
218	user	2	api	d78d9e5511942e357b331d3cbde778e41afd14b56f0ea9775261169919201e49	["*"]	2025-12-02 19:37:34	\N	2025-12-02 14:34:33	2025-12-02 19:37:34
245	user	7	api	c7dad9eaad2b4da02ed2be115d089c7f63c590a41810938d6f5de6eae11c3516	["*"]	2025-12-04 15:26:07	\N	2025-12-04 15:25:55	2025-12-04 15:26:07
228	user	2	api	5f597e611be46a002a3425307137dd83d1a5aa073d3e954def535ce6f1f7fbf9	["*"]	2025-12-02 16:16:28	\N	2025-12-02 16:16:02	2025-12-02 16:16:28
240	user	5	api	910ddf50fdb805928c8b9d0b796de1a3e6bc79e3766d0683db8d3932a1384fd4	["*"]	2025-12-04 14:18:47	\N	2025-12-04 14:18:14	2025-12-04 14:18:47
236	user	2	api	33037b1227c8d92ed3a89483e3799643629ebcb4af4b529a8b92919c751629cf	["*"]	2025-12-04 13:27:35	\N	2025-12-04 11:14:13	2025-12-04 13:27:35
242	user	7	api	60b92ba6c1ce455f10707976f38aec52ef5625bdd3b3a6b9555c34a6ca6306fa	["*"]	2025-12-04 14:21:28	\N	2025-12-04 14:21:04	2025-12-04 14:21:28
233	user	2	api	ba31b3ff99301b830407898d6eaf342961e73be1e5e3a4645a75b96e44a13d67	["*"]	2025-12-03 16:25:01	\N	2025-12-03 16:24:26	2025-12-03 16:25:01
239	user	2	api	b7e5a40c3aa3cc76d1b755678a5534c4eb3b5f22e04da0bc88cf776f0558984b	["*"]	2025-12-04 14:17:19	\N	2025-12-04 14:12:04	2025-12-04 14:17:19
241	user	6	api	e99afa089d14007398528d7835bc1f3e7a7f7641bdf48199ea73a6fbd06550c2	["*"]	2025-12-04 14:20:41	\N	2025-12-04 14:20:15	2025-12-04 14:20:41
243	user	4	api	2b05faff57f845daa4a64c959b694a1da1a3171e89fa2118a0806d81ac8741b3	["*"]	2025-12-04 14:36:21	\N	2025-12-04 14:25:32	2025-12-04 14:36:21
244	user	6	api	efa0d0cde96612e7062a654cea2116770da4c58f4c5990c42219dd8a3256fc0b	["*"]	2025-12-04 15:22:22	\N	2025-12-04 15:22:14	2025-12-04 15:22:22
249	user	2	api	3f761246c4b7b84775ad181d2c23d3bf6523151966f890973ce1e483ea7308d2	["*"]	2025-12-04 17:18:09	\N	2025-12-04 17:13:31	2025-12-04 17:18:09
248	user	7	api	d03d2f3d03b944351c998336ab38f70b2a8da413b3b9240ba3628c542b49f47f	["*"]	2025-12-04 17:27:41	\N	2025-12-04 15:42:23	2025-12-04 17:27:41
247	user	4	api	71350f5054eb0a50dda67e71022b5d8e0fd5a6889d3384d7b5b38f265c4eb705	["*"]	2025-12-04 15:41:24	\N	2025-12-04 15:41:20	2025-12-04 15:41:24
246	user	4	api	c6caa1611848200b88402a00c61063512628c49e11a119d593d1d5e14a559d69	["*"]	2025-12-04 17:34:48	\N	2025-12-04 15:27:50	2025-12-04 17:34:48
250	user	2	api	39ebe97ce8b872469443a27227a03f8e1f43d686dc340dc3b1202529f2fd18b5	["*"]	2025-12-04 17:22:10	\N	2025-12-04 17:19:31	2025-12-04 17:22:10
257	user	7	api	a277158ad192e4666cf331a857193bec119a82d0bfca2c683fd61094bff64b21	["*"]	2025-12-05 09:09:31	\N	2025-12-04 17:28:19	2025-12-05 09:09:31
286	user	2	api	93f06026736f6ddf1ee14b08fa8e257ddb86d63d21cf80e4f749c640d95d350c	["*"]	2025-12-11 09:27:42	\N	2025-12-11 09:10:20	2025-12-11 09:27:42
263	user	2	api	7881bcca7aa8146fad991170d3d63ef2c203a683a3fcaa95daaa4e6132e288d0	["*"]	2025-12-08 14:40:19	\N	2025-12-08 14:38:58	2025-12-08 14:40:19
264	user	2	api	a87c9c69a0e28081c2d98ea329f60db12b6b4053f0871aa958f2d4968d2014af	["*"]	\N	\N	2025-12-08 14:42:01	2025-12-08 14:42:01
272	user	5	api	48bf800ca0583af05ddeb9c731762dc5f152311b977ba3aa8e18ee7ebf395971	["*"]	2025-12-10 14:12:25	\N	2025-12-10 09:44:03	2025-12-10 14:12:25
285	user	5	api	97dd423f3481d6afb0d7c927cb3815ba1510f426a7785110a9629c9297d716e4	["*"]	2025-12-10 17:08:59	\N	2025-12-10 17:08:52	2025-12-10 17:08:59
251	user	7	api	edc79439f9099a0f4cd3701f4b77f2688293af819a7a7234e85da68d6a1d27c0	["*"]	2025-12-04 17:22:58	\N	2025-12-04 17:22:22	2025-12-04 17:22:58
252	user	7	api	1d2d3985c7a181a8c3b036a79e33010b17cc89f0835f4e1a9a76310b97b0f72e	["*"]	\N	\N	2025-12-04 17:24:50	2025-12-04 17:24:50
253	user	7	api	33ecda9133630c0a55bdf0bded77aea59817e48de9e68b490bd0ff3e20838a70	["*"]	\N	\N	2025-12-04 17:25:17	2025-12-04 17:25:17
254	user	7	api	96db1c2e6cfb0bfcaf0763fe86640c43358b36e547695bcf947274c3cdd4b823	["*"]	\N	\N	2025-12-04 17:27:00	2025-12-04 17:27:00
277	user	5	api	255334bb800a90c56af5bd9ba189944ca6a3661c3c3380ac8715dd5771a1f774	["*"]	2025-12-10 14:25:17	\N	2025-12-10 14:22:56	2025-12-10 14:25:17
282	user	4	api	87d3a3c6a5bc244417a5a0993d5045668d18f8e91b7edc33ccb7ca959d064a33	["*"]	2025-12-10 16:32:02	\N	2025-12-10 16:29:04	2025-12-10 16:32:02
274	user	7	api	ef4183732f87c70a5d4e43b5155a551f8459304c1df76863fd11e52703dcbe2c	["*"]	2025-12-10 14:19:40	\N	2025-12-10 14:13:54	2025-12-10 14:19:40
255	user	7	api	6857699e01c2d52c58535e36c0b42552418201475c3acffaf085a5084111b031	["*"]	2025-12-04 17:27:47	\N	2025-12-04 17:27:31	2025-12-04 17:27:47
267	user	2	api	3b56bfa9309a3aa7eb0ff889f48613a7ae0b37ff52551ce663eaf8ae73d08109	["*"]	2025-12-09 16:56:34	\N	2025-12-09 08:46:54	2025-12-09 16:56:34
260	user	2	api	7b1d5d48daeb09bb6a1e1da07c38c56a42ae91e668e3b84729c80f19d7256a7e	["*"]	2025-12-05 11:08:17	\N	2025-12-05 11:05:58	2025-12-05 11:08:17
256	user	2	api	4ca6259ef704e17564597a850a37086559eda0e707dff862bf13fbd43b9ddc04	["*"]	2025-12-04 17:28:02	\N	2025-12-04 17:27:56	2025-12-04 17:28:02
269	user	2	api	1a0df2d51b286fe2bb634bc7e9d0a2db950fe7c1cb28e97797ea8a224463fddb	["*"]	2025-12-09 23:34:06	\N	2025-12-09 12:40:46	2025-12-09 23:34:06
291	user	2	api	1254149bda361687f980912e8ada0897cb2c91ddc8c135d5bade12b74eda2762	["*"]	2025-12-16 09:43:51	\N	2025-12-16 09:41:19	2025-12-16 09:43:51
292	user	2	api	5cd43982e5c787cb0c04d2863071887f3fc38fdb2b1e289c0a7d9bf6407e51ee	["*"]	\N	\N	2025-12-16 13:48:19	2025-12-16 13:48:19
279	user	6	api	b897bea852994b3a4d5883ffcccec9cd0a09f16c69273e9d99d21c5d7ac24545	["*"]	2025-12-10 15:06:37	\N	2025-12-10 14:51:12	2025-12-10 15:06:37
261	user	2	api	64d00cabee5a3a208cfd2a17b4f139d6c23a33b746661b367a87a3049fbf7db3	["*"]	2025-12-05 19:19:44	\N	2025-12-05 17:25:15	2025-12-05 19:19:44
259	user	2	api	00e3cd4f2550307b4afb47d600a27dd567debad3d32ce4e52b6b1b775523b444	["*"]	2025-12-05 09:56:25	\N	2025-12-05 09:54:06	2025-12-05 09:56:25
258	user	2	api	2e8b73eb7a976735f306b29fecc0238ab569cd8b729a7c53ecf7e68d06c41dc3	["*"]	2025-12-04 17:29:18	\N	2025-12-04 17:28:28	2025-12-04 17:29:18
273	user	2	api	0bf0494c2f99f03706db5d0392ed83ae59ea0622988efc29cfdef9fb52e7ddad	["*"]	2025-12-10 11:00:53	\N	2025-12-10 10:54:53	2025-12-10 11:00:53
262	user	2	api	38da28230f8117ea12ae901d36f9a97aefec7cfb64e827bfc03da55c881d692e	["*"]	2025-12-08 10:12:11	\N	2025-12-08 10:11:57	2025-12-08 10:12:11
275	user	5	api	1ee45ee1905eb075f91fec833b7d091e0bfec115eaa8b24a8295c57d40ea68aa	["*"]	2025-12-10 14:22:14	\N	2025-12-10 14:20:23	2025-12-10 14:22:14
265	user	2	api	fb4dbdd7f7b21ad3d9e6ebef980d3abdbb591fc14b194f69cc43832128b096ce	["*"]	2025-12-08 19:34:53	\N	2025-12-08 14:42:10	2025-12-08 19:34:53
266	user	2	api	6617e03142de3ddc3d39c53f065456df70faf95af193f463d80c2865876d3828	["*"]	\N	\N	2025-12-09 08:43:35	2025-12-09 08:43:35
268	user	2	api	e8fa516a360b6be5a3ef681f2914ab03a10e291e115c24711ef33f42e092c1e6	["*"]	2025-12-09 11:13:25	\N	2025-12-09 11:13:18	2025-12-09 11:13:25
288	user	10	api	d0121f601e0858eec3659cdb03ffac41261b0b8975e9c56314518eae1b9efc39	["*"]	2025-12-13 08:23:19	\N	2025-12-13 08:22:06	2025-12-13 08:23:19
271	user	3	api	928ffeaacfab7542c9c0de6df356a5bee860311574006379f4be644417adc458	["*"]	2025-12-10 09:42:33	\N	2025-12-10 08:43:42	2025-12-10 09:42:33
278	user	7	api	bbb40408f333f60827aca66f5892e94c2dd39083c06c71ff2bc41ea7edd76276	["*"]	2025-12-10 14:49:31	\N	2025-12-10 14:26:46	2025-12-10 14:49:31
270	user	2	api	c8d6c70cdd6aae52e480f4aecdec1a737fe476085c3a20a025d98789717f27ba	["*"]	2025-12-10 08:42:57	\N	2025-12-10 08:22:03	2025-12-10 08:42:57
276	user	7	api	01fb8cfcd2368850d8da2f60e28cd469e1b47dcacfacfc0dc1a154c611b3def4	["*"]	2025-12-10 14:22:41	\N	2025-12-10 14:22:32	2025-12-10 14:22:41
281	user	3	api	6183d24879b5c82e2200ac5220b3d1518c2de6911374aa8bace11148ae858400	["*"]	2025-12-10 16:28:32	\N	2025-12-10 16:05:16	2025-12-10 16:28:32
287	user	3	api	720037cf71ee938e8146ebce31b3a4423126af2337c49b3f6a9eda299a93161a	["*"]	2025-12-12 14:36:10	\N	2025-12-12 14:34:04	2025-12-12 14:36:10
280	user	4	api	008f84682247a982b3a753ed66c50d830eeadb241697d4328b4bc7898b82e506	["*"]	2025-12-10 16:03:34	\N	2025-12-10 15:10:38	2025-12-10 16:03:34
284	user	2	api	cb53f50e2f3d324de4bab8bc2ea347b81e7efd6794309b0436d7081261908f78	["*"]	2025-12-10 16:47:42	\N	2025-12-10 16:46:45	2025-12-10 16:47:42
283	user	5	api	85d715360bdda65c9428419a2e0f963958e99545cbf522a99a65cf0bdb8076e6	["*"]	2025-12-10 16:34:10	\N	2025-12-10 16:32:22	2025-12-10 16:34:10
289	user	6	api	f6adc99c2bda603c40e7ab2a4b4b72681fd09c8d9708440f4f948be3b7ad5b57	["*"]	2025-12-13 08:39:34	\N	2025-12-13 08:25:06	2025-12-13 08:39:34
290	user	7	api	d6deea87b723b66290861eac04428cbbebed1b341225e7eabeb0317a8a3e51fa	["*"]	2025-12-13 08:40:58	\N	2025-12-13 08:40:08	2025-12-13 08:40:58
293	user	2	api	d65fac93c6028e9aee75dcc49f81d6dda460e1dc12cf6234f7992b6694306adf	["*"]	\N	\N	2025-12-16 13:49:30	2025-12-16 13:49:30
294	user	2	api	1fdd0b3109162f91bf96644527d3c8be7d2bb94964ce25654d01284d6fd1cdf1	["*"]	\N	\N	2025-12-16 14:00:17	2025-12-16 14:00:17
295	user	2	api	a54bcd6b8b6f0efd65da574fc74e5ec6b9fb5c0f183dbaa06dc02d636c3c94b1	["*"]	\N	\N	2025-12-17 11:25:16	2025-12-17 11:25:16
298	user	3	api	06e2bf6fc1d39e6eb73312ad5f0c19a97df3c40095ec44b943a929dfc88b85e1	["*"]	2025-12-17 11:52:22	\N	2025-12-17 11:46:08	2025-12-17 11:52:22
296	user	2	api	0d98dbd6c2619a06fcbea4c8577526e6b96aae5953953bf755ffd75672d31b13	["*"]	2025-12-17 20:56:26	\N	2025-12-17 11:32:53	2025-12-17 20:56:26
297	user	3	api	80db0d5a595310938ff924ec18fbf05a1706aacb9d5b389af1ccef3942d13241	["*"]	2025-12-17 11:55:56	\N	2025-12-17 11:33:10	2025-12-17 11:55:56
328	user	2	api	6013ef17a3d27dcde5fc2a1380a43ccc8c64449029732628eec45f3dc25e4c82	["*"]	2025-12-19 18:35:57	\N	2025-12-19 15:51:33	2025-12-19 18:35:57
299	user	3	api	004f1d8100853f5cf1f0682aaa78e9fdfdaa4232512e5ec2a214fe8360fb588c	["*"]	2025-12-18 10:11:41	\N	2025-12-18 10:11:32	2025-12-18 10:11:41
317	user	2	android-app	da5fefb513fded1c1cd1ac9e127258fa1a4c6d32827626e270c0ea66f2b4b18e	["*"]	2025-12-18 19:21:00	\N	2025-12-18 19:13:43	2025-12-18 19:21:00
315	user	2	api	26332f597493a13d7a314309a103355327d6b32a4d37b589c4a689c7f72a102b	["*"]	2025-12-18 19:10:54	\N	2025-12-18 19:10:43	2025-12-18 19:10:54
307	user	2	api	b9807a5e3ef1cbee18d66dc0d0a5d322ae957ce8689a11258efcd0c490de9e46	["*"]	2025-12-18 16:50:47	\N	2025-12-18 16:49:42	2025-12-18 16:50:47
320	user	2	api	9f265fe60cbcc0778f00e183c9bb67ee141a50bf4d23562f98d8e66d7ec66e10	["*"]	2025-12-18 19:27:42	\N	2025-12-18 19:27:14	2025-12-18 19:27:42
337	user	2	api	8d8ed524ab192af960441766d6b94572ae6c1800452cd9a81c7ca75858b4cb3c	["*"]	2025-12-24 14:33:11	\N	2025-12-24 14:24:53	2025-12-24 14:33:11
314	user	2	api	1f8f20ce9f02adc0b61ecf90dea0292816d335adfb468e3b9ebc3effd1a224a9	["*"]	2025-12-18 19:13:23	\N	2025-12-18 17:43:19	2025-12-18 19:13:23
321	user	2	api	1399a384c37b1d5e5a46f8fd28b954a72a133c5da393c3754f6d59dbc9bccbf1	["*"]	2025-12-18 19:34:02	\N	2025-12-18 19:34:00	2025-12-18 19:34:02
300	user	2	android-app	4a588ece79076732add648d0075828fed5f2b59062abf8952e8ab472e73b6dc3	["*"]	2025-12-18 13:32:11	\N	2025-12-18 11:16:17	2025-12-18 13:32:11
301	user	2	api	a974c46e6e26e4b9d64edb77cd2a401d3e596fabc25f040d3bbba8efc0f27e75	["*"]	2025-12-18 13:32:32	\N	2025-12-18 13:27:25	2025-12-18 13:32:32
302	user	2	api	a69d1e9655190bd93c894751dab084450055e2f44986841e1ee7f8b055209ebd	["*"]	\N	\N	2025-12-18 14:17:49	2025-12-18 14:17:49
303	user	2	api	95bf6179f645a18c9d7b8dbe278b4faa017739c02992f5f936c772c86ed30702	["*"]	\N	\N	2025-12-18 14:21:33	2025-12-18 14:21:33
312	user	2	api	d3a35b8fca9eb5f87bdb9b012932ed72b5a1da8c8d0e67e50ea2bc0185469712	["*"]	2025-12-18 17:38:45	\N	2025-12-18 17:09:44	2025-12-18 17:38:45
309	user	2	api	ef42fbd19324b8882c5b3c4e3f9265cf0527a26fe6537fe47b75eaa4e31b4a51	["*"]	\N	\N	2025-12-18 16:52:59	2025-12-18 16:52:59
319	user	2	api	b4f235ecc4d76a8799821519bd116d8f511079cce36e5fc6edcf10bbd32c7cbd	["*"]	2025-12-18 19:26:46	\N	2025-12-18 19:20:49	2025-12-18 19:26:46
310	user	2	api	1bc0d7c0d15c6e201fdb17cacf8da873a9f1907ab004adeb11ad77a3b17114e1	["*"]	2025-12-18 16:59:10	\N	2025-12-18 16:53:00	2025-12-18 16:59:10
322	user	2	api	f2abde4be46d3ff2dc2f691d3c397e3d1c04210c48838fb775bd5d721e00a0e9	["*"]	\N	\N	2025-12-18 19:35:32	2025-12-18 19:35:32
323	user	2	api	9df8c2cadbefdbe29b0f1e7a16ee43ebda4e889acff70d5d34502d4d22c9ec37	["*"]	\N	\N	2025-12-18 19:36:11	2025-12-18 19:36:11
324	user	2	api	f9607e5d585ee4b4437275ee1cf14ff9fdcdb3cfe61eaf3ad1d43d959a58b43f	["*"]	\N	\N	2025-12-18 19:40:39	2025-12-18 19:40:39
304	user	2	api	aa8fffc40dbc3196775691eb3234719e5e1d04f61de1b7434c8ac0531ea341be	["*"]	2025-12-18 14:33:39	\N	2025-12-18 14:32:06	2025-12-18 14:33:39
306	user	2	api	b9434835cea45e71c3894ff9eef728934a6a5ebcfd0522366fe296204296ba6a	["*"]	2025-12-18 16:44:48	\N	2025-12-18 15:42:14	2025-12-18 16:44:48
313	user	2	api	2132b147a012200b0066d1f54b60350f9744373acde0e1e14335a585a2f8b3a0	["*"]	2025-12-18 17:42:51	\N	2025-12-18 17:42:42	2025-12-18 17:42:51
325	user	2	api	f2420b2b42cda5eeebc7d274de4cad09d56425ac5c9ab0c65552774754be1951	["*"]	2025-12-18 19:40:44	\N	2025-12-18 19:40:40	2025-12-18 19:40:44
330	user	4	api	e518161f6de6b570361405f7029d5e30b6783a661aa619cbc694a88ba7652766	["*"]	2025-12-19 19:49:36	\N	2025-12-19 19:49:30	2025-12-19 19:49:36
311	user	2	api	f1e6d6331b12248d26c523c3dc02d719dc61b2e11e81645b4cf84a6ff5dde1ec	["*"]	2025-12-18 17:07:12	\N	2025-12-18 17:06:51	2025-12-18 17:07:12
329	user	3	api	724b61a86d74c99ae8093b4b3bbd32aced43688e459f6e45996df98c8ec31cec	["*"]	2025-12-19 16:11:30	\N	2025-12-19 15:54:54	2025-12-19 16:11:30
305	user	2	api	083de835fe716c09690be2ef3ddfc5855a205a8ba7a086a2f7a2b376e6a10dc3	["*"]	2025-12-18 19:04:42	\N	2025-12-18 14:34:30	2025-12-18 19:04:42
333	user	4	api	76d6ca1e66bee963a72566c5d413d458856dd99bcf32c7499f21100d36ee1911	["*"]	2025-12-23 11:05:24	\N	2025-12-23 08:53:38	2025-12-23 11:05:24
316	user	2	api	e56eec81a089a249f1b5351afe7db651b8f091b5ebbee9c134c608e1d411c235	["*"]	\N	\N	2025-12-18 19:10:43	2025-12-18 19:10:43
318	user	2	api	71b59c1d013fc886042fb7c12d2340bde0cca9925d7402ae2d5702ac4159ae04	["*"]	2025-12-18 19:20:00	\N	2025-12-18 19:15:49	2025-12-18 19:20:00
327	user	2	api	a16a004e32093d06049ac22ea4422b7ab4f1d37da3d7935fd752b069a999bd3b	["*"]	2025-12-19 14:43:23	\N	2025-12-19 14:43:12	2025-12-19 14:43:23
331	user	2	api	0d1e9d175d0c681b83c36c9276dc3f03dc8490193c1ebb81bc710170f5587ee8	["*"]	2025-12-20 19:25:24	\N	2025-12-20 19:25:18	2025-12-20 19:25:24
326	user	2	api	22cb5168c64292dab2140b9f5b4c4b0a6ad6f8d1a09210d612a7c5bae43bf34e	["*"]	2025-12-19 14:52:38	\N	2025-12-19 14:39:50	2025-12-19 14:52:38
332	user	2	api	97fa8d5757c3dfb57acd825fde35986b3d0d7f47e9a88d33eac30a0c06677fb8	["*"]	2025-12-22 21:04:40	\N	2025-12-22 20:17:45	2025-12-22 21:04:40
308	user	4	api	bc8ea3e166abbebdf7becc5f8393ba459ba4c5827f8c6e3a74a4c3f6555abf09	["*"]	2025-12-19 17:58:26	\N	2025-12-18 16:51:34	2025-12-19 17:58:26
335	user	2	api	5971cb1b18a4e3662c557e0a4f02e55ad35da06a5acaa556564b33279400daf6	["*"]	2025-12-24 08:53:22	\N	2025-12-24 08:44:05	2025-12-24 08:53:22
334	user	2	api	f9a42154a70de1dad9f0a6f187d88103dd9d3318a560222dc0af2c0ab14374fa	["*"]	2025-12-23 18:10:29	\N	2025-12-23 18:10:23	2025-12-23 18:10:29
336	user	2	api	4351eb87db0087880300de78890365ce6bea5cf99a7d87611ac5ffe6ed599c90	["*"]	\N	\N	2025-12-24 10:18:35	2025-12-24 10:18:35
\.


--
-- Data for Name: return_history; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.return_history (uuid, asset_uuid, source_type, source_id, source_code, note, pic_request_uid, created_at, updated_at, deleted_at, return_code) FROM stdin;
a26f4425-9777-4a8c-a4c1-40c450f0c311	9d02f2bf-b0f6-4695-abf1-f73ad084c052	transfer	fd97e832-862c-4c9a-9df1-b7808de776f4	MOV25110001	\N	Administrator	2025-11-14 13:23:54	2025-11-14 13:23:54	\N	RET25110001
e44be456-2b64-45f4-8e9f-eba031105331	c42fda71-6dab-4329-a31e-4e057432e0ac	disposal	eac0e48c-b826-440c-aba4-946475a71546	DSP25110002	\N	Administrator	2025-11-26 10:13:07	2025-11-26 10:13:07	\N	RET25110002
f93c913f-370e-4ed9-a574-cbf7177e0ea9	4c9602ed-f338-43bc-841c-e37a6c739af4	disposal	f422d828-ea48-4048-a5d0-816cda9745b9	DSP25110001	\N	Administrator	2025-11-26 16:12:09	2025-11-26 16:12:09	\N	RET25110003
f16be4c1-7b4b-4278-8cef-876aa0b53a70	c42fda71-6dab-4329-a31e-4e057432e0ac	transfer	69182b3f-2356-4236-865c-0523612f1f96	OPN25120003	test	Administrator	2025-12-01 13:31:10	2025-12-01 13:31:10	\N	RET25120001
f9713853-e506-4bcd-9591-dd1fdbc364a0	c42fda71-6dab-4329-a31e-4e057432e0ac	transfer	1dea0ffc-cc70-43c4-99db-b17bdd2a4358	MOV25120001	\N	Administrator	2025-12-02 14:36:21	2025-12-02 14:36:21	\N	RET25120002
12c2eca5-a6df-4030-9ef9-49d3c685e5a4	072d51f8-41af-4057-87f6-978113d94eba	transfer	c0c5f676-6f8c-477c-818a-4602aee61cac	MOV25110010	\N	Administrator	2025-12-02 20:16:51	2025-12-02 20:16:51	\N	RET25120003
0728175d-71b8-445a-92f6-be5b07ade7b8	c42fda71-6dab-4329-a31e-4e057432e0ac	transfer	efbcf34f-b668-4a11-acf0-85dc8e5dd500	MOV25120002	\N	Administrator	2025-12-09 09:38:07	2025-12-09 09:38:07	\N	RET25120004
c9388866-575e-4bcc-86de-e61aa1c55d1e	072d51f8-41af-4057-87f6-978113d94eba	transfer	a406e110-533f-4552-be82-fd9ec838af4c	MOV25120008	asset sudah di kembalikan	Asset Management Head	2025-12-09 09:50:49	2025-12-09 09:50:49	\N	RET25120005
ce4802bf-8d0a-4713-9b18-4b5b9029d422	072d51f8-41af-4057-87f6-978113d94eba	transfer	0c9c6c5d-8ac2-4782-a365-e3f482fffca9	OPN25120005	TEST	Asset Management Head	2025-12-10 15:23:27	2025-12-10 15:23:27	\N	RET25120006
e649e6c0-e2a0-40f3-84ec-3409c7c14eb6	a449e1f3-38da-48c6-82a2-30a9b907eec9	transfer	8f5fb0eb-aa42-4d1e-a645-4c0fd567713f	OPN25120007	test	Asset Managmenet Admin	2025-12-10 16:11:21	2025-12-10 16:11:21	\N	RET25120007
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
Ik8pJ0A2lgoBLi8FST4ROlGCVx3eCBoqFazU8owa	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiR2I3MGduS2lwRUMybnloaFE0QktaTlJ3Y2s3bjUyQ01VTHZpZndkMyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118934
6HDDwZFwWvgDf9NwqAXkkxTCB53KL2KHWYG8tVSR	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiTHA4eVR4S1VVc1NvUnYyZ2c2ZWk4dTg3T2xabEx6Tjl5aEVkemFZaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118941
SWNJcHktCHCOiXf77E8hpC7nwGAa7IHPRtkmEp4C	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoib2g4dEFUY2dJUXVvTWlISVMydEdNQVVoVEdJSXFGZTA2OUo0UEtKbyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118942
2JvcHfGqCxeeMv8fmMNjCQ5JHKKbT89NhN2Crmj9	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiZndReVUxNVJ5eVRBckdSaTRzbW5pVkNnNHdXRGVvUUFhVEw2ZTlDNyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118949
929sPFVz4puYXdmhu4ICUkNIOJ0rpSbYJCK9CfQP	2	103.165.138.170	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0	YTo1OntzOjY6Il90b2tlbiI7czo0MDoibGVQN3ZjWlo3bVBuTndtYXgwQ3E4MVN3QkoxTEZxR1JMRE1Qc0RLSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTE6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvYXNzZXRzL2NyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czo5OiJsZGFwX3VzZXIiO2E6Njp7czo4OiJ1c2VybmFtZSI7czo1OiJhZG1pbiI7czo0OiJuYW1lIjtzOjEzOiJBZG1pbmlzdHJhdG9yIjtzOjU6ImVtYWlsIjtzOjE3OiJhZG1pbkBleGFtcGxlLmNvbSI7czoyOiJvdSI7TjtzOjE1OiJrb2RlX2RlcGFydG1lbnQiO047czo1OiJyb2xlcyI7YToyOntpOjA7czo5OiJERVBUX1VTRVIiO2k6MTtzOjg6IlNZU0FETUlOIjt9fX0=	1763951425
UawhPR3ee9JdMWjLkQbyrYxrwnDgfO4dotyFEocn	2	103.18.34.189	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiNUtvNWYxYzU1MDFSZW0xYWJETXZ6SUQybVhGQnZjMlFMMnFFdml3dSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NjA6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvbWFzdGVyLWRhdGEvbWFzdGVyLXVvbSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1763960246
ySe4kErCR7mg1iUBzTJ0r1nGC47s4Dn7YNilwQp0	2	103.165.138.170	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0	YTo1OntzOjY6Il90b2tlbiI7czo0MDoianFkTnpadWNuYkFveEZDSVVhR1RSemV0VDVzN1BYR0ZveGJJenRDciI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTA6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvZGVwcmVjaWF0aW9uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1763982545
DCH6nfNU4RFgyb1jhPykDmxDkxHTu012EU834hb0	2	103.165.138.170	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaGQ4NW1iVlcxckVQT3lXTzBVR2ZYN2lSZjdtZ1RCcUJpMElnUEVlRSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDQ6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvYXNzZXRzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1764041693
y1AmzEqHPEruOQm6orDYKNA0dwIX6pDryxSdTwcR	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiU01JY1JMTENUTmpZZ3d6WTJJNFBsYmdwcWFQdldwZ0h6RFdvT3p6ayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvcmVwb3J0aW5nIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319fQ==	1764042406
yTxzfQnEJUnM2TieC8KShP4d1yNsbgD5jnaMmCic	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNWJKUTR6Y0JJM1J5YncydEF2eDMxcndiZktwVjVxdHRQTmxXbXlhZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1764118937
Yy0EQvzyn3QEG7yeiy3OpcB4XBoeG6dwoEppoB69	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiSkllNU1xaEx1S0FkVHFPQXFYUW82aDR1bFdXa09HczBnbktJTkJkVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118941
7KrhXTS4McKJK7YK1t3Nle6FaAfrnwdx6WTj4Lld	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNDVmcGFyRnVLTDN2V3Y5WWRLZXJtdjViQkZueEtZZjBCNzdEV0prdSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1764118945
uPv5ziTpHyspKvYN9DWOTTbhq8FJw0wJNBZZEz87	2	118.99.103.53	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoidEEwUUQ3b0RDZno1T3RQZms5aVd4SURxSW9qdWFTNUZLVHMyVUdFUyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764118996
oOBLLTmTrxsUdH7OtC3fkv115USpjzZr63W316bX	2	118.99.103.53	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUnZIU0NtWDhCcFppVnpXeklNWVNHc3hjMnBkUG5vT1ZaTnZ5UEY0SiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTA6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvZGVwcmVjaWF0aW9uIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1763953226
vXl1hCGze4jh4NwCCkFSbKRijc5laTYgzdxlJm5T	2	103.18.34.189	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiZzFIamNQQVp4S29kR1YxTkl2OFFmNU5nY21OU3RjZUNjSWpVdTI4VSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDQ6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvYXNzZXRzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1763972528
l91JI0vFxrKP9U1QHB9kzMPzjE13b2mz8WnUDBXX	2	118.99.103.53	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiOEhpM3l0Unc4RkJvT2ljZ1loQUlSUHg4REJRc0xYdUplRVRqTUJEWCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6ImxkYXBfdXNlciI7YTo2OntzOjg6InVzZXJuYW1lIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6NToiZW1haWwiO3M6MTc6ImFkbWluQGV4YW1wbGUuY29tIjtzOjI6Im91IjtOO3M6MTU6ImtvZGVfZGVwYXJ0bWVudCI7TjtzOjU6InJvbGVzIjthOjI6e2k6MDtzOjk6IkRFUFRfVVNFUiI7aToxO3M6ODoiU1lTQURNSU4iO319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTg6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMvdHJhbnNhY3Rpb24vZGlzcG9zYWwiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1764039180
1ubM0x72gO4cRHcWfHapQQwutiiDa4bbIR9fHqYT	\N	103.165.138.170	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoibzNEa3pOTk1rRlZBV0I3aE5kNU9KMkJuVjNvSHRValhYSnhYVHdlcyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbHJ0LmVhc3ltYWludGVuYW5jZS5pZC9wdWJsaWMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1763951311
\.


--
-- Data for Name: user_role; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.user_role (uuid, user_id, role_kode, created_at, updated_at) FROM stdin;
c195fca6-3f15-4ff0-b58b-ca588efd9241	2	SYSADMIN	\N	\N
fe6e54fb-eb99-4c6e-803a-d55ac04815ef	2	DEPT_USER	\N	\N
d315167a-7536-4dc8-a1e6-9934f29b0926	3	AM_ADMIN	\N	\N
bf30e121-415a-4ac2-acfe-4d96a371e15c	4	AM_HEAD	\N	\N
20167546-bd6f-4b84-b63c-719f837b4b57	5	AUDITOR	\N	\N
bf0c6dd5-1ced-423d-8664-6664eb88fcd4	6	DEPT_HEAD	\N	\N
a5a6c5e1-6442-4361-97bb-bca892b12856	7	DEPT_USER	2025-12-04 17:26:54	2025-12-04 17:26:54
307049f1-40a2-4bb3-a825-89f84053c099	9	DEPT_HEAD	2025-12-09 10:18:08	2025-12-09 10:18:08
bd68ad74-fe90-4daf-aa94-8a0b78b0f7e0	10	DEPT_USER	2025-12-09 10:18:28	2025-12-09 10:18:28
2c801690-eb5a-45f9-8b68-706e4123eda5	34	DEPT_HEAD	2025-12-18 10:22:07	2025-12-18 10:22:07
63550f47-c22b-4dc6-8fa7-3cdb7f9d6d50	39	DEPT_USER	2025-12-18 10:23:45	2025-12-18 10:23:45
2ce96185-a238-4485-b184-d1b8419a3db2	18	DEPT_USER	2025-12-18 10:24:36	2025-12-18 10:24:36
05f2a416-f6ec-4066-bfbf-87137b00d3eb	28	DEPT_HEAD	2025-12-18 10:26:35	2025-12-18 10:26:35
443e2c38-fc5e-482d-8e7b-09b8c472207c	24	DEPT_USER	2025-12-18 10:27:48	2025-12-18 10:27:48
eeab2212-dcee-40af-bcbb-2afb6e8aff9c	43	DEPT_USER	2025-12-18 10:29:59	2025-12-18 10:29:59
d7eac74c-a95c-4f45-bd5e-d75f7b4454ef	17	DEPT_HEAD	2025-12-18 10:30:42	2025-12-18 10:30:42
eda6537a-f600-46ad-8d97-fc091be5fb0d	36	DEPT_USER	2025-12-18 10:31:28	2025-12-18 10:31:28
13de1c9f-0405-478f-9cc8-cdb396847603	31	DEPT_USER	2025-12-18 10:32:59	2025-12-18 10:32:59
b2a91188-c1d3-4c9f-9c4f-8da0525e4151	27	DEPT_HEAD	2025-12-18 10:33:48	2025-12-18 10:33:48
d99b0b24-fdcc-40be-89be-55b27e82a18a	23	DEPT_HEAD	2025-12-18 11:20:51	2025-12-18 11:20:51
ca63a288-e0cd-4f7d-8bbd-6fdbd566b207	12	DEPT_USER	2025-12-18 11:23:20	2025-12-18 11:23:20
1bd356b2-2281-476c-9b67-731bd1145172	26	DEPT_HEAD	2025-12-18 11:24:00	2025-12-18 11:24:00
4b457ea3-4c52-41e9-a262-ca9b90e20d65	29	DEPT_HEAD	2025-12-18 11:28:51	2025-12-18 11:28:51
44940d2e-39b6-4789-aad7-00a0a7d10bcf	38	DEPT_USER	2025-12-18 11:29:27	2025-12-18 11:29:27
116201c1-f341-4154-a9a7-a8a60cf68123	40	DEPT_USER	2025-12-18 11:33:03	2025-12-18 11:33:03
ba226692-bff6-4a71-9deb-5070d0676de3	13	DEPT_HEAD	2025-12-18 11:34:01	2025-12-18 11:34:01
ac3d2377-1af9-42e4-8372-ac14271dc75e	30	DEPT_USER	2025-12-18 11:37:20	2025-12-18 11:37:20
2a25d4ae-246f-4b12-9749-0f398ac3a316	21	DEPT_HEAD	2025-12-18 11:38:19	2025-12-18 11:38:19
b3c80c9d-1103-4aa4-b7ea-6f431aef7f32	20	DEPT_USER	2025-12-18 13:06:40	2025-12-18 13:06:40
ff729fb8-9adb-4eef-95e1-dc286a2ba72b	22	DEPT_HEAD	2025-12-18 13:08:22	2025-12-18 13:08:22
1b29e52d-2e38-42fd-a8f9-f7a62702def9	35	DEPT_USER	2025-12-18 13:08:53	2025-12-18 13:08:53
d3f006cb-404f-4494-8b52-4762b9f2f634	25	DEPT_USER	2025-12-18 13:09:48	2025-12-18 13:09:48
f12bca41-1efc-4f0a-b7ac-443fb0ad0f1e	15	DEPT_HEAD	2025-12-18 13:10:29	2025-12-18 13:10:29
f4cf8d64-67fd-4475-bfd6-b88ffd8020b9	33	DEPT_USER	2025-12-18 13:11:52	2025-12-18 13:11:52
2fd0b84d-73eb-4915-8eb7-41b6dc060a68	41	DEPT_USER	2025-12-18 13:12:23	2025-12-18 13:12:23
8bbe307f-a5a3-44fd-9807-e3077e53495e	37	DEPT_USER	2025-12-18 13:12:52	2025-12-18 13:12:52
fa23150b-1974-490d-93ad-e6f38da1ae7f	19	DEPT_HEAD	2025-12-18 13:13:48	2025-12-18 13:13:48
28848dbb-c6b4-4f83-8136-bf120301b38a	32	DEPT_HEAD	2025-12-18 13:14:43	2025-12-18 13:14:43
10502049-59b6-4e63-a913-2a8d0672bd3f	16	DEPT_HEAD	2025-12-18 13:15:49	2025-12-18 13:15:49
86ba50e1-4551-4d73-848c-ca3a2018bbe1	42	DEPT_USER	2025-12-18 13:17:22	2025-12-18 13:17:22
bea55ae7-1ce8-4699-8a69-7c1d0a6485db	14	DEPT_HEAD	2025-12-18 13:18:12	2025-12-18 13:18:12
d196adb5-6822-49b5-a159-79590c6a5853	11	DEPT_USER	2025-12-18 13:18:52	2025-12-18 13:18:52
e6d45b23-d9be-4760-8dbb-2939415527d4	11	DEPT_HEAD	2025-12-18 13:18:52	2025-12-18 13:18:52
472abc8d-c6bb-470b-8b7e-ae9d551fec07	7	DEPT_HEAD	2025-12-19 20:50:44	2025-12-19 20:50:44
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: easymain
--

COPY public.users (id, username, name, email, email_verified_at, password, remember_token, created_at, updated_at, ou, role_kode, kode_department) FROM stdin;
6	dept_head	Department Head	dept_head@email.com	\N	$2y$12$HBcRhnkmIK4AkHDYNg1K7OWiuc.QcaP4eDNGqtKNNKg0b8ywJPjSa	\N	2025-12-03 11:33:21	2025-12-19 20:34:11	\N	DEPT_HEAD	ADV
12	dzulfiqar	Dzulfiqar Ash Shiddiq	dzulfiqar.shiddiq@lrtjakarta.co.id	\N	$2y$12$piS/ip/xdIuoDtVbUrVWS./x9JC.gnMazHpn/XooCb/kPei1h2MDi	\N	2025-12-18 11:13:08	2025-12-18 11:23:20	\N	DEPT_USER	AKP
9	dept_head2	dept head2	dept_head2@email.com	\N	$2y$12$Ds.ZYbGi5TE6C3SXurCOpOXlrVxf8PZyRwd4tMQT/U3LgjnePZ9Yi	\N	2025-12-09 11:09:05	2025-12-13 07:51:49	\N	DEPT_HEAD	BDV
13	Heriyadi	Heriyadi	heriyadi@lrtjakarta.co.id	\N	$2y$12$o1mrEbVElkw.jCXfdwrRXOZeymiQGU1febfSLkl5E9DIZZznhUqh.	\N	2025-12-18 11:13:08	2025-12-18 11:34:01	\N	DEPT_HEAD	KAM
2	admin	Administrator	admin@example.com	\N	$2y$12$2ikMGXIZI7x/FMUKnpB07uS25XhJoVmkwjhq/OFJBhzE6seFa1yLu	7sx8wQBFpdcb8fGezSHPbD5cl74uxtqTLUnmq3neFQ7BSa2SWZ12P59qBHKg	2025-11-10 13:08:40	2025-12-03 14:19:16	local	SYSADMIN	\N
14	Wildan	Wildan Prasetyo Utomo	wildan@lrtjakarta.co.id	\N	$2y$12$ro6Ka1n56iKVGTA9OofAD.6nEVnfmT6J6tIgxN3PFNAGrLmI6zNW2	\N	2025-12-18 11:13:08	2025-12-18 13:18:12	\N	DEPT_HEAD	MKL
7	dept_user	Department User	dept_user@email.com	\N	$2y$12$Gg18vXWYXq7E.mDjGjgzou8U/Ao1E1l3qs1x5Wi6luDH5AB3cVNO6	\N	2025-12-03 11:33:21	2025-12-19 20:49:52	\N	DEPT_USER	AKP
3	am_admin	Asset Managmenet Admin	am_admin@email.com	\N	$2y$12$3vIWskf5pjA.NcZsr9INBuFFbzgS3ZH1NBwfzJO.zqG.z3SlLEIOy	\N	2025-12-03 11:33:21	2025-12-03 11:02:48	\N	AM_ADMIN	\N
4	am_head	Asset Management Head	am_head@email.com	\N	$2y$12$YvhtYfxZ3Fu2bFfyoZNBQe1MRaHK0mFZ73mi0I0MAw0hQJc4OLABC	\N	2025-12-03 11:33:21	2025-12-03 11:02:48	\N	AM_HEAD	\N
5	auditor	Auditor	auditor@email.com	\N	$2y$12$DIj7ecgTIXv7ljmYuvNHWucFehrEXm6JSCEIT64K99oJeq4.Y9pPu	\N	2025-12-03 11:33:21	2025-12-03 11:02:49	\N	AUDITOR	\N
10	dept_user2	Department User	dept_user2@email.com	\N	$2y$12$Dod68ehZd1x4qqL4GiGjl.Pgr4K8J67x4Byunbc8QH8H/DLNVkhXq	\N	2025-12-09 11:09:05	2025-12-09 10:18:28	\N	DEPT_USER	BDV
15	Parmonangan	Parmonangan Manalu	parmonanganm@lrtjakarta.co.id	\N	$2y$12$IGKOPxAQGM0z4BxATE/lcOZYJM6c4.rE0brpFJK2eK/hWuOLYv6e2	\N	2025-12-18 11:13:08	2025-12-18 13:10:29	\N	DEPT_HEAD	POP
16	Sasongko	R Sasongko Hendro	sas.hendro@lrtjakarta.co.id	\N	$2y$12$/QnodK0OcLNobfjBC7MdFe8vPQNsci4v/jY6.vd4vcpZjDMhNCJx6	\N	2025-12-18 11:13:08	2025-12-18 13:15:49	\N	DEPT_HEAD	ASP
38	Fahmi	Fahmi Handyan Arko	fahmi.arko@lrtjakarta.co.id	\N	$2y$12$eR4GFZVU9DLFY.EY459gSunUtsmIkKrUW0fx6TVS7lG1rQO98psLG	\N	2025-12-18 11:13:08	2025-12-18 11:29:27	\N	DEPT_USER	\N
18	Agus	Agus Fitriyadi	agus.fitriyadi@lrtjakarta.co.id	\N	$2y$12$x/vg2io4krtg5ZwznTLjg.KY79y2XfrhYYgRq6tOS7kqOBPh1XG0G	\N	2025-12-18 11:13:08	2025-12-18 10:24:36	\N	DEPT_USER	OIT
19	Rifaldi	Rifaldi Lizarwan	rifal@lrtjakarta.co.id	\N	$2y$12$CRoQwNw0uZanMt5X/5xl8efbWR9pLY5entlLWaAT/BcyhqLaZyfs6	\N	2025-12-18 11:13:08	2025-12-18 13:13:48	\N	DEPT_HEAD	SDM
20	Luthfi	Luthfi Prasetya Kurniawan	luthfi.kurniawan@lrtjakarta.co.id	\N	$2y$12$IIky8KTkdVo.Jnxxz7XaW.kRb8smHo/MqQgPhLlCEfDyUNGnh/MqG	\N	2025-12-18 11:13:08	2025-12-18 13:06:40	\N	DEPT_USER	FOP
21	Julian	Julian Hanggara Adiguna	julian@lrtjakarta.co.id	\N	$2y$12$6RqvPcBbhKGdqapGwmUUT.hayBa7Xgnd64W.BX0ZegSoQ29/o3gMG	\N	2025-12-18 11:13:08	2025-12-18 11:38:19	\N	DEPT_HEAD	RMP
22	Mario	Mario Adhitya	mario.adhitya@lrtjakarta.co.id	\N	$2y$12$qIxtE75OokNTTv3VrmSzmeEbQAgu/Y3YCh.2vYkjRPW/ysqkrnDz6	\N	2025-12-18 11:13:08	2025-12-18 13:08:22	\N	DEPT_HEAD	KOM
23	Dioba	Dioba Biondi Fairmont	dioba.fairmont@lrtjakarta.co.id	\N	$2y$12$19elsCjeIpFGcR4kDumLEOYhBM7VpaHj2STOSHD5UgP0X0LSHUIxO	\N	2025-12-18 11:13:08	2025-12-18 11:20:51	\N	DEPT_HEAD	BUM
24	Ainur	Ainur Hafidz	ainurhafidz@lrtjakarta.co.id	\N	$2y$12$nU6gWEWF5VgbdUE/oflFfOqEq0dtW6czCbM/vDZ.wXr/tEBvBhlLi	\N	2025-12-18 11:13:08	2025-12-18 10:27:48	\N	DEPT_USER	RMP
25	Nilla	Nilla Maghfiroh	nillamag@lrtjakarta.co.id	\N	$2y$12$tpHoJxB.vUfzx3PfCVpfcuW1a5BAn3mj.Gxc9N4C.B5.58Qxlrdr6	\N	2025-12-18 11:13:08	2025-12-18 13:09:48	\N	DEPT_USER	POP
26	Erna	Erna Eksaningrum	erna.eksaningrum@lrtjakarta.co.id	\N	$2y$12$EgXd/QLapFFnC04mravs4eRm8VWVgGEJd5LyH.7Gsfrb5HDfDyu7e	\N	2025-12-18 11:13:08	2025-12-18 11:24:00	\N	DEPT_HEAD	ASP
27	Yosa	Yosa Merina Fahri	yosa.merina@lrtjakarta.co.id	\N	$2y$12$gLij7eUYkSPykDA8rF67vuWzdGp3KnhEhkPmrlYH999vw/iVKiwX.	\N	2025-12-18 11:13:08	2025-12-18 10:33:48	\N	DEPT_HEAD	MKL
28	Agussuginto	Agus Sugianto	agus.sugianto@lrtjakarta.co.id	\N	$2y$12$uJrTQLdYqq.AARoWu35r7e5KD903AHjfxTJptT3rAJbBJmtABlKT2	\N	2025-12-18 11:13:08	2025-12-18 10:26:35	\N	DEPT_HEAD	JLB
29	Fachri	Fachri Nugraha	fachri.nugraha@lrtjakarta.co.id	\N	$2y$12$FEpEUPNiyTnZw4J3NNYc1e3QiUnvkAdl1U1rbrifXD0LvStA1zhyO	\N	2025-12-18 11:13:08	2025-12-18 11:28:51	\N	DEPT_HEAD	DIT
30	Isti	Isti Triastuti Ningrum	isti.triastuti@lrtjakarta.co.id	\N	$2y$12$1l7ebbYMFDbZBNXwAHab1O8ZDmxKfdNs1VNLdyF4d1./FEab8KJVi	\N	2025-12-18 11:13:08	2025-12-18 11:37:20	\N	DEPT_USER	RSN
31	Devi	Devi Merlinta	devinta@lrtjakarta.co.id	\N	$2y$12$Jg8d1h00QvFo2WSXkB.ivux3oFV6e1/ucqkkW6E98/UfRyU8fI2pi	\N	2025-12-18 11:13:08	2025-12-18 10:32:59	\N	DEPT_USER	RMP
32	Rifqi	Rifqi Firmanda	rifqi.firmanda@lrtjakarta.co.id	\N	$2y$12$YpsCY2.2nYpReSzMtycRyOnU5Vt7T.Wxg8kjg1QRgAvC7WXEZApwS	\N	2025-12-18 11:13:08	2025-12-18 13:14:43	\N	DEPT_HEAD	PRP
33	Regi	Regi Maulana Musfiq	regi.musfiq@lrtjakarta.co.id	\N	$2y$12$d4jLIAJI758RwrfHV3zkHusTWixI8GX1fE1Bb8p2S6t/Ax0fL.rc6	\N	2025-12-18 11:13:08	2025-12-18 13:11:52	\N	DEPT_USER	MKL
34	Adhisatya	Adhisatya Mahendra	adhisatya.mahendra@lrtjakarta.co.id	\N	$2y$12$S4SrjHa.acGMcgQqCTnuTuErmVFOFA1LE77flhXUEX9c6tWMIwlt2	\N	2025-12-18 11:13:08	2025-12-18 10:22:07	\N	DEPT_HEAD	FPM
35	Muhammad	Muhammad Nurfadhillah Igus	nurfadhillah.igus@lrtjakarta.co.id	\N	$2y$12$9D73.M6JeTyrQbvEgKl.E.0nt8Zy6oXHkm1Og6ejiRA0FJsZdSTFS	\N	2025-12-18 11:13:08	2025-12-18 13:08:53	\N	DEPT_USER	JLB
36	DeaK	Dea Khusnul Khotimah	dea.khotimah@lrtjakarta.co.id	\N	$2y$12$/NqVXWaTOkZi/o8S3OmyO.8Pfd53eX32hQZIAXNQb9h3VhEDLEDCO	\N	2025-12-18 11:13:08	2025-12-18 10:31:28	\N	DEPT_USER	AKP
37	Ricky	Ricky Kurniawan	ricky.kurniawan@lrtjakarta.co.id	\N	$2y$12$Mk34oGtJ2elO17ckTGf3dePlJvTz3FuVJn5LalI3B7j.AdhSgemPu	\N	2025-12-18 11:13:08	2025-12-18 13:12:52	\N	DEPT_USER	KAM
11	wara	Wara Permana	wara@lrtjakarta.co.id	\N	$2y$12$1h9YCgJPQNUIaJOgNaEN4.3vuzM3z3QSpWlGIw3IS6XdUhKc2/9uq	\N	2025-12-18 11:13:08	2025-12-18 13:18:52	\N	DEPT_USER	AKP
39	Afriandi	Afriandi Simanungkalit	afriandi.simanungkalit@lrtjakarta.co.id	\N	$2y$12$g9TohbzIsLewE3d6bjkLHuLkRMpX0u6Un4hQVAVX7Zetgjmng9KpG	\N	2025-12-18 11:13:08	2025-12-18 10:23:45	\N	DEPT_USER	MKL
40	Ghozi	Ghozi Fawwaz	ghozi@lrtjakarta.co.id	\N	$2y$12$NM9Y9T9GIX58wCN3G1wmzu0ucmkP1.ps0wX8wDfb/xrqx1Dl36D82	\N	2025-12-18 11:13:08	2025-12-18 11:33:03	\N	DEPT_USER	DIT
17	Arny	Arny Zusria	zusria@lrtjakarta.co.id	\N	$2y$12$XV/XJeB/W7DxUt5NXglSqe7huVSZht01gJaQPJ/ES585QuyJnDcCq	\N	2025-12-18 11:13:08	2025-12-18 10:30:42	\N	DEPT_HEAD	AKP
43	AndiNur	Andi Nur Taufik Fajrin	andi.fajrin@lrtjakarta.co.id	\N	$2y$12$OpGSN3pOW72FDB3DQ6L2FO36rJGeojMdJgpHUSkxUCOE.k/NC15Va	\N	2025-12-18 11:13:08	2025-12-18 10:29:59	\N	DEPT_USER	WRH
41	ReviA	Revi Arya Nisa	revi.aryannisa@lrtjakarta.co.id	\N	$2y$12$Ssrs0mrnXfEMxeXzdNS17Opr7KjtdeRfeu7brFOVPE08Bo.t/dK0y	\N	2025-12-18 11:13:08	2025-12-18 13:12:23	\N	DEPT_USER	WRH
42	Syauqi	Syauqi	syauqi@lrtjakarta.co.id	\N	$2y$12$.Hbaepr9irDIvBC.vE5MjeiRxqYlQpjcBcT0IKnjlH0VJiJyhpp8W	\N	2025-12-18 11:13:08	2025-12-18 13:17:22	\N	DEPT_USER	WRH
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: easymain_u_lrtj
--

SELECT pg_catalog.setval('public.migrations_id_seq', 1, false);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: easymain
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 337, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: easymain
--

SELECT pg_catalog.setval('public.users_id_seq', 48, true);


--
-- Name: asset_group_counters asset_group_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.asset_group_counters
    ADD CONSTRAINT asset_group_counters_pkey PRIMARY KEY (group_code);


--
-- Name: asset_parent_counters asset_parent_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.asset_parent_counters
    ADD CONSTRAINT asset_parent_counters_pkey PRIMARY KEY (parent_code);


--
-- Name: assets assets_asset_code_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_code_unique UNIQUE (asset_code);


--
-- Name: assets assets_asset_number_parent_asset_number_child_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_number_parent_asset_number_child_unique UNIQUE (asset_number_parent, asset_number_child);


--
-- Name: assets_assignment assets_assignment_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_pkey PRIMARY KEY (asset_uuid);


--
-- Name: assets_classification assets_classification_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_pkey PRIMARY KEY (asset_uuid);


--
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_asset_uuid_period_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_asset_uuid_period_unique UNIQUE (asset_uuid, period);


--
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_pkey PRIMARY KEY (uuid);


--
-- Name: assets_depr_movements assets_depr_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_movements
    ADD CONSTRAINT assets_depr_movements_pkey PRIMARY KEY (uuid);


--
-- Name: assets_depr_policy assets_depr_policy_asset_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_asset_uuid_unique UNIQUE (asset_uuid);


--
-- Name: assets_depr_policy assets_depr_policy_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_pkey PRIMARY KEY (uuid);


--
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_pkey PRIMARY KEY (uuid);


--
-- Name: assets_depr_yearly assets_depr_yearly_asset_uuid_fiscal_year_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_asset_uuid_fiscal_year_unique UNIQUE (asset_uuid, fiscal_year);


--
-- Name: assets_depr_yearly assets_depr_yearly_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_pkey PRIMARY KEY (uuid);


--
-- Name: assets_disposals assets_disposals_disposal_code_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_disposal_code_unique UNIQUE (disposal_code);


--
-- Name: assets_disposals assets_disposals_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_pkey PRIMARY KEY (uuid);


--
-- Name: assets_document assets_document_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_pkey PRIMARY KEY (asset_uuid);


--
-- Name: assets_identifiers assets_identifiers_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_pkey PRIMARY KEY (asset_uuid);


--
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (uuid);


--
-- Name: assets_qr assets_qr_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_pkey PRIMARY KEY (uuid);


--
-- Name: assets_rfid assets_rfid_epc_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_epc_unique UNIQUE (epc);


--
-- Name: assets_rfid assets_rfid_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_pkey PRIMARY KEY (uuid);


--
-- Name: assets_transfers assets_transfers_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_pkey PRIMARY KEY (uuid);


--
-- Name: assets_value_history assets_value_history_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_value_history
    ADD CONSTRAINT assets_value_history_pkey PRIMARY KEY (uuid);


--
-- Name: assets_value assets_value_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_pkey PRIMARY KEY (asset_uuid);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: master_action master_action_kode_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_action
    ADD CONSTRAINT master_action_kode_unique UNIQUE (kode);


--
-- Name: master_action master_action_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_action
    ADD CONSTRAINT master_action_pkey PRIMARY KEY (uuid);


--
-- Name: master_asset_class master_asset_class_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_kode_unique_all UNIQUE (kode);


--
-- Name: master_asset_class master_asset_class_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_pkey PRIMARY KEY (uuid);


--
-- Name: master_asset_type master_asset_type_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_asset_type
    ADD CONSTRAINT master_asset_type_kode_unique_all UNIQUE (kode);


--
-- Name: master_asset_type master_asset_type_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_asset_type
    ADD CONSTRAINT master_asset_type_pkey PRIMARY KEY (uuid);


--
-- Name: master_category_2 master_category_2_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_kode_unique_all UNIQUE (kode);


--
-- Name: master_category_2 master_category_2_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_pkey PRIMARY KEY (uuid);


--
-- Name: master_category master_category_kode_unique_active; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_active UNIQUE (kode);


--
-- Name: master_category master_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_all UNIQUE (kode);


--
-- Name: master_category master_category_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_pkey PRIMARY KEY (uuid);


--
-- Name: master_division master_division_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_division
    ADD CONSTRAINT master_division_kode_unique_all UNIQUE (kode);


--
-- Name: master_division master_division_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_division
    ADD CONSTRAINT master_division_pkey PRIMARY KEY (uuid);


--
-- Name: master_group_category master_group_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_kode_unique_all UNIQUE (kode);


--
-- Name: master_group_category master_group_category_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_pkey PRIMARY KEY (uuid);


--
-- Name: master_location master_location_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_kode_unique_all UNIQUE (kode);


--
-- Name: master_location master_location_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_pkey PRIMARY KEY (uuid);


--
-- Name: master_menu master_menu_kode_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_menu
    ADD CONSTRAINT master_menu_kode_unique UNIQUE (kode);


--
-- Name: master_menu master_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_menu
    ADD CONSTRAINT master_menu_pkey PRIMARY KEY (uuid);


--
-- Name: master_role master_role_kode_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role
    ADD CONSTRAINT master_role_kode_unique UNIQUE (kode);


--
-- Name: master_role_menu master_role_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_pkey PRIMARY KEY (uuid);


--
-- Name: master_role_menu master_role_menu_role_menu_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_role_menu_unique UNIQUE (role_kode, menu_kode);


--
-- Name: master_role master_role_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role
    ADD CONSTRAINT master_role_pkey PRIMARY KEY (uuid);


--
-- Name: master_status master_status_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_kode_unique_all UNIQUE (kode);


--
-- Name: master_status master_status_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_pkey PRIMARY KEY (uuid);


--
-- Name: master_sub_category master_sub_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_kode_unique_all UNIQUE (kode);


--
-- Name: master_sub_category master_sub_category_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_pkey PRIMARY KEY (uuid);


--
-- Name: master_sumber master_sumber_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_kode_unique_all UNIQUE (kode);


--
-- Name: master_sumber master_sumber_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_pkey PRIMARY KEY (uuid);


--
-- Name: master_transaction master_transaction_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_kode_unique_all UNIQUE (kode);


--
-- Name: master_transaction master_transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_pkey PRIMARY KEY (uuid);


--
-- Name: master_uom master_uom_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_kode_unique_all UNIQUE (kode);


--
-- Name: master_uom master_uom_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_pkey PRIMARY KEY (uuid);


--
-- Name: master_user_code master_user_code_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_kode_unique_all UNIQUE (kode);


--
-- Name: master_user_code master_user_code_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_pkey PRIMARY KEY (uuid);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain_u_lrtj
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: return_history return_history_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.return_history
    ADD CONSTRAINT return_history_pkey PRIMARY KEY (uuid);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: user_role user_role_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_pkey PRIMARY KEY (uuid);


--
-- Name: user_role user_role_user_role_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_user_role_unique UNIQUE (user_id, role_kode);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: assets_depr_ledger_monthly_period_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_ledger_monthly_period_index ON public.assets_depr_ledger_monthly USING btree (period) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_asset_uuid_period_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_asset_uuid_period_index ON public.assets_depr_movements USING btree (asset_uuid, period) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_category_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_category_index ON public.assets_depr_movements USING btree (category) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_depr_start_period_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_depr_start_period_index ON public.assets_depr_movements USING btree (depr_start_period) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_group_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_group_uuid_index ON public.assets_depr_movements USING btree (group_uuid) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_period_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_period_index ON public.assets_depr_movements USING btree (period) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_movements_source_type_source_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_movements_source_type_source_uuid_index ON public.assets_depr_movements USING btree (source_type, source_uuid) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_depr_yearly_fiscal_year_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_depr_yearly_fiscal_year_index ON public.assets_depr_yearly USING btree (fiscal_year) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_disposals_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_disposals_asset_uuid_index ON public.assets_disposals USING btree (asset_uuid);


--
-- Name: assets_disposals_kode_status_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_disposals_kode_status_index ON public.assets_disposals USING btree (kode_status);


--
-- Name: assets_document_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_document_asset_uuid_index ON public.assets_document USING btree (asset_uuid);


--
-- Name: assets_identifiers_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_identifiers_asset_uuid_index ON public.assets_identifiers USING btree (asset_uuid);


--
-- Name: assets_parent_child_unique; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX assets_parent_child_unique ON public.assets USING btree (asset_number_parent, asset_number_child);


--
-- Name: assets_qr_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_qr_asset_uuid_index ON public.assets_qr USING btree (asset_uuid);


--
-- Name: assets_rfid_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_rfid_asset_uuid_index ON public.assets_rfid USING btree (asset_uuid);


--
-- Name: assets_transfers_kode_status_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_transfers_kode_status_index ON public.assets_transfers USING btree (kode_status);


--
-- Name: assets_transfers_transfer_code_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_transfers_transfer_code_index ON public.assets_transfers USING btree (transfer_code);


--
-- Name: assets_transfers_type_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_transfers_type_index ON public.assets_transfers USING btree (type);


--
-- Name: assets_value_history_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_value_history_asset_uuid_index ON public.assets_value_history USING btree (asset_uuid) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: assets_value_history_pic_request_uid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX assets_value_history_pic_request_uid_index ON public.assets_value_history USING btree (pic_request_uid) WITH (fillfactor='100', deduplicate_items='true');


--
-- Name: idx_master_category2_kode_category; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX idx_master_category2_kode_category ON public.master_category_2 USING btree (kode_category);


--
-- Name: idx_master_category_kode_asset_type; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX idx_master_category_kode_asset_type ON public.master_category USING btree (kode_asset_type);


--
-- Name: master_asset_class_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_asset_class_kode_index ON public.master_asset_class USING btree (kode);


--
-- Name: master_asset_class_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_asset_class_kode_unique_active ON public.master_asset_class USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_category_2_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_category_2_kode_index ON public.master_category_2 USING btree (kode);


--
-- Name: master_category_2_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_category_2_kode_unique_active ON public.master_category_2 USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_category_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_category_kode_index ON public.master_category USING btree (kode);


--
-- Name: master_group_category_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_group_category_kode_index ON public.master_group_category USING btree (kode);


--
-- Name: master_group_category_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_group_category_kode_unique_active ON public.master_group_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_location_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_location_kode_index ON public.master_location USING btree (kode);


--
-- Name: master_location_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_location_kode_unique_active ON public.master_location USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_role_menu_actions_gin; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_role_menu_actions_gin ON public.master_role_menu USING gin (actions jsonb_path_ops);


--
-- Name: master_status_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_status_kode_index ON public.master_status USING btree (kode);


--
-- Name: master_status_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_status_kode_unique_active ON public.master_status USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_sub_category_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_sub_category_kode_index ON public.master_sub_category USING btree (kode);


--
-- Name: master_sub_category_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_sub_category_kode_unique_active ON public.master_sub_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_sumber_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_sumber_kode_index ON public.master_sumber USING btree (kode);


--
-- Name: master_sumber_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_sumber_kode_unique_active ON public.master_sumber USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_transaction_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_transaction_kode_index ON public.master_transaction USING btree (kode);


--
-- Name: master_transaction_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_transaction_kode_unique_active ON public.master_transaction USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_uom_kode_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_uom_kode_index ON public.master_uom USING btree (kode);


--
-- Name: master_uom_kode_unique_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX master_uom_kode_unique_active ON public.master_uom USING btree (kode) WHERE (deleted_at IS NULL);


--
-- Name: master_user_code_department_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_user_code_department_index ON public.master_user_code USING btree (department);


--
-- Name: master_user_code_status_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX master_user_code_status_index ON public.master_user_code USING btree (status);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: return_history_asset_uuid_created_at_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX return_history_asset_uuid_created_at_index ON public.return_history USING btree (asset_uuid, created_at);


--
-- Name: return_history_asset_uuid_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX return_history_asset_uuid_index ON public.return_history USING btree (asset_uuid);


--
-- Name: return_history_source_code_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX return_history_source_code_index ON public.return_history USING btree (source_code);


--
-- Name: return_history_source_type_source_id_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX return_history_source_type_source_id_index ON public.return_history USING btree (source_type, source_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: easymain
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: uniq_assets_parent_child_active; Type: INDEX; Schema: public; Owner: easymain
--

CREATE UNIQUE INDEX uniq_assets_parent_child_active ON public.assets USING btree (asset_number_parent, asset_number_child) WHERE (deleted_at IS NULL);


--
-- Name: assets_assignment assets_assignment_asset_maintenance_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_maintenance_foreign FOREIGN KEY (asset_maintenance) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_assignment assets_assignment_asset_owner_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_owner_foreign FOREIGN KEY (asset_owner) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_assignment assets_assignment_asset_user_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_user_foreign FOREIGN KEY (asset_user) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_assignment assets_assignment_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_classification assets_classification_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_classification assets_classification_kode_asset_transaction_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_transaction_foreign FOREIGN KEY (kode_asset_transaction) REFERENCES public.master_transaction(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_classification assets_classification_kode_asset_type_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_type_foreign FOREIGN KEY (kode_asset_type) REFERENCES public.master_asset_type(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_classification assets_classification_kode_category_2_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_2_foreign FOREIGN KEY (kode_category_2) REFERENCES public.master_category_2(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_classification assets_classification_kode_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_foreign FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_classification assets_classification_kode_sub_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_sub_category_foreign FOREIGN KEY (kode_sub_category) REFERENCES public.master_sub_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_depr_movements assets_depr_movements_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_movements
    ADD CONSTRAINT assets_depr_movements_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_depr_policy assets_depr_policy_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_from_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_from_asset_uuid_foreign FOREIGN KEY (from_asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_to_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_to_asset_uuid_foreign FOREIGN KEY (to_asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_depr_yearly assets_depr_yearly_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_disposals assets_disposals_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_document assets_document_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_identifiers assets_identifiers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets assets_kode_asset_class_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_asset_class_foreign FOREIGN KEY (kode_asset_class) REFERENCES public.master_asset_class(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets assets_kode_location_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_location_foreign FOREIGN KEY (kode_location) REFERENCES public.master_location(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets assets_kode_status_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_status_foreign FOREIGN KEY (kode_status) REFERENCES public.master_status(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets assets_kode_sumber_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_sumber_foreign FOREIGN KEY (kode_sumber) REFERENCES public.master_sumber(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assets_qr assets_qr_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_rfid assets_rfid_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_transfers assets_transfers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_value assets_value_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- Name: assets_value_history assets_value_history_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_value_history
    ADD CONSTRAINT assets_value_history_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- Name: assets_value assets_value_kode_uom_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_kode_uom_foreign FOREIGN KEY (kode_uom) REFERENCES public.master_uom(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: master_category_2 fk_cat2_category_kode; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT fk_cat2_category_kode FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: master_category fk_category_asset_type_kode; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT fk_category_asset_type_kode FOREIGN KEY (kode_asset_type) REFERENCES public.master_asset_type(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: master_role_menu master_role_menu_menu_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_menu_kode_foreign FOREIGN KEY (menu_kode) REFERENCES public.master_menu(kode) ON DELETE CASCADE;


--
-- Name: master_role_menu master_role_menu_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE CASCADE;


--
-- Name: user_role user_role_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE CASCADE;


--
-- Name: user_role user_role_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: easymain
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict yJb39FbWM7VbGZVSC0Tcpr6pYLldBWsTqijF77O5fo6b9voJha4sFHmmBzp9BAb

