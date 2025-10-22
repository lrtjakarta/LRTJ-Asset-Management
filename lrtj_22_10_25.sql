--
-- PostgreSQL database dump
--

\restrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-10-22 10:28:19

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

DROP DATABASE IF EXISTS "lrt-jakarta-asset-management-awi";
--
-- TOC entry 5371 (class 1262 OID 16384)
-- Name: lrt-jakarta-asset-management-awi; Type: DATABASE; Schema: -; Owner: -
--

CREATE DATABASE "lrt-jakarta-asset-management-awi" WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'English_Indonesia.1252';


\unrestrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY
\encoding SQL_ASCII
\connect -reuse-previous=on "dbname='lrt-jakarta-asset-management-awi'"
\restrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY

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
-- TOC entry 5372 (class 0 OID 0)
-- Name: lrt-jakarta-asset-management-awi; Type: DATABASE PROPERTIES; Schema: -; Owner: -
--

ALTER DATABASE "lrt-jakarta-asset-management-awi" SET "TimeZone" TO 'Asia/Jakarta';


\unrestrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY
\encoding SQL_ASCII
\connect -reuse-previous=on "dbname='lrt-jakarta-asset-management-awi'"
\restrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY

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

SET default_tablespace = '';

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
    deleted_at timestamp(0) with time zone
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
    before_status character varying(10)
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
    file_size bigint
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
    deleted_at timestamp(0) with time zone
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
-- TOC entry 227 (class 1259 OID 16695)
-- Name: master_asset_type; Type: TABLE; Schema: public; Owner: -
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
    deleted_at timestamp(0) without time zone
);


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
-- TOC entry 5373 (class 0 OID 0)
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
    CONSTRAINT return_history_source_type_chk CHECK (((source_type)::text = ANY ((ARRAY['transfer'::character varying, 'disposal'::character varying])::text[])))
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
    updated_at timestamp(0) without time zone
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
-- TOC entry 5374 (class 0 OID 0)
-- Dependencies: 239
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 5009 (class 2604 OID 16887)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 5010 (class 2604 OID 16904)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5361 (class 0 OID 17179)
-- Dependencies: 249
-- Data for Name: asset_group_counters; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.asset_group_counters (group_code, last_parent_seq, created_at, updated_at) FROM stdin;
A1100	1	2025-10-20 11:15:14+07	2025-10-20 11:15:13+07
\.


--
-- TOC entry 5362 (class 0 OID 17187)
-- Dependencies: 250
-- Data for Name: asset_parent_counters; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.asset_parent_counters (parent_code, last_child_seq, created_at, updated_at) FROM stdin;
A1100000001	1	2025-10-20 11:15:14+07	2025-10-20 12:33:36+07
\.


--
-- TOC entry 5353 (class 0 OID 16917)
-- Dependencies: 241
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets (uuid, kode_group_category, asset_code, asset_number_parent, asset_number_child, description, kode_asset_class, kode_status, kode_location, kode_sumber, created_at, updated_at, deleted_at) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	A1100	A1100000001-00	A1100000001	00	Test Asset Baru diedit	1100	OPE	LOC-1	KD-4	2025-10-20 11:15:13+07	2025-10-20 11:35:14+07	\N
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	1100	A1100000001-01	A1100000001	01	test aaa	1100	OPE	LOC-1	KD-1	2025-10-20 12:33:36+07	2025-10-20 12:43:35+07	\N
\.


--
-- TOC entry 5356 (class 0 OID 17006)
-- Dependencies: 244
-- Data for Name: assets_assignment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_assignment (asset_uuid, asset_owner, asset_user, asset_maintenance, created_at, updated_at, deleted_at) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	UCD	UCD	SAR	2025-10-20 11:15:13+07	2025-10-20 11:39:49+07	\N
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	SAR	SAR	SAR	2025-10-20 12:33:36+07	2025-10-20 12:43:35+07	\N
\.


--
-- TOC entry 5355 (class 0 OID 16967)
-- Dependencies: 243
-- Data for Name: assets_classification; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_classification (asset_uuid, kode_asset_transaction, kode_asset_type, kode_category, kode_category_2, kode_sub_category, created_at, updated_at, deleted_at) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	A	\N	\N	\N	\N	2025-10-20 11:15:13+07	2025-10-20 11:15:13+07	\N
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	A	\N	\N	\N	\N	2025-10-20 12:33:36+07	2025-10-20 12:43:35+07	\N
\.


--
-- TOC entry 5364 (class 0 OID 17693)
-- Dependencies: 252
-- Data for Name: assets_disposals; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_disposals (uuid, asset_uuid, disposal_code, target_status, kode_status, note, file_path, pic_request_uid, pic_approve_uid, created_at, updated_at, deleted_at, file_name, file_mime, file_size, before_status) FROM stdin;
\.


--
-- TOC entry 5358 (class 0 OID 17050)
-- Dependencies: 246
-- Data for Name: assets_document; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_document (asset_uuid, no_po_perjanjian_spk, nota_referensi, no_document, created_at, updated_at, deleted_at) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	\N	1122334455566	1122334455566	2025-10-20 11:15:13+07	2025-10-20 11:15:13+07	\N
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	\N	123123	123123	2025-10-20 12:33:36+07	2025-10-20 12:43:35+07	\N
\.


--
-- TOC entry 5354 (class 0 OID 16955)
-- Dependencies: 242
-- Data for Name: assets_identifiers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_identifiers (asset_uuid, asset_number_maximo, asset_number_dynamic_365, asset_number_internal, created_at, updated_at, deleted_at, alias) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	\N	\N	\N	2025-10-20 11:15:13+07	2025-10-20 15:42:30+07	\N	alias
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	\N	\N	\N	2025-10-20 12:33:36+07	2025-10-20 15:42:56+07	\N	alias
\.


--
-- TOC entry 5359 (class 0 OID 17062)
-- Dependencies: 247
-- Data for Name: assets_qr; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_qr (uuid, asset_uuid, qr_data, image_path, is_active, generated_at, created_at, updated_at, deleted_at) FROM stdin;
02a9206c-a8db-4c74-a2ae-0db77b87fc0c	98dbf2eb-5686-4c9f-b796-8d53a2c4b049	98dbf2eb-5686-4c9f-b796-8d53a2c4b049	qrcodes/98dbf2eb-5686-4c9f-b796-8d53a2c4b049.svg	t	2025-10-20 15:42:30+07	2025-10-20 11:15:13+07	2025-10-20 15:42:30+07	\N
a7bf7363-f9c1-4e6b-a6e1-1c14e2ef7ee1	90a5eefa-0b37-4ff4-b221-f297e5b1d16b	90a5eefa-0b37-4ff4-b221-f297e5b1d16b	qrcodes/90a5eefa-0b37-4ff4-b221-f297e5b1d16b.svg	t	2025-10-20 15:42:56+07	2025-10-20 12:33:36+07	2025-10-20 15:42:56+07	\N
\.


