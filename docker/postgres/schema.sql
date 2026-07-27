--
-- PostgreSQL database dump
--

\restrict ESAoB4pSDBoVhdPzGSOKXpd5mEoFJsHCfpPKCGPsZFPF4ocgBJb7FSReuYh3sFe

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: configuraciones; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.configuraciones (
    id integer NOT NULL,
    clave character varying(100) NOT NULL,
    valor text,
    descripcion character varying(255),
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.configuraciones OWNER TO infra_user;

--
-- Name: configuraciones_id_seq; Type: SEQUENCE; Schema: public; Owner: infra_user
--

CREATE SEQUENCE public.configuraciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.configuraciones_id_seq OWNER TO infra_user;

--
-- Name: configuraciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: infra_user
--

ALTER SEQUENCE public.configuraciones_id_seq OWNED BY public.configuraciones.id;


--
-- Name: redmine_estados; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_estados (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    is_closed boolean DEFAULT false
);


ALTER TABLE public.redmine_estados OWNER TO infra_user;

--
-- Name: redmine_prioridades; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_prioridades (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL
);


ALTER TABLE public.redmine_prioridades OWNER TO infra_user;

--
-- Name: redmine_proyectos; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_proyectos (
    id integer NOT NULL,
    identifier character varying(100) NOT NULL,
    nombre character varying(255) NOT NULL,
    descripcion text,
    created_on timestamp without time zone,
    updated_on timestamp without time zone
);


ALTER TABLE public.redmine_proyectos OWNER TO infra_user;

--
-- Name: redmine_tarea_relaciones; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_tarea_relaciones (
    id integer NOT NULL,
    tarea_origen_id integer,
    tarea_destino_id integer,
    tipo_relacion character varying(50),
    delay integer
);


ALTER TABLE public.redmine_tarea_relaciones OWNER TO infra_user;

--
-- Name: redmine_tarea_seguidores; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_tarea_seguidores (
    tarea_id integer NOT NULL,
    usuario_id integer NOT NULL
);


ALTER TABLE public.redmine_tarea_seguidores OWNER TO infra_user;

--
-- Name: redmine_tareas; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_tareas (
    id integer NOT NULL,
    proyecto_id integer,
    tracker_nombre character varying(100),
    estado_id integer,
    prioridad_id integer,
    asunto character varying(500) NOT NULL,
    descripcion text,
    autor_id integer,
    asignado_a_id integer,
    porcentaje_done integer DEFAULT 0,
    estimated_hours numeric(8,2),
    spent_hours numeric(8,2),
    start_date date,
    due_date date,
    created_on timestamp without time zone,
    updated_on timestamp without time zone,
    closed_on timestamp without time zone,
    parent_id integer,
    categoria character varying(150)
);


ALTER TABLE public.redmine_tareas OWNER TO infra_user;

--
-- Name: redmine_usuarios; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.redmine_usuarios (
    id integer NOT NULL,
    nombre_completo character varying(255) NOT NULL
);


ALTER TABLE public.redmine_usuarios OWNER TO infra_user;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    nombre character varying(50) NOT NULL
);


ALTER TABLE public.roles OWNER TO infra_user;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: infra_user
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO infra_user;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: infra_user
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: infra_user
--

CREATE TABLE public.usuarios (
    id integer NOT NULL,
    username character varying(100) NOT NULL,
    email character varying(150),
    nombre_completo character varying(150),
    rol_id integer DEFAULT 1,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.usuarios OWNER TO infra_user;

--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: infra_user
--

CREATE SEQUENCE public.usuarios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_seq OWNER TO infra_user;

--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: infra_user
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: configuraciones id; Type: DEFAULT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.configuraciones ALTER COLUMN id SET DEFAULT nextval('public.configuraciones_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Name: configuraciones configuraciones_clave_key; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.configuraciones
    ADD CONSTRAINT configuraciones_clave_key UNIQUE (clave);


--
-- Name: configuraciones configuraciones_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.configuraciones
    ADD CONSTRAINT configuraciones_pkey PRIMARY KEY (id);


--
-- Name: redmine_estados redmine_estados_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_estados
    ADD CONSTRAINT redmine_estados_pkey PRIMARY KEY (id);


--
-- Name: redmine_prioridades redmine_prioridades_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_prioridades
    ADD CONSTRAINT redmine_prioridades_pkey PRIMARY KEY (id);


--
-- Name: redmine_proyectos redmine_proyectos_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_proyectos
    ADD CONSTRAINT redmine_proyectos_pkey PRIMARY KEY (id);


--
-- Name: redmine_tarea_relaciones redmine_tarea_relaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tarea_relaciones
    ADD CONSTRAINT redmine_tarea_relaciones_pkey PRIMARY KEY (id);


--
-- Name: redmine_tarea_seguidores redmine_tarea_seguidores_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tarea_seguidores
    ADD CONSTRAINT redmine_tarea_seguidores_pkey PRIMARY KEY (tarea_id, usuario_id);


--
-- Name: redmine_tareas redmine_tareas_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tareas
    ADD CONSTRAINT redmine_tareas_pkey PRIMARY KEY (id);


--
-- Name: redmine_usuarios redmine_usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_usuarios
    ADD CONSTRAINT redmine_usuarios_pkey PRIMARY KEY (id);


--
-- Name: roles roles_nombre_key; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_key UNIQUE (nombre);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_username_key; Type: CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_username_key UNIQUE (username);


--
-- Name: redmine_tarea_relaciones redmine_tarea_relaciones_tarea_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tarea_relaciones
    ADD CONSTRAINT redmine_tarea_relaciones_tarea_origen_id_fkey FOREIGN KEY (tarea_origen_id) REFERENCES public.redmine_tareas(id) ON DELETE CASCADE;


--
-- Name: redmine_tarea_seguidores redmine_tarea_seguidores_tarea_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tarea_seguidores
    ADD CONSTRAINT redmine_tarea_seguidores_tarea_id_fkey FOREIGN KEY (tarea_id) REFERENCES public.redmine_tareas(id) ON DELETE CASCADE;


--
-- Name: redmine_tarea_seguidores redmine_tarea_seguidores_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tarea_seguidores
    ADD CONSTRAINT redmine_tarea_seguidores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.redmine_usuarios(id) ON DELETE CASCADE;


--
-- Name: redmine_tareas redmine_tareas_proyecto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.redmine_tareas
    ADD CONSTRAINT redmine_tareas_proyecto_id_fkey FOREIGN KEY (proyecto_id) REFERENCES public.redmine_proyectos(id) ON DELETE CASCADE;


--
-- Name: usuarios usuarios_rol_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: infra_user
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_rol_id_fkey FOREIGN KEY (rol_id) REFERENCES public.roles(id);


--
-- PostgreSQL database dump complete
--

\unrestrict ESAoB4pSDBoVhdPzGSOKXpd5mEoFJsHCfpPKCGPsZFPF4ocgBJb7FSReuYh3sFe

