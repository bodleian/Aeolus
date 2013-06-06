-- Setup script for basic Aeolus database framework which can then be customised.
--(Not needed if creating copy of EMLO-edit or IMPAcT, as they have their own DB creation scripts.)
-- Create the empty database beforehand, then run this script as postgres.

create role super_role_aeolus    ;
create role editor_role_aeolus   ;
create role viewer_role_aeolus   ;
create role collector_role_aeolus;

--

create user aeolusa1supr         ;
create user c9cont              ;
create user w5view              ;
create user x0minim             ;

--

grant editor_role_aeolus    to super_role_aeolus;
grant viewer_role_aeolus    to super_role_aeolus;
grant collector_role_aeolus to super_role_aeolus;

grant viewer_role_aeolus    to editor_role_aeolus;
grant collector_role_aeolus to editor_role_aeolus;

--

grant super_role_aeolus to aeolusa1supr;

grant collector_role_aeolus to c9cont;

grant viewer_role_aeolus to w5view;

--

--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: aeolus_common_get_orig_column_name(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION aeolus_common_get_orig_column_name(text_id character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, strpos( text_id, '-' ) + 1, strpos( text_id, ':' ) - strpos( text_id, '-' ) - 1 );
end;

$$;


ALTER FUNCTION public.aeolus_common_get_orig_column_name(text_id character varying) OWNER TO postgres;

--
-- Name: aeolus_common_get_orig_id(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION aeolus_common_get_orig_id(text_id character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, strpos( text_id, ':' )+1)::integer ;
end;

$$;


ALTER FUNCTION public.aeolus_common_get_orig_id(text_id character varying) OWNER TO postgres;

--
-- Name: aeolus_common_get_orig_table_name(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION aeolus_common_get_orig_table_name(text_id character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, 1, strpos( text_id, '-' ) - 1 );
end;

$$;


ALTER FUNCTION public.aeolus_common_get_orig_table_name(text_id character varying) OWNER TO postgres;

--
-- Name: aeolus_common_make_text_id(character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION aeolus_common_make_text_id(orig_table_name character varying, orig_column_name character varying, orig_row_id integer) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return trim( orig_table_name ) || '-' || trim( orig_column_name ) || ':' 
         || lpad( orig_row_id::varchar, 9, '0' );
end;

$$;


ALTER FUNCTION public.aeolus_common_make_text_id(orig_table_name character varying, orig_column_name character varying, orig_row_id integer) OWNER TO postgres;

--
-- Name: dbf_alphanumeric(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           FALSE,  -- don't include underscores 
                           ''      -- don't convert any other characters to underscores
                         );

end;

$$;


ALTER FUNCTION public.dbf_alphanumeric(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric(text, boolean, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

declare
  alphanum text;
  string_length integer;
  i integer;
  one_char varchar(1);
  last_char varchar(1);
begin

  alphanum = '';
  last_char = '';
  string_length = length( string_parm );

  for i in 1..string_length loop
    one_char = substr( string_parm, i, 1 );

    if ( one_char >= 'a' and one_char <= 'z' )
    or ( one_char >= 'A' and one_char <= 'Z' )
    or ( one_char >= '0' and one_char <= '9' )
    then
      alphanum = alphanum || one_char;

    elsif one_char = '_' and allow_underscores then
      alphanum = alphanum || one_char;

    elsif strpos( convert_chars, one_char ) > 0 then
      one_char = '_';
      if last_char != '_' then -- don't have multiple underscores in a row
        alphanum = alphanum || one_char;
      end if;
    end if;

    last_char = one_char;
  end loop;

  return alphanum;
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_and_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric_and_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           TRUE,  -- allow underscores
                           ''     -- don't convert any other characters to underscores
                          );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_and_underscore(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_conv_to_underscore(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           TRUE,            -- allow underscores
                           convert_chars    -- convert specified characters to underscores
                         );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_others_to_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric_with_others_to_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $_$

declare
  backslash constant varchar(1) = E'\\';
  bad_chars varchar(100);
begin
  -- We need to add pound sign in the string below. (At the moment not correctly converted to UTF-8)
  bad_chars = ' !"$%^&*()-+={}[]:;@''~#|<,>.?/	' || backslash;

  return dbf_alphanumeric_with_conv_to_underscore( string_parm, bad_chars );
end;

$_$;


ALTER FUNCTION public.dbf_alphanumeric_with_others_to_underscore(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_space_slash_to_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

declare
  backslash constant varchar(1) = E'\\';
  bad_chars varchar(10);
begin
  bad_chars = ' /	' || backslash;
  return dbf_alphanumeric_with_conv_to_underscore( string_parm, bad_chars );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) OWNER TO postgres;


--
-- Name: dbf_aeolus_check_login_creds(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  login_success     constant integer :=  1;
  account_suspended constant integer := -1;
  invalid_creds     constant integer :=  0;    -- invalid username or password

  max_failed_logins_allowed constant integer := 10;

  decoded_username varchar(30);
  password_match_string text;
  active_user integer;
  failure_count integer;

  prev_login_var timestamp;
begin
  --------------------------------------
  -- See if a valid username was entered
  --------------------------------------
  select dbf_aeolus_decode_username( input_username, input_token )
  into decoded_username;

  -------------------------
  -- Username wrong: return
  -------------------------
  if decoded_username is null then
    return invalid_creds;
  end if;

  ------------------------------------
  -- Username OK: what about password?
  ------------------------------------
  select active, md5( pw || input_token ), login_time
  into active_user, password_match_string, prev_login_var
  from aeolus_users
  where username = decoded_username;

  -------------------------------------------------------------
  -- Username and password OK: save details of successful login
  -------------------------------------------------------------
  if password_match_string = input_pw and active_user = 1 then
    update aeolus_users
    set 
      failed_logins = 0, 
      login_time = current_timestamp,
      prev_login = prev_login_var
    where 
      username = decoded_username;
   
    return login_success;

  -----------------------------------
  -- Account suspended: simply return
  -----------------------------------
  elseif active_user = 0 then
    return account_suspended;

  -------------------------------------------------------------
  -- Password wrong. 
  -- Increment login failures and if necessary suspend account.
  -------------------------------------------------------------
  elseif password_match_string != input_pw then
    update aeolus_users
    set failed_logins = failed_logins + 1
    where username = decoded_username;

    select failed_logins
    into failure_count
    from aeolus_users
    where username = decoded_username;

    if failure_count > max_failed_logins_allowed then
      update aeolus_users
      set active = 0
      where username = decoded_username;
    end if;

    return invalid_creds;    -- invalid username or password
  end if;

  return invalid_creds;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) OWNER TO postgres;

--
-- Name: dbf_aeolus_check_session(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_check_session(input_username text, input_session_code text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  timeout_minutes   constant integer := 90;

  session_ok        constant integer := 1;
  session_timed_out constant integer := -1;
  session_not_found constant integer := -2;
  
  last_action timestamp;
  interval_string   varchar(12);
begin
  select session_timestamp
  into last_action
  from aeolus_sessions
  where username = input_username
  and session_code = input_session_code;

  if last_action is null then
    return session_not_found;
  end if;

  interval_string = timeout_minutes::varchar || ' minutes';

  if last_action < current_timestamp - interval_string::interval then
    return session_timed_out;
  end if;
    
  return session_ok;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_check_session(input_username text, input_session_code text) OWNER TO postgres;


--
-- Name: dbf_aeolus_create_sql_user(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  statement varchar(500);
  existing_user varchar(100);
begin
  if substr( input_username, 1, length( 'aeolus' )) != 'aeolus' then
    raise exception 'Username must begin with %', 'aeolus';
  end if;

  select usename
  into existing_user
  from pg_user
  where usename = input_username;

  if FOUND then
    null; -- user already exists in another database
  else
    statement = 'create user ' || input_username;
    execute statement;
  end if;

  statement = 'grant viewer_role_' || 'aeolus' || ' to ' || input_username;
  execute statement;

  if grant_edit_role = 'Y' then
    statement = 'grant editor_role_' || 'aeolus' || ' to ' || input_username;
  end if;
  execute statement;

  return input_username;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) OWNER TO postgres;

--
-- Name: dbf_aeolus_create_user(character varying, character varying, character varying, character varying, text, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  existing_username varchar(100);
begin
  if length( input_username ) <= length( 'aeolus' ) then
    raise exception 'Invalid username.';
  end if;

  select username 
  into existing_username
  from aeolus_users
  where trim(lower( username )) = trim(lower( input_username ));

  if FOUND then
    raise exception 'Invalid username.';
  end if;

  if input_password is null or trim( input_password ) = '' 
  or input_password = md5('') or input_password = md5( ' ' )
  or length( input_password ) != length( md5( ' ' )) -- try and check that the password is already in md5
  then
    raise exception 'Invalid password.';
  end if;

  if input_surname is null or trim( input_surname ) = '' then
    raise exception 'Surname cannot be blank.';
  end if;

  insert into aeolus_USERS (
    username,
    pw,
    surname,
    forename,
    email
  )
  values (
    input_username,
    input_password,
    input_surname,
    coalesce( input_forename, '' ),
    input_email
  );

  return dbf_aeolus_create_sql_user( input_username, grant_edit_role );
end;

$$;


ALTER FUNCTION public.dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) OWNER TO postgres;

--
-- Name: dbf_aeolus_decode_username(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_decode_username(input_username text, input_token text) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  cleartext_username varchar(30);
begin
  select username
  into cleartext_username
  from aeolus_users
  where md5( md5( username ) || input_token ) = input_username;
  
  return cleartext_username;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_decode_username(input_username text, input_token text) OWNER TO postgres;


--
-- Name: dbf_aeolus_delete_session(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_delete_session(session_for_deletion text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin

  delete from aeolus_sessions where session_code = session_for_deletion;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_delete_session(session_for_deletion text) OWNER TO postgres;

--
-- Name: dbf_aeolus_delete_user(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_delete_user(input_username character varying) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  statement varchar(100);
  rowcount integer;
  supervisor_role constant integer := 99;
  existing_user varchar(100);
  views_recd record;
begin
  -- Drop any views belonging to this user
  for views_recd in select viewname
                    from pg_views 
                    where schemaname = 'public'
                    and viewowner = input_username
  loop
    -- Double check that the view still exists
    -- as it may have been dropped by now, as a result of an earlier "cascade"
    select count(*) into rowcount
    from pg_views
    where schemaname = 'public'
    and viewowner = input_username
    and viewname = views_recd.viewname;

    if rowcount > 0 then
      statement = 'drop view ' || views_recd.viewname || ' cascade';
      execute statement;
    end if;
  end loop;

  delete from aeolus_user_saved_query_selection	
  where
    query_id in ( select query_id from aeolus_user_saved_queries
                  where username = input_username );

  delete from aeolus_user_saved_queries
  where username = input_username;

  delete from aeolus_sessions
  where
    username = input_username;

  delete from aeolus_user_roles
  where
    username = input_username;

  delete from aeolus_users
  where
    username = input_username;
  get diagnostics rowcount = row_count;

  -- Start a new block, so that ONLY the bit in the inner block 
  -- is rolled back if an error occurs.
  begin
    select usename
    into existing_user
    from pg_user
    where usename = input_username;

    if FOUND then
      statement = 'drop user ' || input_username ;
      execute statement;
    end if;

  exception
    when dependent_objects_still_exist then
      raise notice 'Cannot drop user % as dependent objects still exist', input_username;
      return 0;
  end;

  return rowcount;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_delete_user(input_username character varying) OWNER TO postgres;


--
-- Name: dbf_aeolus_save_session_data(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin
  if old_session is null or old_session = '' then

    insert into aeolus_sessions (username, session_code)
    values (input_username, new_session );
  else
    update aeolus_sessions 
    set 
      session_timestamp = current_timestamp
    where
      username = input_username
      and session_code = old_session;
  end if;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  delete from aeolus_sessions where session_timestamp < current_date;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) OWNER TO postgres;

--
-- Name: dbf_aeolus_select_user(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_select_user(input_username text) RETURNS record
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  the_user aeolus_users%rowtype;
begin
  select * from aeolus_users 
  into the_user
  where username = input_username;

  the_user.pw = '';

  return the_user;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_select_user(input_username text) OWNER TO postgres;

--
-- Name: dbf_aeolus_select_user_role_ids(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  role_string varchar(2000);
  role_row record;
begin
  role_string = '';
  for role_row in select r.role_id
                    from aeolus_roles r, aeolus_user_roles ur
                    where r.role_id = ur.role_id
                    and ur.username = input_username
                    order by r.role_id
  loop
    if role_string > '' then
      role_string := role_string || ', ';
    end if;

    role_string := role_string || role_row.role_id::varchar ;
  end loop;

  return role_string;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_select_user_role_ids(input_username character varying) OWNER TO postgres;

--
-- Name: dbf_aeolus_select_user_roles(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_select_user_roles(input_username character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  role_string varchar(2000);
  role_row record;
begin
  role_string = '';
  for role_row in select r.role_code
                    from aeolus_roles r, aeolus_user_roles ur
                    where r.role_id = ur.role_id
                    and ur.username = input_username
                    order by r.role_code
  loop
    if role_string > '' then
      role_string := role_string || ', ';
    end if;

    role_string := role_string || '''' || role_row.role_code || '''';
  end loop;

  return role_string;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_select_user_roles(input_username character varying) OWNER TO postgres;

--
-- Name: dbf_aeolus_set_change_cols(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_set_change_cols() RETURNS trigger
    LANGUAGE plpgsql STRICT
    AS $$

begin
  new.change_user = user;
  new.change_timestamp = now();

  return new;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_set_change_cols() OWNER TO postgres;

--
-- Name: dbf_aeolus_set_pw_by_super(character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  existing_username varchar(100);
begin
  select dbf_aeolus_decode_username( md5_user_name::text, token::text )
  into existing_username;

  if existing_username is null then
    raise exception 'No username found matching your selection.';
  else
    update aeolus_users
    set pw = md5_pass
    where username = existing_username;
  end if;

  return existing_username;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) OWNER TO postgres;

--
-- Name: dbf_aeolus_set_user_login_status(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

begin
  if input_active = 1 then
    update aeolus_users
    set
      active = 1,
      failed_logins = 0
    where
      username = input_username;
  else
    update aeolus_users
    set
      active = 0
    where
      username = input_username;

    delete from aeolus_sessions
    where
      username = input_username;
  end if;

  return input_active;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) OWNER TO postgres;

--
-- Name: dbf_aeolus_set_user_roles(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  supervisor_role constant integer := 99;
  statement varchar(500);
  one_role varchar(10);
  role_count integer;
  role_found integer;
  old_superpowers integer;
  new_superpowers integer;
begin
  select role_id into old_superpowers
  from aeolus_user_roles
  where role_id = supervisor_role
  and username = input_username;
  old_superpowers = coalesce( old_superpowers, 0 );

  statement = 'delete from aeolus_user_roles where username = ''' || input_username || '''';

  if trim( coalesce( input_roles, '' )) > '' then
    statement = statement || ' and role_id not in (' || input_roles || ')';
  end if;

  execute statement;

  -- Revoke supervisor permissions if supervisor role taken away
  select role_id into new_superpowers
  from aeolus_user_roles
  where role_id = supervisor_role
  and username = input_username;
  new_superpowers = coalesce( new_superpowers, 0 );

  if old_superpowers = supervisor_role and new_superpowers != supervisor_role then
    statement = 'revoke super_role_aeolus from ' || input_username;
    execute statement;
  end if;

  one_role = '?';
  role_count = 0;
  while one_role is not null and trim( one_role ) > '' loop
    role_count = role_count + 1;
    one_role = split_part( input_roles, ',', role_count );
    one_role = trim( one_role );
    if one_role = '' or one_role = '0' then
      exit;
    end if;

    role_found = 0;
    
    select role_id into role_found
    from aeolus_user_roles
    where username = input_username
    and role_id = one_role::integer;

    if role_found is null or role_found = 0 then
      insert into aeolus_user_roles (username, role_id)
      values (input_username, one_role::integer );

      -- Add supervisor permissions if supervisor role given
      if one_role::integer = supervisor_role and old_superpowers != supervisor_role then
        statement = 'grant super_role_aeolus to ' || input_username;
        execute statement;
      end if;
    end if;
  end loop;

  role_count = role_count - 1;
  return role_count;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) OWNER TO postgres;



--
-- Name: dbf_aeolus_update_user_details(character varying, character varying, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

begin
  update aeolus_users
  set
    surname = input_surname,
    forename = input_forename,
    email = input_email
  where
    username = input_username;

  return input_username;
end;

$$;


ALTER FUNCTION public.dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) OWNER TO postgres;

--
-- Name: dbf_exec_with_rowcount(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dbf_exec_with_rowcount(statement_parm text) RETURNS integer
    LANGUAGE plpgsql
    AS $$


declare
  rowcount_var integer := 0;
begin
  execute statement_parm;
  get diagnostics rowcount_var = row_count;
  return rowcount_var;
end;


$$;


ALTER FUNCTION public.dbf_exec_with_rowcount(statement_parm text) OWNER TO postgres;

SET default_tablespace = '';

SET default_with_oids = false;


--
-- Name: aeolus_sessions_session_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_sessions_session_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_sessions_session_id_seq OWNER TO postgres;


--
-- Name: aeolus_help_options_option_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_help_options_option_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_help_options_option_id_seq OWNER TO postgres;

--
-- Name: aeolus_help_options; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_help_options (
    option_id integer DEFAULT nextval('aeolus_help_options_option_id_seq'::regclass) NOT NULL,
    menu_item_id integer,
    button_name character varying(100) DEFAULT ''::character varying NOT NULL,
    help_page_id integer NOT NULL,
    order_in_manual integer DEFAULT 0 NOT NULL,
    menu_depth integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.aeolus_help_options OWNER TO postgres;

--
-- Name: aeolus_help_pages_page_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_help_pages_page_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_help_pages_page_id_seq OWNER TO postgres;

--
-- Name: aeolus_help_pages; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_help_pages (
    page_id integer DEFAULT nextval('aeolus_help_pages_page_id_seq'::regclass) NOT NULL,
    page_title character varying(500) NOT NULL,
    custom_url character varying(500),
    published_text text DEFAULT 'Sorry, no help currently available.'::text NOT NULL,
    draft_text text
);


ALTER TABLE public.aeolus_help_pages OWNER TO postgres;


--
-- Name: aeolus_menu_item_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_menu_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_menu_item_id_seq OWNER TO postgres;

--
-- Name: aeolus_menu_order_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_menu_order_seq
    START WITH 1
    INCREMENT BY 10
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_menu_order_seq OWNER TO postgres;

--
-- Name: aeolus_menu; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_menu (
    menu_item_id integer DEFAULT nextval('aeolus_menu_item_id_seq'::regclass) NOT NULL,
    menu_item_name text NOT NULL,
    menu_order integer DEFAULT nextval('aeolus_menu_order_seq'::regclass),
    parent_id integer,
    has_children integer DEFAULT 0 NOT NULL,
    class_name character varying(100),
    method_name character varying(100),
    user_restriction character varying(30) DEFAULT ''::character varying NOT NULL,
    hidden_parent integer,
    called_as_popup integer DEFAULT 0 NOT NULL,
    collection character varying(20) DEFAULT ''::character varying NOT NULL,
    CONSTRAINT aeolus_chk_item_is_submenu_or_form CHECK (((((has_children = 0) AND (class_name IS NOT NULL)) AND (method_name IS NOT NULL)) OR (((has_children = 1) AND (class_name IS NULL)) AND (method_name IS NULL)))),
    CONSTRAINT aeolus_chk_menu_item_called_as_popup CHECK (((called_as_popup = 0) OR (called_as_popup = 1)))
);


ALTER TABLE public.aeolus_menu OWNER TO postgres;

--
-- Name: aeolus_report_groups_report_group_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_report_groups_report_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_report_groups_report_group_id_seq OWNER TO postgres;

--
-- Name: aeolus_report_groups; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_report_groups (
    report_group_id integer DEFAULT nextval('aeolus_report_groups_report_group_id_seq'::regclass) NOT NULL,
    report_group_title text,
    report_group_order integer DEFAULT 1 NOT NULL,
    on_main_reports_menu integer DEFAULT 0 NOT NULL,
    report_group_code character varying(100)
);


ALTER TABLE public.aeolus_report_groups OWNER TO postgres;

--
-- Name: aeolus_report_outputs; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_report_outputs (
    output_id character varying(250) DEFAULT ''::character varying NOT NULL,
    line_number integer DEFAULT 0 NOT NULL,
    line_text text
);


ALTER TABLE public.aeolus_report_outputs OWNER TO postgres;

--
-- Name: aeolus_reports_report_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_reports_report_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_reports_report_id_seq OWNER TO postgres;

--
-- Name: aeolus_reports; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_reports (
    report_id integer DEFAULT nextval('aeolus_reports_report_id_seq'::regclass) NOT NULL,
    report_title text,
    class_name character varying(40),
    method_name character varying(40),
    report_group_id integer,
    menu_item_id integer,
    has_csv_option integer DEFAULT 0 NOT NULL,
    is_dummy_option integer DEFAULT 0 NOT NULL,
    report_code character varying(100),
    parm_list text,
    parm_titles text,
    prompt_for_parms smallint DEFAULT 0 NOT NULL,
    default_parm_values text,
    parm_methods text,
    report_help text
);


ALTER TABLE public.aeolus_reports OWNER TO postgres;

--
-- Name: aeolus_roles_role_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_roles_role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_roles_role_id_seq OWNER TO postgres;

--
-- Name: aeolus_roles; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_roles (
    role_id integer DEFAULT nextval('aeolus_roles_role_id_seq'::regclass) NOT NULL,
    role_code character varying(20) DEFAULT ''::character varying NOT NULL,
    role_name text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.aeolus_roles OWNER TO postgres;

--
-- Name: aeolus_sessions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_sessions (
    session_id integer DEFAULT nextval('aeolus_sessions_session_id_seq'::regclass) NOT NULL,
    session_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    session_code text,
    username character varying(100)
);


ALTER TABLE public.aeolus_sessions OWNER TO postgres;


--
-- Name: aeolus_user_roles; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_user_roles (
    username character varying(30) NOT NULL,
    role_id integer NOT NULL
);


ALTER TABLE public.aeolus_user_roles OWNER TO postgres;

--
-- Name: aeolus_user_saved_queries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_user_saved_queries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_user_saved_queries_id_seq OWNER TO postgres;

--
-- Name: aeolus_user_saved_queries; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_user_saved_queries (
    query_id integer DEFAULT nextval('aeolus_user_saved_queries_id_seq'::regclass) NOT NULL,
    username character varying(30) DEFAULT "current_user"() NOT NULL,
    query_class character varying(100) NOT NULL,
    query_method character varying(100) NOT NULL,
    query_title text DEFAULT ''::text NOT NULL,
    query_order_by character varying(100) DEFAULT ''::character varying NOT NULL,
    query_sort_descending smallint DEFAULT 0 NOT NULL,
    query_entries_per_page smallint DEFAULT 20 NOT NULL,
    query_record_layout character varying(12) DEFAULT 'across_page'::character varying NOT NULL,
    query_menu_item_name text,
    creation_timestamp timestamp without time zone DEFAULT now()
);


ALTER TABLE public.aeolus_user_saved_queries OWNER TO postgres;

--
-- Name: aeolus_user_saved_query_selection_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_user_saved_query_selection_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_user_saved_query_selection_id_seq OWNER TO postgres;

--
-- Name: aeolus_user_saved_query_selection; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_user_saved_query_selection (
    selection_id integer DEFAULT nextval('aeolus_user_saved_query_selection_id_seq'::regclass) NOT NULL,
    query_id integer NOT NULL,
    column_name character varying(100) NOT NULL,
    column_value character varying(500) NOT NULL,
    op_name character varying(100) NOT NULL,
    op_value character varying(100) NOT NULL,
    column_value2 character varying(500) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.aeolus_user_saved_query_selection OWNER TO postgres;

--
-- Name: aeolus_users; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE aeolus_users (
    username character varying(30) NOT NULL,
    pw text NOT NULL,
    surname character varying(30) DEFAULT ''::character varying NOT NULL,
    forename character varying(30) DEFAULT ''::character varying NOT NULL,
    failed_logins integer DEFAULT 0 NOT NULL,
    login_time timestamp without time zone,
    prev_login timestamp without time zone,
    active smallint DEFAULT 1 NOT NULL,
    email text,
    CONSTRAINT aeolus_users_active CHECK (((active = 0) OR (active = 1))),
    CONSTRAINT aeolus_users_pw CHECK ((pw > ''::text))
);


ALTER TABLE public.aeolus_users OWNER TO postgres;

--
-- Name: aeolus_users_and_roles_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW aeolus_users_and_roles_view AS
    SELECT u.username, u.surname, u.forename, u.active, u.email, r.role_id, r.role_code, r.role_name FROM aeolus_users u, aeolus_user_roles ur, aeolus_roles r WHERE (((r.role_id = ur.role_id) AND ((ur.username)::text = (u.username)::text)) AND (((u.username)::name = "current_user"()) OR ("current_user"() IN (SELECT aeolus_user_roles.username FROM aeolus_user_roles WHERE (aeolus_user_roles.role_id = (99)))))) UNION SELECT u2.username, u2.surname, u2.forename, u2.active, u2.email, NULL::integer AS role_id, NULL::character varying AS role_code, NULL::text AS role_name FROM aeolus_users u2 WHERE ((NOT (EXISTS (SELECT ur2.role_id FROM aeolus_user_roles ur2 WHERE ((u2.username)::text = (ur2.username)::text)))) AND (((u2.username)::name = "current_user"()) OR ("current_user"() IN (SELECT aeolus_user_roles.username FROM aeolus_user_roles WHERE (aeolus_user_roles.role_id = (99)))))) ORDER BY 1, 8;


ALTER TABLE public.aeolus_users_and_roles_view OWNER TO postgres;

--
-- Name: aeolus_users_username_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE aeolus_users_username_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.aeolus_users_username_seq OWNER TO postgres;

--
-- Name: aeolus_help_options_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_help_options
    ADD CONSTRAINT aeolus_help_options_pkey PRIMARY KEY (option_id);


--
-- Name: aeolus_help_pages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_help_pages
    ADD CONSTRAINT aeolus_help_pages_pkey PRIMARY KEY (page_id);



--
-- Name: aeolus_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_menu
    ADD CONSTRAINT aeolus_menu_pkey PRIMARY KEY (menu_item_id);


--
-- Name: aeolus_report_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_report_groups
    ADD CONSTRAINT aeolus_report_groups_pkey PRIMARY KEY (report_group_id);


--
-- Name: aeolus_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_reports
    ADD CONSTRAINT aeolus_reports_pkey PRIMARY KEY (report_id);


--
-- Name: aeolus_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_roles
    ADD CONSTRAINT aeolus_roles_pkey PRIMARY KEY (role_id);


--
-- Name: aeolus_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_sessions
    ADD CONSTRAINT aeolus_sessions_pkey PRIMARY KEY (session_id);


--
-- Name: aeolus_uniq_help_option_menu_item_button; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_help_options
    ADD CONSTRAINT aeolus_uniq_help_option_menu_item_button UNIQUE (menu_item_id, button_name);



--
-- Name: aeolus_uniq_role_code; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_roles
    ADD CONSTRAINT aeolus_uniq_role_code UNIQUE (role_code);


--
-- Name: aeolus_uniq_role_name; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_roles
    ADD CONSTRAINT aeolus_uniq_role_name UNIQUE (role_name);


--
-- Name: aeolus_uniq_session_code; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_sessions
    ADD CONSTRAINT aeolus_uniq_session_code UNIQUE (session_code);



--
-- Name: aeolus_user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_user_roles
    ADD CONSTRAINT aeolus_user_roles_pkey PRIMARY KEY (username, role_id);


--
-- Name: aeolus_user_saved_queries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_user_saved_queries
    ADD CONSTRAINT aeolus_user_saved_queries_pkey PRIMARY KEY (query_id);


--
-- Name: aeolus_user_saved_query_selection_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_user_saved_query_selection
    ADD CONSTRAINT aeolus_user_saved_query_selection_pkey PRIMARY KEY (selection_id);


--
-- Name: aeolus_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY aeolus_users
    ADD CONSTRAINT aeolus_users_pkey PRIMARY KEY (username);


--
-- Name: aeolus_fk_help_option_menu_item; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_help_options
    ADD CONSTRAINT aeolus_fk_help_option_menu_item FOREIGN KEY (menu_item_id) REFERENCES aeolus_menu(menu_item_id);


--
-- Name: aeolus_fk_help_option_page; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_help_options
    ADD CONSTRAINT aeolus_fk_help_option_page FOREIGN KEY (help_page_id) REFERENCES aeolus_help_pages(page_id);


--
-- Name: aeolus_fk_sessions_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_sessions
    ADD CONSTRAINT aeolus_fk_sessions_username FOREIGN KEY (username) REFERENCES aeolus_users(username);


--
-- Name: aeolus_fk_tracking_menu_parent_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_menu
    ADD CONSTRAINT aeolus_fk_tracking_menu_parent_id FOREIGN KEY (parent_id) REFERENCES aeolus_menu(menu_item_id);


--
-- Name: aeolus_fk_user_roles_role_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_user_roles
    ADD CONSTRAINT aeolus_fk_user_roles_role_id FOREIGN KEY (role_id) REFERENCES aeolus_roles(role_id);


--
-- Name: aeolus_fk_user_roles_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_user_roles
    ADD CONSTRAINT aeolus_fk_user_roles_username FOREIGN KEY (username) REFERENCES aeolus_users(username);


--
-- Name: aeolus_fk_user_saved_queries_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_user_saved_queries
    ADD CONSTRAINT aeolus_fk_user_saved_queries_username FOREIGN KEY (username) REFERENCES aeolus_users(username);


--
-- Name: aeolus_fk_user_saved_query_selection_query_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_user_saved_query_selection
    ADD CONSTRAINT aeolus_fk_user_saved_query_selection_query_id FOREIGN KEY (query_id) REFERENCES aeolus_user_saved_queries(query_id);


--
-- Name: aeolusfk_reports_menu_item_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_reports
    ADD CONSTRAINT aeolusfk_reports_menu_item_id FOREIGN KEY (menu_item_id) REFERENCES aeolus_menu(menu_item_id);


--
-- Name: aeolusfk_reports_report_group_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY aeolus_reports
    ADD CONSTRAINT aeolusfk_reports_report_group_id FOREIGN KEY (report_group_id) REFERENCES aeolus_report_groups(report_group_id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- Name: dbf_alphanumeric(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric(string_parm text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric(string_parm text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric(string_parm text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric(string_parm text) TO PUBLIC;


--
-- Name: dbf_alphanumeric(text, boolean, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) TO PUBLIC;


--
-- Name: dbf_alphanumeric_and_underscore(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric_and_underscore(string_parm text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric_and_underscore(string_parm text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_and_underscore(string_parm text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_and_underscore(string_parm text) TO PUBLIC;


--
-- Name: dbf_alphanumeric_with_conv_to_underscore(text, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) TO PUBLIC;


--
-- Name: dbf_alphanumeric_with_others_to_underscore(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric_with_others_to_underscore(string_parm text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric_with_others_to_underscore(string_parm text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_others_to_underscore(string_parm text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_others_to_underscore(string_parm text) TO PUBLIC;


--
-- Name: dbf_alphanumeric_with_space_slash_to_underscore(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) FROM postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) TO postgres;
GRANT ALL ON FUNCTION dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) TO PUBLIC;




--
-- Name: dbf_aeolus_check_login_creds(text, text, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_check_login_creds(input_username text, input_pw text, input_token text) TO x0minim;


--
-- Name: dbf_aeolus_check_session(text, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_check_session(input_username text, input_session_code text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_check_session(input_username text, input_session_code text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_check_session(input_username text, input_session_code text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_check_session(input_username text, input_session_code text) TO x0minim;




--
-- Name: dbf_aeolus_create_sql_user(character varying, character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_create_sql_user(input_username character varying, grant_edit_role character varying) TO super_role_aeolus;


--
-- Name: dbf_aeolus_create_user(character varying, character varying, character varying, character varying, text, character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) TO super_role_aeolus;


--
-- Name: dbf_aeolus_decode_username(text, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_decode_username(input_username text, input_token text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_decode_username(input_username text, input_token text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_decode_username(input_username text, input_token text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_decode_username(input_username text, input_token text) TO x0minim;


--
-- Name: dbf_aeolus_delete_session(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_delete_session(session_for_deletion text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_delete_session(session_for_deletion text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_delete_session(session_for_deletion text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_delete_session(session_for_deletion text) TO x0minim;


--
-- Name: dbf_aeolus_delete_user(character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_delete_user(input_username character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_delete_user(input_username character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_delete_user(input_username character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_delete_user(input_username character varying) TO super_role_aeolus;



--
-- Name: dbf_aeolus_save_session_data(text, text, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_save_session_data(input_username text, old_session text, new_session text) TO x0minim;


--
-- Name: dbf_aeolus_select_user(text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_select_user(input_username text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_select_user(input_username text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user(input_username text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user(input_username text) TO editor_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user(input_username text) TO viewer_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user(input_username text) TO collector_role_aeolus;


--
-- Name: dbf_aeolus_select_user_role_ids(character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) TO editor_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) TO viewer_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_role_ids(input_username character varying) TO collector_role_aeolus;


--
-- Name: dbf_aeolus_select_user_roles(character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) TO editor_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) TO viewer_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_select_user_roles(input_username character varying) TO collector_role_aeolus;


--
-- Name: dbf_aeolus_set_change_cols(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_set_change_cols() FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_set_change_cols() FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_change_cols() TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_change_cols() TO editor_role_aeolus;


--
-- Name: dbf_aeolus_set_pw_by_super(character varying, character varying, character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) TO super_role_aeolus;


--
-- Name: dbf_aeolus_set_user_login_status(character varying, integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_user_login_status(input_username character varying, input_active integer) TO super_role_aeolus;


--
-- Name: dbf_aeolus_set_user_roles(character varying, character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_set_user_roles(input_username character varying, input_roles character varying) TO super_role_aeolus;


--
-- Name: dbf_aeolus_update_user_details(character varying, character varying, character varying, text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) FROM PUBLIC;
REVOKE ALL ON FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) FROM postgres;
GRANT ALL ON FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) TO postgres;
GRANT ALL ON FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) TO super_role_aeolus;
GRANT ALL ON FUNCTION dbf_aeolus_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) TO editor_role_aeolus;

--
-- Name: aeolus_help_options_option_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_help_options_option_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_help_options_option_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_help_options_option_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_help_options_option_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_help_options; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_help_options FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_help_options FROM postgres;
GRANT ALL ON TABLE aeolus_help_options TO postgres;
GRANT ALL ON TABLE aeolus_help_options TO super_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_options TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_options TO viewer_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_options TO w5view;


--
-- Name: aeolus_help_pages_page_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_help_pages_page_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_help_pages_page_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_help_pages_page_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_help_pages_page_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_help_pages; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_help_pages FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_help_pages FROM postgres;
GRANT ALL ON TABLE aeolus_help_pages TO postgres;
GRANT ALL ON TABLE aeolus_help_pages TO super_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_pages TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_pages TO viewer_role_aeolus;
GRANT SELECT ON TABLE aeolus_help_pages TO w5view;


--
-- Name: aeolus_menu_item_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_menu_item_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_menu_item_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_menu_item_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_menu_item_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_menu_order_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_menu_order_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_menu_order_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_menu_order_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_menu_order_seq TO editor_role_aeolus;


--
-- Name: aeolus_menu; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_menu FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_menu FROM postgres;
GRANT ALL ON TABLE aeolus_menu TO postgres;
GRANT ALL ON TABLE aeolus_menu TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_menu TO viewer_role_aeolus;


--
-- Name: aeolus_report_groups_report_group_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_report_groups_report_group_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_report_groups_report_group_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_report_groups_report_group_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_report_groups_report_group_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_report_groups; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_report_groups FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_report_groups FROM postgres;
GRANT ALL ON TABLE aeolus_report_groups TO postgres;
GRANT ALL ON TABLE aeolus_report_groups TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_report_groups TO viewer_role_aeolus;


--
-- Name: aeolus_report_outputs; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_report_outputs FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_report_outputs FROM postgres;
GRANT ALL ON TABLE aeolus_report_outputs TO postgres;
GRANT ALL ON TABLE aeolus_report_outputs TO super_role_aeolus;
GRANT ALL ON TABLE aeolus_report_outputs TO editor_role_aeolus;
GRANT ALL ON TABLE aeolus_report_outputs TO viewer_role_aeolus;


--
-- Name: aeolus_reports_report_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_reports_report_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_reports_report_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_reports_report_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_reports_report_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_reports; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_reports FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_reports FROM postgres;
GRANT ALL ON TABLE aeolus_reports TO postgres;
GRANT ALL ON TABLE aeolus_reports TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_reports TO viewer_role_aeolus;


--
-- Name: aeolus_roles; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_roles FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_roles FROM postgres;
GRANT ALL ON TABLE aeolus_roles TO postgres;
GRANT SELECT ON TABLE aeolus_roles TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_roles TO viewer_role_aeolus;


--
-- Name: aeolus_user_saved_queries_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_user_saved_queries_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_user_saved_queries_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_user_saved_queries_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_user_saved_queries_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_user_saved_queries; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_user_saved_queries FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_user_saved_queries FROM postgres;
GRANT ALL ON TABLE aeolus_user_saved_queries TO postgres;
GRANT ALL ON TABLE aeolus_user_saved_queries TO editor_role_aeolus;


--
-- Name: aeolus_user_saved_query_selection_id_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_user_saved_query_selection_id_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_user_saved_query_selection_id_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_user_saved_query_selection_id_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_user_saved_query_selection_id_seq TO editor_role_aeolus;


--
-- Name: aeolus_user_saved_query_selection; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_user_saved_query_selection FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_user_saved_query_selection FROM postgres;
GRANT ALL ON TABLE aeolus_user_saved_query_selection TO postgres;
GRANT ALL ON TABLE aeolus_user_saved_query_selection TO editor_role_aeolus;


--
-- Name: aeolus_users_and_roles_view; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE aeolus_users_and_roles_view FROM PUBLIC;
REVOKE ALL ON TABLE aeolus_users_and_roles_view FROM postgres;
GRANT ALL ON TABLE aeolus_users_and_roles_view TO postgres;
GRANT SELECT ON TABLE aeolus_users_and_roles_view TO editor_role_aeolus;
GRANT SELECT ON TABLE aeolus_users_and_roles_view TO viewer_role_aeolus;


--
-- Name: aeolus_users_username_seq; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON SEQUENCE aeolus_users_username_seq FROM PUBLIC;
REVOKE ALL ON SEQUENCE aeolus_users_username_seq FROM postgres;
GRANT ALL ON SEQUENCE aeolus_users_username_seq TO postgres;
GRANT ALL ON SEQUENCE aeolus_users_username_seq TO super_role_aeolus;


--
-- Temporarily drop self-referential foreign key constraint in Menu table
--
alter table aeolus_menu drop constraint aeolus_fk_tracking_menu_parent_id;


--
-- Populate lookup tables
--

--
-- Data for Name: aeolus_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO aeolus_roles (role_id, role_code, role_name) VALUES (1, 'b2edit', 'Can edit database');
INSERT INTO aeolus_roles (role_id, role_code, role_name) VALUES (2, 'c3view', 'Read-only access');
INSERT INTO aeolus_roles (role_id, role_code, role_name) VALUES (99, 'a1supr', '*Supervisor*');

--
-- Data for Name: aeolus_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO aeolus_users (username, pw, surname, forename, failed_logins, login_time, prev_login, active, email) VALUES ('aeolusa1supr', '93906cef4f5517707291cf77121e8f76', 'Database Administrator', 'Aeolus', 0, NULL, NULL, 1, '');

--
-- Data for Name: aeolus_user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO aeolus_user_roles (username, role_id) VALUES ('aeolusa1supr', 99);

--
-- Data for Name: aeolus_menu; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (-1, 'Hidden options', 11, -1, 1, NULL, NULL, '', NULL, 0, '');


INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (118, 'Your own reports/saved queries', 941, NULL, 0, 'report', 'saved_query_list', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (119, 'Edit title of saved query', 951, -1, 0, 'report', 'edit_saved_query', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (120, 'Edit title of saved query', 961, -1, 0, 'report', 'edit_saved_query2', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (121, 'Delete saved query', 971, -1, 0, 'report', 'delete_saved_query', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (122, 'Delete saved query', 981, -1, 0, 'report', 'delete_saved_query2', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (141, 'Edit your own details', 1171, NULL, 0, 'user', 'edit_user1_self', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (142, 'Edit your own details', 1181, -1, 0, 'user', 'edit_user2_self', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (143, 'Edit your own details', 1191, -1, 0, 'user', 'save_user_password_own', '', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (106, 'Add new options for fields', 821, NULL, 1, NULL, NULL, 'b2edit', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (107, 'Add or edit subjects', 831, 106, 0, 'subject', 'edit_lookup_table1', 'b2edit', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (117, 'Supervisor-only options', 931, NULL, 1, NULL, NULL, 'a1supr', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (137, 'Database users', 1131, 117, 0, 'user', 'browse_users', 'a1supr', NULL, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (138, 'Edit user', 1141, -1, 0, 'user', 'edit_user1_other', 'a1supr', 117, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (139, 'Edit user', 1151, -1, 0, 'user', 'edit_user2_other', 'a1supr', 117, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (140, 'Edit user', 1161, -1, 0, 'user', 'save_user_password_other', 'a1supr', 117, 0, '');

INSERT INTO aeolus_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) VALUES (144, 'Delete user', 1201, -1, 0, 'user', 'delete_user2', 'a1supr', 117, 0, '');



--
-- PostgreSQL database dump complete
--

--
-- Restore self-referential foreign key constraint in Menu table
--
alter table aeolus_menu add constraint aeolus_fk_tracking_menu_parent_id 
 FOREIGN KEY (parent_id) REFERENCES aeolus_menu(menu_item_id);

--
-- Where data has been inserted, set sequences to the maximum ID number in the table
--

select setval( 'aeolus_menu_item_id_seq'::regclass,   (select max( menu_item_id ) from aeolus_menu ));
select setval( 'aeolus_menu_order_seq'::regclass,     (select max( menu_order   ) from aeolus_menu ));
select setval( 'aeolus_roles_role_id_seq'::regclass,  (select max( role_id      ) from aeolus_roles ));