--
-- TOC entry 5360 (class 0 OID 17083)
-- Dependencies: 248
-- Data for Name: assets_rfid; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_rfid (uuid, asset_uuid, epc, tag_type, encoded_at, is_active, deactivated_at, note, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- TOC entry 5363 (class 0 OID 17568)
-- Dependencies: 251
-- Data for Name: assets_transfers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_transfers (uuid, asset_uuid, transfer_code, type, before, after, kode_status, note, created_at, updated_at, deleted_at, pic_request_uid, pic_approve_uid, file_path, file_name, file_mime, file_size) FROM stdin;
\.


--
-- TOC entry 5357 (class 0 OID 17032)
-- Dependencies: 245
-- Data for Name: assets_value; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.assets_value (asset_uuid, price, quantity, is_pajak, vat_in, kode_uom, total, useful_life_month, useful_life_year, created_at, updated_at, deleted_at) FROM stdin;
98dbf2eb-5686-4c9f-b796-8d53a2c4b049	15000000.00	1.000	t	1800000.00	KG	16800000.00	12	1.00	2025-10-20 11:15:13+07	2025-10-20 11:15:13+07	\N
90a5eefa-0b37-4ff4-b221-f297e5b1d16b	1000000.00	1.000	t	120000.00	KG	1120000.00	12	1.00	2025-10-20 12:33:36+07	2025-10-20 12:43:35+07	\N
\.


--
-- TOC entry 5335 (class 0 OID 16432)
-- Dependencies: 223
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
a75f3f172bfb296f2e10cbfc6dfc1883:timer	i:1761027908;	1761027908
a75f3f172bfb296f2e10cbfc6dfc1883	i:1;	1761027908
\.


--
-- TOC entry 5336 (class 0 OID 16442)
-- Dependencies: 224
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- TOC entry 5347 (class 0 OID 16834)
-- Dependencies: 235
-- Data for Name: master_asset_class; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_asset_class (uuid, kode, name, status, created_at, updated_at, deleted_at, kode_transaction) FROM stdin;
36d2c150-b55c-441d-9954-d8c3b98521e4	1100	Asset Class 1	t	2025-10-03 16:53:34	2025-10-20 11:12:48	\N	A
7f492877-d0c2-49db-9740-cb2ed8dfa73b	1101	Asset Class 2	t	2025-10-20 11:13:30	2025-10-20 11:13:30	\N	J
04f7a113-062f-4a34-b6ff-6a15f0ae7f5b	1102	Asset Class 3	t	2025-10-20 13:11:32	2025-10-20 13:11:36	\N	J
\.


--
-- TOC entry 5339 (class 0 OID 16695)
-- Dependencies: 227
-- Data for Name: master_asset_type; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_asset_type (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
264c7161-ed0e-4604-8fb6-562f11d90668	2	Asset Type 2	t	2025-10-08 15:49:44	2025-10-08 16:48:12	\N
c437599e-129f-469c-b583-da6f100a0645	1	Asset Type 1	t	2025-10-03 14:58:28	2025-10-08 16:48:16	\N
\.


--
-- TOC entry 5340 (class 0 OID 16708)
-- Dependencies: 228
-- Data for Name: master_category; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_category (uuid, kode, name, kode_asset_type, status, created_at, updated_at, deleted_at) FROM stdin;
2e4cedf9-2cca-4134-a1ac-686099e85546	2	test	2	t	2025-10-08 15:59:11	2025-10-08 16:48:26	\N
b3cec431-138b-494e-9c68-0487f3a8fe80	1	Category 1	1	t	2025-10-03 15:08:15	2025-10-08 16:48:31	\N
\.


--
-- TOC entry 5341 (class 0 OID 16745)
-- Dependencies: 229
-- Data for Name: master_category_2; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_category_2 (uuid, kode, name, status, kode_category, created_at, updated_at, deleted_at) FROM stdin;
cea6d18f-21ac-4a15-b439-b4db70d58f60	1	Category 2-1	t	2	2025-10-03 15:44:00	2025-10-08 16:57:09	\N
a259262a-3291-4dd6-b06f-44a329761950	2	test2	t	1	2025-10-08 16:27:52	2025-10-08 16:57:20	\N
\.


--
-- TOC entry 5344 (class 0 OID 16794)
-- Dependencies: 232
-- Data for Name: master_group_category; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_group_category (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
aed27b3a-a105-4237-9d17-b4835dadced1	GRC-1	Group 1	t	2025-10-03 16:20:38	2025-10-03 16:20:38	\N
\.


--
-- TOC entry 5343 (class 0 OID 16781)
-- Dependencies: 231
-- Data for Name: master_location; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_location (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
4d9871af-8036-4731-89ee-d4e3be4fb074	LOC-1	Location 1	t	2025-10-03 16:08:17	2025-10-03 16:09:06	\N
\.


--
-- TOC entry 5346 (class 0 OID 16820)
-- Dependencies: 234
-- Data for Name: master_status; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_status (uuid, kode, name, type, status, created_at, updated_at, deleted_at) FROM stdin;
588d5c64-4c42-4202-a5ca-563418babd6c	IDL	Idle	Asset	t	2025-10-10 13:11:44	2025-10-10 13:11:44	\N
02ec8263-005d-45de-b3ef-2c0c9a4a0645	OPE	Operation	Asset	t	2025-10-10 13:18:07	2025-10-10 13:18:07	\N
b4800a62-66cc-45f0-a788-84a130c7a700	RPR	Repair	Asset	t	2025-10-10 13:18:23	2025-10-10 13:18:23	\N
34704a59-76d7-4534-a122-4f892a9c4487	DIS	Disposal	Asset	t	2025-10-10 13:18:32	2025-10-10 13:18:32	\N
2c7658b3-bcf1-4d9c-b5e2-7c23a1215547	RET	Returned	Return	t	2025-10-10 13:19:05	2025-10-10 13:19:05	\N
9ef8e275-cf15-4748-a55d-368e8b5ea0ea	DISP	Disposed	Disposal	t	2025-10-10 13:19:36	2025-10-10 13:19:36	\N
e6a5d54e-acfe-49be-a12b-34ac9bd8de01	APR	Waiting for Approval	Transfer	t	2025-10-03 16:45:06	2025-10-10 13:21:45	\N
ac82e7e6-6025-4351-b8d7-a2e4d6df2987	REJ	Rejected	Transfer	t	2025-10-10 13:18:56	2025-10-10 13:22:01	\N
ea5708f7-6c6b-43ea-9951-37db03cfac45	ACC	Accepted	Transfer	t	2025-10-10 13:18:45	2025-10-10 13:22:08	\N
\.


--
-- TOC entry 5342 (class 0 OID 16768)
-- Dependencies: 230
-- Data for Name: master_sub_category; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_sub_category (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
1f447db3-1c95-4767-8d57-d80bd1ab79c8	SUB-1	Sub Category 1	t	2025-10-03 15:57:46	2025-10-03 15:57:56	\N
6ab569ba-ebc4-4515-ba2d-c57ee571774b	2	Sub 2	t	2025-10-09 10:02:59	2025-10-09 10:02:59	\N
\.


--
-- TOC entry 5337 (class 0 OID 16539)
-- Dependencies: 225
-- Data for Name: master_sumber; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_sumber (uuid, name, status, created_at, updated_at, deleted_at, kode) FROM stdin;
ea9ad77b-fe65-4989-9f89-6ba753f24b51	Maximo	t	2025-10-03 02:27:54+07	2025-10-03 10:34:17+07	\N	KD-2
e8547379-798e-4bcc-8f1f-aaa55de98c0c	Dynamic 365	t	2025-10-03 02:28:16+07	2025-10-03 10:35:56+07	\N	KD-3
4992eb72-95a2-4915-af6d-e3cb226f050e	Excel	t	2025-10-03 02:27:19+07	2025-10-03 11:10:02+07	\N	KD-1
856d03e3-59c5-4129-be95-3c366bb404b6	Directly from Web	t	2025-10-10 13:24:14+07	2025-10-10 13:24:29+07	\N	KD-4
\.


--
-- TOC entry 5338 (class 0 OID 16572)
-- Dependencies: 226
-- Data for Name: master_transaction; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_transaction (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
7e93bc0a-2fc6-4e8e-9a5b-2228359f1682	A	Transaction 1	t	2025-10-03 11:35:07	2025-10-08 16:47:41	\N
f9fce6e5-b49b-4cda-a194-47f240efb727	J	Transaction 2	t	2025-10-20 12:47:33	2025-10-20 12:47:33	\N
\.


--
-- TOC entry 5345 (class 0 OID 16807)
-- Dependencies: 233
-- Data for Name: master_uom; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_uom (uuid, kode, name, status, created_at, updated_at, deleted_at) FROM stdin;
309fb8aa-6e19-4969-8dc7-849178a57031	KG	Kilogram	t	2025-10-03 16:29:29	2025-10-03 16:30:04	\N
\.


--
-- TOC entry 5348 (class 0 OID 16866)
-- Dependencies: 236
-- Data for Name: master_user_code; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.master_user_code (uuid, kode, department, description, status, created_at, updated_at, deleted_at) FROM stdin;
739e9057-94d2-46b5-93d2-aa1db045fb72	SAR	ROLLINGSTOCK DIVISION	test	t	2025-10-06 10:14:22	2025-10-06 10:17:21	\N
2db36a77-3f07-482e-9ad6-7010fcbae9dd	UCD	Test User Code	Test	t	2025-10-20 11:38:36	2025-10-20 11:38:36	\N
\.


--
-- TOC entry 5350 (class 0 OID 16884)
-- Dependencies: 238
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
1	App\\Models\\User	1	api	a29eddfab040f455acea8a582f2ea89c8d3bbb9ddc693c8bf5a74bf82422b385	["*"]	2025-10-06 11:17:50	\N	2025-10-06 11:16:33	2025-10-06 11:17:50
2	App\\Models\\User	1	api	9c25f33e023b4301b6630df2090773ebc62e9b441eb6f93ac24d13bd68c45ee5	["*"]	\N	\N	2025-10-06 11:27:16	2025-10-06 11:27:16
3	App\\Models\\User	1	api	a4b056d3d956d2f44aad401045df8442e2f26ff0f2781b70eb92424b688d24a0	["*"]	2025-10-06 12:40:18	\N	2025-10-06 12:40:10	2025-10-06 12:40:18
4	App\\Models\\User	1	api	a9472ad566c13c96990af5566836ef839b1349f82804078bf8f7db017ceb2b08	["*"]	2025-10-06 13:27:14	\N	2025-10-06 13:27:02	2025-10-06 13:27:14
5	App\\Models\\User	1	api	913e5af4c5d67e5e9cf47d640090d958ce8dfa2650ddff858c8998b6856067d7	["*"]	\N	\N	2025-10-06 13:42:13	2025-10-06 13:42:13
6	App\\Models\\User	1	api	d280512ede143c1fab0372ba275ab23cfcc9572d64214b28c37766144ffc47fd	["*"]	\N	\N	2025-10-06 13:43:46	2025-10-06 13:43:46
7	App\\Models\\User	1	api	4577f837af37628f045a5d13563833394ac3c34d2bd745aaf132981ad397a398	["*"]	\N	\N	2025-10-06 13:45:17	2025-10-06 13:45:17
8	App\\Models\\User	1	api	ddd8de1fd5b0d47365ea0a5afb064f32b2ab11ba5afa5abc5dc270afa5f2cb27	["*"]	\N	\N	2025-10-06 13:46:19	2025-10-06 13:46:19
9	App\\Models\\User	1	api	d8a5da8c5c14e760d52cfa00853a3ae6db7a772f9d1196cf39df13258a6b334f	["*"]	\N	\N	2025-10-06 13:48:03	2025-10-06 13:48:03
10	App\\Models\\User	1	api	aa5e0b5183b3210de2b0958e8fef39132f8b09c0dce1aebbd977d8bfb6de9e43	["*"]	\N	\N	2025-10-06 13:49:03	2025-10-06 13:49:03
28	App\\Models\\User	1	api	4206f123df40bd73aed9546fc05eedb7c61cfa5ada8d57ef6e1d145dd0209f32	["*"]	2025-10-13 15:59:35	\N	2025-10-13 15:59:33	2025-10-13 15:59:35
11	App\\Models\\User	1	api	33b81fffcbde8ba8b08a72d2684877e9fcf26cef75d6730fe6755e4c3286b4d0	["*"]	2025-10-06 14:20:41	\N	2025-10-06 14:20:35	2025-10-06 14:20:41
12	App\\Models\\User	1	api	12e0c32501865f878419d9fac6be92d380fd51db728c0384c40fbf06bf8643e8	["*"]	\N	\N	2025-10-06 14:21:50	2025-10-06 14:21:50
13	App\\Models\\User	1	api	24435bdcef247b90f5e8c6b8e5ddb43141f4639fd6156fd0c8340479a9ee35ec	["*"]	2025-10-06 14:22:16	\N	2025-10-06 14:22:12	2025-10-06 14:22:16
33	App\\Models\\User	1	api	ff2c942adc0e8ea8809ba60b49fa50a24823e86e625b0e25637f5295c2ea0cb5	["*"]	2025-10-13 16:39:37	\N	2025-10-13 16:39:32	2025-10-13 16:39:37
14	App\\Models\\User	1	api	dc80f6588f8ca44fafdc0a06ef4a396851cf8a94f2f63d631c9029ab7926ede8	["*"]	2025-10-13 14:04:04	\N	2025-10-13 13:59:33	2025-10-13 14:04:04
16	App\\Models\\User	1	api	6df07409b08d3caceb22eb670c9d08d5e166aeaaa9e5a39ef649eb752ce0b160	["*"]	2025-10-13 15:29:16	\N	2025-10-13 15:19:27	2025-10-13 15:29:16
17	App\\Models\\User	1	api	d49f17b188eece563b02096f976a6865a439bc2ae56310194e1127f387ab2660	["*"]	2025-10-13 15:40:54	\N	2025-10-13 15:40:52	2025-10-13 15:40:54
18	App\\Models\\User	1	api	a776aa48312a1abb913c6aaa260cf238705b8579e916d2a5f7a10996c8a47396	["*"]	2025-10-13 15:41:56	\N	2025-10-13 15:41:54	2025-10-13 15:41:56
19	App\\Models\\User	1	api	fe08d19b61e48acba1e5d8b860106ba5d52923836db16d433bdda51251c96eba	["*"]	2025-10-13 15:43:15	\N	2025-10-13 15:43:13	2025-10-13 15:43:15
20	App\\Models\\User	1	api	4adcdf7b395ad8efa2ec0a32654740818bdf6325a78178fd3b1de42e440da2ef	["*"]	2025-10-13 15:45:03	\N	2025-10-13 15:45:01	2025-10-13 15:45:03
21	App\\Models\\User	1	api	2bdc2c6f4bafbc46e8451076d954c4004f410fb1c912e5fe91191e4afa81a57b	["*"]	2025-10-13 15:46:05	\N	2025-10-13 15:46:03	2025-10-13 15:46:05
22	App\\Models\\User	1	api	60d6f016d0fa044d9a71052d4ae79e1c43108eab9c0d2464cae7860215eb9abf	["*"]	2025-10-13 15:47:38	\N	2025-10-13 15:47:36	2025-10-13 15:47:38
29	App\\Models\\User	1	api	978af8a1fde954ce563c435454a1801b273b2623a50862a1d479e4c42152522e	["*"]	2025-10-13 16:04:01	\N	2025-10-13 16:00:37	2025-10-13 16:04:01
23	App\\Models\\User	1	api	bfbde5c639e50ec5ec25cb578cc526bd1c98f6daae35564c46cf43b60940389f	["*"]	2025-10-13 15:51:45	\N	2025-10-13 15:51:43	2025-10-13 15:51:45
24	App\\Models\\User	1	api	3608b2ca1270ac668bed5c8a49e933e662a2c8656ffb968e1e474c045b0f609b	["*"]	2025-10-13 15:53:20	\N	2025-10-13 15:53:18	2025-10-13 15:53:20
25	App\\Models\\User	1	api	faf90b5aeb988fe38264c62220634deb8865fb2a002e0e86c906ea0d83dc3b35	["*"]	2025-10-13 15:55:05	\N	2025-10-13 15:55:03	2025-10-13 15:55:05
26	App\\Models\\User	1	api	2a2f3625ba288a5ac410ac1255eff8789c681ad60ec123e4532d6484f5511965	["*"]	2025-10-13 15:56:11	\N	2025-10-13 15:56:09	2025-10-13 15:56:11
27	App\\Models\\User	1	api	23fc2086bc12b9bcebcf18ce8ac10a8506959ebcc3ddb1595417ba503afccde0	["*"]	2025-10-13 15:58:35	\N	2025-10-13 15:58:33	2025-10-13 15:58:35
30	App\\Models\\User	1	api	6fb10ada435b30679752b8d845dea1e8e0bc2b0a56bb61e5d3b86ae7b8281466	["*"]	2025-10-13 16:12:33	\N	2025-10-13 16:12:30	2025-10-13 16:12:33
31	App\\Models\\User	1	api	aa255789924561618737b5397484dc6582837e1821a02e2d9f16f6552c1e3f47	["*"]	2025-10-13 16:17:52	\N	2025-10-13 16:17:50	2025-10-13 16:17:52
34	App\\Models\\User	1	api	fa80b71a2743c535a65978526e6e52ebb045b67190437f47a38188f5fcd65be5	["*"]	2025-10-13 16:40:45	\N	2025-10-13 16:40:43	2025-10-13 16:40:45
36	App\\Models\\User	1	api	eeb04fdd0a767f96fa8c2e8e3927d6a82a6b7e5e4189c5d77413f16a769b06b5	["*"]	2025-10-13 16:51:43	\N	2025-10-13 16:49:36	2025-10-13 16:51:43
37	App\\Models\\User	1	api	697f9a91a0c9eb9f656ff00b4547bf0e9f44e29965c68e8a3abc6a0950bc8e23	["*"]	2025-10-13 16:54:18	\N	2025-10-13 16:54:16	2025-10-13 16:54:18
32	App\\Models\\User	1	api	0772a6f066ae0997aef9716fd0d6e4825448048a513b2c32d5f86496f9feb153	["*"]	2025-10-13 16:32:09	\N	2025-10-13 16:30:44	2025-10-13 16:32:09
35	App\\Models\\User	1	api	a32109788a58d73988e4f7ab7c7f60c2470a58621aa56bc7440309c84d0ac2da	["*"]	2025-10-13 16:45:48	\N	2025-10-13 16:41:20	2025-10-13 16:45:48
39	App\\Models\\User	1	api	1fbbb4c74c8b10c4e3881c500f3ade5aead57d6d9af28f8b348867d17556273b	["*"]	2025-10-13 17:06:05	\N	2025-10-13 17:06:03	2025-10-13 17:06:05
38	App\\Models\\User	1	api	e779832a4eb7a2e1bfd02e3f88164ee931526074e8fa7beabdfcc20d6857d4f2	["*"]	2025-10-13 17:03:54	\N	2025-10-13 17:03:51	2025-10-13 17:03:54
40	App\\Models\\User	1	api	c9899714cc82aa004ca97115f43ff5d7dc617fab500e80a55cf0822179750cdc	["*"]	2025-10-13 17:06:38	\N	2025-10-13 17:06:36	2025-10-13 17:06:38
41	App\\Models\\User	1	api	d1242071b28289f34d3beda2e728a54dc5fe39188ddfebfd10e38eda2eefa6a9	["*"]	2025-10-13 17:07:08	\N	2025-10-13 17:07:06	2025-10-13 17:07:08
42	App\\Models\\User	1	api	f7c99ef4da74b2b9b5de3bd62a26805e76a108fcd3e1921b327576bc70ad39f7	["*"]	2025-10-13 17:07:46	\N	2025-10-13 17:07:44	2025-10-13 17:07:46
44	App\\Models\\User	1	api	da447682161b4b119320beaf5fbb9d0432a0527330c298fbc77159da8f5681b2	["*"]	2025-10-13 17:09:22	\N	2025-10-13 17:09:20	2025-10-13 17:09:22
43	App\\Models\\User	1	api	e0e5f10a109f4e3006d00738463b04256130b4223007017935f3dda27c962793	["*"]	2025-10-13 17:08:30	\N	2025-10-13 17:08:27	2025-10-13 17:08:30
45	App\\Models\\User	1	api	baf879dd8e18b88614e73df237dadfc982a190a4ab5492d59b3e8af5a08c724a	["*"]	2025-10-13 17:10:47	\N	2025-10-13 17:10:45	2025-10-13 17:10:47
46	App\\Models\\User	1	api	8d17c7063d95e217469c638459766265fe14d7aeb1c0a4737014fe5b797f7c56	["*"]	2025-10-13 17:11:22	\N	2025-10-13 17:11:20	2025-10-13 17:11:22
47	App\\Models\\User	1	api	09f2f627cce3c281c017643b89e8ae9a4f61262ab4e0bfb45bbc2a3018c296f7	["*"]	2025-10-13 17:12:49	\N	2025-10-13 17:12:47	2025-10-13 17:12:49
48	App\\Models\\User	1	api	c75abf1000e2ae1cee105a3bac6f738e90caa220b1563c5bca79d8979078a3a1	["*"]	2025-10-13 17:13:34	\N	2025-10-13 17:13:32	2025-10-13 17:13:34
60	user	1	api	18c627939a43bbfb46b26beae0097925602fd7d6fec28f7b9077fdb2cdd3b3c1	["*"]	2025-10-21 13:20:42	\N	2025-10-21 13:20:12	2025-10-21 13:20:42
49	App\\Models\\User	1	api	781ab02605b6b5228eacaec1038a5549545ecfee6169d8b15cc8c1f3588e4603	["*"]	2025-10-13 17:14:30	\N	2025-10-13 17:14:05	2025-10-13 17:14:30
50	App\\Models\\User	1	api	57d040473c8f608b5265d9ea473ae4bf0ec1904342f337d8a5c9ad0ed7ae97bb	["*"]	2025-10-13 17:15:25	\N	2025-10-13 17:15:23	2025-10-13 17:15:25
51	App\\Models\\User	1	api	7b9e37be1a5d1308bec67d8c5d68392c82c9423ff590adfcef0ba938b0f6b7a4	["*"]	2025-10-13 17:15:56	\N	2025-10-13 17:15:54	2025-10-13 17:15:56
52	App\\Models\\User	1	api	a7248d64779806d4790b2b3d716f3b3b618fe03ba5b3635d525f8b8ecabd42e0	["*"]	2025-10-13 17:16:29	\N	2025-10-13 17:16:27	2025-10-13 17:16:29
53	App\\Models\\User	1	api	9a143b98c54e8cfa067dd70f2bb0eb2d321562e2a158012fe9f079c9098afa63	["*"]	2025-10-13 17:16:50	\N	2025-10-13 17:16:48	2025-10-13 17:16:50
54	App\\Models\\User	1	api	9a2f188d3353895ee0bef7e2ffe69849df4307b565fcd46b18f7870da7456352	["*"]	2025-10-13 17:18:35	\N	2025-10-13 17:18:33	2025-10-13 17:18:35
55	App\\Models\\User	1	api	c54f1fd6047c5486cf2c0be21494b084106d01c3f13cdd2d80c60975d016cf94	["*"]	2025-10-13 17:19:07	\N	2025-10-13 17:19:04	2025-10-13 17:19:07
56	App\\Models\\User	1	api	795ffdbd076e0bdd4d8d6d5014267a6ef80606cf090126cb10e5fa95c461960e	["*"]	2025-10-13 17:19:44	\N	2025-10-13 17:19:42	2025-10-13 17:19:44
57	App\\Models\\User	1	api	9513762e72f102b9d2ec8526690a39ba922750c4813fcfd6ca7e105942774ad9	["*"]	2025-10-13 17:20:13	\N	2025-10-13 17:20:11	2025-10-13 17:20:13
61	user	1	api	ee56fd72e22aee7453a3be53c0039ed5d388e5a127092d36ef614bbae7c07448	["*"]	2025-10-21 13:24:17	\N	2025-10-21 13:24:09	2025-10-21 13:24:17
58	App\\Models\\User	1	api	4bb2baaba0f3c38d3de320409f2b12b4fabe887346c3015556473b2360c86c30	["*"]	2025-10-13 17:20:41	\N	2025-10-13 17:20:38	2025-10-13 17:20:41
59	user	1	api	a5afee3ba1219c373260924db8b5d4e0ce4b3e90d9b92a068123525804edbc68	["*"]	2025-10-21 13:18:23	\N	2025-10-21 13:07:28	2025-10-21 13:18:23
15	App\\Models\\User	1	api	d7b749ad98e64e7b574aa89d51246ee435f2e8d5e67863c439adca995a1d4e8f	["*"]	2025-10-21 13:20:04	\N	2025-10-13 14:01:45	2025-10-21 13:20:04
\.


--
-- TOC entry 5365 (class 0 OID 17715)
-- Dependencies: 253
-- Data for Name: return_history; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.return_history (uuid, asset_uuid, source_type, source_id, source_code, note, pic_request_uid, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- TOC entry 5334 (class 0 OID 16420)
-- Dependencies: 222
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
snu4GcoeEu8GuSzNdQLQHj4yYvpAKDGHPUKbfaUa	\N	127.0.0.1	PostmanRuntime/7.49.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiVFJXbG8xd042cVBkcUNGYlFkamVkUWFHc080MnoyaVExVWJIdWladCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1761027479
s5O6Es90NggrF4LAv28sEdNb8cSMXH6ppAys99D8	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWZXNGk3b0xubXZwWG9aN01TNml5dXUxSE1KN1kza2YwWm9PS2hGbSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hc3NldHMvZGV0YWlsLzkwYTVlZWZhLTBiMzctNGZmNC1iMjIxLWYyOTdlNWIxZDE2YiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6OToibGRhcF91c2VyIjthOjU6e3M6MzoidWlkIjtzOjU6ImFkbWluIjtzOjQ6Im5hbWUiO3M6MTM6IkFkbWluaXN0cmF0b3IiO3M6MjoiZG4iO3M6MTc6ImNuPWFkbWluLGRjPWxvY2FsIjtzOjI6Im91IjtzOjU6ImxvY2FsIjtzOjg6ImF1dGhfdmlhIjtzOjY6InN0YXRpYyI7fX0=	1760952665
\.


--
-- TOC entry 5352 (class 0 OID 16901)
-- Dependencies: 240
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, username, name, email, email_verified_at, password, remember_token, created_at, updated_at) FROM stdin;
1	admin	Administrator	admin@example.com	\N	$2y$12$7HkMdpb8Uz3M.93Bo6cdDORRh6s/95lpBy0C6aFgS8eymXS5rwTiK	\N	2025-10-06 11:16:33	2025-10-06 11:16:33
\.


--
-- TOC entry 5375 (class 0 OID 0)
-- Dependencies: 237
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 61, true);


--
-- TOC entry 5376 (class 0 OID 0)
-- Dependencies: 239
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- TOC entry 5143 (class 2606 OID 17186)
-- Name: asset_group_counters asset_group_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_group_counters
    ADD CONSTRAINT asset_group_counters_pkey PRIMARY KEY (group_code);


--
-- TOC entry 5145 (class 2606 OID 17194)
-- Name: asset_parent_counters asset_parent_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asset_parent_counters
    ADD CONSTRAINT asset_parent_counters_pkey PRIMARY KEY (parent_code);


--
-- TOC entry 5115 (class 2606 OID 16954)
-- Name: assets assets_asset_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_code_unique UNIQUE (asset_code);


--
-- TOC entry 5117 (class 2606 OID 16950)
-- Name: assets assets_asset_number_parent_asset_number_child_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_number_parent_asset_number_child_unique UNIQUE (asset_number_parent, asset_number_child);


--
-- TOC entry 5128 (class 2606 OID 17031)
-- Name: assets_assignment assets_assignment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5126 (class 2606 OID 17005)
-- Name: assets_classification assets_classification_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5153 (class 2606 OID 17713)
-- Name: assets_disposals assets_disposals_disposal_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_disposal_code_unique UNIQUE (disposal_code);


--
-- TOC entry 5156 (class 2606 OID 17710)
-- Name: assets_disposals assets_disposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5133 (class 2606 OID 17061)
-- Name: assets_document assets_document_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5124 (class 2606 OID 16966)
-- Name: assets_identifiers assets_identifiers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5120 (class 2606 OID 16952)
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5136 (class 2606 OID 17082)
-- Name: assets_qr assets_qr_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5139 (class 2606 OID 17105)
-- Name: assets_rfid assets_rfid_epc_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_epc_unique UNIQUE (epc);


--
-- TOC entry 5141 (class 2606 OID 17103)
-- Name: assets_rfid assets_rfid_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5148 (class 2606 OID 17586)
-- Name: assets_transfers assets_transfers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5130 (class 2606 OID 17049)
-- Name: assets_value assets_value_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_pkey PRIMARY KEY (asset_uuid);


--
-- TOC entry 5029 (class 2606 OID 16451)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5027 (class 2606 OID 16441)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5094 (class 2606 OID 16865)
-- Name: master_asset_class master_asset_class_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5096 (class 2606 OID 16845)
-- Name: master_asset_class master_asset_class_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_class
    ADD CONSTRAINT master_asset_class_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5043 (class 2606 OID 16705)
-- Name: master_asset_type master_asset_type_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_type
    ADD CONSTRAINT master_asset_type_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5045 (class 2606 OID 16707)
-- Name: master_asset_type master_asset_type_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_asset_type
    ADD CONSTRAINT master_asset_type_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5058 (class 2606 OID 16853)
-- Name: master_category_2 master_category_2_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5060 (class 2606 OID 16763)
-- Name: master_category_2 master_category_2_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT master_category_2_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5049 (class 2606 OID 16726)
-- Name: master_category master_category_kode_unique_active; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_active UNIQUE (kode);


--
-- TOC entry 5051 (class 2606 OID 16767)
-- Name: master_category master_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5053 (class 2606 OID 16728)
-- Name: master_category master_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT master_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5076 (class 2606 OID 16859)
-- Name: master_group_category master_group_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5078 (class 2606 OID 16805)
-- Name: master_group_category master_group_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_group_category
    ADD CONSTRAINT master_group_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5070 (class 2606 OID 16857)
-- Name: master_location master_location_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5072 (class 2606 OID 16792)
-- Name: master_location master_location_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_location
    ADD CONSTRAINT master_location_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5088 (class 2606 OID 16863)
-- Name: master_status master_status_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5090 (class 2606 OID 16832)
-- Name: master_status master_status_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_status
    ADD CONSTRAINT master_status_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5064 (class 2606 OID 16855)
-- Name: master_sub_category master_sub_category_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5066 (class 2606 OID 16779)
-- Name: master_sub_category master_sub_category_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sub_category
    ADD CONSTRAINT master_sub_category_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5033 (class 2606 OID 16849)
-- Name: master_sumber master_sumber_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5035 (class 2606 OID 16548)
-- Name: master_sumber master_sumber_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_sumber
    ADD CONSTRAINT master_sumber_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5039 (class 2606 OID 16851)
-- Name: master_transaction master_transaction_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5041 (class 2606 OID 16583)
-- Name: master_transaction master_transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_transaction
    ADD CONSTRAINT master_transaction_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5082 (class 2606 OID 16861)
-- Name: master_uom master_uom_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5084 (class 2606 OID 16818)
-- Name: master_uom master_uom_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_uom
    ADD CONSTRAINT master_uom_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5099 (class 2606 OID 16880)
-- Name: master_user_code master_user_code_kode_unique_all; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_kode_unique_all UNIQUE (kode);


--
-- TOC entry 5101 (class 2606 OID 16882)
-- Name: master_user_code master_user_code_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_user_code
    ADD CONSTRAINT master_user_code_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5104 (class 2606 OID 16896)
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- TOC entry 5106 (class 2606 OID 16899)
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- TOC entry 5160 (class 2606 OID 17728)
-- Name: return_history return_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.return_history
    ADD CONSTRAINT return_history_pkey PRIMARY KEY (uuid);


--
-- TOC entry 5024 (class 2606 OID 16429)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5109 (class 2606 OID 16916)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5111 (class 2606 OID 16912)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5113 (class 2606 OID 16914)
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- TOC entry 5151 (class 1259 OID 17711)
-- Name: assets_disposals_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_disposals_asset_uuid_index ON public.assets_disposals USING btree (asset_uuid);


--
-- TOC entry 5154 (class 1259 OID 17714)
-- Name: assets_disposals_kode_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_disposals_kode_status_index ON public.assets_disposals USING btree (kode_status);


--
-- TOC entry 5131 (class 1259 OID 17059)
-- Name: assets_document_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_document_asset_uuid_index ON public.assets_document USING btree (asset_uuid);


--
-- TOC entry 5122 (class 1259 OID 16964)
-- Name: assets_identifiers_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_identifiers_asset_uuid_index ON public.assets_identifiers USING btree (asset_uuid);


--
-- TOC entry 5118 (class 1259 OID 17346)
-- Name: assets_parent_child_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX assets_parent_child_unique ON public.assets USING btree (asset_number_parent, asset_number_child);


--
-- TOC entry 5134 (class 1259 OID 17080)
-- Name: assets_qr_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_qr_asset_uuid_index ON public.assets_qr USING btree (asset_uuid);


--
-- TOC entry 5137 (class 1259 OID 17101)
-- Name: assets_rfid_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_rfid_asset_uuid_index ON public.assets_rfid USING btree (asset_uuid);


--
-- TOC entry 5146 (class 1259 OID 17591)
-- Name: assets_transfers_kode_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_kode_status_index ON public.assets_transfers USING btree (kode_status);


--
-- TOC entry 5149 (class 1259 OID 17587)
-- Name: assets_transfers_transfer_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_transfer_code_index ON public.assets_transfers USING btree (transfer_code);


--
-- TOC entry 5150 (class 1259 OID 17588)
-- Name: assets_transfers_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX assets_transfers_type_index ON public.assets_transfers USING btree (type);


--
-- TOC entry 5054 (class 1259 OID 16756)
-- Name: idx_master_category2_kode_category; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_master_category2_kode_category ON public.master_category_2 USING btree (kode_category);


--
-- TOC entry 5046 (class 1259 OID 16719)
-- Name: idx_master_category_kode_asset_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_master_category_kode_asset_type ON public.master_category USING btree (kode_asset_type);


--
-- TOC entry 5091 (class 1259 OID 16843)
-- Name: master_asset_class_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_asset_class_kode_index ON public.master_asset_class USING btree (kode);


--
-- TOC entry 5092 (class 1259 OID 16846)
-- Name: master_asset_class_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_asset_class_kode_unique_active ON public.master_asset_class USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5055 (class 1259 OID 16755)
-- Name: master_category_2_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_category_2_kode_index ON public.master_category_2 USING btree (kode);


--
-- TOC entry 5056 (class 1259 OID 16764)
-- Name: master_category_2_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_category_2_kode_unique_active ON public.master_category_2 USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5047 (class 1259 OID 16718)
-- Name: master_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_category_kode_index ON public.master_category USING btree (kode);


--
-- TOC entry 5073 (class 1259 OID 16803)
-- Name: master_group_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_group_category_kode_index ON public.master_group_category USING btree (kode);


--
-- TOC entry 5074 (class 1259 OID 16806)
-- Name: master_group_category_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_group_category_kode_unique_active ON public.master_group_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5067 (class 1259 OID 16790)
-- Name: master_location_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_location_kode_index ON public.master_location USING btree (kode);


--
-- TOC entry 5068 (class 1259 OID 16793)
-- Name: master_location_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_location_kode_unique_active ON public.master_location USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5085 (class 1259 OID 16830)
-- Name: master_status_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_status_kode_index ON public.master_status USING btree (kode);


--
-- TOC entry 5086 (class 1259 OID 16833)
-- Name: master_status_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_status_kode_unique_active ON public.master_status USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5061 (class 1259 OID 16777)
-- Name: master_sub_category_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_sub_category_kode_index ON public.master_sub_category USING btree (kode);


--
-- TOC entry 5062 (class 1259 OID 16780)
-- Name: master_sub_category_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_sub_category_kode_unique_active ON public.master_sub_category USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5030 (class 1259 OID 16550)
-- Name: master_sumber_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_sumber_kode_index ON public.master_sumber USING btree (kode);


--
-- TOC entry 5031 (class 1259 OID 16564)
-- Name: master_sumber_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_sumber_kode_unique_active ON public.master_sumber USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5036 (class 1259 OID 16581)
-- Name: master_transaction_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_transaction_kode_index ON public.master_transaction USING btree (kode);


--
-- TOC entry 5037 (class 1259 OID 16584)
-- Name: master_transaction_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_transaction_kode_unique_active ON public.master_transaction USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5079 (class 1259 OID 16816)
-- Name: master_uom_kode_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_uom_kode_index ON public.master_uom USING btree (kode);


--
-- TOC entry 5080 (class 1259 OID 16819)
-- Name: master_uom_kode_unique_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX master_uom_kode_unique_active ON public.master_uom USING btree (kode) WHERE (deleted_at IS NULL);


--
-- TOC entry 5097 (class 1259 OID 16877)
-- Name: master_user_code_department_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_user_code_department_index ON public.master_user_code USING btree (department);


--
-- TOC entry 5102 (class 1259 OID 16878)
-- Name: master_user_code_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX master_user_code_status_index ON public.master_user_code USING btree (status);


--
-- TOC entry 5107 (class 1259 OID 16897)
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- TOC entry 5157 (class 1259 OID 17724)
-- Name: return_history_asset_uuid_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_asset_uuid_created_at_index ON public.return_history USING btree (asset_uuid, created_at);


--
-- TOC entry 5158 (class 1259 OID 17729)
-- Name: return_history_asset_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_asset_uuid_index ON public.return_history USING btree (asset_uuid);


--
-- TOC entry 5161 (class 1259 OID 17726)
-- Name: return_history_source_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_source_code_index ON public.return_history USING btree (source_code);


--
-- TOC entry 5162 (class 1259 OID 17725)
-- Name: return_history_source_type_source_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX return_history_source_type_source_id_index ON public.return_history USING btree (source_type, source_id);


--
-- TOC entry 5022 (class 1259 OID 16431)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- TOC entry 5025 (class 1259 OID 16430)
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- TOC entry 5121 (class 1259 OID 17381)
-- Name: uniq_assets_parent_child_active; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_assets_parent_child_active ON public.assets USING btree (asset_number_parent, asset_number_child) WHERE (deleted_at IS NULL);


--
-- TOC entry 5176 (class 2606 OID 17025)
-- Name: assets_assignment assets_assignment_asset_maintenance_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_maintenance_foreign FOREIGN KEY (asset_maintenance) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5177 (class 2606 OID 17015)
-- Name: assets_assignment assets_assignment_asset_owner_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_owner_foreign FOREIGN KEY (asset_owner) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5178 (class 2606 OID 17020)
-- Name: assets_assignment assets_assignment_asset_user_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_user_foreign FOREIGN KEY (asset_user) REFERENCES public.master_user_code(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5179 (class 2606 OID 17010)
-- Name: assets_assignment assets_assignment_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_assignment
    ADD CONSTRAINT assets_assignment_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5170 (class 2606 OID 16974)
-- Name: assets_classification assets_classification_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5171 (class 2606 OID 16979)
-- Name: assets_classification assets_classification_kode_asset_transaction_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_transaction_foreign FOREIGN KEY (kode_asset_transaction) REFERENCES public.master_transaction(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5172 (class 2606 OID 16984)
-- Name: assets_classification assets_classification_kode_asset_type_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_asset_type_foreign FOREIGN KEY (kode_asset_type) REFERENCES public.master_asset_type(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5173 (class 2606 OID 16994)
-- Name: assets_classification assets_classification_kode_category_2_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_2_foreign FOREIGN KEY (kode_category_2) REFERENCES public.master_category_2(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5174 (class 2606 OID 16989)
-- Name: assets_classification assets_classification_kode_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_category_foreign FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5175 (class 2606 OID 16999)
-- Name: assets_classification assets_classification_kode_sub_category_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_classification
    ADD CONSTRAINT assets_classification_kode_sub_category_foreign FOREIGN KEY (kode_sub_category) REFERENCES public.master_sub_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5186 (class 2606 OID 17704)
-- Name: assets_disposals assets_disposals_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_disposals
    ADD CONSTRAINT assets_disposals_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5182 (class 2606 OID 17054)
-- Name: assets_document assets_document_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_document
    ADD CONSTRAINT assets_document_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5169 (class 2606 OID 16959)
-- Name: assets_identifiers assets_identifiers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_identifiers
    ADD CONSTRAINT assets_identifiers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5165 (class 2606 OID 16929)
-- Name: assets assets_kode_asset_class_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_asset_class_foreign FOREIGN KEY (kode_asset_class) REFERENCES public.master_asset_class(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5166 (class 2606 OID 16939)
-- Name: assets assets_kode_location_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_location_foreign FOREIGN KEY (kode_location) REFERENCES public.master_location(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5167 (class 2606 OID 16934)
-- Name: assets assets_kode_status_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_status_foreign FOREIGN KEY (kode_status) REFERENCES public.master_status(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5168 (class 2606 OID 16944)
-- Name: assets assets_kode_sumber_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_kode_sumber_foreign FOREIGN KEY (kode_sumber) REFERENCES public.master_sumber(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5183 (class 2606 OID 17075)
-- Name: assets_qr assets_qr_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_qr
    ADD CONSTRAINT assets_qr_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5184 (class 2606 OID 17096)
-- Name: assets_rfid assets_rfid_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_rfid
    ADD CONSTRAINT assets_rfid_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5185 (class 2606 OID 17580)
-- Name: assets_transfers assets_transfers_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_transfers
    ADD CONSTRAINT assets_transfers_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5180 (class 2606 OID 17038)
-- Name: assets_value assets_value_asset_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_asset_uuid_foreign FOREIGN KEY (asset_uuid) REFERENCES public.assets(uuid) ON DELETE CASCADE;


--
-- TOC entry 5181 (class 2606 OID 17043)
-- Name: assets_value assets_value_kode_uom_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.assets_value
    ADD CONSTRAINT assets_value_kode_uom_foreign FOREIGN KEY (kode_uom) REFERENCES public.master_uom(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5164 (class 2606 OID 16757)
-- Name: master_category_2 fk_cat2_category_kode; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category_2
    ADD CONSTRAINT fk_cat2_category_kode FOREIGN KEY (kode_category) REFERENCES public.master_category(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 5163 (class 2606 OID 16720)
-- Name: master_category fk_category_asset_type_kode; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.master_category
    ADD CONSTRAINT fk_category_asset_type_kode FOREIGN KEY (kode_asset_type) REFERENCES public.master_asset_type(kode) ON UPDATE CASCADE ON DELETE RESTRICT;


-- Completed on 2025-10-22 10:28:20

--
-- PostgreSQL database dump complete
--

\unrestrict Ghht0iSY2bVHTEwclxhjOJ19bUgdmrYD7x8Ka8yVgbbEoublhJwolVjsgW7oqNY

