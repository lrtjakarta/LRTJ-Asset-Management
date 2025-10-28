
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


