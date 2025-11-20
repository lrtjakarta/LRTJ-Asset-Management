--
-- PostgreSQL database dump
--

\restrict Xa8hGpCMRBrVm4N8DltIJiXhuseK85m0Z8kbMEFUdfrlwBKHKuBGEVUcrK1l9Ks

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-11-20 09:43:33

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
-- TOC entry 5 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA public;


--
-- TOC entry 5560 (class 0 OID 0)
-- Dependencies: 5
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 249 (class 1259 OID 17179)
-- Name: asset_group_counters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asset_group_counters (
    group_code character varying(50) NOT NULL,
    last_parent_seq bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- TOC entry 250 (class 1259 OID 17187)
-- Name: asset_parent_counters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asset_parent_counters (
    parent_code character varying(100) NOT NULL,
    last_child_seq bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- TOC entry 241 (class 1259 OID 16917)
-- Name: assets; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 244 (class 1259 OID 17006)
-- Name: assets_assignment; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 243 (class 1259 OID 16967)
-- Name: assets_classification; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 258 (class 1259 OID 17958)
-- Name: assets_depr_ledger_monthly; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 257 (class 1259 OID 17934)
-- Name: assets_depr_movements; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT assets_depr_movements_category_check CHECK (((category)::text = ANY ((ARRAY['ADDITION'::character varying, 'TRANSFER_IN'::character varying, 'TRANSFER_OUT'::character varying, 'DISPOSAL'::character varying, 'ADJUSTMENT_VALUE'::character varying, 'ADJUSTMENT_DEPRECIATION'::character varying])::text[])))
);


--
-- TOC entry 255 (class 1259 OID 17874)
-- Name: assets_depr_policy; Type: TABLE; Schema: public; Owner: -
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
    CONSTRAINT assets_depr_policy_convention_check CHECK (((convention)::text = ANY ((ARRAY['PRORATA_MONTH'::character varying, 'FULL_MONTH'::character varying, 'HALF_MONTH'::character varying, 'PRORATA_DAILY'::character varying])::text[]))),
    CONSTRAINT assets_depr_policy_method_check CHECK (((method)::text = 'SL'::text)),
    CONSTRAINT assets_depr_policy_start_rule_check CHECK (((start_rule)::text = 'CUT_OFF_NEXT_OR_NEXT2'::text))
);


--
-- TOC entry 259 (class 1259 OID 18013)
-- Name: assets_depr_transfer_requests; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 256 (class 1259 OID 17906)
-- Name: assets_depr_yearly; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 252 (class 1259 OID 17693)
-- Name: assets_disposals; Type: TABLE; Schema: public; Owner: -
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
    ba_file_size bigint
);


--
-- TOC entry 246 (class 1259 OID 17050)
-- Name: assets_document; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 242 (class 1259 OID 16955)
-- Name: assets_identifiers; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 247 (class 1259 OID 17062)
-- Name: assets_qr; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 248 (class 1259 OID 17083)
-- Name: assets_rfid; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 251 (class 1259 OID 17568)
-- Name: assets_transfers; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 245 (class 1259 OID 17032)
-- Name: assets_value; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 254 (class 1259 OID 17843)
-- Name: assets_value_history; Type: TABLE; Schema: public; Owner: -
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
    acq_code character varying(64) NOT NULL
);


--
-- TOC entry 223 (class 1259 OID 16432)
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- TOC entry 224 (class 1259 OID 16442)
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- TOC entry 261 (class 1259 OID 26079)
-- Name: master_action; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 235 (class 1259 OID 16834)
-- Name: master_asset_class; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 228 (class 1259 OID 16708)
-- Name: master_category; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 229 (class 1259 OID 16745)
-- Name: master_category_2; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 227 (class 1259 OID 16695)
-- Name: master_division; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.master_division (
    uuid uuid DEFAULT gen_random_uuid() CONSTRAINT master_asset_type_uuid_not_null NOT NULL,
    kode character varying(50) CONSTRAINT master_asset_type_kode_not_null NOT NULL,
    name character varying(191) CONSTRAINT master_asset_type_name_not_null NOT NULL,
    status boolean DEFAULT true CONSTRAINT master_asset_type_status_not_null NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- TOC entry 232 (class 1259 OID 16794)
-- Name: master_group_category; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 231 (class 1259 OID 16781)
-- Name: master_location; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 262 (class 1259 OID 26105)
-- Name: master_menu; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 260 (class 1259 OID 26066)
-- Name: master_role; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 263 (class 1259 OID 26122)
-- Name: master_role_menu; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 234 (class 1259 OID 16820)
-- Name: master_status; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 230 (class 1259 OID 16768)
-- Name: master_sub_category; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 225 (class 1259 OID 16539)
-- Name: master_sumber; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 226 (class 1259 OID 16572)
-- Name: master_transaction; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 233 (class 1259 OID 16807)
-- Name: master_uom; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 236 (class 1259 OID 16866)
-- Name: master_user_code; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 221 (class 1259 OID 16387)
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- TOC entry 220 (class 1259 OID 16386)
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
-- TOC entry 5561 (class 0 OID 0)
-- Dependencies: 220
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- TOC entry 238 (class 1259 OID 16884)
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 237 (class 1259 OID 16883)
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5562 (class 0 OID 0)
-- Dependencies: 237
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- TOC entry 253 (class 1259 OID 17715)
-- Name: return_history; Type: TABLE; Schema: public; Owner: -
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
    return_code character varying(64) NOT NULL,
    CONSTRAINT return_history_source_type_chk CHECK (((source_type)::text = ANY (ARRAY[('transfer'::character varying)::text, ('disposal'::character varying)::text])))
);


--
-- TOC entry 222 (class 1259 OID 16420)
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
-- TOC entry 264 (class 1259 OID 26155)
-- Name: user_role; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_role (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id bigint NOT NULL,
    role_kode character varying(50) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- TOC entry 240 (class 1259 OID 16901)
-- Name: users; Type: TABLE; Schema: public; Owner: -
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
-- TOC entry 239 (class 1259 OID 16900)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5563 (class 0 OID 0)
-- Dependencies: 239
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 5060 (class 2604 OID 16390)
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- TOC entry 5085 (class 2604 OID 16887)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 5086 (class 2604 OID 16904)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5539 (class 0 OID 17179)
-- Dependencies: 249
-- Data for Name: asset_group_counters; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.asset_group_counters VALUES ('J1102', 2, '2025-10-24 12:42:04+07', '2025-10-24 12:43:03+07');
INSERT INTO public.asset_group_counters VALUES ('A1100', 2, '2025-10-20 11:15:14+07', '2025-11-12 11:30:20+07');
INSERT INTO public.asset_group_counters VALUES ('A1103', 1, '2025-11-14 14:49:08+07', '2025-11-14 14:49:07+07');
INSERT INTO public.asset_group_counters VALUES ('AWI1100', 2, '2025-11-18 13:12:11+07', '2025-11-18 13:12:41+07');
INSERT INTO public.asset_group_counters VALUES ('AWI1103', 1, '2025-11-18 13:13:19+07', '2025-11-18 13:13:18+07');


--
-- TOC entry 5540 (class 0 OID 17187)
-- Dependencies: 250
-- Data for Name: asset_parent_counters; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.asset_parent_counters VALUES ('A1100000001', 1, '2025-10-20 11:15:14+07', '2025-10-20 12:33:36+07');
INSERT INTO public.asset_parent_counters VALUES ('J1102000001', 1, '2025-10-24 12:42:04+07', '2025-10-24 12:42:03+07');
INSERT INTO public.asset_parent_counters VALUES ('J1102000002', 1, '2025-10-24 12:43:04+07', '2025-10-24 12:43:03+07');
INSERT INTO public.asset_parent_counters VALUES ('A1100000002', 1, '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07');
INSERT INTO public.asset_parent_counters VALUES ('A1103000001', 1, '2025-11-14 14:49:08+07', '2025-11-14 14:49:07+07');
INSERT INTO public.asset_parent_counters VALUES ('AWI1100000001', 0, '2025-11-18 13:12:11+07', '2025-11-18 13:12:11+07');
INSERT INTO public.asset_parent_counters VALUES ('AWI1100000002', 0, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07');
INSERT INTO public.asset_parent_counters VALUES ('AWI1103000001', 0, '2025-11-18 13:13:19+07', '2025-11-18 13:13:19+07');


--
-- TOC entry 5531 (class 0 OID 16917)
-- Dependencies: 241
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets VALUES ('79389766-c932-4879-acfb-62f247896577', 'A1100', 'UPL25110001', 'A1100000002', '01', 'test fdff', '1100', 'DIS', 'LOC-1', 'KD-4', '2025-11-12 11:30:20+07', '2025-11-17 19:08:13+07', NULL, 'UPL25110001', NULL);
INSERT INTO public.assets VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', 'AWI1100', 'AWI1100000001-00', 'AWI1100000001', '00', 'chilld', '1100', 'OPE', 'LOC-1', 'KD-1', '2025-11-18 13:12:10+07', '2025-11-18 13:12:10+07', NULL, 'UPL25110003', NULL);
INSERT INTO public.assets VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', 'AWI1100', 'AWI1100000002-00', 'AWI1100000002', '00', '123', '1100', 'OPE', 'LOC-1', 'KD-1', '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL, 'UPL25110004', NULL);
INSERT INTO public.assets VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', 'AWI1103', 'AWI1103000001-00', 'AWI1103000001', '00', 'asdasd', '1103', 'IDL', 'LOC-1', 'KD-1', '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL, 'UPL25110005', NULL);
INSERT INTO public.assets VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '1100', 'A1100000001-01', 'A1100000001', '01', 'test aaa', '1100', 'DIS', 'LOC-1', 'KD-1', '2025-10-20 12:33:36+07', '2025-11-19 15:46:58+07', NULL, NULL, NULL);
INSERT INTO public.assets VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', 'J1102', 'J1102000002-01', 'J1102000002', '01', 'test acq', '1102', 'DIS', 'LOC-1', 'KD-1', '2025-10-24 12:43:03+07', '2025-11-06 10:52:08+07', NULL, NULL, NULL);
INSERT INTO public.assets VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', 'A1103', 'A1103000001-01', 'A1103000001', '01', 'test baru', '1103', 'OPE', 'LOC-2', 'KD-4', '2025-11-14 14:49:07+07', '2025-11-14 15:00:29+07', NULL, 'UPL25110002', 'nigger');
INSERT INTO public.assets VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'A1100', 'A1100000001-00', 'A1100000001', '00', 'Dummy Asset 1 edit', '1100', 'DIS', 'LOC-1', 'KD-1', '2025-10-20 11:15:13+07', '2025-11-17 16:16:03+07', NULL, NULL, NULL);
INSERT INTO public.assets VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', 'A1101', 'A1101000002-00', 'A1101000002', '00', 'Dummy Asset 2', '1101', 'DIS', 'LOC-1', 'KD-1', '2025-10-23 15:50:24+07', '2025-11-17 16:49:04+07', NULL, NULL, NULL);


--
-- TOC entry 5534 (class 0 OID 17006)
-- Dependencies: 244
-- Data for Name: assets_assignment; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_assignment VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', 'UCD', 'SAR', 'SAR', '2025-10-24 12:43:03+07', '2025-10-24 14:33:06+07', NULL);
INSERT INTO public.assets_assignment VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', 'SAR', 'SAR', 'SAR', '2025-10-23 16:35:40+07', '2025-11-11 13:47:03+07', NULL);
INSERT INTO public.assets_assignment VALUES ('79389766-c932-4879-acfb-62f247896577', 'SAR', 'UCD', 'UCD', '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', NULL);
INSERT INTO public.assets_assignment VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', 'SAR', 'SAR', 'SAR', '2025-11-14 14:49:07+07', '2025-11-14 14:49:07+07', NULL);
INSERT INTO public.assets_assignment VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'SAR', 'SAR', 'SAR', '2025-10-20 12:33:36+07', '2025-11-14 17:12:36+07', NULL);
INSERT INTO public.assets_assignment VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', 'SAR', 'SAR', 'SAR', '2025-11-18 13:12:11+07', '2025-11-18 13:12:11+07', NULL);
INSERT INTO public.assets_assignment VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', 'SAR', 'SAR', 'SAR', '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL);
INSERT INTO public.assets_assignment VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', 'SAR', 'SAR', 'SAR', '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL);
INSERT INTO public.assets_assignment VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'SAR', 'SAR', 'SAR', '2025-10-20 11:15:13+07', '2025-11-19 15:18:06+07', NULL);


--
-- TOC entry 5533 (class 0 OID 16967)
-- Dependencies: 243
-- Data for Name: assets_classification; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_classification VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'A', NULL, NULL, NULL, NULL, '2025-10-20 12:33:36+07', '2025-10-20 12:43:35+07', NULL);
INSERT INTO public.assets_classification VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'A', NULL, NULL, NULL, NULL, '2025-10-20 11:15:13+07', '2025-10-23 16:56:54+07', NULL);
INSERT INTO public.assets_classification VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', 'A', NULL, NULL, NULL, NULL, '2025-10-23 16:35:40+07', '2025-10-23 16:56:54+07', NULL);
INSERT INTO public.assets_classification VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', 'J', NULL, NULL, NULL, NULL, '2025-10-24 12:43:03+07', '2025-10-24 12:43:03+07', NULL);
INSERT INTO public.assets_classification VALUES ('79389766-c932-4879-acfb-62f247896577', 'A', NULL, NULL, NULL, NULL, '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', NULL);
INSERT INTO public.assets_classification VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', 'A', NULL, NULL, NULL, NULL, '2025-11-14 14:49:07+07', '2025-11-14 14:49:07+07', NULL);
INSERT INTO public.assets_classification VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', 'AWI', NULL, NULL, NULL, NULL, '2025-11-18 13:12:10+07', '2025-11-18 13:12:10+07', NULL);
INSERT INTO public.assets_classification VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', 'AWI', NULL, NULL, NULL, NULL, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL);
INSERT INTO public.assets_classification VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', 'AWI', NULL, NULL, NULL, NULL, '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL);


--
-- TOC entry 5548 (class 0 OID 17958)
-- Dependencies: 258
-- Data for Name: assets_depr_ledger_monthly; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_depr_ledger_monthly VALUES ('a044659f-a578-48d9-966d-feba83b847bc', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 2000000.00, 0.00, 2200000.00, 0.00, 0.00, 0.00, 0.00, 350000.00, 350000.00, 3850000.00, '2025-11-03 14:50:31', '2025-11-04 13:19:42', NULL);
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a044659f-a169-4c1e-b6e1-adfb68bc47d9', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '2025-11-01', 1120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 93333.00, 93333.00, 1026667.00, '2025-11-03 14:50:31', '2025-11-07 10:04:50', 'DEP25110001');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a044659f-a3df-4951-8407-b1265c612c3a', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 12000000.00, 0.00, 0.00, 2200000.00, 0.00, 0.00, 100000.00, 204166.00, 304166.00, 9695834.00, '2025-11-03 14:50:31', '2025-11-07 13:23:50', 'DEP25110002');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-02bb-4472-92ce-87184909928b', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '2025-07-01', 1120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1120000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110001');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8392-4fd3-81a2-836971d89370', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-07-01', 12000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 12000000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110002');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8537-42f0-939f-4a06b3cb4afc', '8576d2d0-0914-4937-93ea-da10998f1fb9', '2025-07-01', 154550000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 154550000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110003');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8893-46a8-bd3f-48ec0cc7edb8', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '2025-08-01', 1120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1120000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110001');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8a38-4172-9434-78d0abe4d50a', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-08-01', 12000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 12000000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110002');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8bd5-4a87-82ca-66c695e641e8', '8576d2d0-0914-4937-93ea-da10998f1fb9', '2025-08-01', 154550000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3219791.00, 3219791.00, 151330209.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110003');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8e64-404a-8d5e-9bbc1b5452e4', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '2025-09-01', 1120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1120000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110001');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-8ff1-425c-aece-ae99c5d8764f', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-09-01', 12000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 12000000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110002');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-9194-48a0-9f43-834b80fe506f', '8576d2d0-0914-4937-93ea-da10998f1fb9', '2025-09-01', 151330209.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3219791.00, 6439582.00, 148110418.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110003');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-9402-4ec6-a58c-3c8fa5fd6e47', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '2025-10-01', 1120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1120000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110001');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-958f-46be-af5c-827f006a536e', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-10-01', 12000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 12000000.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110002');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0549338-9713-4218-9be2-55877cbc3958', '8576d2d0-0914-4937-93ea-da10998f1fb9', '2025-10-01', 148110418.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3219791.00, 9659373.00, 144890627.00, '2025-11-11 15:51:16', '2025-11-11 15:51:16', 'DEP25110003');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a044659f-a71b-4aa5-85cf-f741873dd7e3', '8576d2d0-0914-4937-93ea-da10998f1fb9', '2025-11-01', 144890627.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3219791.00, 12879164.00, 141670836.00, '2025-11-03 14:50:31', '2025-11-11 15:51:16', 'DEP25110003');
INSERT INTO public.assets_depr_ledger_monthly VALUES ('a0624068-3aa4-4528-92a6-2a20d64597a4', 'd215a643-1404-43a4-a0f1-133f86ad6095', '2025-11-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-11-18 11:01:18', '2025-11-18 11:01:18', 'DEP25110001');


