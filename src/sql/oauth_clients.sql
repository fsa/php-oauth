CREATE TABLE oauth_clients (
    uuid uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id varchar NOT NULL UNIQUE,
    client_secret varchar,
    redirect_uris varchar[],
    description varchar
);
COMMENT ON TABLE oauth_clients IS 'Клиенты сервера OAuth 2.0';
