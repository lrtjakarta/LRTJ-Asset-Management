--
-- PostgreSQL database dump
--

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

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA public;


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'standard public schema';


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