--
-- TOC entry 5547 (class 0 OID 17934)
-- Dependencies: 257
-- Data for Name: assets_depr_movements; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_depr_movements VALUES ('a0446858-ad03-45b1-b3d3-7b8ca215d455', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'TRANSFER_OUT', 100000.00, '2025-12-01', 'b5a86b10-44bf-4090-8e00-eaa3f4cb391c', 'manual', NULL, NULL, '2025-11-03 14:58:08', '2025-11-03 14:58:08');
INSERT INTO public.assets_depr_movements VALUES ('a0446858-b076-47db-ab91-8cf3c13be97f', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 'TRANSFER_IN', 100000.00, '2025-12-01', 'b5a86b10-44bf-4090-8e00-eaa3f4cb391c', 'manual', NULL, NULL, '2025-11-03 14:58:08', '2025-11-03 14:58:08');
INSERT INTO public.assets_depr_movements VALUES ('a0446be2-35f9-4b43-9f77-e3d971a3264e', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'TRANSFER_OUT', 1000000.00, '2025-12-01', '0b85364d-5d98-4800-973f-8cb8c4940a40', 'manual', NULL, '| carry-over gross out', '2025-11-03 15:08:02', '2025-11-03 15:08:02');
INSERT INTO public.assets_depr_movements VALUES ('a0446be2-38f3-4452-8961-54c0f1928441', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'ADJUSTMENT_DEPRECIATION', 0.00, '2025-12-01', '0b85364d-5d98-4800-973f-8cb8c4940a40', 'manual', NULL, '| carry-over accum -FROM', '2025-11-03 15:08:02', '2025-11-03 15:08:02');
INSERT INTO public.assets_depr_movements VALUES ('a0446be2-3947-484e-ab41-70094c6f9729', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 'TRANSFER_IN', 1000000.00, '2025-12-01', '0b85364d-5d98-4800-973f-8cb8c4940a40', 'manual', NULL, '| carry-over gross in', '2025-11-03 15:08:02', '2025-11-03 15:08:02');
INSERT INTO public.assets_depr_movements VALUES ('a0446be2-399c-4eec-8388-50c9b4c4e463', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 'ADJUSTMENT_DEPRECIATION', 0.00, '2025-12-01', '0b85364d-5d98-4800-973f-8cb8c4940a40', 'manual', NULL, '| carry-over accum +TO', '2025-11-03 15:08:02', '2025-11-03 15:08:02');
INSERT INTO public.assets_depr_movements VALUES ('a0448f10-9c2b-4913-a5d1-a04af7dbf158', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'TRANSFER_OUT', 100000.00, '2025-12-01', '85ad2fea-6dca-4294-a419-27864d1e3fe0', 'manual', NULL, '| acq-fix gross OUT', '2025-11-03 16:46:24', '2025-11-03 16:46:24');
INSERT INTO public.assets_depr_movements VALUES ('a0448f10-9f78-4b9d-86d3-56907b743e8b', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 'TRANSFER_IN', 100000.00, '2025-12-01', '85ad2fea-6dca-4294-a419-27864d1e3fe0', 'manual', NULL, '| acq-fix gross IN', '2025-11-03 16:46:24', '2025-11-03 16:46:24');
INSERT INTO public.assets_depr_movements VALUES ('a0464812-7afc-42be-88df-5cca87bae28e', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'TRANSFER_OUT', 1000000.00, '2025-12-01', '79fe018f-b55f-4d8e-ab37-c10c0ad77e81', 'depr_transfer_request', 'a0462910-57bd-43ee-88e5-3dcb3bd51db2', 'test edit', '2025-11-04 13:19:33', '2025-11-04 13:19:33');
INSERT INTO public.assets_depr_movements VALUES ('a0464812-7f03-4b10-91d1-8e2f78d0dfe6', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '2025-11-01', 'TRANSFER_IN', 1000000.00, '2025-12-01', '79fe018f-b55f-4d8e-ab37-c10c0ad77e81', 'depr_transfer_request', 'a0462910-57bd-43ee-88e5-3dcb3bd51db2', 'test edit', '2025-11-04 13:19:33', '2025-11-04 13:19:33');
INSERT INTO public.assets_depr_movements VALUES ('a04c528e-6be9-4e23-86cd-23f40f7ffdc0', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '2025-11-01', 'ADJUSTMENT_DEPRECIATION', 100000.00, '2025-11-01', NULL, 'manual', NULL, NULL, '2025-11-07 13:23:50', '2025-11-07 13:23:50');


--
-- TOC entry 5545 (class 0 OID 17874)
-- Dependencies: 255
-- Data for Name: assets_depr_policy; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_depr_policy VALUES ('a044659f-9b6a-43a5-8602-74aba31d9420', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'SL', 48, 0.00, '2025-10-01', 'PRORATA_MONTH', 15, 'CUT_OFF_NEXT_OR_NEXT2', true, '2025-11-03 14:50:31', '2025-11-03 14:50:31');
INSERT INTO public.assets_depr_policy VALUES ('a044659f-9bba-4bcf-9567-fab0fb155c30', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'SL', 12, 0.00, '2025-10-01', 'PRORATA_MONTH', 15, 'CUT_OFF_NEXT_OR_NEXT2', true, '2025-11-03 14:50:31', '2025-11-03 14:50:31');
INSERT INTO public.assets_depr_policy VALUES ('a044659f-0e62-489f-a72d-4efdc51c8c59', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'SL', 12, 0.00, '2025-10-01', 'PRORATA_MONTH', 15, 'CUT_OFF_NEXT_OR_NEXT2', true, '2025-11-03 14:50:31', '2025-11-04 13:58:38');
INSERT INTO public.assets_depr_policy VALUES ('a044659f-9c06-4d91-8dd8-74a4485acb9f', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'SL', 48, 0.00, '2025-07-01', 'PRORATA_MONTH', 15, 'CUT_OFF_NEXT_OR_NEXT2', true, '2025-11-03 14:50:31', '2025-11-04 14:12:46');
INSERT INTO public.assets_depr_policy VALUES ('a0624067-7b28-4b32-8976-9e0007f5ebda', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'SL', 60, 0.00, '2025-11-14', 'PRORATA_MONTH', 15, 'CUT_OFF_NEXT_OR_NEXT2', true, '2025-11-18 11:01:18', '2025-11-18 11:01:18');


--
-- TOC entry 5549 (class 0 OID 18013)
-- Dependencies: 259
-- Data for Name: assets_depr_transfer_requests; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_depr_transfer_requests VALUES ('a0462910-57bd-43ee-88e5-3dcb3bd51db2', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'tf-val', 1000000.00, '2025-11-03', 'test edit', 'depr_transfer_attachments/CSUu1I3Z8egcd2NhpzHxlI6i9HJYnZZrfdoGVA4B.jpg', 'ACC', 'admin', 'admin', '2025-11-04 13:19:33', '79fe018f-b55f-4d8e-ab37-c10c0ad77e81', '2025-11-04 11:52:50', '2025-11-04 13:19:33', NULL, NULL);
INSERT INTO public.assets_depr_transfer_requests VALUES ('a0464853-9738-440f-9bfd-29280be2d74a', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'tf-val', 100000.00, '2025-11-04', 'rej', NULL, 'REJ', 'admin', 'admin', '2025-11-04 13:20:33', 'acebf5f2-b961-4369-b3eb-17a5453574ac', '2025-11-04 13:20:15', '2025-11-04 13:20:33', NULL, NULL);
INSERT INTO public.assets_depr_transfer_requests VALUES ('a04649fb-e200-4059-8d52-f506d55a63e0', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'tf-val', 100000.00, '2025-11-02', 'testis', NULL, 'APR', 'admin', NULL, NULL, 'ee5b677d-ab31-4b81-923f-31d21cff4c51', '2025-11-04 13:24:53', '2025-11-04 13:33:18', NULL, NULL);
INSERT INTO public.assets_depr_transfer_requests VALUES ('a0525352-35e0-4ea6-aa8b-f8006f46e220', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'tf-val', 1000.00, '2025-11-10', NULL, NULL, 'APR', 'admin', NULL, NULL, '7e2a49a7-6132-4734-8bc1-12dffb47de84', '2025-11-10 13:00:56', '2025-11-10 13:00:56', NULL, 'TRF25110001');


--
-- TOC entry 5546 (class 0 OID 17906)
-- Dependencies: 256
-- Data for Name: assets_depr_yearly; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_depr_yearly VALUES ('a045f0ee-8749-48b6-903f-c25b17e5e645', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 2025, 0.00, 2200000.00, 350000.00, 0.00, 350000.00, 3850000.00, '2025-11-04 09:15:53', '2025-11-04 14:12:16');
INSERT INTO public.assets_depr_yearly VALUES ('a045f0ed-fb62-4212-8726-e26713aa2f11', '8576d2d0-0914-4937-93ea-da10998f1fb9', 2025, 0.00, 0.00, 12879164.00, 0.00, 12879164.00, 154550000.00, '2025-11-04 09:15:53', '2025-11-18 11:08:56');
INSERT INTO public.assets_depr_yearly VALUES ('a045f0ee-86cd-49a2-be00-9ed26d5328d8', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 2025, 0.00, 0.00, 93333.00, 0.00, 93333.00, 1120000.00, '2025-11-04 09:15:53', '2025-11-18 11:08:56');
INSERT INTO public.assets_depr_yearly VALUES ('a045f0ee-87b8-4173-9924-d132a70500ca', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 2025, 0.00, -2200000.00, 204166.00, 100000.00, 304166.00, 12000000.00, '2025-11-04 09:15:53', '2025-11-18 11:08:56');
INSERT INTO public.assets_depr_yearly VALUES ('a0624322-f217-42b2-9c94-d995ba3aa4a4', 'd215a643-1404-43a4-a0f1-133f86ad6095', 2025, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-11-18 11:08:56', '2025-11-18 11:08:56');


--
-- TOC entry 5542 (class 0 OID 17693)
-- Dependencies: 252
-- Data for Name: assets_disposals; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_disposals VALUES ('d757da0e-3ca8-4277-abd5-3a878657056b', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'DSP25110003', 'DIS', 'ACC', 'test', NULL, 'Administrator', 'Administrator', '2025-11-17 13:00:33', '2025-11-17 16:16:03', NULL, NULL, NULL, NULL, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-17 16:04:38", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": "2025-11-17 16:04:38", "approved_by": "Administrator"}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": "2025-11-17 16:16:03", "approved_by": "Administrator"}]}', 'disposals_form/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/DSP25110003-form-20251117161603-annxer.xlsx', 'Form Disposal Aset Tetap - DSP25110002.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 284343, 'disposals_ba/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/DSP25110003-ba-20251117161603-rdwnkE.docx', '5b. Berita Acara Disposal Aset Tetap.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 295353);
INSERT INTO public.assets_disposals VALUES ('2fe7a1eb-a2ea-4615-b56c-fa5b44f9724e', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'DSP25110001', 'DIS', 'REJ', 'test', NULL, 'admin', 'Administrator', '2025-11-06 10:32:27', '2025-11-17 16:36:59', NULL, NULL, NULL, NULL, 'OPE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_disposals VALUES ('70545ee0-a644-4a1b-992b-a8b797c32a04', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'DSP25110002', 'DIS', 'ACC', 'test', NULL, 'admin', 'Administrator', '2025-11-06 10:32:37', '2025-11-17 16:44:00', NULL, NULL, NULL, NULL, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-17 16:43:52", "approved_by": "admin"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": "2025-11-17 16:43:52", "approved_by": "Administrator"}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": "2025-11-17 16:43:59", "approved_by": "Administrator"}]}', 'disposals_form/90a5eefa-0b37-4ff4-b221-f297e5b1d16b/DSP25110002-form-20251117164400-aMyaXJ.xlsx', 'Form Disposal Aset Tetap - DSP25110003 (2).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 284343, 'disposals_ba/90a5eefa-0b37-4ff4-b221-f297e5b1d16b/DSP25110002-ba-20251117164400-5dMU3u.docx', 'BA Disposal - DSP25110002 (2).docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 293900);
INSERT INTO public.assets_disposals VALUES ('cc892824-da5d-4fe6-8b6f-803119460a61', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'DSP25110008', 'DIS', 'ACC', NULL, NULL, 'Administrator', 'Administrator', '2025-11-19 14:55:19', '2025-11-19 14:55:19', NULL, NULL, NULL, NULL, 'DIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_disposals VALUES ('0df0aaca-27fc-4962-83ca-7d4bb2e2dd51', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'DSP25110004', 'DIS', 'ACC', 'nigger', 'disposals/8576d2d0-0914-4937-93ea-da10998f1fb9/DSP25110004-20251117164846-OwypMN.jpg', 'Administrator', 'Administrator', '2025-11-17 16:48:46', '2025-11-17 16:49:04', NULL, 'RvSrraqrR9NLxEU74l1wJhnbppuAJqVSjYfQScxJ.jpg', 'image/jpeg', 85821, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-17 16:48:46", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": "2025-11-17 16:48:52", "approved_by": "Administrator"}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": "2025-11-17 16:49:04", "approved_by": "Administrator"}]}', 'disposals_form/8576d2d0-0914-4937-93ea-da10998f1fb9/DSP25110004-form-20251117164904-hAsm1E.xlsx', 'Form Disposal Aset Tetap - DSP25110003 (3).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 284343, 'disposals_ba/8576d2d0-0914-4937-93ea-da10998f1fb9/DSP25110004-ba-20251117164904-yyXP6h.docx', 'BA Disposal - DSP25110002 (2).docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 293900);
INSERT INTO public.assets_disposals VALUES ('3a520428-593d-4a56-8551-92566aebec19', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'DSP25110009', 'DIS', 'ACC', NULL, 'disposals/90a5eefa-0b37-4ff4-b221-f297e5b1d16b/DSP25110009-20251119154658-vbadef.jpg', 'Administrator', 'Administrator', '2025-11-19 15:46:58', '2025-11-19 15:46:58', NULL, 'G2bmFV7WMAAVIAs.jpg', 'image/jpeg', 85821, 'OPE', NULL, 'disposals/90a5eefa-0b37-4ff4-b221-f297e5b1d16b/DSP25110009-20251119154658-Ei7xjB.xlsx', 'Form_Disposal_Preview_A1100000001-01.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 284325, 'disposals/90a5eefa-0b37-4ff4-b221-f297e5b1d16b/DSP25110009-20251119154658-rHH8Cy.docx', 'BA_Disposal_Preview_A1100000001-01.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 293901);
INSERT INTO public.assets_disposals VALUES ('f9149ec3-bde9-4cc0-add1-c79d85b4b5f5', '79389766-c932-4879-acfb-62f247896577', 'DSP25110005', 'DIS', 'ACC', 'test', NULL, 'Administrator', 'Administrator', '2025-11-17 18:03:14', '2025-11-17 19:08:13', NULL, NULL, NULL, NULL, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-17 18:03:14", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": "2025-11-17 19:08:00", "approved_by": "Administrator"}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": "2025-11-17 19:08:13", "approved_by": "Administrator"}]}', 'disposals_form/79389766-c932-4879-acfb-62f247896577/DSP25110005-form-20251117190813-gOjKL5.xlsx', 'Form Disposal Aset Tetap - DSP25110003 (2).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 284343, 'disposals_ba/79389766-c932-4879-acfb-62f247896577/DSP25110005-ba-20251117190813-jrgF5I.docx', 'BA Disposal - DSP25110002 (2).docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 293900);
INSERT INTO public.assets_disposals VALUES ('d0c3b154-1857-44b1-8321-a0c24b9ccf39', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'DSP25110006', 'DIS', 'APR', 'test', NULL, 'Administrator', NULL, '2025-11-17 19:12:39', '2025-11-17 19:12:39', NULL, NULL, NULL, NULL, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-17 19:12:39", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": null, "approved_by": null}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": null, "approved_by": null}]}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_disposals VALUES ('bf9e8538-86e5-4f1c-a2ba-d2c1e7012523', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'DSP25110007', 'DIS', 'ACC', NULL, 'disposals/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/DSP25110007-20251119145223-ZPdAM2.jpg', 'Administrator', 'Administrator', '2025-11-19 14:52:23', '2025-11-19 14:52:23', NULL, 'G2bmFV7WMAAVIAs.jpg', 'image/jpeg', 85821, 'DIS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_disposals VALUES ('fe060c3b-1036-46fe-b250-ab30f641d259', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'DSP25110010', 'DIS', 'APR', 'test', NULL, 'Administrator', NULL, '2025-11-19 16:23:29', '2025-11-19 16:23:29', NULL, NULL, NULL, NULL, 'OPE', '{"key": "disposal_request", "steps": [{"code": "create", "role": "User Departemen", "label": "Create Disposal Request", "approved_at": "2025-11-19 16:23:29", "approved_by": "Administrator"}, {"code": "dept_head", "role": "Dept.Head / Section", "label": "Approval Dept.Head / Section", "approved_at": null, "approved_by": null}, {"code": "asset_mgt", "role": "Asset Management", "label": "Pelaksanaan & BA Disposal (Asset Management)", "approved_at": null, "approved_by": null}]}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);


--
-- TOC entry 5536 (class 0 OID 17050)
-- Dependencies: 246
-- Data for Name: assets_document; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_document VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', NULL, '123123', '123123', '2025-10-20 12:33:36+07', '2025-10-20 12:43:35+07', NULL);
INSERT INTO public.assets_document VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '-', 'PPA-2006-00332', 'JB-2007-00277 : FAACQ', '2025-10-20 11:15:13+07', '2025-10-23 16:56:54+07', NULL);
INSERT INTO public.assets_document VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', '-', 'PPA-2006-00332', 'JB-2007-00277 : FAACQ', '2025-10-23 16:34:28+07', '2025-10-23 16:56:54+07', NULL);
INSERT INTO public.assets_document VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', '123', 'asdasd1', '11123easd', '2025-10-24 12:43:03+07', '2025-10-24 12:44:18+07', NULL);
INSERT INTO public.assets_document VALUES ('79389766-c932-4879-acfb-62f247896577', NULL, '111', '111', '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', NULL);
INSERT INTO public.assets_document VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', NULL, '11122233312313', NULL, '2025-11-14 14:49:07+07', '2025-11-14 14:49:07+07', NULL);
INSERT INTO public.assets_document VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', NULL, '112233', NULL, '2025-11-18 13:12:10+07', '2025-11-18 13:12:10+07', NULL);
INSERT INTO public.assets_document VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', NULL, '55944', NULL, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL);
INSERT INTO public.assets_document VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', NULL, 'qweqwe', NULL, '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL);


