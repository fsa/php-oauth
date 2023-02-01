CREATE TABLE oauth_refresh_tokens (
    token varchar UNIQUE,
    expires_at timestamptz,
    client_id varchar,
    payload jsonb,
    created timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);
COMMENT ON TABLE auth_tokens IS 'Refresh токены OAuth 2.0';
