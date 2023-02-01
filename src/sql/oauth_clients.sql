CREATE TABLE oauth_clients (
    id varchar NOT NULL UNIQUE,
    secret varchar,
    redirect_uris varchar[],
    description varchar
);
COMMENT ON TABLE oauth_clients IS 'Клиенты сервера OAuth 2.0';