--
-- TOC entry 5532 (class 0 OID 16955)
-- Dependencies: 242
-- Data for Name: assets_identifiers; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_identifiers VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', NULL, 'MPS2007000002', NULL, '2025-10-23 16:32:54+07', '2025-10-23 16:56:54+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', '1122333', NULL, NULL, '2025-10-24 12:43:03+07', '2025-10-24 12:44:18+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('79389766-c932-4879-acfb-62f247896577', NULL, NULL, NULL, '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', NULL, 'MPS2007000001', NULL, '2025-10-20 11:15:13+07', '2025-11-14 14:48:29+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', NULL, NULL, NULL, '2025-11-14 14:49:07+07', '2025-11-14 15:00:29+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', NULL, NULL, NULL, '2025-11-18 13:12:10+07', '2025-11-18 13:12:10+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', NULL, NULL, NULL, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', NULL, NULL, NULL, '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL, NULL);
INSERT INTO public.assets_identifiers VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', NULL, NULL, NULL, '2025-10-20 12:33:36+07', '2025-11-19 13:59:50+07', NULL, NULL);


--
-- TOC entry 5537 (class 0 OID 17062)
-- Dependencies: 247
-- Data for Name: assets_qr; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_qr VALUES ('248e8167-0e37-4fcb-a0fe-cf4450d28b75', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'qrcodes/989c0c7d-135b-402c-ae51-5e0d94917b1c.svg', true, '2025-10-24 12:44:18+07', '2025-10-24 12:43:03+07', '2025-10-24 12:44:18+07', NULL);
INSERT INTO public.assets_qr VALUES ('6d53bd82-d742-4711-aceb-f28a2214bf0e', '8576d2d0-0914-4937-93ea-da10998f1fb9', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'qrcodes/8576d2d0-0914-4937-93ea-da10998f1fb9.svg', true, '2025-11-11 15:39:06+07', '2025-10-24 16:28:27+07', '2025-11-11 15:39:06+07', NULL);
INSERT INTO public.assets_qr VALUES ('fde3cdfe-93d7-4006-9201-7182ef400fbc', '79389766-c932-4879-acfb-62f247896577', '79389766-c932-4879-acfb-62f247896577', 'qrcodes/79389766-c932-4879-acfb-62f247896577.svg', true, '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', '2025-11-12 11:30:20+07', NULL);
INSERT INTO public.assets_qr VALUES ('02a9206c-a8db-4c74-a2ae-0db77b87fc0c', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'qrcodes/98dbf2eb-5686-4c9f-b796-8d53a2c4b049.svg', true, '2025-11-14 14:48:38+07', '2025-10-20 11:15:13+07', '2025-11-14 14:48:38+07', NULL);
INSERT INTO public.assets_qr VALUES ('7ed89e4e-4384-47ce-884a-9cab81154348', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'qrcodes/d215a643-1404-43a4-a0f1-133f86ad6095.svg', true, '2025-11-14 15:00:29+07', '2025-11-14 14:49:07+07', '2025-11-14 15:00:29+07', NULL);
INSERT INTO public.assets_qr VALUES ('2c8b5787-632e-4b93-acb9-148fe0b73164', '671058e5-f686-4229-80f9-11cad85c3e91', '671058e5-f686-4229-80f9-11cad85c3e91', 'qrcodes/671058e5-f686-4229-80f9-11cad85c3e91.svg', true, '2025-11-18 13:12:11+07', '2025-11-18 13:12:11+07', '2025-11-18 13:12:11+07', NULL);
INSERT INTO public.assets_qr VALUES ('2d32e4ea-b50b-46ff-bfa3-aaddf83f7aca', '2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', '2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', 'qrcodes/2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e.svg', true, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL);
INSERT INTO public.assets_qr VALUES ('dfba4d77-7eee-4d56-a002-4bf221681a25', '0c945e04-28f4-4188-96e2-8216f5bf4dd9', '0c945e04-28f4-4188-96e2-8216f5bf4dd9', 'qrcodes/0c945e04-28f4-4188-96e2-8216f5bf4dd9.svg', true, '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL);
INSERT INTO public.assets_qr VALUES ('a7bf7363-f9c1-4e6b-a6e1-1c14e2ef7ee1', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'qrcodes/90a5eefa-0b37-4ff4-b221-f297e5b1d16b.svg', true, '2025-11-19 13:59:50+07', '2025-10-20 12:33:36+07', '2025-11-19 13:59:50+07', NULL);


--
-- TOC entry 5538 (class 0 OID 17083)
-- Dependencies: 248
-- Data for Name: assets_rfid; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 5541 (class 0 OID 17568)
-- Dependencies: 251
-- Data for Name: assets_transfers; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_transfers VALUES ('39ddcced-d4a5-4d3c-9fab-a77c83a0f821', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110001', 'user', '{"value": "UCD"}', '{"value": "SAR"}', 'RET', 'test', '2025-11-06 10:32:09+07', '2025-11-06 10:51:21+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('a069ea19-fbb8-4665-9176-c8a3c9c3de21', '989c0c7d-135b-402c-ae51-5e0d94917b1c', 'OPN25110001', 'status', '{"value": "DIS"}', '{"value": "OPE"}', 'RET', NULL, '2025-11-06 10:46:02+07', '2025-11-06 10:52:08+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('e47adcc0-cd88-450b-9fe1-d6bc4f6bfdb6', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'OPN25110002', 'status', '{"value": "DIS"}', '{"value": "OPE"}', 'RET', NULL, '2025-11-06 10:46:21+07', '2025-11-11 13:38:36+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('1c542677-cbd7-4b6f-ae05-87d62d881351', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110003', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'RET', NULL, '2025-11-10 10:09:20+07', '2025-11-11 13:45:03+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('18400fc5-e98d-4b6c-924c-d7041b743bd5', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'MOV25110002', 'user', '{"value": "SAR"}', '{"value": "UCD"}', 'RET', NULL, '2025-11-06 10:35:38+07', '2025-11-11 13:47:03+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('d1da3730-8faa-496b-8416-d7f5a41c8601', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'OPN25110003', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'ACC', NULL, '2025-11-11 13:51:18+07', '2025-11-11 13:51:18+07', NULL, 'admin', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('b7bc1b66-2a32-44e5-9e95-58a6b46ab57a', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'MOV25110006', 'owner', '{"value": "UCD"}', '{"value": "SAR"}', 'ACC', NULL, '2025-11-14 17:05:50+07', '2025-11-14 17:12:36+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('df3acef3-0a99-448b-b11f-fc70d59710c5', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110004', 'location', '{"value": "LOC-1"}', '{"value": "LOC-2"}', 'ACC', NULL, '2025-11-14 10:32:53+07', '2025-11-14 10:57:08+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, '{"key":"movement_location","steps":[{"code":"create","label":"Create","role":"User Departemen","approved_by":"Administrator","approved_at":"2025-11-14 10:32:53"},{"code":"dept_head","label":"Approval Dept.Head \/ Section","role":"User - Dept.Head \/ Section","approved_by":"Administrator","approved_at":"2025-11-14 10:57:00"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":"Administrator","approved_at":"2025-11-14 10:57:08"}]}', NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('8c12b79d-a65d-482f-a86a-1b43636faa81', '79389766-c932-4879-acfb-62f247896577', 'MOV25110008', 'maintenance', '{"value": "UCD"}', '{"value": "SAR"}', 'REJ', NULL, '2025-11-14 18:22:46+07', '2025-11-14 18:35:17+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, '{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen (New Owner\/User\/Maint)","approved_by":"Administrator","approved_at":"2025-11-14 18:22:46"},{"code":"new_dept_head","label":"Approval Dept.Head New Owner\/User\/Maint","role":"User - Dept.Head \/ Section (New)","approved_by":"Administrator","approved_at":"2025-11-14 18:26:16"},{"code":"old_dept_head","label":"Approval Dept.Head Old Owner\/User\/Maint (optional)","role":"User - Dept.Head \/ Section (Old)","approved_by":null,"approved_at":null,"rejected_by":"Administrator","rejected_at":"2025-11-14 18:35:17"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}],"rejected_by":"Administrator","rejected_at":"2025-11-14 18:35:17"}', NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('59368177-9b96-45bb-ad13-6a6333d3a227', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110005', 'location', '{"value": "LOC-2"}', '{"value": "LOC-1"}', 'ACC', NULL, '2025-11-14 14:09:03+07', '2025-11-14 14:09:13+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, '{"key":"movement_location","steps":[{"code":"create","label":"Create","role":"User Departemen","approved_by":"Administrator","approved_at":"2025-11-14 14:09:03"},{"code":"dept_head","label":"Approval Dept.Head \/ Section","role":"User - Dept.Head \/ Section","approved_by":"Administrator","approved_at":"2025-11-14 14:09:11"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":"Administrator","approved_at":"2025-11-14 14:09:13"}]}', NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('0f60d212-dffb-4570-a062-eb711f8e8009', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110005', 'owner', '{"value": "UCD"}', '{"value": "SAR"}', 'ACC', 'TEST DARI STOCK OPNAME', '2025-11-19 15:06:33+07', '2025-11-19 15:06:33+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110005-20251119150633-yzhWO1.xlsx', 'Form_Transfer_Preview_A1103000001-01.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28355, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('54a4eebb-09fd-4369-82cb-9c1291ebbbce', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110006', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'ACC', NULL, '2025-11-19 15:08:24+07', '2025-11-19 15:08:24+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110006-20251119150824-YPG1AP.jpg', 'RvSrraqrR9NLxEU74l1wJhnbppuAJqVSjYfQScxJ.jpg', 'image/jpeg', 85821, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('606d2c09-6ddd-42af-8e79-c636f0025dd9', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 'MOV25110009', 'status', '{"value": "OPE"}', '{"value": "IDL"}', 'ACC', NULL, '2025-11-14 18:32:06+07', '2025-11-14 18:32:10+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('b2108681-38d7-462c-9779-da835b4af0dc', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110007', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'ACC', NULL, '2025-11-14 18:11:38+07', '2025-11-14 18:50:48+07', NULL, 'Administrator', 'Administrator', NULL, NULL, NULL, NULL, '{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen (New Owner\/User\/Maint)","approved_by":"Administrator","approved_at":"2025-11-14 18:11:38"},{"code":"new_dept_head","label":"Approval Dept.Head New Owner\/User\/Maint","role":"User - Dept.Head \/ Section (New)","approved_by":"Administrator","approved_at":"2025-11-14 18:11:45"},{"code":"old_dept_head","label":"Approval Dept.Head Old Owner\/User\/Maint (optional)","role":"User - Dept.Head \/ Section (Old)","approved_by":"Administrator","approved_at":"2025-11-14 18:11:46"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":"Administrator","approved_at":"2025-11-14 18:50:48"}]}', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/MOV25110007-20251114185048-8jFFJz.xlsx', 'Form Transfer Asset - MOV25110008.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28465);
INSERT INTO public.assets_transfers VALUES ('44baec9a-a8c1-4bd2-886c-4ce578d3fbb5', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110004', 'user', '{"value": "UCD"}', '{"value": "SAR"}', 'ACC', NULL, '2025-11-19 14:42:48+07', '2025-11-19 14:42:48+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110004-20251119144248-TYtIYm.xlsx', 'Form_Transfer_Preview_A1100000001-00.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28353, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('c8582abf-30f3-4c5f-8830-f393690ca7cd', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110007', 'owner', '{"value": "UCD"}', '{"value": "SAR"}', 'ACC', 'TEST DARI SO', '2025-11-19 15:12:32+07', '2025-11-19 15:12:32+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110007-20251119151232-BEOX0b.xlsx', 'Form_Transfer_OPN25110005.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28357, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('82b17df0-d42e-449f-b53c-81f1068528f8', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110008', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'ACC', 'TEST', '2025-11-19 15:15:31+07', '2025-11-19 15:15:31+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110008-20251119151531-4ShGAT.xlsx', 'Form_Transfer_OPN25110005.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28357, NULL, 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110008-20251119151531-9H5Ft0.jpg', 'RvSrraqrR9NLxEU74l1wJhnbppuAJqVSjYfQScxJ.jpg', 'image/jpeg', 85821);
INSERT INTO public.assets_transfers VALUES ('2d83a3a1-d008-48a5-8676-a40e5c30bf94', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110009', 'owner', '{"value": "UCD"}', '{"value": "SAR"}', 'ACC', 'test', '2025-11-19 15:18:06+07', '2025-11-19 15:18:06+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110009-20251119151806-l1ryXR.jpg', 'RvSrraqrR9NLxEU74l1wJhnbppuAJqVSjYfQScxJ.jpg', 'image/jpeg', 85821, NULL, 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110009-20251119151806-XEtO7w.xlsx', 'Form_Transfer_OPN25110005.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28357);
INSERT INTO public.assets_transfers VALUES ('915510ef-1c72-4dda-b836-b59df99d4969', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'OPN25110010', 'owner', '{"value": "SAR"}', '{"value": "SAR"}', 'ACC', NULL, '2025-11-19 15:31:05+07', '2025-11-19 15:31:05+07', NULL, 'Administrator', 'Administrator', 'transfers/98dbf2eb-5686-4c9f-b796-8d53a2c4b049/OPN25110010-20251119153105-gAYr8W.xlsx', 'Form_Transfer_Preview_UPL25110001.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 28465, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('f9c58389-22f4-4d40-9e91-351a4996c684', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110011', 'owner', '{"value": "SAR"}', '{"value": "UCD"}', 'APR', NULL, '2025-11-19 16:18:45+07', '2025-11-19 16:18:45+07', NULL, 'Administrator', NULL, NULL, NULL, NULL, NULL, '{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen (New Owner\/User\/Maint)","approved_by":"Administrator","approved_at":"2025-11-19 16:18:45"},{"code":"new_dept_head","label":"Approval Dept.Head New Owner\/User\/Maint","role":"User - Dept.Head \/ Section (New)","approved_by":null,"approved_at":null},{"code":"old_dept_head","label":"Approval Dept.Head Old Owner\/User\/Maint (optional)","role":"User - Dept.Head \/ Section (Old)","approved_by":null,"approved_at":null},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}]}', NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('ce018f5b-a372-48b4-ad42-4a5e9ab0b943', 'd215a643-1404-43a4-a0f1-133f86ad6095', 'MOV25110010', 'location', '{"value": "LOC-2"}', '{"value": "LOC-1"}', 'APR', NULL, '2025-11-14 18:34:01+07', '2025-11-19 16:18:53+07', NULL, 'Administrator', NULL, NULL, NULL, NULL, NULL, '{"key":"movement_location","steps":[{"code":"create","label":"Create","role":"User Departemen","approved_by":"Administrator","approved_at":"2025-11-14 18:34:01"},{"code":"dept_head","label":"Approval Dept.Head \/ Section","role":"User - Dept.Head \/ Section","approved_by":"Administrator","approved_at":"2025-11-19 16:18:53"},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}]}', NULL, NULL, NULL, NULL);
INSERT INTO public.assets_transfers VALUES ('7d3a7a2e-76d4-4ea1-9aa0-b6e969609448', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'MOV25110012', 'owner', '{"value": "SAR"}', '{"value": "SAR"}', 'APR', NULL, '2025-11-19 16:19:09+07', '2025-11-19 16:19:09+07', NULL, 'Administrator', NULL, NULL, NULL, NULL, NULL, '{"key":"movement_assignment","steps":[{"code":"create","label":"Create Request","role":"User Departemen (New Owner\/User\/Maint)","approved_by":"Administrator","approved_at":"2025-11-19 16:19:09"},{"code":"new_dept_head","label":"Approval Dept.Head New Owner\/User\/Maint","role":"User - Dept.Head \/ Section (New)","approved_by":null,"approved_at":null},{"code":"old_dept_head","label":"Approval Dept.Head Old Owner\/User\/Maint (optional)","role":"User - Dept.Head \/ Section (Old)","approved_by":null,"approved_at":null},{"code":"asset_mgt","label":"Completed (Asset Management)","role":"Asset Management","approved_by":null,"approved_at":null}]}', NULL, NULL, NULL, NULL);


--
-- TOC entry 5535 (class 0 OID 17032)
-- Dependencies: 245
-- Data for Name: assets_value; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_value VALUES ('8576d2d0-0914-4937-93ea-da10998f1fb9', 154550000.00, 1.000, false, 0.00, 'KG', 154550000.00, 48, 4.00, '2025-10-23 15:50:24+07', '2025-11-10 09:58:32+07', NULL, '2025-07-01', '2025-07-01');
INSERT INTO public.assets_value VALUES ('98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 12000000.00, 1.000, false, 0.00, 'KG', 12000000.00, 48, 4.00, '2025-10-20 11:15:13+07', '2025-11-10 09:58:44+07', NULL, '2025-10-01', '2025-10-01');
INSERT INTO public.assets_value VALUES ('989c0c7d-135b-402c-ae51-5e0d94917b1c', 2000000.00, 1.000, false, 0.00, 'KG', 2000000.00, 12, 1.00, '2025-10-24 12:43:03+07', '2025-11-10 10:10:04+07', NULL, '2025-10-01', '2025-10-01');
INSERT INTO public.assets_value VALUES ('d215a643-1404-43a4-a0f1-133f86ad6095', NULL, NULL, true, 0.00, NULL, 0.00, NULL, 0.00, '2025-11-14 14:49:07+07', '2025-11-14 14:49:07+07', NULL, NULL, NULL);
INSERT INTO public.assets_value VALUES ('79389766-c932-4879-acfb-62f247896577', 1000000.00, 1.000, true, 110000.00, 'KG', 1110000.00, 12, 1.00, '2025-11-12 11:30:20+07', '2025-11-17 14:58:48+07', NULL, '2025-11-17', '2025-11-17');
INSERT INTO public.assets_value VALUES ('671058e5-f686-4229-80f9-11cad85c3e91', NULL, NULL, true, 0.00, NULL, 0.00, NULL, 0.00, '2025-11-18 13:12:11+07', '2025-11-18 13:12:11+07', NULL, NULL, NULL);
INSERT INTO public.assets_value VALUES ('2d0f05e5-8fbf-4fac-896b-25ec3ebb3d0e', NULL, NULL, true, 0.00, NULL, 0.00, NULL, 0.00, '2025-11-18 13:12:41+07', '2025-11-18 13:12:41+07', NULL, NULL, NULL);
INSERT INTO public.assets_value VALUES ('0c945e04-28f4-4188-96e2-8216f5bf4dd9', NULL, NULL, true, 0.00, NULL, 0.00, NULL, 0.00, '2025-11-18 13:13:18+07', '2025-11-18 13:13:18+07', NULL, NULL, NULL);
INSERT INTO public.assets_value VALUES ('90a5eefa-0b37-4ff4-b221-f297e5b1d16b', 1000000.00, 1.000, true, 110000.00, 'KG', 1110000.00, 12, 1.00, '2025-10-20 12:33:36+07', '2025-11-19 13:59:50+07', NULL, '2025-10-01', '2025-10-01');


--
-- TOC entry 5544 (class 0 OID 17843)
-- Dependencies: 254
-- Data for Name: assets_value_history; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.assets_value_history VALUES ('b14a6176-a287-473c-b06a-c051a85e6463', '8576d2d0-0914-4937-93ea-da10998f1fb9', '{"quantity":"1.000","kode_uom":"KG","price":"154550000.00","is_pajak":false,"vat_in":"0.00","total":"154550000.00","useful_life_month":48,"useful_life_year":"4.00"}', '{"quantity":1,"kode_uom":"KG","price":154550000,"is_pajak":0,"vat_in":0,"total":154550000,"useful_life_month":"48","useful_life_year":4,"actual_date":"2025-07-01","capitalization_date":"2025-07-01"}', 'admin', NULL, '2025-11-10 09:58:32', '2025-11-10 09:58:32', NULL, 'ACQ25110004');
INSERT INTO public.assets_value_history VALUES ('947148f6-d3c3-46d8-923e-1acbf12ce00b', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '{"quantity":"1.000","kode_uom":"KG","price":"12000000.00","is_pajak":false,"vat_in":"0.00","total":"12000000.00","useful_life_month":48,"useful_life_year":"4.00"}', '{"quantity":1,"kode_uom":"KG","price":12000000,"is_pajak":0,"vat_in":0,"total":12000000,"useful_life_month":"48","useful_life_year":4,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 09:58:44', '2025-11-10 09:58:44', NULL, 'ACQ25110005');
INSERT INTO public.assets_value_history VALUES ('00ed0eb3-f848-4a54-a0b4-6f50c5825d91', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '{"quantity":"1.000","kode_uom":"KG","price":"2000000.00","is_pajak":false,"vat_in":"0.00","total":"2000000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":2000000,"is_pajak":0,"vat_in":0,"total":2000000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 09:58:51', '2025-11-10 09:58:51', NULL, 'ACQ25110006');
INSERT INTO public.assets_value_history VALUES ('5a40850b-11a5-4e24-8eb9-93f9906a08b0', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '{"quantity":"1.000","kode_uom":"KG","price":"2000000.00","is_pajak":false,"vat_in":"0.00","total":"2000000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":2000000,"is_pajak":0,"vat_in":0,"total":2000000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 10:10:04', '2025-11-10 10:10:04', NULL, 'ACQ25110007');
INSERT INTO public.assets_value_history VALUES ('217834cf-2774-4414-a99a-6340f96f1eb9', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '{"quantity":"1.000","kode_uom":"KG","price":"1000000.00","is_pajak":true,"vat_in":"120000.00","total":"1120000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":1000000,"is_pajak":1,"vat_in":120000,"total":1120000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 17:10:24', '2025-11-10 17:10:24', NULL, 'ACQ25110008');
INSERT INTO public.assets_value_history VALUES ('3ef73319-caff-4b7f-b6ae-a2c5e005d444', '79389766-c932-4879-acfb-62f247896577', '{"quantity":"1.000","kode_uom":"KG","price":"1000000.00","is_pajak":true,"vat_in":"11000000.00","total":"12000000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":1000000,"is_pajak":1,"vat_in":110000,"total":1110000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-11-17","capitalization_date":"2025-11-17"}', 'Administrator', NULL, '2025-11-17 14:58:48', '2025-11-17 14:58:48', NULL, 'ACQ25110011');
INSERT INTO public.assets_value_history VALUES ('5403c2b4-2785-42a7-bea2-6417e6ba78f8', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', '{"quantity":"1.000","kode_uom":"KG","price":"12000000.00","is_pajak":false,"vat_in":"0.00","total":"12000000.00","useful_life_month":48,"useful_life_year":"4.00"}', '{"quantity":1,"kode_uom":"KG","price":12000000,"is_pajak":0,"vat_in":0,"total":12000000,"useful_life_month":"48","useful_life_year":4,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-06 10:58:18', '2025-11-14 14:03:17', NULL, 'ACQ25110001');
INSERT INTO public.assets_value_history VALUES ('7a8619db-d951-40d3-ac5c-1aea7c28db1d', '90a5eefa-0b37-4ff4-b221-f297e5b1d16b', '{"quantity":"1.000","kode_uom":"KG","price":"1000000.00","is_pajak":true,"vat_in":"120000.00","total":"1120000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":1000000,"is_pajak":1,"vat_in":120000,"total":1120000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 09:57:21', '2025-11-14 14:03:19', NULL, 'ACQ25110002');
INSERT INTO public.assets_value_history VALUES ('2efcd952-d348-4f4f-8723-19271eb70fb3', '989c0c7d-135b-402c-ae51-5e0d94917b1c', '{"quantity":"1.000","kode_uom":"KG","price":"2000000.00","is_pajak":false,"vat_in":"0.00","total":"2000000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":2000000,"is_pajak":0,"vat_in":0,"total":2000000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-10-01","capitalization_date":"2025-10-01"}', 'admin', NULL, '2025-11-10 09:58:24', '2025-11-14 14:06:21', NULL, 'ACQ25110003');
INSERT INTO public.assets_value_history VALUES ('53841d85-ab1f-49f2-91c2-0f6a32d45983', '79389766-c932-4879-acfb-62f247896577', '{"quantity":null,"kode_uom":null,"price":null,"is_pajak":true,"vat_in":"0.00","total":"0.00","useful_life_month":null,"useful_life_year":"0.00"}', '{"quantity":1,"kode_uom":"KG","price":1000000,"is_pajak":1,"vat_in":100000000,"total":101000000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-11-17","capitalization_date":"2025-11-17"}', 'Administrator', NULL, '2025-11-17 14:54:30', '2025-11-17 14:54:30', NULL, 'ACQ25110009');
INSERT INTO public.assets_value_history VALUES ('68fe7e6c-aa8b-4b29-b9fc-f47b074a7ca9', '79389766-c932-4879-acfb-62f247896577', '{"quantity":"1.000","kode_uom":"KG","price":"1000000.00","is_pajak":true,"vat_in":"100000000.00","total":"101000000.00","useful_life_month":12,"useful_life_year":"1.00"}', '{"quantity":1,"kode_uom":"KG","price":1000000,"is_pajak":1,"vat_in":11000000,"total":12000000,"useful_life_month":"12","useful_life_year":1,"actual_date":"2025-11-17","capitalization_date":"2025-11-17"}', 'Administrator', NULL, '2025-11-17 14:56:29', '2025-11-17 14:56:29', NULL, 'ACQ25110010');


--
-- TOC entry 5513 (class 0 OID 16432)
-- Dependencies: 223
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.cache VALUES ('93fa3944508933cf81cf5dcb437def87:timer', 'i:1763606569;', 1763606569);
INSERT INTO public.cache VALUES ('93fa3944508933cf81cf5dcb437def87', 'i:1;', 1763606569);
INSERT INTO public.cache VALUES ('31b064f62215ead3cd85f335b36b20c0:timer', 'i:1763606569;', 1763606569);
INSERT INTO public.cache VALUES ('31b064f62215ead3cd85f335b36b20c0', 'i:1;', 1763606569);
INSERT INTO public.cache VALUES ('a5fc55b22fa62d71c12f0c4ed1d551fc:timer', 'i:1763606569;', 1763606569);
INSERT INTO public.cache VALUES ('a5fc55b22fa62d71c12f0c4ed1d551fc', 'i:1;', 1763606569);


--
-- TOC entry 5514 (class 0 OID 16442)
-- Dependencies: 224
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 5551 (class 0 OID 26079)
-- Dependencies: 261
-- Data for Name: master_action; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_action VALUES ('16c26417-908e-4b3f-a837-9aaee50ace09', 'C', 'Create', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_action VALUES ('a3c3dbc9-f303-43da-89ec-ce992cc42043', 'R', 'Read', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_action VALUES ('af1252e9-52ab-4222-89b7-7a74e12a2701', 'U', 'Update', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_action VALUES ('7f7c630e-f82b-4601-8021-5c940ea1dbb9', 'D', 'Delete', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_action VALUES ('dc544ed9-1c29-40b4-84a2-0ff2ca5b3b54', 'APR', 'Approve', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);


--
-- TOC entry 5525 (class 0 OID 16834)
-- Dependencies: 235
-- Data for Name: master_asset_class; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_asset_class VALUES ('36d2c150-b55c-441d-9954-d8c3b98521e4', '1100', 'Asset Class 1', true, '2025-10-03 16:53:34', '2025-10-20 11:12:48', NULL, 'A');
INSERT INTO public.master_asset_class VALUES ('7f492877-d0c2-49db-9740-cb2ed8dfa73b', '1101', 'Asset Class 2', true, '2025-10-20 11:13:30', '2025-10-20 11:13:30', NULL, 'J');
INSERT INTO public.master_asset_class VALUES ('04f7a113-062f-4a34-b6ff-6a15f0ae7f5b', '1102', 'Asset Class 3', true, '2025-10-20 13:11:32', '2025-10-20 13:11:36', NULL, 'J');
INSERT INTO public.master_asset_class VALUES ('d1b3ff83-4fab-4f7a-919f-f1445b45736c', '1103', 'Asset Class 4', true, '2025-11-12 11:42:01', '2025-11-12 11:42:01', NULL, NULL);


--
-- TOC entry 5518 (class 0 OID 16708)
-- Dependencies: 228
-- Data for Name: master_category; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_category VALUES ('2e4cedf9-2cca-4134-a1ac-686099e85546', '2', 'test', '2', true, '2025-10-08 15:59:11', '2025-10-08 16:48:26', NULL);
INSERT INTO public.master_category VALUES ('b3cec431-138b-494e-9c68-0487f3a8fe80', '1', 'Category 1', '1', true, '2025-10-03 15:08:15', '2025-10-08 16:48:31', NULL);


--
-- TOC entry 5519 (class 0 OID 16745)
-- Dependencies: 229
-- Data for Name: master_category_2; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_category_2 VALUES ('cea6d18f-21ac-4a15-b439-b4db70d58f60', '1', 'Category 2-1', true, '2', '2025-10-03 15:44:00', '2025-10-08 16:57:09', NULL);
INSERT INTO public.master_category_2 VALUES ('a259262a-3291-4dd6-b06f-44a329761950', '2', 'test2', true, '1', '2025-10-08 16:27:52', '2025-10-08 16:57:20', NULL);


--
-- TOC entry 5517 (class 0 OID 16695)
-- Dependencies: 227
-- Data for Name: master_division; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_division VALUES ('c437599e-129f-469c-b583-da6f100a0645', '1', 'Division 1', true, '2025-10-03 14:58:28', '2025-11-14 14:26:12', NULL);
INSERT INTO public.master_division VALUES ('264c7161-ed0e-4604-8fb6-562f11d90668', '2', 'Division 2', true, '2025-10-08 15:49:44', '2025-11-14 14:26:35', NULL);


--
-- TOC entry 5522 (class 0 OID 16794)
-- Dependencies: 232
-- Data for Name: master_group_category; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_group_category VALUES ('aed27b3a-a105-4237-9d17-b4835dadced1', 'GRC-1', 'Group 1', true, '2025-10-03 16:20:38', '2025-10-03 16:20:38', NULL);


--
-- TOC entry 5521 (class 0 OID 16781)
-- Dependencies: 231
-- Data for Name: master_location; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_location VALUES ('4d9871af-8036-4731-89ee-d4e3be4fb074', 'LOC-1', 'Location 1', true, '2025-10-03 16:08:17', '2025-10-03 16:09:06', NULL);
INSERT INTO public.master_location VALUES ('2dc4048a-3be2-465a-b8ae-b976482cb9d5', 'LOC-2', 'Location 2', true, '2025-11-14 10:15:45', '2025-11-14 10:15:45', NULL);


--
-- TOC entry 5552 (class 0 OID 26105)
-- Dependencies: 262
-- Data for Name: master_menu; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_menu VALUES ('d9862c48-264e-4e02-a4ee-952fd6b58f07', 'MASTER_DATA', 'Master Data', 2, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","U","D"]');
INSERT INTO public.master_menu VALUES ('611cf464-b5e7-4c12-9b81-1f8f3bd49474', 'USER_MGMT', 'User Management', 3, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["R","U"]');
INSERT INTO public.master_menu VALUES ('275ea155-50ca-4b6d-a946-8b4a26650ed5', 'ASSETS', 'Assets', 5, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","U","D"]');
INSERT INTO public.master_menu VALUES ('197b4cae-1fcf-4fd2-b335-f51a3b1da7db', 'DEPRECIATION', 'Depreciation', 6, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R"]');
INSERT INTO public.master_menu VALUES ('429cec35-4b62-43c8-88df-eea2fac3e3e3', 'TRANSFER', 'Transfer Requests', 8, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","U","D","APR"]');
INSERT INTO public.master_menu VALUES ('9da966b7-f08e-46e1-996d-f6eed4171033', 'MOVEMENT', 'Movement', 9, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","U","D","APR"]');
INSERT INTO public.master_menu VALUES ('a0eaaee8-98bf-43b3-958c-d8a8d259b736', 'TRASH', 'Trash', 14, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["R","U","D"]');
INSERT INTO public.master_menu VALUES ('bf5a2509-07f4-4282-ba02-d1d681f85c1a', 'DISPOSAL', 'Disposal', 10, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","U","D", "APR"]');
INSERT INTO public.master_menu VALUES ('9cb3f51c-5f8b-4a35-91fb-d3839befaabe', 'STOCK_OPN', 'Stock Opname', 12, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R"]');
INSERT INTO public.master_menu VALUES ('11e8e2a9-d37d-4f5c-93b4-50aa64d13c5b', 'RETURN', 'Return', 11, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","D"]');
INSERT INTO public.master_menu VALUES ('fdfcf397-c74d-4aa1-90d4-a21528061beb', 'ACQUISITION', 'Acquisition', 7, true, '2025-11-10 14:21:09', '2025-11-10 14:21:09', NULL, '["C","R","D"]');


--
-- TOC entry 5550 (class 0 OID 26066)
-- Dependencies: 260
-- Data for Name: master_role; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_role VALUES ('f42b61ee-c65a-4662-8c28-3cf605718d31', 'AM_HEAD', 'Asset Management Head', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_role VALUES ('5e0d513f-19ff-4d5b-84e8-3cad4ab9c49c', 'AM_ADMIN', 'Asset Management Admin', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_role VALUES ('6a6962ee-8878-438b-affb-a37f4353885f', 'DEPT_HEAD', 'User - Department Head', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_role VALUES ('b59bd014-1bc0-4f0e-9da0-157d0b7a1255', 'DEPT_USER', 'User Departemen', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_role VALUES ('8d012067-abee-453c-a3c5-286dc8b7987f', 'AUDITOR', 'Auditor', true, '2025-11-10 14:05:30', '2025-11-10 14:05:30', NULL);
INSERT INTO public.master_role VALUES ('c8f6127a-5993-427d-8c7b-5797ac70fffc', 'SYSADMIN', 'System Administrator', true, '2025-11-10 14:05:30', '2025-11-14 13:59:07', NULL);


--
-- TOC entry 5553 (class 0 OID 26122)
-- Dependencies: 263
-- Data for Name: master_role_menu; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_role_menu VALUES ('521b3e2e-728a-4f8b-afe8-6ff74b260763', 'SYSADMIN', 'MASTER_DATA', '["C", "R", "U", "D"]', true, '2025-11-10 16:02:10', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('3a1b0587-674b-4699-baff-c527e953ea96', 'SYSADMIN', 'USER_MGMT', '["R", "U"]', true, '2025-11-10 14:26:17', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('655f937d-ba46-4d1f-ade8-b5fa1f367791', 'SYSADMIN', 'ASSETS', '["C", "R", "U", "D"]', true, '2025-11-10 16:34:00', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('3394868e-4d1a-4565-be91-350fa1c99d41', 'SYSADMIN', 'DEPRECIATION', '["C", "R"]', true, '2025-11-11 09:28:13', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('02bc8cd7-10d6-43b1-824c-518795e0956c', 'SYSADMIN', 'TRANSFER', '["C", "R", "U", "D", "APR"]', true, '2025-11-10 18:16:04', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('43b5e425-cd3a-4ea3-8d9d-45d428294f78', 'SYSADMIN', 'MOVEMENT', '["C", "R", "U", "D", "APR"]', true, '2025-11-10 17:25:00', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('dbb0b5b3-3466-4584-a205-a4351fd957e8', 'SYSADMIN', 'TRASH', '["R", "U", "D"]', true, '2025-11-11 09:31:22', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('7dd55aa2-a07d-48ef-b164-c77039f11f97', 'SYSADMIN', 'DISPOSAL', '["C", "R", "U", "D", "APR"]', true, '2025-11-10 17:46:34', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('22c4d4b5-bdfe-4514-8158-69702fafb1ec', 'SYSADMIN', 'STOCK_OPN', '["C", "R"]', true, '2025-11-10 18:34:06', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('2dd6e593-ae09-4ac3-8b13-a598dd96c874', 'SYSADMIN', 'RETURN', '["C", "R", "D"]', true, '2025-11-10 18:02:56', '2025-11-14 13:59:08', NULL);
INSERT INTO public.master_role_menu VALUES ('2278cbd0-6a6d-4f49-a253-80ac6acbd189', 'SYSADMIN', 'ACQUISITION', '["C", "R", "D"]', true, '2025-11-10 17:08:44', '2025-11-14 13:59:08', NULL);


--
-- TOC entry 5524 (class 0 OID 16820)
-- Dependencies: 234
-- Data for Name: master_status; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_status VALUES ('588d5c64-4c42-4202-a5ca-563418babd6c', 'IDL', 'Idle', 'Asset', true, '2025-10-10 13:11:44', '2025-10-10 13:11:44', NULL);
INSERT INTO public.master_status VALUES ('02ec8263-005d-45de-b3ef-2c0c9a4a0645', 'OPE', 'Operation', 'Asset', true, '2025-10-10 13:18:07', '2025-10-10 13:18:07', NULL);
INSERT INTO public.master_status VALUES ('b4800a62-66cc-45f0-a788-84a130c7a700', 'RPR', 'Repair', 'Asset', true, '2025-10-10 13:18:23', '2025-10-10 13:18:23', NULL);
INSERT INTO public.master_status VALUES ('34704a59-76d7-4534-a122-4f892a9c4487', 'DIS', 'Disposal', 'Asset', true, '2025-10-10 13:18:32', '2025-10-10 13:18:32', NULL);
INSERT INTO public.master_status VALUES ('2c7658b3-bcf1-4d9c-b5e2-7c23a1215547', 'RET', 'Returned', 'Return', true, '2025-10-10 13:19:05', '2025-10-10 13:19:05', NULL);
INSERT INTO public.master_status VALUES ('9ef8e275-cf15-4748-a55d-368e8b5ea0ea', 'DISP', 'Disposed', 'Disposal', true, '2025-10-10 13:19:36', '2025-10-10 13:19:36', NULL);
INSERT INTO public.master_status VALUES ('e6a5d54e-acfe-49be-a12b-34ac9bd8de01', 'APR', 'Waiting for Approval', 'Transfer', true, '2025-10-03 16:45:06', '2025-10-10 13:21:45', NULL);
INSERT INTO public.master_status VALUES ('ac82e7e6-6025-4351-b8d7-a2e4d6df2987', 'REJ', 'Rejected', 'Transfer', true, '2025-10-10 13:18:56', '2025-10-10 13:22:01', NULL);
INSERT INTO public.master_status VALUES ('ea5708f7-6c6b-43ea-9951-37db03cfac45', 'ACC', 'Accepted', 'Transfer', true, '2025-10-10 13:18:45', '2025-10-10 13:22:08', NULL);


--
-- TOC entry 5520 (class 0 OID 16768)
-- Dependencies: 230
-- Data for Name: master_sub_category; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_sub_category VALUES ('1f447db3-1c95-4767-8d57-d80bd1ab79c8', 'SUB-1', 'Sub Category 1', true, '2025-10-03 15:57:46', '2025-10-03 15:57:56', NULL);
INSERT INTO public.master_sub_category VALUES ('6ab569ba-ebc4-4515-ba2d-c57ee571774b', '2', 'Sub 2', true, '2025-10-09 10:02:59', '2025-10-09 10:02:59', NULL);


--
-- TOC entry 5515 (class 0 OID 16539)
-- Dependencies: 225
-- Data for Name: master_sumber; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_sumber VALUES ('ea9ad77b-fe65-4989-9f89-6ba753f24b51', 'Maximo', true, '2025-10-03 02:27:54+07', '2025-10-03 10:34:17+07', NULL, 'KD-2');
INSERT INTO public.master_sumber VALUES ('e8547379-798e-4bcc-8f1f-aaa55de98c0c', 'Dynamic 365', true, '2025-10-03 02:28:16+07', '2025-10-03 10:35:56+07', NULL, 'KD-3');
INSERT INTO public.master_sumber VALUES ('4992eb72-95a2-4915-af6d-e3cb226f050e', 'Excel', true, '2025-10-03 02:27:19+07', '2025-10-03 11:10:02+07', NULL, 'KD-1');
INSERT INTO public.master_sumber VALUES ('856d03e3-59c5-4129-be95-3c366bb404b6', 'Directly from Web', true, '2025-10-10 13:24:14+07', '2025-10-10 13:24:29+07', NULL, 'KD-4');


--
-- TOC entry 5516 (class 0 OID 16572)
-- Dependencies: 226
-- Data for Name: master_transaction; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_transaction VALUES ('f9fce6e5-b49b-4cda-a194-47f240efb727', 'J', 'Jakpro', true, '2025-10-20 12:47:33', '2025-11-10 16:22:05', NULL);
INSERT INTO public.master_transaction VALUES ('7e93bc0a-2fc6-4e8e-9a5b-2228359f1682', 'A', 'LRTJ', true, '2025-10-03 11:35:07', '2025-11-10 16:22:12', NULL);
INSERT INTO public.master_transaction VALUES ('64534d2e-912c-4792-99b5-1854f9d28ef7', 'AWI', 'Adikari', true, '2025-11-10 16:22:28', '2025-11-10 16:22:28', NULL);


--
-- TOC entry 5523 (class 0 OID 16807)
-- Dependencies: 233
-- Data for Name: master_uom; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_uom VALUES ('309fb8aa-6e19-4969-8dc7-849178a57031', 'KG', 'Kilogram', true, '2025-10-03 16:29:29', '2025-10-03 16:30:04', NULL);


--
-- TOC entry 5526 (class 0 OID 16866)
-- Dependencies: 236
-- Data for Name: master_user_code; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.master_user_code VALUES ('739e9057-94d2-46b5-93d2-aa1db045fb72', 'SAR', 'ROLLINGSTOCK DIVISION', 'test', true, '2025-10-06 10:14:22', '2025-11-14 14:39:03', NULL, '1');
INSERT INTO public.master_user_code VALUES ('2db36a77-3f07-482e-9ad6-7010fcbae9dd', 'UCD', 'Test User Code', 'Test', true, '2025-10-20 11:38:36', '2025-11-14 14:39:09', NULL, '1');


--
-- TOC entry 5511 (class 0 OID 16387)
-- Dependencies: 221
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO public.migrations VALUES (4, '2025_10_02_042243_create_master_sumber_table', 2);
INSERT INTO public.migrations VALUES (5, '2025_10_03_032548_add_kode_to_master_sumber_table', 3);
INSERT INTO public.migrations VALUES (6, '2025_10_03_112437_create_master_transaction_table', 4);
INSERT INTO public.migrations VALUES (7, '2025_10_03_133225_create_master_asset_type_table', 5);
INSERT INTO public.migrations VALUES (8, '2025_10_03_134649_create_master_category_table', 6);
INSERT INTO public.migrations VALUES (9, '2025_10_03_135703_create_master_category_2_table', 7);
INSERT INTO public.migrations VALUES (10, '2025_10_03_142318_create_master_asset_type_table', 8);
INSERT INTO public.migrations VALUES (11, '2025_10_03_142478_create_master_asset_type_table', 9);
INSERT INTO public.migrations VALUES (12, '2025_10_03_152357_add_unique_kode_all_to_master_category', 10);
INSERT INTO public.migrations VALUES (13, '2025_10_03_152412_create_master_category_2_table', 11);
INSERT INTO public.migrations VALUES (14, '2025_10_03_152812_create_master_category_2_table', 12);
INSERT INTO public.migrations VALUES (15, '2025_10_03_152928_create_master_category_2_table', 13);
INSERT INTO public.migrations VALUES (16, '2025_10_03_153038_add_unique_kode_all_to_master_category', 14);
INSERT INTO public.migrations VALUES (17, '2025_10_03_154649_create_master_sub_category_table', 15);
INSERT INTO public.migrations VALUES (18, '2025_10_03_160021_create_master_location_table', 16);
INSERT INTO public.migrations VALUES (19, '2025_10_03_161119_create_master_group_category_table', 17);
INSERT INTO public.migrations VALUES (20, '2025_10_03_162225_create_master_uom_table', 18);
INSERT INTO public.migrations VALUES (21, '2025_10_03_163353_create_master_status_table', 19);
INSERT INTO public.migrations VALUES (22, '2025_10_03_164543_create_master_asset_class_table', 20);
INSERT INTO public.migrations VALUES (23, '2025_10_06_091104_update_fk_cascade_on_kode_columns', 21);
INSERT INTO public.migrations VALUES (24, '2025_10_06_091432_add_cascade_to_all_fk', 22);
INSERT INTO public.migrations VALUES (25, '2025_10_06_095741_create_master_user_code_table', 23);
INSERT INTO public.migrations VALUES (26, '2025_10_06_105631_create_personal_access_tokens_table', 24);
INSERT INTO public.migrations VALUES (27, '2025_10_06_110027_create_users_table', 25);
INSERT INTO public.migrations VALUES (28, '2025_10_08_113721_create_assets_table', 26);
INSERT INTO public.migrations VALUES (29, '2025_10_08_114248_create_assets_identifiers_table', 27);
INSERT INTO public.migrations VALUES (30, '2025_10_08_114401_create_assets_classification_table', 28);
INSERT INTO public.migrations VALUES (31, '2025_10_08_114645_create_assets_assignment_table', 29);
INSERT INTO public.migrations VALUES (32, '2025_10_08_114756_create_assets_value_table', 30);
INSERT INTO public.migrations VALUES (33, '2025_10_08_114955_create_assets_document_table', 31);
INSERT INTO public.migrations VALUES (34, '2025_10_08_123220_create_assets_qr_table', 32);
INSERT INTO public.migrations VALUES (35, '2025_10_08_123322_create_assets_rfid_table', 33);
INSERT INTO public.migrations VALUES (36, '2025_10_08_124358_add_soft_deletes_to_asset_children', 34);
INSERT INTO public.migrations VALUES (37, '2025_10_08_140633_add_asset_parent_number_counter', 35);
INSERT INTO public.migrations VALUES (38, '2025_10_09_163626_fix_parent_child_uniques', 36);
INSERT INTO public.migrations VALUES (39, '2025_10_10_140003_create_table_assets_transfer', 37);
INSERT INTO public.migrations VALUES (40, '2025_10_10_140054_add_fk_to_kode_status_asset', 38);
INSERT INTO public.migrations VALUES (41, '2025_10_10_153700_add_pic_to_asset_transfer', 39);
INSERT INTO public.migrations VALUES (42, '2025_10_16_113439_add_file_to_transfer', 40);
INSERT INTO public.migrations VALUES (43, '2025_10_16_135829_create_table_disposal', 41);
INSERT INTO public.migrations VALUES (44, '2025_10_16_144032_add_file_meta_to_table_disposal', 42);
INSERT INTO public.migrations VALUES (45, '2025_10_16_160508_create_table_return_history', 43);
INSERT INTO public.migrations VALUES (46, '2025_10_24_125518_create_table_assets_value_history', 44);
INSERT INTO public.migrations VALUES (47, '2025_10_29_085523_create_table_depr_policy', 45);
INSERT INTO public.migrations VALUES (48, '2025_10_29_085555_create_table_depr_yearly', 45);
INSERT INTO public.migrations VALUES (49, '2025_10_29_085600_create_table_depr_movement', 45);
INSERT INTO public.migrations VALUES (50, '2025_10_29_085652_create_table_depr_monthly', 45);
INSERT INTO public.migrations VALUES (51, '2025_11_04_104104_create_assets_depr_transfer_requests_table', 46);
INSERT INTO public.migrations VALUES (52, '2025_11_04_105114_add_soft_delete_assets_depr_transfer_requests_table', 47);
INSERT INTO public.migrations VALUES (53, '2025_11_04_115159_alter_transfer_requests_requested_by_to_string', 48);
INSERT INTO public.migrations VALUES (54, '2025_11_10_135439_create_table_master_role', 49);
INSERT INTO public.migrations VALUES (55, '2025_11_10_135542_create_table_master_action', 50);
INSERT INTO public.migrations VALUES (56, '2025_11_10_135613_create_table_master_menu', 51);
INSERT INTO public.migrations VALUES (57, '2025_11_10_135914_create_table_master_role_menu', 52);
INSERT INTO public.migrations VALUES (58, '2025_11_10_135956_add_ou_to_users', 53);
INSERT INTO public.migrations VALUES (59, '2025_11_10_144325_create_user_role_table', 54);
INSERT INTO public.migrations VALUES (60, '2025_11_11_094127_add_actions_to_menu', 55);
INSERT INTO public.migrations VALUES (61, '2025_11_14_100432_add_flow_to_transfer', 56);
INSERT INTO public.migrations VALUES (62, '2025_11_14_181556_add_flow_file_to_transfer', 57);
INSERT INTO public.migrations VALUES (63, '2025_11_17_111844_add_flow_to_disposal', 58);
INSERT INTO public.migrations VALUES (64, '2025_11_17_150255_add_ba_to_disposal', 59);
INSERT INTO public.migrations VALUES (65, '2025_11_19_160257_add_kode_department_to_users_table', 60);


--
-- TOC entry 5528 (class 0 OID 16884)
-- Dependencies: 238
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.personal_access_tokens VALUES (1, 'App\Models\User', 1, 'api', 'a29eddfab040f455acea8a582f2ea89c8d3bbb9ddc693c8bf5a74bf82422b385', '["*"]', '2025-10-06 11:17:50', NULL, '2025-10-06 11:16:33', '2025-10-06 11:17:50');
INSERT INTO public.personal_access_tokens VALUES (2, 'App\Models\User', 1, 'api', '9c25f33e023b4301b6630df2090773ebc62e9b441eb6f93ac24d13bd68c45ee5', '["*"]', NULL, NULL, '2025-10-06 11:27:16', '2025-10-06 11:27:16');
INSERT INTO public.personal_access_tokens VALUES (3, 'App\Models\User', 1, 'api', 'a4b056d3d956d2f44aad401045df8442e2f26ff0f2781b70eb92424b688d24a0', '["*"]', '2025-10-06 12:40:18', NULL, '2025-10-06 12:40:10', '2025-10-06 12:40:18');
INSERT INTO public.personal_access_tokens VALUES (4, 'App\Models\User', 1, 'api', 'a9472ad566c13c96990af5566836ef839b1349f82804078bf8f7db017ceb2b08', '["*"]', '2025-10-06 13:27:14', NULL, '2025-10-06 13:27:02', '2025-10-06 13:27:14');
INSERT INTO public.personal_access_tokens VALUES (5, 'App\Models\User', 1, 'api', '913e5af4c5d67e5e9cf47d640090d958ce8dfa2650ddff858c8998b6856067d7', '["*"]', NULL, NULL, '2025-10-06 13:42:13', '2025-10-06 13:42:13');
INSERT INTO public.personal_access_tokens VALUES (6, 'App\Models\User', 1, 'api', 'd280512ede143c1fab0372ba275ab23cfcc9572d64214b28c37766144ffc47fd', '["*"]', NULL, NULL, '2025-10-06 13:43:46', '2025-10-06 13:43:46');
INSERT INTO public.personal_access_tokens VALUES (7, 'App\Models\User', 1, 'api', '4577f837af37628f045a5d13563833394ac3c34d2bd745aaf132981ad397a398', '["*"]', NULL, NULL, '2025-10-06 13:45:17', '2025-10-06 13:45:17');
INSERT INTO public.personal_access_tokens VALUES (8, 'App\Models\User', 1, 'api', 'ddd8de1fd5b0d47365ea0a5afb064f32b2ab11ba5afa5abc5dc270afa5f2cb27', '["*"]', NULL, NULL, '2025-10-06 13:46:19', '2025-10-06 13:46:19');
INSERT INTO public.personal_access_tokens VALUES (9, 'App\Models\User', 1, 'api', 'd8a5da8c5c14e760d52cfa00853a3ae6db7a772f9d1196cf39df13258a6b334f', '["*"]', NULL, NULL, '2025-10-06 13:48:03', '2025-10-06 13:48:03');
INSERT INTO public.personal_access_tokens VALUES (10, 'App\Models\User', 1, 'api', 'aa5e0b5183b3210de2b0958e8fef39132f8b09c0dce1aebbd977d8bfb6de9e43', '["*"]', NULL, NULL, '2025-10-06 13:49:03', '2025-10-06 13:49:03');
INSERT INTO public.personal_access_tokens VALUES (28, 'App\Models\User', 1, 'api', '4206f123df40bd73aed9546fc05eedb7c61cfa5ada8d57ef6e1d145dd0209f32', '["*"]', '2025-10-13 15:59:35', NULL, '2025-10-13 15:59:33', '2025-10-13 15:59:35');
INSERT INTO public.personal_access_tokens VALUES (11, 'App\Models\User', 1, 'api', '33b81fffcbde8ba8b08a72d2684877e9fcf26cef75d6730fe6755e4c3286b4d0', '["*"]', '2025-10-06 14:20:41', NULL, '2025-10-06 14:20:35', '2025-10-06 14:20:41');
INSERT INTO public.personal_access_tokens VALUES (12, 'App\Models\User', 1, 'api', '12e0c32501865f878419d9fac6be92d380fd51db728c0384c40fbf06bf8643e8', '["*"]', NULL, NULL, '2025-10-06 14:21:50', '2025-10-06 14:21:50');
INSERT INTO public.personal_access_tokens VALUES (13, 'App\Models\User', 1, 'api', '24435bdcef247b90f5e8c6b8e5ddb43141f4639fd6156fd0c8340479a9ee35ec', '["*"]', '2025-10-06 14:22:16', NULL, '2025-10-06 14:22:12', '2025-10-06 14:22:16');
INSERT INTO public.personal_access_tokens VALUES (33, 'App\Models\User', 1, 'api', 'ff2c942adc0e8ea8809ba60b49fa50a24823e86e625b0e25637f5295c2ea0cb5', '["*"]', '2025-10-13 16:39:37', NULL, '2025-10-13 16:39:32', '2025-10-13 16:39:37');
INSERT INTO public.personal_access_tokens VALUES (14, 'App\Models\User', 1, 'api', 'dc80f6588f8ca44fafdc0a06ef4a396851cf8a94f2f63d631c9029ab7926ede8', '["*"]', '2025-10-13 14:04:04', NULL, '2025-10-13 13:59:33', '2025-10-13 14:04:04');
INSERT INTO public.personal_access_tokens VALUES (16, 'App\Models\User', 1, 'api', '6df07409b08d3caceb22eb670c9d08d5e166aeaaa9e5a39ef649eb752ce0b160', '["*"]', '2025-10-13 15:29:16', NULL, '2025-10-13 15:19:27', '2025-10-13 15:29:16');
INSERT INTO public.personal_access_tokens VALUES (17, 'App\Models\User', 1, 'api', 'd49f17b188eece563b02096f976a6865a439bc2ae56310194e1127f387ab2660', '["*"]', '2025-10-13 15:40:54', NULL, '2025-10-13 15:40:52', '2025-10-13 15:40:54');
INSERT INTO public.personal_access_tokens VALUES (18, 'App\Models\User', 1, 'api', 'a776aa48312a1abb913c6aaa260cf238705b8579e916d2a5f7a10996c8a47396', '["*"]', '2025-10-13 15:41:56', NULL, '2025-10-13 15:41:54', '2025-10-13 15:41:56');
INSERT INTO public.personal_access_tokens VALUES (19, 'App\Models\User', 1, 'api', 'fe08d19b61e48acba1e5d8b860106ba5d52923836db16d433bdda51251c96eba', '["*"]', '2025-10-13 15:43:15', NULL, '2025-10-13 15:43:13', '2025-10-13 15:43:15');
INSERT INTO public.personal_access_tokens VALUES (20, 'App\Models\User', 1, 'api', '4adcdf7b395ad8efa2ec0a32654740818bdf6325a78178fd3b1de42e440da2ef', '["*"]', '2025-10-13 15:45:03', NULL, '2025-10-13 15:45:01', '2025-10-13 15:45:03');
INSERT INTO public.personal_access_tokens VALUES (21, 'App\Models\User', 1, 'api', '2bdc2c6f4bafbc46e8451076d954c4004f410fb1c912e5fe91191e4afa81a57b', '["*"]', '2025-10-13 15:46:05', NULL, '2025-10-13 15:46:03', '2025-10-13 15:46:05');
INSERT INTO public.personal_access_tokens VALUES (22, 'App\Models\User', 1, 'api', '60d6f016d0fa044d9a71052d4ae79e1c43108eab9c0d2464cae7860215eb9abf', '["*"]', '2025-10-13 15:47:38', NULL, '2025-10-13 15:47:36', '2025-10-13 15:47:38');
INSERT INTO public.personal_access_tokens VALUES (29, 'App\Models\User', 1, 'api', '978af8a1fde954ce563c435454a1801b273b2623a50862a1d479e4c42152522e', '["*"]', '2025-10-13 16:04:01', NULL, '2025-10-13 16:00:37', '2025-10-13 16:04:01');
INSERT INTO public.personal_access_tokens VALUES (23, 'App\Models\User', 1, 'api', 'bfbde5c639e50ec5ec25cb578cc526bd1c98f6daae35564c46cf43b60940389f', '["*"]', '2025-10-13 15:51:45', NULL, '2025-10-13 15:51:43', '2025-10-13 15:51:45');
INSERT INTO public.personal_access_tokens VALUES (24, 'App\Models\User', 1, 'api', '3608b2ca1270ac668bed5c8a49e933e662a2c8656ffb968e1e474c045b0f609b', '["*"]', '2025-10-13 15:53:20', NULL, '2025-10-13 15:53:18', '2025-10-13 15:53:20');
INSERT INTO public.personal_access_tokens VALUES (25, 'App\Models\User', 1, 'api', 'faf90b5aeb988fe38264c62220634deb8865fb2a002e0e86c906ea0d83dc3b35', '["*"]', '2025-10-13 15:55:05', NULL, '2025-10-13 15:55:03', '2025-10-13 15:55:05');
INSERT INTO public.personal_access_tokens VALUES (26, 'App\Models\User', 1, 'api', '2a2f3625ba288a5ac410ac1255eff8789c681ad60ec123e4532d6484f5511965', '["*"]', '2025-10-13 15:56:11', NULL, '2025-10-13 15:56:09', '2025-10-13 15:56:11');
INSERT INTO public.personal_access_tokens VALUES (27, 'App\Models\User', 1, 'api', '23fc2086bc12b9bcebcf18ce8ac10a8506959ebcc3ddb1595417ba503afccde0', '["*"]', '2025-10-13 15:58:35', NULL, '2025-10-13 15:58:33', '2025-10-13 15:58:35');
INSERT INTO public.personal_access_tokens VALUES (30, 'App\Models\User', 1, 'api', '6fb10ada435b30679752b8d845dea1e8e0bc2b0a56bb61e5d3b86ae7b8281466', '["*"]', '2025-10-13 16:12:33', NULL, '2025-10-13 16:12:30', '2025-10-13 16:12:33');
INSERT INTO public.personal_access_tokens VALUES (31, 'App\Models\User', 1, 'api', 'aa255789924561618737b5397484dc6582837e1821a02e2d9f16f6552c1e3f47', '["*"]', '2025-10-13 16:17:52', NULL, '2025-10-13 16:17:50', '2025-10-13 16:17:52');
INSERT INTO public.personal_access_tokens VALUES (34, 'App\Models\User', 1, 'api', 'fa80b71a2743c535a65978526e6e52ebb045b67190437f47a38188f5fcd65be5', '["*"]', '2025-10-13 16:40:45', NULL, '2025-10-13 16:40:43', '2025-10-13 16:40:45');
INSERT INTO public.personal_access_tokens VALUES (36, 'App\Models\User', 1, 'api', 'eeb04fdd0a767f96fa8c2e8e3927d6a82a6b7e5e4189c5d77413f16a769b06b5', '["*"]', '2025-10-13 16:51:43', NULL, '2025-10-13 16:49:36', '2025-10-13 16:51:43');
INSERT INTO public.personal_access_tokens VALUES (37, 'App\Models\User', 1, 'api', '697f9a91a0c9eb9f656ff00b4547bf0e9f44e29965c68e8a3abc6a0950bc8e23', '["*"]', '2025-10-13 16:54:18', NULL, '2025-10-13 16:54:16', '2025-10-13 16:54:18');
INSERT INTO public.personal_access_tokens VALUES (32, 'App\Models\User', 1, 'api', '0772a6f066ae0997aef9716fd0d6e4825448048a513b2c32d5f86496f9feb153', '["*"]', '2025-10-13 16:32:09', NULL, '2025-10-13 16:30:44', '2025-10-13 16:32:09');
INSERT INTO public.personal_access_tokens VALUES (35, 'App\Models\User', 1, 'api', 'a32109788a58d73988e4f7ab7c7f60c2470a58621aa56bc7440309c84d0ac2da', '["*"]', '2025-10-13 16:45:48', NULL, '2025-10-13 16:41:20', '2025-10-13 16:45:48');
INSERT INTO public.personal_access_tokens VALUES (39, 'App\Models\User', 1, 'api', '1fbbb4c74c8b10c4e3881c500f3ade5aead57d6d9af28f8b348867d17556273b', '["*"]', '2025-10-13 17:06:05', NULL, '2025-10-13 17:06:03', '2025-10-13 17:06:05');
INSERT INTO public.personal_access_tokens VALUES (38, 'App\Models\User', 1, 'api', 'e779832a4eb7a2e1bfd02e3f88164ee931526074e8fa7beabdfcc20d6857d4f2', '["*"]', '2025-10-13 17:03:54', NULL, '2025-10-13 17:03:51', '2025-10-13 17:03:54');
INSERT INTO public.personal_access_tokens VALUES (40, 'App\Models\User', 1, 'api', 'c9899714cc82aa004ca97115f43ff5d7dc617fab500e80a55cf0822179750cdc', '["*"]', '2025-10-13 17:06:38', NULL, '2025-10-13 17:06:36', '2025-10-13 17:06:38');
INSERT INTO public.personal_access_tokens VALUES (41, 'App\Models\User', 1, 'api', 'd1242071b28289f34d3beda2e728a54dc5fe39188ddfebfd10e38eda2eefa6a9', '["*"]', '2025-10-13 17:07:08', NULL, '2025-10-13 17:07:06', '2025-10-13 17:07:08');
INSERT INTO public.personal_access_tokens VALUES (42, 'App\Models\User', 1, 'api', 'f7c99ef4da74b2b9b5de3bd62a26805e76a108fcd3e1921b327576bc70ad39f7', '["*"]', '2025-10-13 17:07:46', NULL, '2025-10-13 17:07:44', '2025-10-13 17:07:46');
INSERT INTO public.personal_access_tokens VALUES (44, 'App\Models\User', 1, 'api', 'da447682161b4b119320beaf5fbb9d0432a0527330c298fbc77159da8f5681b2', '["*"]', '2025-10-13 17:09:22', NULL, '2025-10-13 17:09:20', '2025-10-13 17:09:22');
INSERT INTO public.personal_access_tokens VALUES (43, 'App\Models\User', 1, 'api', 'e0e5f10a109f4e3006d00738463b04256130b4223007017935f3dda27c962793', '["*"]', '2025-10-13 17:08:30', NULL, '2025-10-13 17:08:27', '2025-10-13 17:08:30');
INSERT INTO public.personal_access_tokens VALUES (45, 'App\Models\User', 1, 'api', 'baf879dd8e18b88614e73df237dadfc982a190a4ab5492d59b3e8af5a08c724a', '["*"]', '2025-10-13 17:10:47', NULL, '2025-10-13 17:10:45', '2025-10-13 17:10:47');
INSERT INTO public.personal_access_tokens VALUES (46, 'App\Models\User', 1, 'api', '8d17c7063d95e217469c638459766265fe14d7aeb1c0a4737014fe5b797f7c56', '["*"]', '2025-10-13 17:11:22', NULL, '2025-10-13 17:11:20', '2025-10-13 17:11:22');
INSERT INTO public.personal_access_tokens VALUES (47, 'App\Models\User', 1, 'api', '09f2f627cce3c281c017643b89e8ae9a4f61262ab4e0bfb45bbc2a3018c296f7', '["*"]', '2025-10-13 17:12:49', NULL, '2025-10-13 17:12:47', '2025-10-13 17:12:49');
INSERT INTO public.personal_access_tokens VALUES (48, 'App\Models\User', 1, 'api', 'c75abf1000e2ae1cee105a3bac6f738e90caa220b1563c5bca79d8979078a3a1', '["*"]', '2025-10-13 17:13:34', NULL, '2025-10-13 17:13:32', '2025-10-13 17:13:34');
INSERT INTO public.personal_access_tokens VALUES (60, 'user', 1, 'api', '18c627939a43bbfb46b26beae0097925602fd7d6fec28f7b9077fdb2cdd3b3c1', '["*"]', '2025-10-21 13:20:42', NULL, '2025-10-21 13:20:12', '2025-10-21 13:20:42');
INSERT INTO public.personal_access_tokens VALUES (49, 'App\Models\User', 1, 'api', '781ab02605b6b5228eacaec1038a5549545ecfee6169d8b15cc8c1f3588e4603', '["*"]', '2025-10-13 17:14:30', NULL, '2025-10-13 17:14:05', '2025-10-13 17:14:30');
INSERT INTO public.personal_access_tokens VALUES (50, 'App\Models\User', 1, 'api', '57d040473c8f608b5265d9ea473ae4bf0ec1904342f337d8a5c9ad0ed7ae97bb', '["*"]', '2025-10-13 17:15:25', NULL, '2025-10-13 17:15:23', '2025-10-13 17:15:25');
INSERT INTO public.personal_access_tokens VALUES (51, 'App\Models\User', 1, 'api', '7b9e37be1a5d1308bec67d8c5d68392c82c9423ff590adfcef0ba938b0f6b7a4', '["*"]', '2025-10-13 17:15:56', NULL, '2025-10-13 17:15:54', '2025-10-13 17:15:56');
INSERT INTO public.personal_access_tokens VALUES (52, 'App\Models\User', 1, 'api', 'a7248d64779806d4790b2b3d716f3b3b618fe03ba5b3635d525f8b8ecabd42e0', '["*"]', '2025-10-13 17:16:29', NULL, '2025-10-13 17:16:27', '2025-10-13 17:16:29');
INSERT INTO public.personal_access_tokens VALUES (53, 'App\Models\User', 1, 'api', '9a143b98c54e8cfa067dd70f2bb0eb2d321562e2a158012fe9f079c9098afa63', '["*"]', '2025-10-13 17:16:50', NULL, '2025-10-13 17:16:48', '2025-10-13 17:16:50');
INSERT INTO public.personal_access_tokens VALUES (54, 'App\Models\User', 1, 'api', '9a2f188d3353895ee0bef7e2ffe69849df4307b565fcd46b18f7870da7456352', '["*"]', '2025-10-13 17:18:35', NULL, '2025-10-13 17:18:33', '2025-10-13 17:18:35');
INSERT INTO public.personal_access_tokens VALUES (55, 'App\Models\User', 1, 'api', 'c54f1fd6047c5486cf2c0be21494b084106d01c3f13cdd2d80c60975d016cf94', '["*"]', '2025-10-13 17:19:07', NULL, '2025-10-13 17:19:04', '2025-10-13 17:19:07');
INSERT INTO public.personal_access_tokens VALUES (56, 'App\Models\User', 1, 'api', '795ffdbd076e0bdd4d8d6d5014267a6ef80606cf090126cb10e5fa95c461960e', '["*"]', '2025-10-13 17:19:44', NULL, '2025-10-13 17:19:42', '2025-10-13 17:19:44');
INSERT INTO public.personal_access_tokens VALUES (57, 'App\Models\User', 1, 'api', '9513762e72f102b9d2ec8526690a39ba922750c4813fcfd6ca7e105942774ad9', '["*"]', '2025-10-13 17:20:13', NULL, '2025-10-13 17:20:11', '2025-10-13 17:20:13');
INSERT INTO public.personal_access_tokens VALUES (61, 'user', 1, 'api', 'ee56fd72e22aee7453a3be53c0039ed5d388e5a127092d36ef614bbae7c07448', '["*"]', '2025-10-21 13:24:17', NULL, '2025-10-21 13:24:09', '2025-10-21 13:24:17');
INSERT INTO public.personal_access_tokens VALUES (58, 'App\Models\User', 1, 'api', '4bb2baaba0f3c38d3de320409f2b12b4fabe887346c3015556473b2360c86c30', '["*"]', '2025-10-13 17:20:41', NULL, '2025-10-13 17:20:38', '2025-10-13 17:20:41');
INSERT INTO public.personal_access_tokens VALUES (59, 'user', 1, 'api', 'a5afee3ba1219c373260924db8b5d4e0ce4b3e90d9b92a068123525804edbc68', '["*"]', '2025-10-21 13:18:23', NULL, '2025-10-21 13:07:28', '2025-10-21 13:18:23');
INSERT INTO public.personal_access_tokens VALUES (15, 'App\Models\User', 1, 'api', 'd7b749ad98e64e7b574aa89d51246ee435f2e8d5e67863c439adca995a1d4e8f', '["*"]', '2025-10-21 13:20:04', NULL, '2025-10-13 14:01:45', '2025-10-21 13:20:04');


--
-- TOC entry 5543 (class 0 OID 17715)
-- Dependencies: 253
-- Data for Name: return_history; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.return_history VALUES ('882839fb-b356-4614-bbec-30a34d20a6ad', '98dbf2eb-5686-4c9f-b796-8d53a2c4b049', 'transfer', '1c542677-cbd7-4b6f-ae05-87d62d881351', 'MOV25110003', 'test', 'admin', '2025-11-11 13:45:03', '2025-11-11 13:45:03', NULL, 'RET25110001');
INSERT INTO public.return_history VALUES ('d72433ef-00c8-42c4-8d22-862b9b6be5e4', '8576d2d0-0914-4937-93ea-da10998f1fb9', 'transfer', '18400fc5-e98d-4b6c-924c-d7041b743bd5', 'MOV25110002', NULL, 'admin', '2025-11-11 13:47:03', '2025-11-11 13:47:03', NULL, 'RET25110002');


--
-- TOC entry 5512 (class 0 OID 16420)
-- Dependencies: 222
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.sessions VALUES ('JQ5s7P5tSDljMZS0GHiA4ukLA653KBeYA6YvkJuc', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiYjh1Rm43RHc0ajBlTkMweWdRNTRJZUpmSmpKRWxTYU1QemRvR3RPNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hc3NldHMvYnVsay11cGxvYWQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6OToibGRhcF91c2VyIjthOjQ6e3M6ODoidXNlcm5hbWUiO3M6NToiYWRtaW4iO3M6NDoibmFtZSI7czoxMzoiQWRtaW5pc3RyYXRvciI7czo1OiJlbWFpbCI7czoxNzoiYWRtaW5AZXhhbXBsZS5jb20iO3M6Mjoib3UiO047fX0=', 1763545173);
INSERT INTO public.sessions VALUES ('q03Bp9VFnIp3sbphB3MUJMZyOI4ojeY6FenBhUOp', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiaHNSTjhzNWpKNUhnUEtUNHdXdkxUTlkzM1pkb1RxdnRyVXhyam9scCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zdG9jay1vcG5hbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6OToibGRhcF91c2VyIjthOjY6e3M6ODoidXNlcm5hbWUiO3M6NToiYWRtaW4iO3M6NDoibmFtZSI7czoxMzoiQWRtaW5pc3RyYXRvciI7czo1OiJlbWFpbCI7czoxNzoiYWRtaW5AZXhhbXBsZS5jb20iO3M6Mjoib3UiO047czoxNToia29kZV9kZXBhcnRtZW50IjtOO3M6NToicm9sZXMiO2E6Mjp7aTowO3M6OToiREVQVF9VU0VSIjtpOjE7czo4OiJTWVNBRE1JTiI7fX19', 1763606517);


--
-- TOC entry 5554 (class 0 OID 26155)
-- Dependencies: 264
-- Data for Name: user_role; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.user_role VALUES ('c195fca6-3f15-4ff0-b58b-ca588efd9241', 1, 'SYSADMIN', NULL, NULL);
INSERT INTO public.user_role VALUES ('fe6e54fb-eb99-4c6e-803a-d55ac04815ef', 1, 'DEPT_USER', NULL, NULL);


--
-- TOC entry 5530 (class 0 OID 16901)
-- Dependencies: 240
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users VALUES (1, 'admin', 'Administrator', 'admin@example.com', NULL, '$2y$12$7HkMdpb8Uz3M.93Bo6cdDORRh6s/95lpBy0C6aFgS8eymXS5rwTiK', '8foAPGmGMTHJsgFD2TJSca5lly3TVLAmvyN60pLC1RhdBznVmQYLjGtMZi0B', '2025-10-06 11:16:33', '2025-11-20 09:41:49', NULL, 'SYSADMIN', NULL);


--
-- TOC entry 5564 (class 0 OID 0)
-- Dependencies: 220
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 65, true);


--
-- TOC entry 5565 (class 0 OID 0)
-- Dependencies: 237
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 61, true);


--
-- TOC entry 5566 (class 0 OID 0)
-- Dependencies: 239
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- TOC entry 5258 (class 2606 OID 17186)
-- Name: asset_group_counters asset_group_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_group_counters
    ADD CONSTRAINT asset_group_counters_pkey PRIMARY KEY (group_code);


--
-- TOC entry 5260 (class 2606 OID 17194)
-- Name: asset_parent_counters asset_parent_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_parent_counters
    ADD CONSTRAINT asset_parent_counters_pkey PRIMARY KEY (parent_code);


--
-- TOC entry 5230 (class 2606 OID 16954)
-- Name: assets assets_asset_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_code_unique UNIQUE (asset_code);


--
-- TOC entry 5232 (class 2606 OID 16950)
-- Name: assets assets_asset_number_parent_asset_number_child_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_number_parent_asset_number_child_unique UNIQUE (asset_number_parent, asset_number_child);


--
-- TOC entry 5243 (class 2606 OID 17031)
-- Name: assets_assignment assets_assignment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5241 (class 2606 OID 17005)
-- Name: assets_classification assets_classification_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5300 (class 2606 OID 17985)
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_asset_uuid_period_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_asset_uuid_period_unique UNIQUE (asset_uuid, period);


--
-- TOC entry 5303 (class 2606 OID 17993)
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5297 (class 2606 OID 17957)
-- Name: assets_depr_movements assets_depr_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_movements
    ADD CONSTRAINT assets_depr_movements_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5283 (class 2606 OID 17905)
-- Name: assets_depr_policy assets_depr_policy_asset_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_asset_uuid_unique UNIQUE (asset_uuid);


--
-- TOC entry 5285 (class 2606 OID 17903)
-- Name: assets_depr_policy assets_depr_policy_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5305 (class 2606 OID 18037)
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5287 (class 2606 OID 17925)
-- Name: assets_depr_yearly assets_depr_yearly_asset_uuid_fiscal_year_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_asset_uuid_fiscal_year_unique UNIQUE (asset_uuid, fiscal_year);


--
-- TOC entry 5290 (class 2606 OID 17933)
-- Name: assets_depr_yearly assets_depr_yearly_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5268 (class 2606 OID 17713)
-- Name: assets_disposals assets_disposals_disposal_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_disposal_code_unique UNIQUE (disposal_code);


--
-- TOC entry 5271 (class 2606 OID 17710)
-- Name: assets_disposals assets_disposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5248 (class 2606 OID 17061)
-- Name: assets_document assets_document_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5239 (class 2606 OID 16966)
-- Name: assets_identifiers assets_identifiers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5235 (class 2606 OID 16952)
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5251 (class 2606 OID 17082)
-- Name: assets_qr assets_qr_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5254 (class 2606 OID 17105)
-- Name: assets_rfid assets_rfid_epc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_epc_unique UNIQUE (epc);


--
-- TOC entry 5256 (class 2606 OID 17103)
-- Name: assets_rfid assets_rfid_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5263 (class 2606 OID 17586)
-- Name: assets_transfers assets_transfers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5281 (class 2606 OID 17857)
-- Name: assets_value_history assets_value_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value_history
    ADD CONSTRAINT assets_value_history_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5245 (class 2606 OID 17049)
-- Name: assets_value assets_value_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5144 (class 2606 OID 16451)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5142 (class 2606 OID 16441)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5311 (class 2606 OID 26091)
-- Name: master_action master_action_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_action
    ADD CONSTRAINT master_action_kode_unique UNIQUE (kode);


--
-- TOC entry 5313 (class 2606 OID 26089)
-- Name: master_action master_action_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_action
    ADD CONSTRAINT master_action_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5209 (class 2606 OID 16865)
-- Name: master_asset_class master_asset_class_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5211 (class 2606 OID 16845)
-- Name: master_asset_class master_asset_class_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5173 (class 2606 OID 16853)
-- Name: master_category_2 master_category_2_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5175 (class 2606 OID 16763)
-- Name: master_category_2 master_category_2_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5164 (class 2606 OID 16726)
-- Name: master_category master_category_kode_unique_active; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_active UNIQUE (kode);


--
-- TOC entry 5166 (class 2606 OID 16767)
-- Name: master_category master_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5168 (class 2606 OID 16728)
-- Name: master_category master_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5158 (class 2606 OID 16705)
-- Name: master_division master_division_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_division
    ADD CONSTRAINT master_division_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5160 (class 2606 OID 16707)
-- Name: master_division master_division_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_division
    ADD CONSTRAINT master_division_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5191 (class 2606 OID 16859)
-- Name: master_group_category master_group_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5193 (class 2606 OID 16805)
-- Name: master_group_category master_group_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5185 (class 2606 OID 16857)
-- Name: master_location master_location_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5187 (class 2606 OID 16792)
-- Name: master_location master_location_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5315 (class 2606 OID 26121)
-- Name: master_menu master_menu_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_menu
    ADD CONSTRAINT master_menu_kode_unique UNIQUE (kode);


--
-- TOC entry 5317 (class 2606 OID 26119)
-- Name: master_menu master_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_menu
    ADD CONSTRAINT master_menu_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5307 (class 2606 OID 26078)
-- Name: master_role master_role_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role
    ADD CONSTRAINT master_role_kode_unique UNIQUE (kode);


--
-- TOC entry 5320 (class 2606 OID 26148)
-- Name: master_role_menu master_role_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5322 (class 2606 OID 26136)
-- Name: master_role_menu master_role_menu_role_menu_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_role_menu_unique UNIQUE (role_kode, menu_kode);


--
-- TOC entry 5309 (class 2606 OID 26076)
-- Name: master_role master_role_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role
    ADD CONSTRAINT master_role_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5203 (class 2606 OID 16863)
-- Name: master_status master_status_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5205 (class 2606 OID 16832)
-- Name: master_status master_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5179 (class 2606 OID 16855)
-- Name: master_sub_category master_sub_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5181 (class 2606 OID 16779)
-- Name: master_sub_category master_sub_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5148 (class 2606 OID 16849)
-- Name: master_sumber master_sumber_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5150 (class 2606 OID 16548)
-- Name: master_sumber master_sumber_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5154 (class 2606 OID 16851)
-- Name: master_transaction master_transaction_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5156 (class 2606 OID 16583)
-- Name: master_transaction master_transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5197 (class 2606 OID 16861)
-- Name: master_uom master_uom_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5199 (class 2606 OID 16818)
-- Name: master_uom master_uom_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5214 (class 2606 OID 16880)
-- Name: master_user_code master_user_code_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5216 (class 2606 OID 16882)
-- Name: master_user_code master_user_code_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5136 (class 2606 OID 16395)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 5219 (class 2606 OID 16896)
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- TOC entry 5221 (class 2606 OID 16899)
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- TOC entry 5275 (class 2606 OID 17728)
-- Name: return_history return_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.return_history
    ADD CONSTRAINT return_history_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5139 (class 2606 OID 16429)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5324 (class 2606 OID 26175)
-- Name: user_role user_role_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5326 (class 2606 OID 26163)
-- Name: user_role user_role_user_role_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_user_role_unique UNIQUE (user_id, role_kode);


--
-- TOC entry 5224 (class 2606 OID 16916)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5226 (class 2606 OID 16912)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5228 (class 2606 OID 16914)
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- TOC entry 5301 (class 1259 OID 17991)
-- Name: assets_depr_ledger_monthly_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_ledger_monthly_period_index ON public.assets_depr_ledger_monthly USING btree (period);


--
-- TOC entry 5291 (class 1259 OID 17950)
-- Name: assets_depr_movements_asset_uuid_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_asset_uuid_period_index ON public.assets_depr_movements USING btree (asset_uuid, period);


--
-- TOC entry 5292 (class 1259 OID 17952)
-- Name: assets_depr_movements_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_category_index ON public.assets_depr_movements USING btree (category);


--
-- TOC entry 5293 (class 1259 OID 17955)
-- Name: assets_depr_movements_depr_start_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_depr_start_period_index ON public.assets_depr_movements USING btree (depr_start_period);


--
-- TOC entry 5294 (class 1259 OID 17954)
-- Name: assets_depr_movements_group_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_group_uuid_index ON public.assets_depr_movements USING btree (group_uuid);


--
-- TOC entry 5295 (class 1259 OID 17951)
-- Name: assets_depr_movements_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_period_index ON public.assets_depr_movements USING btree (period);


--
-- TOC entry 5298 (class 1259 OID 17953)
-- Name: assets_depr_movements_source_type_source_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_movements_source_type_source_uuid_index ON public.assets_depr_movements USING btree (source_type, source_uuid);


--
-- TOC entry 5288 (class 1259 OID 17931)
-- Name: assets_depr_yearly_fiscal_year_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_depr_yearly_fiscal_year_index ON public.assets_depr_yearly USING btree (fiscal_year);


--
-- TOC entry 5266 (class 1259 OID 17711)
-- Name: assets_disposals_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_disposals_asset_uuid_index ON public.assets_disposals USING btree (asset_uuid);


--
-- TOC entry 5269 (class 1259 OID 17714)
-- Name: assets_disposals_kode_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_disposals_kode_status_index ON public.assets_disposals USING btree (kode_status);


--
-- TOC entry 5246 (class 1259 OID 17059)
-- Name: assets_document_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_document_asset_uuid_index ON public.assets_document USING btree (asset_uuid);


--
-- TOC entry 5237 (class 1259 OID 16964)
-- Name: assets_identifiers_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_identifiers_asset_uuid_index ON public.assets_identifiers USING btree (asset_uuid);


--
-- TOC entry 5233 (class 1259 OID 17346)
-- Name: assets_parent_child_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX assets_parent_child_unique ON public.assets USING btree (asset_number_parent, asset_number_child);


--
-- TOC entry 5249 (class 1259 OID 17080)
-- Name: assets_qr_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_qr_asset_uuid_index ON public.assets_qr USING btree (asset_uuid);


--
-- TOC entry 5252 (class 1259 OID 17101)
-- Name: assets_rfid_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_rfid_asset_uuid_index ON public.assets_rfid USING btree (asset_uuid);


--
-- TOC entry 5261 (class 1259 OID 17591)
-- Name: assets_transfers_kode_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_kode_status_index ON public.assets_transfers USING btree (kode_status);


--
-- TOC entry 5264 (class 1259 OID 17587)
-- Name: assets_transfers_transfer_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_transfer_code_index ON public.assets_transfers USING btree (transfer_code);


--
-- TOC entry 5265 (class 1259 OID 17588)
-- Name: assets_transfers_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_type_index ON public.assets_transfers USING btree (type);


--
-- TOC entry 5278 (class 1259 OID 17858)
-- Name: assets_value_history_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_value_history_asset_uuid_index ON public.assets_value_history USING btree (asset_uuid);


--
-- TOC entry 5279 (class 1259 OID 17859)
-- Name: assets_value_history_pic_request_uid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_value_history_pic_request_uid_index ON public.assets_value_history USING btree (pic_request_uid);


--
-- TOC entry 5169 (class 1259 OID 16756)
-- Name: idx_master_category2_kode_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_master_category2_kode_category ON public.master_category_2 USING btree (kode_category);


--
-- TOC entry 5161 (class 1259 OID 16719)
-- Name: idx_master_category_kode_asset_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_master_category_kode_asset_type ON public.master_category USING btree (kode_asset_type);


--
-- TOC entry 5206 (class 1259 OID 16843)
-- Name: master_asset_class_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_asset_class_kode_index ON public.master_asset_class USING btree (kode);


--
-- TOC entry 5207 (class 1259 OID 16846)
-- Name: master_asset_class_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_asset_class_kode_unique_active ON public.master_asset_class USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5170 (class 1259 OID 16755)
-- Name: master_category_2_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_category_2_kode_index ON public.master_category_2 USING btree (kode);


--
-- TOC entry 5171 (class 1259 OID 16764)
-- Name: master_category_2_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_category_2_kode_unique_active ON public.master_category_2 USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5162 (class 1259 OID 16718)
-- Name: master_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_category_kode_index ON public.master_category USING btree (kode);


--
-- TOC entry 5188 (class 1259 OID 16803)
-- Name: master_group_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_group_category_kode_index ON public.master_group_category USING btree (kode);


--
-- TOC entry 5189 (class 1259 OID 16806)
-- Name: master_group_category_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_group_category_kode_unique_active ON public.master_group_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5182 (class 1259 OID 16790)
-- Name: master_location_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_location_kode_index ON public.master_location USING btree (kode);


--
-- TOC entry 5183 (class 1259 OID 16793)
-- Name: master_location_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_location_kode_unique_active ON public.master_location USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5318 (class 1259 OID 26149)
-- Name: master_role_menu_actions_gin; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_role_menu_actions_gin ON public.master_role_menu USING gin (actions jsonb_path_ops);


--
-- TOC entry 5200 (class 1259 OID 16830)
-- Name: master_status_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_status_kode_index ON public.master_status USING btree (kode);


--
-- TOC entry 5201 (class 1259 OID 16833)
-- Name: master_status_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_status_kode_unique_active ON public.master_status USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5176 (class 1259 OID 16777)
-- Name: master_sub_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_sub_category_kode_index ON public.master_sub_category USING btree (kode);


--
-- TOC entry 5177 (class 1259 OID 16780)
-- Name: master_sub_category_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_sub_category_kode_unique_active ON public.master_sub_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5145 (class 1259 OID 16550)
-- Name: master_sumber_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_sumber_kode_index ON public.master_sumber USING btree (kode);


--
-- TOC entry 5146 (class 1259 OID 16564)
-- Name: master_sumber_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_sumber_kode_unique_active ON public.master_sumber USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5151 (class 1259 OID 16581)
-- Name: master_transaction_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_transaction_kode_index ON public.master_transaction USING btree (kode);


--
-- TOC entry 5152 (class 1259 OID 16584)
-- Name: master_transaction_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_transaction_kode_unique_active ON public.master_transaction USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5194 (class 1259 OID 16816)
-- Name: master_uom_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_uom_kode_index ON public.master_uom USING btree (kode);


--
-- TOC entry 5195 (class 1259 OID 16819)
-- Name: master_uom_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_uom_kode_unique_active ON public.master_uom USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5212 (class 1259 OID 16877)
-- Name: master_user_code_department_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_user_code_department_index ON public.master_user_code USING btree (department);


--
-- TOC entry 5217 (class 1259 OID 16878)
-- Name: master_user_code_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_user_code_status_index ON public.master_user_code USING btree (status);


--
-- TOC entry 5222 (class 1259 OID 16897)
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- TOC entry 5272 (class 1259 OID 17724)
-- Name: return_history_asset_uuid_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_asset_uuid_created_at_index ON public.return_history USING btree (asset_uuid, created_at);


--
-- TOC entry 5273 (class 1259 OID 17729)
-- Name: return_history_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_asset_uuid_index ON public.return_history USING btree (asset_uuid);


--
-- TOC entry 5276 (class 1259 OID 17726)
-- Name: return_history_source_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_source_code_index ON public.return_history USING btree (source_code);


--
-- TOC entry 5277 (class 1259 OID 17725)
-- Name: return_history_source_type_source_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_source_type_source_id_index ON public.return_history USING btree (source_type, source_id);


--
-- TOC entry 5137 (class 1259 OID 16431)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- TOC entry 5140 (class 1259 OID 16430)
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- TOC entry 5236 (class 1259 OID 17381)
-- Name: uniq_assets_parent_child_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_assets_parent_child_active ON public.assets USING btree (asset_number_parent, asset_number_child) WHERE (deleted_at IS NULL);


--
-- TOC entry 5341 (class 2606 OID 17025)
-- Name: assets_assignment assets_assignment_asset_maintenance_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_maintenance_foreign FOREIGN KEY (asset_maintenance) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5342 (class 2606 OID 17015)
-- Name: assets_assignment assets_assignment_asset_owner_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_owner_foreign FOREIGN KEY (asset_owner) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5343 (class 2606 OID 17020)
-- Name: assets_assignment assets_assignment_asset_user_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_user_foreign FOREIGN KEY (asset_user) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5344 (class 2606 OID 17010)
-- Name: assets_assignment assets_assignment_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5335 (class 2606 OID 16974)
-- Name: assets_classification assets_classification_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5336 (class 2606 OID 16979)
-- Name: assets_classification assets_classification_kode_asset_transaction_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_transaction_foreign FOREIGN KEY (kode_asset_transaction) REFERENCES public.master_transaction(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5337 (class 2606 OID 16984)
-- Name: assets_classification assets_classification_kode_asset_type_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_type_foreign FOREIGN KEY (kode_asset_type) REFERENCES public.master_division(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5338 (class 2606 OID 16994)
-- Name: assets_classification assets_classification_kode_category_2_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_2_foreign FOREIGN KEY (kode_category_2) REFERENCES public.master_category_2(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5339 (class 2606 OID 16989)
-- Name: assets_classification assets_classification_kode_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_foreign FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5340 (class 2606 OID 16999)
-- Name: assets_classification assets_classification_kode_sub_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_sub_category_foreign FOREIGN KEY (kode_sub_category) REFERENCES public.master_sub_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5356 (class 2606 OID 17986)
-- Name: assets_depr_ledger_monthly assets_depr_ledger_monthly_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_ledger_monthly
    ADD CONSTRAINT assets_depr_ledger_monthly_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5355 (class 2606 OID 17945)
-- Name: assets_depr_movements assets_depr_movements_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_movements
    ADD CONSTRAINT assets_depr_movements_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5353 (class 2606 OID 17897)
-- Name: assets_depr_policy assets_depr_policy_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_policy
    ADD CONSTRAINT assets_depr_policy_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5357 (class 2606 OID 18026)
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_from_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_from_asset_uuid_foreign FOREIGN KEY (from_asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5358 (class 2606 OID 18031)
-- Name: assets_depr_transfer_requests assets_depr_transfer_requests_to_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_transfer_requests
    ADD CONSTRAINT assets_depr_transfer_requests_to_asset_uuid_foreign FOREIGN KEY (to_asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5354 (class 2606 OID 17926)
-- Name: assets_depr_yearly assets_depr_yearly_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_depr_yearly
    ADD CONSTRAINT assets_depr_yearly_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5351 (class 2606 OID 17704)
-- Name: assets_disposals assets_disposals_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5347 (class 2606 OID 17054)
-- Name: assets_document assets_document_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5334 (class 2606 OID 16959)
-- Name: assets_identifiers assets_identifiers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5330 (class 2606 OID 16929)
-- Name: assets assets_kode_asset_class_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_asset_class_foreign FOREIGN KEY (kode_asset_class) REFERENCES public.master_asset_class(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5331 (class 2606 OID 16939)
-- Name: assets assets_kode_location_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_location_foreign FOREIGN KEY (kode_location) REFERENCES public.master_location(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5332 (class 2606 OID 16934)
-- Name: assets assets_kode_status_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_status_foreign FOREIGN KEY (kode_status) REFERENCES public.master_status(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5333 (class 2606 OID 16944)
-- Name: assets assets_kode_sumber_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_sumber_foreign FOREIGN KEY (kode_sumber) REFERENCES public.master_sumber(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5348 (class 2606 OID 17075)
-- Name: assets_qr assets_qr_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5349 (class 2606 OID 17096)
-- Name: assets_rfid assets_rfid_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5350 (class 2606 OID 17580)
-- Name: assets_transfers assets_transfers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5345 (class 2606 OID 17038)
-- Name: assets_value assets_value_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5352 (class 2606 OID 17851)
-- Name: assets_value_history assets_value_history_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value_history
    ADD CONSTRAINT assets_value_history_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid);


--
-- TOC entry 5346 (class 2606 OID 17043)
-- Name: assets_value assets_value_kode_uom_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_kode_uom_foreign FOREIGN KEY (kode_uom) REFERENCES public.master_uom(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5328 (class 2606 OID 16757)
-- Name: master_category_2 fk_cat2_category_kode; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT fk_cat2_category_kode FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5327 (class 2606 OID 16720)
-- Name: master_category fk_category_asset_type_kode; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT fk_category_asset_type_kode FOREIGN KEY (kode_asset_type) REFERENCES public.master_division(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5359 (class 2606 OID 26142)
-- Name: master_role_menu master_role_menu_menu_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_menu_kode_foreign FOREIGN KEY (menu_kode) REFERENCES public.master_menu(kode) ON DELETE CASCADE;


--
-- TOC entry 5360 (class 2606 OID 26137)
-- Name: master_role_menu master_role_menu_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_role_menu
    ADD CONSTRAINT master_role_menu_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE CASCADE;


--
-- TOC entry 5361 (class 2606 OID 26169)
-- Name: user_role user_role_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE CASCADE;


--
-- TOC entry 5362 (class 2606 OID 26164)
-- Name: user_role user_role_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_role
    ADD CONSTRAINT user_role_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 5329 (class 2606 OID 26150)
-- Name: users users_role_kode_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_kode_foreign FOREIGN KEY (role_kode) REFERENCES public.master_role(kode) ON DELETE SET NULL;


-- Completed on 2025-11-20 09:43:33

--
-- PostgreSQL database dump complete
--

\unrestrict Xa8hGpCMRBrVm4N8DltIJiXhuseK85m0Z8kbMEFUdfrlwBKHKuBGEVUcrK1l9Ks

